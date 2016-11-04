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
    $this->addDefaultButtons(ts('Generate invoice'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    $cases = array();

    foreach($this->activities as $activity) {
      if (in_array($activity['activity_id'], $this->_activityHolderIds)) {
        $cases[$activity['case_id']][$activity['activity_id']] = $activity;
      }
    }

    foreach ($cases as $case_id => $activities) {
      $total = 0.00;
      $line_items = array();
      $contact_id = FALSE;
      foreach ($activities as $activity_id => $activity) {
        $contact_id = $activity['contact_id'];
        $line_item = array(
          'label' => 'Hier komt een omschrijving van de activiteit. Datum + Type en tijdsduur?',
          'qty' => 1,
          'unit_price' => 10,
          'line_total' => 10,
          'financial_type_id' => 1,
          'entity_id' => $activity_id,
          'entity_table' => 'civicrm_activity',
        );
        //$line = civicrm_api3('LineItem', 'Create', $line_item);
        $total = $total + $line_item['line_total'];
        $line_items[] = $line_item;
      }
      $contributionParams['contact_id'] = $contact_id;
      $contributionParams['financial_type_id'] = 1;
      $contributionParams['contribution_status_id'] = 1;
      $contributionParams['payment_instrument_id'] = 4;
      $contributionParams['skipLineItem'] = true;
      //$contributionParams['line_item'][$line['id']][] = $line['values'][$line['id']];
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