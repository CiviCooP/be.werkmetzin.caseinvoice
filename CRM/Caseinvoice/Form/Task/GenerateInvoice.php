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

  public function setDefaultValues() {
    $defaults = array();
    $statusValues = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id');
    foreach($statusValues as $status_id => $label) {
      if ($label == 'Pending') {
        $defaults['contribution_status_id'] = $status_id;
      }
    }
    return $defaults;
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
        'case_financial_type' => $dao->case_financial_type,
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
    $config = CRM_Core_Config::singleton();
    $submittedValues = $this->_submitValues;

    $cases = array();
    $caseIds = array();

    $payment_instrument_id = $submittedValues['payment_instrument_id'];
    $contribution_status_id = $submittedValues['contribution_status_id'];

    foreach($this->activities as $activity) {
      $save_on_case_id = $activity['case_id'];
      $parent_case_id = $this->getParentCaseId($activity['case_id']);
      if ($parent_case_id) {
        $save_on_case_id = $parent_case_id;
      }

      if (in_array($activity['activity_id'], $this->_activityHolderIds)) {
        $cases[$save_on_case_id][$activity['case_id']][$activity['activity_id']] = $activity;
      }
      if (!in_array($activity['case_id'], $caseIds)) {
        $caseIds[] = $activity['case_id'];
      }
      if (!in_array($save_on_case_id, $caseIds)) {
        $caseIds[] = $save_on_case_id;
      }
    }

    $caseInvoiceSettings = $this->getInvoiceSettingsForCases($caseIds);
    $caseContacts = $this->getCaseContacts($caseIds);

    foreach ($cases as $save_on_case_id => $cases_to_invoice) {
      foreach ($cases_to_invoice as $case_id => $activities) {
        if (!$caseInvoiceSettings[$case_id]) {
          continue;
        }
        $invoiceSetting = $caseInvoiceSettings[$case_id];

        $total = 0.00;
        $total_tax_amount = 0.00;
        $line_items = array();
        foreach ($activities as $activity_id => $activity) {
          $financial_type_id = $invoiceSetting['case_financial_type'];
          $minutes = $this->calculateRoundedMinutes($activity['duration'], $invoiceSetting['rounding']);
          $hours = $minutes > 0 ? ($minutes / 60) : 0;
          $price = round($hours * $invoiceSetting['rate'], 2);
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
        }
      }
      if (!$total) {
        continue;
      }

      $parentInvoiceSetting = $caseInvoiceSettings[$save_on_case_id];
      $contact_id = $caseContacts[$save_on_case_id];
      if (!empty($parentInvoiceSetting['invoice_contact'])) {
        $contact_id = $parentInvoiceSetting['invoice_contact'];
      }

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
        'case_id' => $save_on_case_id,
        'contribution_id' => $contribution['id']
      ));
    }
  }

}