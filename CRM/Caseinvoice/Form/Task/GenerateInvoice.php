<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Caseinvoice_Form_Task_GenerateInvoice extends CRM_Caseinvoice_Form_Task {

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
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {

    $this->addSelect('financial_type_id',
      array('entity' => 'contribution', 'multiple' => false),
      true //Required
    );

    $this->addSelect('payment_instrument_id',
      array('entity' => 'contribution', 'label' => ts('Payment Method'), 'option_url' => NULL, 'placeholder' => ts('- select -')),
      true //Required
    );

    $statusValues = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id');
    $this->add('select', 'contribution_status_id',
      ts('Contribution Status'), $statusValues,
      TRUE, array('class' => 'crm-select2', 'multiple' => false)
    );


    $this->addDefaultButtons(ts('Generate invoice'), 'done');
  }

  protected function getInvoiceSettingsForCases($caseIds) {
    $settings = array();
    $sql = "SELECT * FROM civicrm_value_case_invoice_settings WHERE entity_id IN (".implode(", ", $caseIds).")";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $settings[$dao->entity_id] = array(
        'rate' => $dao->rate,
        'rounding' => $dao->rounding,
        'invoice_contact' => $dao->invoice_contact,
      );
    }
    return $settings;
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
    $config = CRM_Core_Config::singleton();
    $submittedValues = $this->_submitValues;

    $cases = array();
    $caseIds = array();
    $caseContactIds = array();

    $financial_type_id = $submittedValues['financial_type_id'];
    $payment_instrument_id = $submittedValues['payment_instrument_id'];
    $contribution_status_id = $submittedValues['contribution_status_id'];

    foreach($this->activities as $activity) {
      if (in_array($activity['activity_id'], $this->_activityHolderIds)) {
        $cases[$activity['case_id']][$activity['activity_id']] = $activity;
      }
      if (!in_array($activity['case_id'], $caseIds)) {
        $caseIds[] = $activity['case_id'];
      }
      if (!isset($caseContactIds[$activity['case_id']])) {
        $caseContactIds[$activity['case_id']] = $activity['contact_id'];
      }
    }

    $caseInvoiceSettings = $this->getInvoiceSettingsForCases($caseIds);

    foreach ($cases as $case_id => $activities) {
      if (!$caseInvoiceSettings[$case_id]) {
        continue;
      }
      $invoiceSetting = $caseInvoiceSettings[$case_id];
      $contact_id = $caseContactIds[$case_id];
      if (!empty($invoiceSetting['invoice_contact'])) {
        $contact_id = $invoiceSetting['invoice_contact'];
      }

      $total = 0.00;
      $line_items = array();
      foreach ($activities as $activity_id => $activity) {
        $minutes = $this->calculateRoundedMinutes($activity['duration'], $invoiceSetting['rounding']);
        $hours = $minutes > 0 ? ($minutes / 60) : 0;
        $price = round($hours * $invoiceSetting['rate'], 2);
        $label = CRM_Utils_Date::customFormat($activity['activity_date_time'], $config->dateformatFull) . ' '.$activity['activity_type_label'].': '.$minutes.' minuten';

        $line_item = array(
          'label' => $label,
          'qty' => $financial_type_id,
          'unit_price' => $price,
          'line_total' => $price,
          'financial_type_id' => 1,
          'entity_id' => $activity_id,
          'entity_table' => 'civicrm_activity',
        );
        $total = $total + $line_item['line_total'];
        $line_items[] = $line_item;
      }
      if (!$total) {
        continue;
      }
      $contributionParams['contact_id'] = $contact_id;
      $contributionParams['financial_type_id'] = $financial_type_id;
      $contributionParams['contribution_status_id'] = $contribution_status_id;
      $contributionParams['payment_instrument_id'] = $payment_instrument_id;
      $contributionParams['skipLineItem'] = true;
      $contributionParams['skipRecentView'] = true;
      $contributionParams['total_amount'] = $total;
      $contribution = civicrm_api3('Contribution', 'create', $contributionParams);
      foreach($line_items as $line_item) {
        $line_item['contribution_id'] = $contribution['id'];
        civicrm_api3('LineItem', 'Create', $line_item);
      }
      civicrm_api3('CaseContribution', 'create', array('case_id' => $case_id, 'contribution_id' => $contribution['id']));
    }
  }

}