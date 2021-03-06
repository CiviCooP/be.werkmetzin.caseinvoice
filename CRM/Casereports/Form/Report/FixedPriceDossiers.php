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
class CRM_Casereports_Form_Report_FixedPriceDossiers extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_relField = FALSE;
  protected $_exposeContactID = FALSE;

  protected $_customGroupExtends = array('Case');

  protected $_absoluteUrl = TRUE;

  protected $km;

  public function __construct() {
    $this->case_types = CRM_Case_PseudoConstant::caseType();
    $this->case_statuses = CRM_Core_OptionGroup::values('case_status');
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
            'default' => TRUE,
          ),
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
        ),
        'order_bys' => array(
          'sort_name' => array(
            'name' => 'sort_name',
            'title' => ts('Client'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC',
            'default_is_section' => '0',
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
          ),
          'case_type_id' => array(
            'title' => ts('Case type'),
            'default' => true,
          ),
          'status_id' => array(
            'title' => ts('Status'),
            'default' => true,
          ),
        ),
        'filters' => array(
          'case_type_id' => array(
            'title' => ts('Case Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->case_types,
          ),
          'status_id' => array(
            'title' => ts('Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->case_statuses,
          ),
        ),
        'order_bys' => array(
          'subject' => array(
            'name' => 'subject',
            'title' => ts('Case Subject'),
            'default' => '1',
            'default_weight' => '1',
            'default_order' => 'ASC',
            'default_is_section' => '0',
          ),
        ),
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
  }

  public function from() {
    $case = $this->_aliases['civicrm_case'];
    $client = $this->_aliases['client'];
    $staff = $this->_aliases['staff'];
    $relationship = $this->_aliases['civicrm_relationship'];
    $relationship_type = $this->_aliases['civicrm_relationship_type'];


    $this->_from = "FROM civicrm_case {$case} ";
    $this->_from .= "INNER JOIN civicrm_case_contact ON civicrm_case_contact.case_id = ${case}.id ";
    $this->_from .= "INNER JOIN civicrm_contact {$client} on {$client}.id = civicrm_case_contact.contact_id ";
    $this->_from .= "INNER JOIN civicrm_relationship {$relationship} ON {$relationship}.case_id = {$case}.id ";
    $this->_from .= "INNER JOIN civicrm_relationship_type {$relationship_type} ON {$relationship_type}.id = {$relationship}.relationship_type_id ";
    $this->_from .= "INNER JOIN civicrm_contact {$staff} ON {$staff}.id = {$relationship}.contact_id_b ";
  }

  public function where() {
    parent::where();

    $relationship = $this->_aliases['civicrm_relationship'];
    $this->_where .= "AND {$relationship}.is_active = '1' AND ({$relationship}.start_date IS NULL OR DATE({$relationship}.start_date) <= NOW()) AND ({$relationship}.end_date IS NULL OR DATE({$relationship}.end_date) >= NOW())";
    if (CRM_Utils_Array::value("my_cases_value", $this->_params)) {
      $session = CRM_Core_Session::singleton();
      $this->_where .= " AND {$relationship}.contact_id_b = '".$session->get('userID')."'";
    }
  }

  public function modifyColumnHeaders() {
    $this->_columnHeaders['manage_case'] = array(
      'title' => '',
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
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      // convert Case ID and Subject to links to Manage Case
      if (array_key_exists('civicrm_case_subject', $row) && array_key_exists('civicrm_case_id', $row) && !empty($rows[$rowNum]['client_id'])) {
        $url = CRM_Utils_System::url("civicrm/contact/view/case", 'reset=1&action=view&cid=' . $row['client_id'] . '&id=' .$row['civicrm_case_id'],$this->_absoluteUrl);
        $rows[$rowNum]['manage_case'] = ts('Manage case');
        $rows[$rowNum]['manage_case_link'] = $url;
        $rows[$rowNum]['manage_case_hover'] = ts("Manage Case");
        $entryFound = TRUE;
      }

      if (array_key_exists('civicrm_case_status_id', $row)) {
        $rows[$rowNum]['civicrm_case_status_id'] = $this->case_statuses[$row['civicrm_case_status_id']];
      }

      if (array_key_exists('civicrm_case_case_type_id', $row)) {
        $rows[$rowNum]['civicrm_case_case_type_id'] = $this->case_types[$row['civicrm_case_case_type_id']];
      }

      if (array_key_exists('client_client_name', $row) && !empty($rows[$rowNum]['client_id'])) {
        $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $row['client_id'],$this->_absoluteUrl);
        $rows[$rowNum]['client_client_name_link'] = $url;
        $rows[$rowNum]['client_client_name_hover'] = ts("View contact");
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}
