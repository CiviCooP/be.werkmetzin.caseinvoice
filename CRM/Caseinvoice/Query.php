<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Caseinvoice_Query {

  public static function query($formValue) {
    $return = array();

    $where = "";

    $sql = "SELECT a.id as activity_id, a.activity_date_time, a.activity_type_id, a.status_id as activity_status_id, c.id as case_id, c.case_type_id, c.status_id as case_status_id, contact.display_name, contact.id as contact_id
            FROM civicrm_activity a 
            INNER JOIN civicrm_case_activity ca on a.id = ca.activity_id 
            INNER JOIN civicrm_case c on ca.case_id = c.id
            INNER JOIN civicrm_case_contact cc on c.id = cc.case_id
            INNER JOIN civicrm_contact contact on contact.id = cc.contact_id
            WHERE 1
            {$where} 
            ORDER BY contact.sort_name, c.id, a.activity_date_time
            ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()) {
      $row = array(
        'activity_id' => $dao->activity_id,
        'activity_date_time' => $dao->activity_date_time,
        'activity_type_id' => $dao->activity_type_id,
        'activity_status_id' => $dao->activity_status_id,
        'case_id' => $dao->case_id,
        'case_type_id' => $dao->case_type_id,
        'case_status_id' => $dao->case_status_id,
        'contact_id' => $dao->contact_id,
        'display_name' => $dao->display_name,
        'checkbox' => CRM_Core_Form::CB_PREFIX . $dao->activity_id,
      );
      $return[] = $row;
    }
    return $return;
  }

}