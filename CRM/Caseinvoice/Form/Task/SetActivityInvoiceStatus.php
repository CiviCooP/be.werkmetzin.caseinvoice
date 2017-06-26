<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Caseinvoice_Form_Task_SetActivityInvoiceStatus extends CRM_Caseinvoice_Form_CompleteInvoiceTask {

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
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/case/completeinvoice', $urlParams));
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addDefaultButtons(ts('Werk facturatie status van activiteiten bij'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    $count=0;
    for($i=0; $i<count($this->activities); $i++) {
      if (in_array($this->activities[$i]['activity_id'], $this->_activityHolderIds)) {
      	$activity_id = $this->activities[$i]['activity_id'];
				$id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_value_factuurcoach WHERE entity_id = %1", array(1=>array($activity_id, 'Integer')));
      	if ($id) {
      		$updateSql = "UPDATE civicrm_value_factuurcoach SET gefactureerd = 1 WHERE entity_id = %1";
      		$params[1] = array($activity_id, 'Integer');
      		CRM_Core_DAO::executeQuery($updateSql, $params);
				} else {
      		$insertSql = "INSERT INTO civicrm_value_factuurcoach (entity_id, gefactureerd) VALUES (%1, 1)";
					$params[1] = array($activity_id, 'Integer');
					CRM_Core_DAO::executeQuery($insertSql, $params);
				}
        $count++;
      }
    }

    CRM_Core_Session::setStatus('Updated '.$count.' activities', '', 'success');
  }

}