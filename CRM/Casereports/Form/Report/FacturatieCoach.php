<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Casereports_Form_Report_FacturatieCoach extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_relField = FALSE;
  protected $_exposeContactID = FALSE;

  protected $_customGroupExtends = array('Case', 'Activity');

  protected $_absoluteUrl = TRUE;

  public function __construct() {
    $this->case_types = CRM_Case_PseudoConstant::caseType();
    $this->case_statuses = CRM_Core_OptionGroup::values('case_status');
    $this->activity_statuses = CRM_Core_OptionGroup::values('activity_status');
    $this->activity_type = CRM_Core_OptionGroup::values('activity_type');
    $rels = CRM_Core_PseudoConstant::relationshipType();
    foreach ($rels as $relid => $v) {
      $this->rel_types[$relid] = $v['label_b_a'];
    }

    $this->_columns = array(
      'client' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'client',
        'fields' => array(
          'client_name' => array(
            'name' => 'sort_name',
            'title' => ts('Client'),
            'required' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
      ),
      'civicrm_case' => array(
        'dao' => 'CRM_Case_DAO_Case',
        'fields' => array(
          'id' => array(
            'title' => ts('Case ID'),
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'subject' => array(
            'title' => ts('Case Subject'),
            'default' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'case_type_id' => array(
            'title' => ts('Case Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->case_types,
          ),
          'status_id' => array(
            'title' => ts('Case Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->case_statuses,
          ),
        ),
      ),
      'civicrm_activity' => array(
        'fields' => array(
          'activity_type_id' => array(
            'title' => ts('Activity Type'),
          ),
          'activity_date_time' => array(
            'title' => ts('Activity Date'),
            'default' => true,
          ),
          'duration' => array(
            'title' => ts('Duration'),
            'default' => true,
          ),
        ),
        'filters' => array(
          'activity_type_id' => array(
            'title' => ts('Activity Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE),
            'pseudofield' => TRUE,
          ),
          'activity_status_id' => array(
            'title' => ts('Status'),
            'name' => 'status_id',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::activityStatus('label'),
            'pseudofield' => TRUE,
          ),
          'activity_date_time' => array(
            'title' => ts('Activity Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
        )
      ),
      'staff' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'staff',
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Staff Member'),
            'default' => FALSE,
          ),
        ),
        'filters' => array(
          'my_cases' => array(
            'title' => ts('My cases'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => array('0' => ts('No'), '1' => ts('Yes')),
            'pseudofield' => TRUE,
            'default' => '1',
          ),
        ),
      ),
      'civicrm_relationship' => array(
        'dao' => 'CRM_Contact_DAO_Relationship',
        'filters' => array(
          'relationship_type_id' => array(
            'title' => ts('Staff Relationship'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->rel_types,
          ),
        ),
      ),
      'civicrm_relationship_type' => array(
        'dao' => 'CRM_Contact_DAO_RelationshipType',
        'fields' => array(
          'label_b_a' => array(
            'title' => ts('Relationship'),
            'default' => FALSE,
          ),
        ),
      ),
    );

    parent::__construct();
  }

  public function select() {
    parent::select();

    $activity = $this->_aliases['civicrm_activity'];
    $this->_select .= ", invoice_settings.rate_coach AS invoice_settings_rate_coach, invoice_settings.rounding AS invoice_settings_rounding, {$activity}.duration AS activity_duration, km.km as activity_km";
  }

  public function from() {
    $activity = $this->_aliases['civicrm_activity'];
    $case = $this->_aliases['civicrm_case'];
    $client = $this->_aliases['client'];
    $staff = $this->_aliases['staff'];
    $relationship = $this->_aliases['civicrm_relationship'];
    $relationship_type = $this->_aliases['civicrm_relationship_type'];

    $this->_from = "FROM civicrm_activity {$activity} ";
    $this->_from .= "INNER JOIN civicrm_case_activity ON civicrm_case_activity.activity_id = {$activity}.id ";
    $this->_from .= "INNER JOIN civicrm_case {$case} ON civicrm_case_activity.case_id = {$case}.id ";
    $this->_from .= "INNER JOIN civicrm_case_contact ON civicrm_case_contact.case_id = ${case}.id ";
    $this->_from .= "INNER JOIN civicrm_contact {$client} on {$client}.id = civicrm_case_contact.contact_id ";
    $this->_from .= "INNER JOIN civicrm_relationship {$relationship} ON {$relationship}.case_id = {$case}.id ";
    $this->_from .= "INNER JOIN civicrm_relationship_type {$relationship_type} ON {$relationship_type}.id = {$relationship}.relationship_type_id ";
    $this->_from .= "INNER JOIN civicrm_contact {$staff} ON {$staff}.id = {$relationship}.contact_id_b ";
    $this->_from .= "LEFT JOIN civicrm_value_case_invoice_settings invoice_settings ON invoice_settings.entity_id = {$case}.id ";
    $this->_from .= "LEFT JOIN civicrm_value_km km ON km.entity_id = {$activity}.id";
  }

  public function where() {
    parent::where();

    $activity = $this->_aliases['civicrm_activity'];
    $case = $this->_aliases['civicrm_case'];
    $client = $this->_aliases['client'];
    $staff = $this->_aliases['staff'];
    $relationship = $this->_aliases['civicrm_relationship'];
    $relationship_type = $this->_aliases['civicrm_relationship_type'];

    $this->_where .= " AND {$activity}.is_deleted = '0' AND {$activity}.is_current_revision = '1' AND {$case}.is_deleted = '0'";
    $this->_where .= "AND {$relationship}.is_active = '1' AND ({$relationship}.start_date IS NULL OR DATE({$relationship}.start_date) <= NOW()) AND ({$relationship}.end_date IS NULL OR DATE({$relationship}.end_date) >= NOW())";
    if (CRM_Utils_Array::value("my_cases_value", $this->_params)) {
      $session = CRM_Core_Session::singleton();
      $this->_where .= " AND {$relationship}.contact_id_b = '".$session->get('userID')."'";
    }
  }

  public function orderBy() {
    $activity = $this->_aliases['civicrm_activity'];
    $case = $this->_aliases['civicrm_case'];
    $client = $this->_aliases['client'];
    $staff = $this->_aliases['staff'];
    $relationship = $this->_aliases['civicrm_relationship'];
    $relationship_type = $this->_aliases['civicrm_relationship_type'];

    $this->_orderBy = "ORDER BY `{$client}`.`sort_name` ASC, {$case}.id ASC, {$activity}.activity_date_time DESC";
  }

  public function modifyColumnHeaders() {
    $km = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'km', null, 0.4);

    $this->_columnHeaders['invoice_settings_rate_coach'] = array(
      'no_display' => true,
    );
    $this->_columnHeaders['invoice_settings_rounding'] = array(
      'no_display' => true,
    );
    $this->_columnHeaders['activity_duration'] = array(
      'no_display' => true,
    );
    $this->_columnHeaders['to_invoice'] = array(
      'title' => 'Te facturen (uren)',
      'type' => CRM_Utils_Type::T_MONEY,
    );
    $this->_columnHeaders['activity_km'] = array(
      'no_display' => true,
    );
    $this->_columnHeaders['to_invoice_km'] = array(
      'title' => 'Te facturen (KM a € '.$km.' per KM)',
      'type' => CRM_Utils_Type::T_MONEY,
    );
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
    $km = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'km', null, 0.4);
    $activity = $this->_aliases['civicrm_activity'];
    $case = $this->_aliases['civicrm_case'];
    $client = $this->_aliases['client'];
    $staff = $this->_aliases['staff'];
    $relationship = $this->_aliases['civicrm_relationship'];
    $relationship_type = $this->_aliases['civicrm_relationship_type'];

    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      // convert Case ID and Subject to links to Manage Case
      if (array_key_exists('civicrm_case_subject', $row) && array_key_exists('civicrm_case_id', $row) && !empty($rows[$rowNum]['client_id'])) {
        $url = CRM_Utils_System::url("civicrm/contact/view/case", 'reset=1&action=view&cid=' . $row['client_id'] . '&id=' .$row['civicrm_case_id'],$this->_absoluteUrl);
        $rows[$rowNum]['civicrm_case_subject_link'] = $url;
        $rows[$rowNum]['civicrm_case_subject_hover'] = ts("Manage Case");
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_activity_activity_type_id', $row)) {
        $type = '';
        if (isset($this->activity_type[$row['civicrm_activity_activity_type_id']])) {
          $type = $this->activity_type[$row['civicrm_activity_activity_type_id']];
        }
        $rows[$rowNum]['civicrm_activity_activity_type_id'] = $type;
        $entryFound = TRUE;
      }

      if (array_key_exists('client_client_name', $row) && !empty($rows[$rowNum]['client_id'])) {
        $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $row['client_id'],$this->_absoluteUrl);
        $rows[$rowNum]['client_client_name_link'] = $url;
        $rows[$rowNum]['client_client_name_hover'] = ts("View contact");
        $entryFound = TRUE;
      }

      $roundedMinutes = $this->calculateRoundedMinutes($row['activity_duration'], $row['invoice_settings_rounding']);
      $hours = $roundedMinutes > 0 ? ($roundedMinutes / 60) : 0;
      $rows[$rowNum]['to_invoice'] = round($hours * $row['invoice_settings_rate_coach'], 2);
      if (empty($rows[$rowNum]['to_invoice'])) {
        $rows[$rowNum]['to_invoice'] = '0.00';
      }
      $rows[$rowNum]['to_invoice_km'] = round($row['activity_km'] * $km, 2);
      if (empty($rows[$rowNum]['to_invoice_km'])) {
        $rows[$rowNum]['to_invoice_km'] = '0.00';
      }

      if (!$entryFound) {
        break;
      }
    }
  }

  protected function calculateRoundedMinutes($duration, $rounding) {
    if ($duration == 0) {
      return 0.0;
    }
    $minutes = $duration;
    switch ($rounding) {
      case '15_minutes':
        //10 / 15 = 0.667
        //34 / 15 = 2.2667
        $quarters = ceil($duration / 15);
        $minutes = $quarters * 15;
        break;
      case '30_minutes':
        //10 / 30 = 0.3333
        //34 / 30 = 1.13333
        $halfhours = ceil($duration / 30);
        $minutes = $halfhours * 30;
        break;
      case '60_minutes':
        //10 / 60 = 0.16667
        //64 / 60 = 1.06667
        $hours = ceil($duration / 60);
        $minutes = $hours * 60;
        break;
    }

    return $minutes;
  }


}
