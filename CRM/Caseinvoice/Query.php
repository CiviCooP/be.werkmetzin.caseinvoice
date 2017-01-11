<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Caseinvoice_Query {

  public static function query($formValues) {
    $coachingsinformatie = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'Coachingsinformatie'));
    $Chequenummer_kiezen = civicrm_api3('CustomField', 'getsingle', array('name' => 'Chequenummer_kiezen', 'custom_group_id' => $coachingsinformatie['id']));

    $return = array();

    $params = array();
    $paramCount = 1;
    $where = "a.id NOT IN (SELECT civicrm_line_item.entity_id FROM civicrm_line_item WHERE civicrm_line_item.entity_table = 'civicrm_activity')";
    $where .= " AND a.is_test = '0' AND a.is_current_revision = '1' AND a.is_deleted = '0' AND c.is_deleted = '0'";
    $where .= " AND contact.is_deleted = '0'";

    if (!empty($formValues['case_type_id'])) {
      $where .= " AND c.case_type_id IN (".implode(", ", $formValues['case_type_id']).")";
    }
    if (!empty($formValues['case_status_id'])) {
      $where .= " AND c.status_id IN (".implode(", ", $formValues['case_status_id']).")";
    }
    if (!empty($formValues['activity_type_id'])) {
      $where .= " AND a.activity_type_id IN (".implode(", ", $formValues['activity_type_id']).")";
    }
    if (!empty($formValues['status_id'])) {
      $where .= " AND a.status_id IN (".implode(", ", $formValues['status_id']).")";
    }
    if (!empty($formValues['betaalwijze'])) {
      $where .= " AND (";
      foreach($formValues['betaalwijze'] as $betaalwijze) {
        $where .= "`".$coachingsinformatie['table_name']."`.`".$Chequenummer_kiezen['column_name']."` = %".$paramCount;
        $params[$paramCount] = array($betaalwijze, 'String');
        $paramCount++;
      }
      $where .= ")";
    }

    $relative = $formValues['activity_date_relative'];
    $from = $formValues['activity_date_low'];
    $to = $formValues['activity_date_high'];
    if ($relative) {
      list($from, $to) = CRM_Utils_Date::getFromTo($relative, $from, $to);
    }
    if ($from) {
      $where .= " AND a.activity_date_time >= %{$paramCount}";
      $fromDate = new DateTime($from);
      $params[$paramCount] = array($fromDate->format('Y-m-d'), 'String');
      $paramCount++;
    }
    if ($to) {
      $where .= " AND a.activity_date_time <= %{$paramCount}";
      $toDate = new DateTime($to);
      $params[$paramCount] = array($toDate->format('Y-m-d'), 'String');
      $paramCount++;
    }

    $where .= " AND a.duration IS NOT NULL AND a.duration > 0";

    $sql = "SELECT 
              a.id as activity_id, a.activity_date_time, a.activity_type_id, a.status_id as activity_status_id, activity_type.label as activity_type_label, activity_status.label as activity_status_label, a.duration,
              c.id as case_id, c.case_type_id, case_type.title as case_type_label, c.status_id as case_status_id, case_status.label AS case_status_label,
              contact.display_name, contact.id as contact_id,
              parent_case.id AS parent_case_id, parent_case_type.title as parent_case_type_label, parent_case_status.label AS parent_case_status_label, parent_contact.id as parent_contact_id, parent_contact.display_name as parent_display_name
            FROM civicrm_activity a 
            INNER JOIN civicrm_case_activity ca on a.id = ca.activity_id 
            INNER JOIN civicrm_case c on ca.case_id = c.id
            INNER JOIN civicrm_case_contact cc on c.id = cc.case_id
            INNER JOIN civicrm_contact contact on contact.id = cc.contact_id
            
            LEFT JOIN `{$coachingsinformatie['table_name']}` ON `{$coachingsinformatie['table_name']}`.entity_id = c.id 
            
            LEFT JOIN civicrm_case_type case_type ON case_type.id = c.case_type_id
            LEFT JOIN civicrm_option_group og_case_status ON og_case_status.name = 'case_status'
            LEFT JOIN civicrm_option_value case_status ON case_status.option_group_id = og_case_status.id AND case_status.value = c.status_id
            
            LEFT JOIN civicrm_option_group og_activity_type ON og_activity_type.name = 'activity_type'
            LEFT JOIN civicrm_option_value activity_type ON activity_type.option_group_id = og_activity_type.id AND activity_type.value = a.activity_type_id
            LEFT JOIN civicrm_option_group og_activity_status ON og_activity_status.name = 'activity_status'
            LEFT JOIN civicrm_option_value activity_status ON activity_status.option_group_id = og_activity_status.id AND activity_status.value = a.status_id
            
            LEFT JOIN civicrm_value_caselink_case caselink ON caselink.entity_id = c.id
            LEFT JOIN civicrm_case parent_case ON parent_case.id = caselink.case_id
            LEFT JOIN civicrm_case_contact parent_cc on parent_case.id = parent_cc.case_id
            LEFT JOIN civicrm_contact parent_contact on parent_contact.id = parent_cc.contact_id
            LEFT JOIN civicrm_case_type parent_case_type ON parent_case_type.id = parent_case.case_type_id
            LEFT JOIN civicrm_option_group og_parent_case_status ON og_parent_case_status.name = 'case_status'
            LEFT JOIN civicrm_option_value parent_case_status ON parent_case_status.option_group_id = og_parent_case_status.id AND parent_case_status.value = parent_case.status_id
            
            WHERE
            {$where} 
            ORDER BY parent_contact.sort_name, contact.sort_name, c.id, a.activity_date_time
            ";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while($dao->fetch()) {
      $row = array(
        'activity_id' => $dao->activity_id,
        'activity_date_time' => $dao->activity_date_time,
        'activity_type_id' => $dao->activity_type_id,
        'activity_type_label' => $dao->activity_type_label,
        'activity_status_id' => $dao->activity_status_id,
        'activity_status_label' => $dao->activity_status_label,
        'duration' => $dao->duration,
        'case_id' => $dao->case_id,
        'case_type_id' => $dao->case_type_id,
        'case_type_label' => $dao->case_type_label,
        'case_status_id' => $dao->case_status_id,
        'case_status_label' => $dao->case_status_label,
        'contact_id' => $dao->contact_id,
        'display_name' => $dao->display_name,
        'parent_case_id' => $dao->parent_case_id,
        'parent_case_type_label' => $dao->parent_case_type_label,
        'parent_case_status_label' => $dao->parent_case_status_label,
        'parent_contact_id' => $dao->parent_contact_id,
        'parent_display_name' => $dao->parent_display_name,
        'checkbox' => CRM_Core_Form::CB_PREFIX . $dao->activity_id,
      );
      $return[] = $row;
    }
    return $return;
  }

}