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

  private $coachings_activity_type_ids = array();

  private $ondersteunings_activity_type_ids = array();

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();

		$this->coachings_activity_type_ids = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'coachings_activity_type_ids', null, 0);
		if (!is_array($this->coachings_activity_type_ids) || empty($this->coachings_activity_type_ids)) {
			CRM_Core_Error::fatal('Coachingactiviteiten zijn niet <a href="'.CRM_Utils_System::url('civicrm/admin/form/caseinvoicesettings'). '">ingesteld</a>');
		}
		$this->ondersteunings_activity_type_ids = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'ondersteunings_activity_type_ids', null, 0);
		if (!is_array($this->ondersteunings_activity_type_ids) || empty($this->ondersteunings_activity_type_ids)) {
			CRM_Core_Error::fatal('Ondersteuningsactiviteiten zijn niet <a href="'.CRM_Utils_System::url('civicrm/admin/form/caseinvoicesettings'). '">ingesteld</a>');
		}

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

  protected function getInvoiceSettingsForCases($caseIds) {
    $settings = array();
    $sql = "SELECT * FROM civicrm_value_case_invoice_settings WHERE entity_id IN (".implode(", ", $caseIds).")";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $settings[$dao->entity_id] = array(
        'rate' => $dao->rate,
        'rate_ondersteuning' => $dao->rate_ondersteuning,
        'rounding' => $dao->rounding,
        'invoice_contact' => $dao->invoice_contact,
        'case_financial_type' => $dao->case_financial_type,
        'invoice_setting' => $dao->invoice_setting,
      );
    }
    return $settings;
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

  protected function calculateRoundedMinutes($duration, $rounding) {
    if ($duration == 0) {
      return 0.0;
    }
    $minutes = $duration;
    switch ($rounding) {
      case '15_minutes':
        //10 / 15 = 0.667
        //34 / 15 = 2.2667
        $quarters = ceil($duration / 15);
        $minutes = $quarters * 15;
        break;
      case '30_minutes':
        //10 / 30 = 0.3333
        //34 / 30 = 1.13333
        $halfhours = ceil($duration / 30);
        $minutes = $halfhours * 30;
        break;
      case '60_minutes':
        //10 / 60 = 0.16667
        //64 / 60 = 1.06667
        $hours = ceil($duration / 60);
        $minutes = $hours * 60;
        break;
    }

    return $minutes;
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

    $caseInvoiceSettings = $this->getInvoiceSettingsForCases($caseIds);
    $caseContacts = $this->getCaseContacts($caseIds);

    $contributionCount = 0;
    foreach ($cases as $save_on_case_id => $cases_to_invoice) {
      $contributionCount = $contributionCount + $this->createInvoicePerParentCase($save_on_case_id, $cases_to_invoice, $caseInvoiceSettings, $caseContacts, $km, $payment_instrument_id, $contribution_status_id);
    }

    CRM_Core_Session::setStatus(''.$contributionCount.' factuur(en) aangemaakt', '', 'success');
  }

  private function createInvoicePerParentCase($parent_case_id, $cases_to_invoice, $caseInvoiceSettings, $caseContacts, $km, $payment_instrument_id, $contribution_status_id) {
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
        $financial_type_id = $invoiceSetting['case_financial_type'];
        $minutes = $this->calculateRoundedMinutes($activity['duration'], $invoiceSetting['rounding']);
        $hours = $minutes > 0 ? ($minutes / 60) : 0;
        $rate = $this->determineRate($activity, $invoiceSetting);
        $price = round($hours * $rate, 2);
        $display_name = civicrm_api3('Contact', 'getvalue', array('return' => 'display_name', 'id' => $caseContacts[$case_id]));
        $label = CRM_Utils_Date::customFormat($activity['activity_date_time'], $config->dateformatFull) . ' ' . $activity['activity_type_label'] . ' - ' . $display_name . ': ' . $minutes . ' minuten';

        $line_item = array(
          'label' => $label,
          'qty' => 1,
          'unit_price' => $price,
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
            'qty' => 1,
            'unit_price' => $km_price,
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
        $this->createInvoice($line_items, $contact_id, $financial_type_id, $contribution_status_id, $payment_instrument_id, $total, $total_tax_amount, $parent_case_id);
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
      $this->createInvoice($line_items, $contact_id, $financial_type_id, $contribution_status_id, $payment_instrument_id, $total, $total_tax_amount, $parent_case_id);
      $contributionCount ++;
    }

    return $contributionCount;
  }

	/**
	 * Determine the rate for this activity
	 *
	 * @param array $activity
	 * @param array $invoiceSetting
	 *
	 * @return float
	 */
  private function determineRate($activity, $invoiceSetting) {

		if (in_array($activity['activity_type_id'], $this->coachings_activity_type_ids)) {
			if (empty($invoiceSetting['rate'])) {
				CRM_Core_Error::fatal('Uurtarief (coachingsactiviteiten) not set for case: '.$activity['display_name'].' (id = '.$activity['case_id'].') ');
			}
			return (float) $invoiceSetting['rate'];
		} elseif (in_array($activity['activity_type_id'], $this->ondersteunings_activity_type_ids)) {
			if (empty($invoiceSetting['rate_ondersteuning'])) {
				CRM_Core_Error::fatal('Uurtarief (ondersteuningsactiviteiten) not set for case: '.$activity['display_name'].' (id = '.$activity['case_id'].') ');
			}
			return (float) $invoiceSetting['rate_ondersteuning'];
		}
		CRM_Core_Error::fatal('Uurtarief not set for case: '.$activity['display_name'].' (id = '.$activity['case_id'].') ');
	}

  private function createInvoice($line_items, $contact_id, $financial_type_id, $contribution_status_id, $payment_instrument_id, $total, $total_tax_amount, $case_id) {
    $contributionParams = array();
    $contributionParams['contact_id'] = $contact_id;
    $contributionParams['financial_type_id'] = $financial_type_id;
    $contributionParams['contribution_status_id'] = $contribution_status_id;
    $contributionParams['payment_instrument_id'] = $payment_instrument_id;
    $contributionParams['skipLineItem'] = TRUE;
    $contributionParams['skipRecentView'] = TRUE;
    $contributionParams['total_amount'] = $total + $total_tax_amount;
    $contributionParams['tax_amount'] = $total_tax_amount;
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

}