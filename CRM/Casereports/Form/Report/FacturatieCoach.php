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

  protected $_add2groupSupported = FALSE;
	protected $_csvSupported = FALSE;

  protected $_absoluteUrl = TRUE;

  protected $km;

  protected $coachings_activity_type_ids = array();
	protected $ondersteunings_activity_type_ids = array();
	protected $day_part_activity_type_ids = array();
	protected $day_activity_type_ids = array();
	protected $facturatie_fee_activity_type_ids = array();
	protected $activity_status_id;

	protected $facturatie_fee_custom_group;
	protected $facturatie_fee_custom_field;

  public function __construct() {
		$this->activity_status_id = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'activity_status_id', null, 0);
		if (empty($this->activity_status_id)) {
			CRM_Core_Error::fatal('Activiteitsstatus is niet <a href="'.CRM_Utils_System::url('civicrm/admin/form/caseinvoicesettings'). '">ingesteld</a>');
		}
		$this->coachings_activity_type_ids = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'coachings_activity_type_ids', null, 0);
		if (!is_array($this->coachings_activity_type_ids) || empty($this->coachings_activity_type_ids)) {
			CRM_Core_Error::fatal('Coachingactiviteiten zijn niet <a href="'.CRM_Utils_System::url('civicrm/admin/form/caseinvoicesettings'). '">ingesteld</a>');
		}
		$this->ondersteunings_activity_type_ids = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'ondersteunings_activity_type_ids', null, 0);
		if (!is_array($this->ondersteunings_activity_type_ids) || empty($this->ondersteunings_activity_type_ids)) {
			CRM_Core_Error::fatal('Ondersteuningsactiviteiten zijn niet <a href="'.CRM_Utils_System::url('civicrm/admin/form/caseinvoicesettings'). '">ingesteld</a>');
		}
		$this->day_part_activity_type_ids = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'day_part_activity_type_ids', null, 0);
		if (!is_array($this->day_part_activity_type_ids) || empty($this->day_part_activity_type_ids)) {
			CRM_Core_Error::fatal('Dagdeelactiviteiten zijn niet <a href="'.CRM_Utils_System::url('civicrm/admin/form/caseinvoicesettings'). '">ingesteld</a>');
		}
		$this->day_activity_type_ids = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'day_activity_type_ids', null, 0);
		if (!is_array($this->day_activity_type_ids) || empty($this->day_activity_type_ids)) {
			CRM_Core_Error::fatal('Dagactiviteiten zijn niet <a href="'.CRM_Utils_System::url('civicrm/admin/form/caseinvoicesettings'). '">ingesteld</a>');
		}
		$this->facturatie_fee_custom_group = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'facturatie_fee'));
		$this->facturatie_fee_custom_field = civicrm_api3('CustomField', 'getsingle', array('name' => 'amount', 'custom_group_id' => $this->facturatie_fee_custom_group['id']));
		$this->facturatie_fee_activity_type_ids = $this->facturatie_fee_custom_group['extends_entity_column_value'];

    $this->km = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'km', null, 0.4);
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
            'no_display' => true,
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
						'no_display' => true,
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
						'default' => true,
						'required' => true,
          ),
          'activity_date_time' => array(
            'title' => ts('Activity Date'),
            'default' => true,
          ),
          'duration' => array(
            'title' => ts('Duration'),
            'default' => true,
						'required' => true,
						'no_display' => true,
          ),
        ),
        'filters' => array(
          'activity_date_time' => array(
            'title' => ts('Activity Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ),
        ),
      ),
      'coach' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'alias' => 'coach',
        'fields' => array(
          'sort_name' => array(
            'title' => ts('Coach'),
            'default' => FALSE,
          ),
        ),
        'filters' => array(
          'my_cases' => array(
            'title' => ts('Alleen activiteiten die door mezelf gerapporteerd zijn'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => array('0' => ts('No'), '1' => ts('Yes')),
            'pseudofield' => TRUE,
            'default' => '1',
          ),
        ),
      ),
    );

    parent::__construct();
  }

  public function select() {
    parent::select();

    $activity = $this->_aliases['civicrm_activity'];
		$case = $this->_aliases['civicrm_case'];
    $client = $this->_aliases['client'];

    $this->_select .= ", 
    	{$activity}.activity_type_id as activity_type_id, 
    	coach_invoice_settings.rate_coach AS invoice_settings_rate_coach, 
    	coach_invoice_settings.rate_ondersteuning AS invoice_settings_rate_ondersteuning,
    	coach_invoice_settings.rate_daypart AS invoice_settings_rate_day_part,
    	coach_invoice_settings.rate_day AS invoice_settings_rate_day,
    	invoice_settings.rounding AS invoice_settings_rounding, 
    	{$activity}.duration AS activity_duration, 
    	km.km as activity_km,
    	facturatie_fee.amount as activity_fee_amount";
  }

  public function from() {
    $activity = $this->_aliases['civicrm_activity'];
    $case = $this->_aliases['civicrm_case'];
    $client = $this->_aliases['client'];
    $coach = $this->_aliases['coach'];

    $this->_from = "FROM civicrm_activity {$activity} ";
    $this->_from .= "INNER JOIN civicrm_case_activity ON civicrm_case_activity.activity_id = {$activity}.id ";
    $this->_from .= "INNER JOIN civicrm_case {$case} ON civicrm_case_activity.case_id = {$case}.id ";
    $this->_from .= "INNER JOIN civicrm_case_contact ON civicrm_case_contact.case_id = ${case}.id ";
    $this->_from .= "INNER JOIN civicrm_contact {$client} on {$client}.id = civicrm_case_contact.contact_id ";
    $this->_from .= "INNER JOIN civicrm_activity_contact ON {$activity}.id = civicrm_activity_contact.activity_id AND civicrm_activity_contact.record_type_id = 2 "; // 2 = source contact
    $this->_from .= "INNER JOIN civicrm_contact {$coach} ON {$coach}.id = civicrm_activity_contact.contact_id ";

    $this->_from .= "LEFT JOIN civicrm_value_case_invoice_settings invoice_settings ON invoice_settings.entity_id = {$case}.id ";
		$this->_from .= "LEFT JOIN civicrm_value_coach_invoice_settings coach_invoice_settings ON coach_invoice_settings.entity_id = {$case}.id ";
    $this->_from .= "LEFT JOIN civicrm_value_km km ON km.entity_id = {$activity}.id ";
		$this->_from .= "LEFT JOIN civicrm_value_facturatie_fee facturatie_fee ON facturatie_fee.entity_id = {$activity}.id ";
  }

  public function where() {
    parent::where();

    $activity = $this->_aliases['civicrm_activity'];
    $case = $this->_aliases['civicrm_case'];

    $this->_where .= " AND {$activity}.is_deleted = '0' AND {$activity}.is_current_revision = '1' AND {$case}.is_deleted = '0'";
    if (CRM_Utils_Array::value("my_cases_value", $this->_params)) {
      $session = CRM_Core_Session::singleton();
      $this->_where .= " AND civicrm_activity_contact.contact_id = '".$session->get('userID')."'";
    }

		$activity_type_ids = array_merge($this->coachings_activity_type_ids, $this->ondersteunings_activity_type_ids, $this->facturatie_fee_activity_type_ids, $this->day_part_activity_type_ids, $this->day_activity_type_ids);
    $this->_where .= " AND {$activity}.activity_type_id IN (".implode(',', $activity_type_ids).") ";
    $this->_where .= " AND {$activity}.status_id = '".$this->activity_status_id."'";
		$this->_where .= " AND {$activity}.id NOT IN (select entity_id FROM civicrm_value_factuurcoach WHERE gefactureerd = 1)";

    if (isset($this->_submitValues['export_case_id']) && !empty($this->_submitValues['export_case_id'])) {
      $this->_where .= " AND {$case}.id = '".$this->_submitValues['export_case_id']."'";
    }
  }

  public function orderBy() {
    $activity = $this->_aliases['civicrm_activity'];
    $case = $this->_aliases['civicrm_case'];
    $client = $this->_aliases['client'];
		$this->_sections = array(
			'civicrm_case_id' => array(
				'name' => 'id',
				'title' => ts('Case'),
				'column' => 'id',
				'order' => 'asc',
				'alias' => 'case_civireport',
				'dbAlias' => "{$case}.id",
				'tplField' => 'civicrm_case_id',
			)
		);
		$this->_orderBy = "ORDER BY `{$client}`.`sort_name` ASC, {$case}.id ASC, {$activity}.activity_date_time DESC";
		$this->assign('sections', $this->_sections);
  }

  public function doTemplateAssignment(&$rows) {
    parent::doTemplateAssignment($rows);

    $this->buildCaseListForExport();
  }

  protected function buildCaseListForExport() {
    $parentCases = array();
    $case = $this->_aliases['civicrm_case'];
    $client = $this->_aliases['client'];
    $select = "SELECT DISTINCT {$case}.id, {$case}.subject, {$client}.sort_name as client";
    $sql = "{$select} {$this->_from} {$this->_where} {$this->_groupBy} {$this->_having} {$this->_orderBy}";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()) {
      $parentCases[$dao->id] = $dao->client .' - ' . $dao->subject;
    }
    $this->add('select', 'export_case_id', ts('Select case'), $parentCases, false, array(
      'style' => 'min-width:250px',
      'class' => 'crm-select2 huge',
      'multiple' => FALSE,
      'placeholder' => ts('- Alle dossiers -'),
    ));
  }

  public function modifyColumnHeaders() {
    $km = $this->km;

		$this->_columnHeaders['civicrm_case_id'] = array(
      'no_display' => true,
    );
    $this->_columnHeaders['client_id'] = array(
      'no_display' => true,
    );
    $this->_columnHeaders['invoice_settings_rate_coach'] = array(
      'no_display' => true,
    );
    $this->_columnHeaders['invoice_settings_rate_ondersteuning'] = array(
    	'no_display' => true,
		);
		$this->_columnHeaders['invoice_settings_rate_day_part'] = array(
			'no_display' => true,
		);
		$this->_columnHeaders['invoice_settings_rate_day'] = array(
			'no_display' => true,
		);
    $this->_columnHeaders['invoice_settings_rounding'] = array(
      'no_display' => true,
    );
    $this->_columnHeaders['activity_duration'] = array(
      'no_display' => true,
    );
		$this->_columnHeaders['activity_type_id'] = array(
			'no_display' => true,
		);
		$this->_columnHeaders['quantity'] = array(
			'title' => 'Aantal',
			'type' => CRM_Utils_Type::T_FLOAT,
		);
    $this->_columnHeaders['to_invoice'] = array(
      'title' => 'Te facturen',
      'type' => CRM_Utils_Type::T_MONEY,
    );
    $this->_columnHeaders['activity_km'] = array(
      'no_display' => true,
    );
    $this->_columnHeaders['activity_fee_amount'] = array(
    	'no_display' => true,
		);
    $this->_columnHeaders['to_invoice_km'] = array(
      'title' => 'Te facturen (KM a € '.$km.' per KM)',
      'type' => CRM_Utils_Type::T_MONEY,
    );
		$this->_columnHeaders['manage_case'] = array(
			'title' => 'Manage case',
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

    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
    	$url = CRM_Utils_System::url("civicrm/contact/view/case", 'reset=1&action=view&cid=' . $row['client_id'] . '&id=' .$row['civicrm_case_id'],$this->_absoluteUrl);
			$rows[$rowNum]['manage_case_link'] = $url;
      $rows[$rowNum]['manage_case'] = ts("Manage Case");

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

      $quantity = $this->detemineQuantity($row['activity_type_id'], $row);
      $rate = $this->determineRate($row['activity_type_id'], $row);
			$unit = $this->determineUnit($row['activity_type_id'], $row);
			$rows[$rowNum]['quantity'] = number_format($quantity, 2, ',', '.'). ' '.$unit;
      $rows[$rowNum]['to_invoice'] = round($quantity * $rate, 2);
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

	protected function determineUnit($activity_type_id, $row) {
		if (in_array($activity_type_id, $this->coachings_activity_type_ids)) {
			return 'uur';
		} elseif (in_array($activity_type_id, $this->ondersteunings_activity_type_ids)) {
			return 'uur';
		} elseif (in_array($activity_type_id, $this->day_part_activity_type_ids)) {
			return 'dagdeel';
		} elseif (in_array($activity_type_id, $this->day_activity_type_ids)) {
			return 'dag';
		}
		return '';
	}

  protected function detemineQuantity($activity_type_id, $row) {
		if (in_array($activity_type_id, $this->coachings_activity_type_ids)) {
			$roundedMinutes = $this->calculateRoundedMinutes($row['activity_duration'], $row['invoice_settings_rounding']);
			$hours = $roundedMinutes > 0 ? ($roundedMinutes / 60) : 0;
			return $hours;
		} elseif (in_array($activity_type_id, $this->ondersteunings_activity_type_ids)) {
			$roundedMinutes = $this->calculateRoundedMinutes($row['activity_duration'], $row['invoice_settings_rounding']);
			$hours = $roundedMinutes > 0 ? ($roundedMinutes / 60) : 0;
			return $hours;
		} elseif (in_array($activity_type_id, $this->facturatie_fee_activity_type_ids)) {
			return 1;
		} elseif (in_array($activity_type_id, $this->day_part_activity_type_ids)) {
			return 1;
		} elseif (in_array($activity_type_id, $this->day_activity_type_ids)) {
			return 1;
		}
		return 0.00;
	}

  protected function determineRate($activity_type_id, $row) {
		if (in_array($activity_type_id, $this->coachings_activity_type_ids)) {
			if (empty($row['invoice_settings_rate_coach'])) {
				return 0.00;
			}
			return (float) $row['invoice_settings_rate_coach'];
		} elseif (in_array($activity_type_id, $this->ondersteunings_activity_type_ids)) {
			if (empty($row['invoice_settings_rate_ondersteuning'])) {
				return 0.00;
			}
			return (float) $row['invoice_settings_rate_ondersteuning'];
		} elseif (in_array($activity_type_id, $this->day_part_activity_type_ids)) {
			if (empty($row['invoice_settings_rate_day_part'])) {
				return 0.00;
			}
			return (float) $row['invoice_settings_rate_day_part'];
		} elseif (in_array($activity_type_id, $this->day_activity_type_ids)) {
			if (empty($row['invoice_settings_rate_day'])) {
				return 0.00;
			}
			return (float) $row['invoice_settings_rate_day'];
		} elseif (in_array($activity_type_id, $this->facturatie_fee_activity_type_ids)) {
			if (empty($row['activity_fee_amount'])) {
				return 0.00;
			}
			return (float) $row['activity_fee_amount'];
		}
		return 0.00;
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
