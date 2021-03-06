<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

class CRM_Caseinvoice_Query {

  public static function query($formValues, $onlyNotInvoicedActivities=true, $includeFixedPriceCases=false) {
    $km = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'km', null, 0.4);

    $coach_relationship_type_id = civicrm_api3('RelationshipType', 'getvalue', array('name_b_a' => 'Coach', 'return' => 'id'));
		$activity_status_id = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'activity_status_id', null, 0);
		if (empty($activity_status_id)) {
			CRM_Core_Error::fatal('Activiteitsstatus is niet <a href="'.CRM_Utils_System::url('civicrm/admin/form/caseinvoicesettings'). '">ingesteld</a>');
		}
		$coachings_activity_type_ids = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'coachings_activity_type_ids', null, 0);
		if (!is_array($coachings_activity_type_ids) || empty($coachings_activity_type_ids)) {
			CRM_Core_Error::fatal('Coachingactiviteiten zijn niet <a href="'.CRM_Utils_System::url('civicrm/admin/form/caseinvoicesettings'). '">ingesteld</a>');
		}
		$ondersteunings_activity_type_ids = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'ondersteunings_activity_type_ids', null, 0);
		if (!is_array($ondersteunings_activity_type_ids) || empty($ondersteunings_activity_type_ids)) {
			CRM_Core_Error::fatal('Ondersteuningsactiviteiten zijn niet <a href="'.CRM_Utils_System::url('civicrm/admin/form/caseinvoicesettings'). '">ingesteld</a>');
		}
		$activity_type_ids = array_merge($coachings_activity_type_ids, $ondersteunings_activity_type_ids);

    $coachingsinformatie = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'Coachingsinformatie'));
    $Chequenummer_kiezen = civicrm_api3('CustomField', 'getsingle', array('name' => 'Chequenummer_kiezen', 'custom_group_id' => $coachingsinformatie['id']));

    $return = array();

    $params = array();
    $paramCount = 1;
    $where = " 1";
    if ($onlyNotInvoicedActivities) {
      $where .= " AND a.id NOT IN (
      	SELECT civicrm_line_item.entity_id 
      	FROM civicrm_line_item
      	INNER JOIN civicrm_contribution ON civicrm_line_item.contribution_id = civicrm_contribution.id 
      	WHERE civicrm_line_item.entity_table = 'civicrm_activity'
      	AND civicrm_contribution.is_test = '0'
    	)";
    }
    $where .= " AND a.is_test = '0' AND a.is_current_revision = '1' AND a.is_deleted = '0' AND c.is_deleted = '0'";
    $where .= " AND contact.is_deleted = '0'";

    if (!empty($formValues['case_type_id'])) {
      $where .= " AND c.case_type_id IN (".implode(", ", $formValues['case_type_id']).")";
    }
    if (!empty($formValues['case_status_id'])) {
      $where .= " AND c.status_id IN (".implode(", ", $formValues['case_status_id']).")";
    }

		$where .= " AND a.activity_type_id IN (".implode(", ", $activity_type_ids).")";
		$where .= " AND a.status_id = %".$paramCount;
		$params[$paramCount] = array($activity_status_id, 'Integer');
		$paramCount++;
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

    $where .= " AND case_role.relationship_type_id = %".$paramCount." AND case_role.is_active = '1'";
    $params[$paramCount] = array($coach_relationship_type_id, 'Integer');
    $paramCount++;

    if (!$includeFixedPriceCases) {
      $where .= " AND ((parent_invoice_settings.id IS NULL OR parent_invoice_settings.fixed_price_hourly_rate != 'fixed_price') AND invoice_settings.fixed_price_hourly_rate != 'fixed_price')";
    }

    if (!empty($formValues['coach'])) {
      $where .= " AND coach.id IN (".$formValues['coach'].")";
    }
    if (!empty($formValues['client'])) {
      $where .= " AND (contact.id IN (".$formValues['client'].") OR parent_contact.id IN (".$formValues['client']."))";
    }

    $where .= " AND a.duration IS NOT NULL AND a.duration > 0";

    $sql = "SELECT 
              a.id as activity_id, a.activity_date_time, a.activity_type_id, a.status_id as activity_status_id, activity_type.label as activity_type_label, activity_status.label as activity_status_label, a.duration,
              c.id as case_id, c.case_type_id, case_type.title as case_type_label, c.status_id as case_status_id, case_status.label AS case_status_label,
              contact.display_name, contact.id as contact_id,
              parent_case.id AS parent_case_id, parent_case_type.title as parent_case_type_label, parent_case_status.label AS parent_case_status_label, parent_contact.id as parent_contact_id, parent_contact.display_name as parent_display_name,
              civicrm_value_km.km
            FROM civicrm_activity a
            INNER JOIN civicrm_case_activity ca on a.id = ca.activity_id 
            INNER JOIN civicrm_case c on ca.case_id = c.id
            INNER JOIN civicrm_case_contact cc on c.id = cc.case_id
            INNER JOIN civicrm_contact contact on contact.id = cc.contact_id
            
            INNER JOIN civicrm_relationship case_role ON case_role.case_id = c.id
            INNER JOIN civicrm_contact coach on coach.id = case_role.contact_id_b
            
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
            
            LEFT JOIN civicrm_value_km ON civicrm_value_km.entity_id = a.id 
            
            LEFT JOIN civicrm_value_case_invoice_settings invoice_settings ON invoice_settings.entity_id = c.id
            LEFT JOIN civicrm_value_case_invoice_settings parent_invoice_settings ON parent_invoice_settings.entity_id = parent_case.id
            
            WHERE
            {$where} 
            ORDER BY parent_contact.sort_name, contact.sort_name, c.id, a.activity_date_time
            ";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while($dao->fetch()) {
      $invoiceSettings = CRM_Caseinvoice_Util::getInvoiceSettingsForCases(array($dao->case_id));
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
        'km' => $dao->km,
        'to_invoice' => 0.00,
        'to_invoice_km' => 0.00,
        'checkbox' => CRM_Core_Form::CB_PREFIX . $dao->activity_id,
      );

      if (!CRM_Caseinvoice_Util::validInvoiceSettings($row, $invoiceSettings[$dao->case_id])) {
        continue;
      }

      $row['to_invoice'] = CRM_Caseinvoice_Util::calculateInvoiceAmount($row, $invoiceSettings[$dao->case_id]);
      if (!empty($row['km'])) {
        $row['to_invoice_km'] = round($km * $row['km'], 2);
      }

      $return[] = $row;
    }
    return $return;
  }

}