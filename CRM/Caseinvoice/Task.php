<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Caseinvoice_Task {

  /**
   * The task array.
   *
   * @var array
   */
  static $_tasks = NULL;

  /**
   * The optional task array.
   *
   * @var array
   */
  static $_optionalTasks = NULL;

  /**
   * These tasks are the core set of tasks that the user can perform.
   * on a contact / group of contacts
   *
   * @return array
   *   the set of tasks for a group of contacts
   */
  public static function &tasks() {
    if (!(self::$_tasks)) {
      self::$_tasks = array(
        1 => array(
          'title' => ts('Generate invoices'),
          'class' => 'CRM_Caseinvoice_Form_Task_GenerateInvoice',
          'result' => FALSE,
        ),
      );
    }
    CRM_Utils_Hook::searchTasks('caseinvoice', self::$_tasks);
    asort(self::$_tasks);
    return self::$_tasks;
  }

  /**
   * These tasks are the core set of task titles.
   * on activity
   *
   * @return array
   *   the set of task titles
   */
  public static function &taskTitles() {
    self::tasks();
    $titles = array();
    foreach (self::$_tasks as $id => $value) {
      $titles[$id] = $value['title'];
    }
    return $titles;
  }

  /**
   * These tasks are the core set of tasks that the user can perform.
   * on activity
   *
   * @param int $value
   *
   * @return array
   *   the set of tasks for a group of activity
   */
  public static function getTask($value) {
    self::tasks();
    if (!$value || !CRM_Utils_Array::value($value, self::$_tasks)) {
      // make the print task by default
      $value = 1;
    }
    return array(
      self::$_tasks[$value]['class'],
      self::$_tasks[$value]['result'],
    );
  }

}