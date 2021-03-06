<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Caseinvoice_Form_CompleteInvoiceTask extends CRM_Core_Form {

  /**
   * The task being performed.
   *
   * @var int
   */
  protected $_task;

  /**
   * The additional clause that we restrict the search with.
   *
   * @var string
   */
  protected $_componentClause = NULL;

  /**
   * The array that holds all the component ids.
   *
   * @var array
   */
  protected $_componentIds;

  /**
   * The array that holds all the contact ids.
   *
   * @var array
   */
  public $_contactIds;

  /**
   * The array that holds all the member ids.
   *
   * @var array
   */
  public $_activityHolderIds;

  /**
   * Build all the data structures needed to build the form.
   *
   * @param
   *
   * @return void
   */
  public function preProcess() {
    self::preProcessCommon($this);
  }

  /**
   * @param CRM_Core_Form $form
   * @param bool $useTable
   */
  public static function preProcessCommon(&$form, $useTable = FALSE) {
    $util = CRM_Caseinvoice_Util_CompleteInvoices::singleton();
    $form->_activityHolderIds = array();

    $values = $form->controller->exportValues($form->get('searchFormName'));

    $form->_task = $values['task'];
    $caseInvoiceTasks = CRM_Caseinvoice_Task::completeInvoiceTasks();
    $form->assign('taskName', $caseInvoiceTasks[$form->_task]);

    $formValues = $form->get('formValues');
		$count = $util->count($formValues);
  	$pager = self::getPager($count);
		list($offset, $limit) = $pager->getOffsetAndRowCount();
		if (isset($formValues['radio_ts']) && $formValues['radio_ts'] == 'ts_all') {
			$offset = 0;
			$limit = $count;
		}
    $form->activities = $util->query($formValues, $offset, $limit);

    $ids = array();
    if ($values['radio_ts'] == 'ts_sel') {
      foreach ($values as $name => $value) {
        if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          $ids[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }
    }
    else {
      foreach($form->activities as $activity) {
        $ids[] = $activity['activity_id'];
      }
    }

    if (!empty($ids)) {
      $form->_componentClause = ' civicrm_activity.id IN ( ' . implode(',', $ids) . ' ) ';
      $form->assign('totalSelectedActivities', count($ids));
    }

    $form->_activityHolderIds = $form->_componentIds = $ids;
  }

	/**
	 * @return CRM_Utils_Pager
	 */
  protected static function getPager($count) {
    $params = array();
    $params['total'] = $count;
    $params['status'] = ts('Activities %%StatusMessage%%');
    $params['csvString'] = NULL;
    $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    return new CRM_Utils_Pager($params);
  }


  /**
   * Simple shell that derived classes can call to add buttons to
   * the form with a customized title for the main Submit
   *
   * @param string $title
   *   Title of the main button.
   * @param string $nextType
   *   Button type for the form after processing.
   * @param string $backType
   * @param bool $submitOnce
   *
   * @return void
   */
  public function addDefaultButtons($title, $nextType = 'next', $backType = 'back', $submitOnce = FALSE) {
    $this->addButtons(array(
        array(
          'type' => $nextType,
          'name' => $title,
          'isDefault' => TRUE,
        ),
        array(
          'type' => $backType,
          'name' => ts('Cancel'),
        ),
      )
    );
  }

}
