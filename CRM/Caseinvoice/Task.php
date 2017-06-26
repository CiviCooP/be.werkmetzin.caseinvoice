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
  static $_complete_invoice_tasks = NULL;

  static $_generate_invoice_tasks = NULL;

  /**
   * These tasks are the core set of tasks that the user can perform.
   * on a contact / group of contacts
   */
  public static function tasks() {
    if (!(self::$_complete_invoice_tasks)) {
      self::$_complete_invoice_tasks = array(
        1 => array(
          'title' => ts('Werk facturatie status bij'),
          'class' => 'CRM_Caseinvoice_Form_Task_SetActivityInvoiceStatus',
          'result' => FALSE,
        ),
      );
    }
    CRM_Utils_Hook::searchTasks('completeinvoice', self::$_complete_invoice_tasks);

    if (!(self::$_generate_invoice_tasks)) {
      self::$_generate_invoice_tasks = array(
        1 => array(
          'title' => ts('Maak uitgaande facturen'),
          'class' => 'CRM_Caseinvoice_Form_Task_GenerateInvoice',
          'result' => FALSE,
        ),
      );
    }
    CRM_Utils_Hook::searchTasks('generateinvoice', self::$_complete_invoice_tasks);
    asort(self::$_complete_invoice_tasks);
    asort(self::$_generate_invoice_tasks);
  }

  public static function completeInvoiceTasks() {
    self::tasks();
    return self::$_complete_invoice_tasks;
  }

  /**
   * These tasks are the core set of task titles.
   * on activity
   *
   * @return array
   *   the set of task titles
   */
  public static function &CompleteInvoiceTaskTitles() {
    self::tasks();
    $titles = array();
    foreach (self::$_complete_invoice_tasks as $id => $value) {
      $titles[$id] = $value['title'];
    }
    return $titles;
  }

  public static function &GenerateInvoiceTaskTitles() {
    self::tasks();
    $titles = array();
    foreach (self::$_generate_invoice_tasks as $id => $value) {
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
  public static function getCompleteInvoiceTask($value) {
    self::tasks();
    if (!$value || !CRM_Utils_Array::value($value, self::$_complete_invoice_tasks)) {
      // make the print task by default
      $value = 1;
    }
    return array(
      self::$_complete_invoice_tasks[$value]['class'],
      self::$_complete_invoice_tasks[$value]['result'],
    );
  }

  public static function getGenerateInvoiceTask($value) {
    self::tasks();
    if (!$value || !CRM_Utils_Array::value($value, self::$_generate_invoice_tasks)) {
      // make the print task by default
      $value = 1;
    }
    return array(
      self::$_generate_invoice_tasks[$value]['class'],
      self::$_generate_invoice_tasks[$value]['result'],
    );
  }

}