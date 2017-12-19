<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Caseinvoice_StateMachine_GenerateInvoices extends CRM_Core_StateMachine {

  /**
   * The task that the wizard is currently processing.
   *
   * @var string
   */
  protected $_task;

  /**
   * Class constructor.
   *
   * @param object $controller
   * @param \const|int $action
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $this->_pages = array();

    $this->_pages['CRM_Caseinvoice_Form_GenerateInvoices'] = NULL;
    list($task, $result) = $this->taskName($controller, 'Search');
    $this->_task = $task;

    if (is_array($task)) {
      foreach ($task as $t) {
        $this->_pages[$t] = NULL;
      }
    }
    else {
      $this->_pages[$task] = NULL;
    }
    $this->addSequentialPages($this->_pages, $action);
  }

  /**
   * Determine the form name based on the action. This allows us
   * to avoid using  conditional state machine, much more efficient
   * and simpler
   *
   * @param CRM_Core_Controller $controller
   *   The controller object.
   *
   * @param string $formName
   *
   * @return string
   *   the name of the form that will handle the task
   */
  public function taskName($controller, $formName = 'Search') {
    // total hack, check POST vars and then session to determine stuff
    $value = CRM_Utils_Array::value('task', $_POST);
    if (!isset($value)) {
      $value = $this->_controller->get('task');
    }
    $this->_controller->set('task', $value);
    return CRM_Caseinvoice_Task::getGenerateInvoiceTask($value);
  }

  /**
   * Return the form name of the task.
   *
   * @return string
   */
  public function getTaskFormName() {
    return CRM_Utils_String::getClassName($this->_task);
  }

  /**
   * Should the controller reset the session.
   * In some cases, specifically search we want to remember
   * state across various actions and want to go back to the
   * beginning from the final state, but retain the same session
   * values
   *
   * @return bool
   */
  public function shouldReset() {
    return FALSE;
  }

}
