<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Caseinvoice_Form_CompleteInvoices extends CRM_Core_Form_Search {


  public function preProcess() {
    parent::preProcess();

    $this->_searchButtonName = $this->getButtonName('refresh');
    $this->_actionButtonName = $this->getButtonName('next', 'action');

    $this->_done = FALSE;
    $this->defaults = array();

    // we allow the controller to set force/reset externally, useful when we are being
    // driven by the wizard framework
    $this->_reset = CRM_Utils_Request::retrieve('reset', 'Boolean', CRM_Core_DAO::$_nullObject);
    $this->_force = CRM_Utils_Request::retrieve('force', 'Boolean', $this, FALSE);
    $this->_limit = CRM_Utils_Request::retrieve('limit', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'search');

    $this->assign("context", $this->_context);

    if (!empty($_POST) && !$this->controller->isModal()) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }
    else {
      $this->_formValues = $this->get('formValues');
    }

    $activities = array();
    if (!empty($this->_formValues)) {
      $activities = $this->query($this->_formValues);
      foreach($activities as $activity) {
        $this->addElement('checkbox', $activity['checkbox'], NULL, NULL, array('class' => 'select-row'));
      }
      $this->assign('pager', $this->getPager($activities));
      $this->addElement('checkbox', 'toggleSelect', NULL, NULL, array('class' => 'select-rows'));
    }

    $this->assign('activities', $activities);
  }

  protected function getPager($activities) {
    $params = array();
    $params['total'] = count($activities);
    $params['status'] = ts('Activities %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    return new CRM_Utils_Pager($params);
  }

  protected function query($formValues) {
    $util = CRM_Caseinvoice_Util_CompleteInvoices::singleton();
    return $util->query($formValues);
  }

  public function buildQuickForm() {

		$this->addEntityRef('coach', ts('Coach'),
			array(
				'entity' => 'contact',
				'api' => array('params' => array('contact_sub_type' => "Coach")),
				'multiple' => 'multiple',
				'option_url' => NULL,
				'placeholder' => ts('- any -'))
		);

    $this->addEntityRef('client', ts('Client'),
      array(
        'entity' => 'contact',
        'api' => array('params' => array()),
        'multiple' => 'multiple',
        'option_url' => NULL,
        'placeholder' => ts('- any -'))
    );

    $this->add('select', 'case_type_id',
      ts('Case Type'),
      CRM_Case_PseudoConstant::caseType('title', FALSE),
      FALSE, array('class' => 'crm-select2', 'multiple' => 'multiple')
    );

    $this->add('select', 'case_status_id',
      ts('Case Status'),
      CRM_Case_PseudoConstant::caseStatus('label', FALSE),
      FALSE, array('class' => 'crm-select2', 'multiple' => 'multiple')
    );

    CRM_Core_Form_Date::buildDateRange($this, 'activity_date', 1, '_low', '_high', ts('From'), FALSE, FALSE);

    $coachingsinformatie = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'Coachingsinformatie'));
    $betaalwijze = civicrm_api3('CustomField', 'getsingle', array('name' => 'Chequenummer_kiezen', 'custom_group_id' => $coachingsinformatie['id']));
    $this->addSelect('betaalwijze',
      array('entity' => 'case', 'field' => 'custom_'.$betaalwijze['id'], 'label' => $betaalwijze['label'], 'multiple' => 'multiple', 'option_url' => NULL, 'placeholder' => ts('- any -'))
    );

    $this->addTaskMenu(CRM_Caseinvoice_Task::CompleteInvoiceTaskTitles());
    $this->assign('actionButtonName', $this->_actionButtonName);

    $this->addButtons(array(
      array(
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ),
    ));

    $resources = CRM_Core_Resources::singleton();
    $resources
      ->addScriptFile('civicrm', 'js/crm.searchForm.js', 1, 'html-header')
      ->addStyleFile('civicrm', 'css/searchForm.css', 1, 'html-header');

    $this->addClass('crm-search-form');

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    if ($this->_done) {
      return;
    }

    $this->_done = TRUE;

    if (!empty($_POST)) {
      $this->_formValues = $this->controller->exportValues($this->_name);
    }

    $this->set('formValues', $this->_formValues);

    $buttonName = $this->controller->getButtonName();
    if ($buttonName == $this->_actionButtonName) {
      // check actionName and if next, then do not repeat a search, since we are going to the next page
      // hack, make sure we reset the task values
      $stateMachine = $this->controller->getStateMachine();
      $formName = $stateMachine->getTaskFormName();
      $this->controller->resetPage($formName);
      return;
    }
  }

}
