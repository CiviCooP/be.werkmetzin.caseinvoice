<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Caseinvoice_Form_Task_GenerateInvoice extends CRM_Caseinvoice_Form_GenerateInvoiceTask {

  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific Activity?
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();

    //set the context for redirection for any task actions
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $form);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/case/generateinvoice', $urlParams));
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {

    $this->add('text', 'source', ts('Referentiecode'), false);
    $this->add('text', 'km', ts('KM Vergoedeing'), true);

    $this->addFormRule(array('CRM_Caseinvoice_Form_Task_GenerateInvoice', 'checkKm'));

    $this->addSelect('payment_instrument_id',
      array('entity' => 'contribution', 'label' => ts('Payment Method'), 'option_url' => NULL, 'placeholder' => ts('- select -')),
      true //Required
    );

    $statusValues = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id');
    $this->add('select', 'contribution_status_id',
      ts('Contribution Status'), $statusValues,
      TRUE, array('class' => 'crm-select2', 'multiple' => false)
    );


    $this->addDefaultButtons(ts('Maak facturen'), 'done');
  }

  public static function checkKm($fields) {
    $km = self::convertToFloat($fields['km']);
    $errors = array();
    if (!is_numeric($km)) {
      $errors['km'] = ts('Enter a valid amount');
    }
    if (count($errors)) {
      return $errors;
    }
    return true;
  }

  static function convertToFloat($money) {
    $config = CRM_Core_Config::singleton();
    $currency = $config->defaultCurrency;
    $_currencySymbols = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'currency', array('keyColumn' => 'name', 'labelColumn' => 'symbol'));
    $currencySymbol = CRM_Utils_Array::value($currency, $_currencySymbols, $currency);
    $replacements = array(
      $currency => '',
      $currencySymbol => '',
      $config->monetaryThousandSeparator => '',
    );
    $return =  trim(strtr($money, $replacements));
    $decReplacements = array(
      $config->monetaryDecimalPoint => '.',
    );
    $return = trim(strtr($return, $decReplacements));
    return $return;
  }


  public function setDefaultValues() {
		$defaults = array();
		$defaults['km'] = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'km', null, 0.4);
		$defaults['payment_instrument_id'] = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'payment_instrument_id', null, 0);
		$defaults['contribution_status_id'] = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'contribution_status_id', null, 0);
		return $defaults;
  }

  protected function getCaseContacts($caseIds) {
    $contacts = array();
    $sql = "SELECT * FROM civicrm_case_contact WHERE case_id IN (".implode(", ", $caseIds).") GROUP BY case_id";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $contacts[$dao->case_id] = $dao->contact_id;
    }
    return $contacts;
  }

  protected function getParentCaseId($case_id) {
    $dao = CRM_Core_DAO::executeQuery("SELECT case_id FROM civicrm_value_caselink_case WHERE entity_id = %1", array(1=>array($case_id, 'Integer')));
    if ($dao->fetch()) {
      return $dao->case_id;
    }
    return false;
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    $submittedValues = $this->_submitValues;

    $cases = array();
    $caseIds = array();

    $payment_instrument_id = $submittedValues['payment_instrument_id'];
    $contribution_status_id = $submittedValues['contribution_status_id'];
    $km = $submittedValues['km'];
    $source = $submittedValues['source'];

    foreach($this->activities as $activity) {
      if (!in_array($activity['activity_id'], $this->_activityHolderIds)) {
        continue;
      }
      $save_on_case_id = $activity['case_id'];
      $parent_case_id = $this->getParentCaseId($activity['case_id']);
      if ($parent_case_id) {
        $save_on_case_id = $parent_case_id;
      }

      $cases[$save_on_case_id][$activity['case_id']][$activity['activity_id']] = $activity;
      if (!in_array($activity['case_id'], $caseIds)) {
        $caseIds[] = $activity['case_id'];
      }
      if (!in_array($save_on_case_id, $caseIds)) {
        $caseIds[] = $save_on_case_id;
      }
    }

    $caseInvoiceSettings = CRM_Caseinvoice_Util::getInvoiceSettingsForCases($caseIds);
    $caseContacts = $this->getCaseContacts($caseIds);

    $contributionCount = 0;
    foreach ($cases as $save_on_case_id => $cases_to_invoice) {
      $contributionCount = $contributionCount + $this->createInvoicePerParentCase($save_on_case_id, $cases_to_invoice, $caseInvoiceSettings, $caseContacts, $km, $payment_instrument_id, $contribution_status_id, $source);
    }

    CRM_Core_Session::setStatus(''.$contributionCount.' factuur(en) aangemaakt', '', 'success');
  }

  private function createInvoicePerParentCase($parent_case_id, $cases_to_invoice, $caseInvoiceSettings, $caseContacts, $km, $payment_instrument_id, $contribution_status_id, $source) {
    $config = CRM_Core_Config::singleton();
    $contributionCount = 0;
    $total = 0.00;
    $total_tax_amount = 0.00;
    $line_items = array();

    $parentInvoiceSetting = $caseInvoiceSettings[$parent_case_id];

    // Check whether we collect the invoices and generate one invoice on the parent case.
    $collectInvoices = false;
    if (!empty($parentInvoiceSetting) && !empty($parentInvoiceSetting['invoice_setting']) && $parentInvoiceSetting['invoice_setting'] == 'collected_invoice') {
      $collectInvoices = true;
    }

    foreach ($cases_to_invoice as $case_id => $activities) {
      if (!$caseInvoiceSettings[$case_id]) {
        CRM_Core_Session::setStatus(ts('No invoice settings present for case with id %1', array(1=>$case_id)));
        continue;
      }
      $invoiceSetting = $caseInvoiceSettings[$case_id];

      foreach ($activities as $activity_id => $activity) {
        if (!CRM_Caseinvoice_Util::validInvoiceSettings($activity, $invoiceSetting)) {
          continue;
        }
        $financial_type_id = $invoiceSetting['case_financial_type'];
        $price = CRM_Caseinvoice_Util::calculateInvoiceAmount($activity, $invoiceSetting);
        $label = CRM_Caseinvoice_Util::calculateInvoiceAmountLabel($activity, $invoiceSetting, $caseContacts[$case_id]);
				$qty = CRM_Caseinvoice_Util::calculateHours($activity, $invoiceSetting);
				$rate = CRM_Caseinvoice_Util::determineRate($activity, $invoiceSetting);

        $line_item = array(
          'label' => $label,
          'qty' => $qty,
          'unit_price' => $rate,
          'line_total' => $price,
          'financial_type_id' => $financial_type_id,
          'entity_id' => $activity_id,
          'entity_table' => 'civicrm_activity',
        );
        $line_item = CRM_Contribute_BAO_Contribution::checkTaxAmount($line_item, TRUE);
        $total = $total + $line_item['line_total'];
        $total_tax_amount = $total_tax_amount + $line_item['tax_amount'];
        $line_items[] = $line_item;

        if (!empty($activity['km'])) {
          $km_price = round($km * $activity['km'], 2);
          $km_label = CRM_Utils_Date::customFormat($activity['activity_date_time'], $config->dateformatFull) . ' ' . $activity['activity_type_label'] . ' - ' . $display_name . ': ' . $activity['km'] . ' KM';

          $line_item = array(
            'label' => $km_label,
            'qty' => $activity['km'],
            'unit_price' => $km,
            'line_total' => $km_price,
            'financial_type_id' => $financial_type_id,
            'entity_id' => $activity_id,
            'entity_table' => 'civicrm_activity',
          );
          $line_item = CRM_Contribute_BAO_Contribution::checkTaxAmount($line_item, TRUE);
          $total = $total + $line_item['line_total'];
          $total_tax_amount = $total_tax_amount + $line_item['tax_amount'];
          $line_items[] = $line_item;
        }
      }

      if (!$total) {
        CRM_Core_Session::setStatus(ts('No invoice settings present for case with id %1', array(1=>$case_id)));
        // Reset the data
        $line_items = array();
        $total_tax_amount = 0.00;
        $total = 0.00;
        continue;
      }

      if (!$collectInvoices) {
        $contact_id = $caseContacts[$parent_case_id];
        if (!empty($parentInvoiceSetting['invoice_contact'])) {
          $contact_id = $parentInvoiceSetting['invoice_contact'];
        }
        $this->createInvoice($line_items, $contact_id, $financial_type_id, $contribution_status_id, $payment_instrument_id, $total, $total_tax_amount, $parent_case_id, $source);
        $contributionCount ++;

        // Reset the data
        $line_items = array();
        $total_tax_amount = 0.00;
        $total = 0.00;
      }
    }

    if ($collectInvoices) {
      $contact_id = $caseContacts[$parent_case_id];
      if (!empty($parentInvoiceSetting['invoice_contact'])) {
        $contact_id = $parentInvoiceSetting['invoice_contact'];
      }
      $this->createInvoice($line_items, $contact_id, $financial_type_id, $contribution_status_id, $payment_instrument_id, $total, $total_tax_amount, $parent_case_id, $source);
      $contributionCount ++;
    }

    return $contributionCount;
  }

  private function createInvoice($line_items, $contact_id, $financial_type_id, $contribution_status_id, $payment_instrument_id, $total, $total_tax_amount, $case_id, $source) {
    $contributionParams = array();
    $contributionParams['contact_id'] = $contact_id;
    $contributionParams['financial_type_id'] = $financial_type_id;
    $contributionParams['contribution_status_id'] = $contribution_status_id;
    $contributionParams['payment_instrument_id'] = $payment_instrument_id;
    $contributionParams['skipLineItem'] = TRUE;
    $contributionParams['skipRecentView'] = TRUE;
    $contributionParams['total_amount'] = round(($total + $total_tax_amount), 2);
    $contributionParams['tax_amount'] = round($total_tax_amount, 2);
		$contributionParams['net_amount'] = round($total, 2);
    $contributionParams['source'] = $source;
		$this->alterContributionParameters($contributionParams);
    $contribution = civicrm_api3('Contribution', 'create', $contributionParams);
		
    foreach ($line_items as $line_item) {
      $line_item['contribution_id'] = $contribution['id'];
			civicrm_api3('LineItem', 'Create', $line_item);
    }
    civicrm_api3('CaseContribution', 'create', array(
      'case_id' => $case_id,
      'contribution_id' => $contribution['id']
    ));
  }

	protected function alterContributionParameters(&$contributionParameters) {
		// Child classes could override this function to alter the contribution params.
	}

}