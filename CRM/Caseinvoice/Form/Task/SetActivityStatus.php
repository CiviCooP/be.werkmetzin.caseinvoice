<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Caseinvoice_Form_Task_SetActivityStatus extends CRM_Caseinvoice_Form_CompleteInvoiceTask {

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
    $activityStatus = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'status_id', array('flip' => 1, 'labelColumn' => 'name'));
    $this->addSelect('status_id',
      array('entity' => 'activity', 'multiple' => false, 'option_url' => NULL, 'placeholder' => ts('- any -'))
    );
    $this->setDefaults(array('status_id' => array($activityStatus['Betaald'])));


    $this->addDefaultButtons(ts('Werk activiteitsstatus bij'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    $count=0;
    $submittedValues = $this->_submitValues;
    for($i=0; $i<count($this->activities); $i++) {
      if (in_array($this->activities[$i]['activity_id'], $this->_activityHolderIds)) {
        $params = array();
        $params['id'] = $this->activities[$i]['activity_id'];
        $params['status_id'] = $submittedValues['status_id'];
        civicrm_api3('Activity', 'create', $params);
        $count++;
      }
    }

    CRM_Core_Session::setStatus('Updated '.$count.' activities', '', 'success');


  }

}