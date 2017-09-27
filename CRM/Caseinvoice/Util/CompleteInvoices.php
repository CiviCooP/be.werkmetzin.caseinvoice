<?php

class CRM_Caseinvoice_Util_CompleteInvoices {

  /**
   * @var CRM_Caseinvoice_Util_CompleteInvoices
   */
  private static $singleton;
	
	private $select;
	private $from;
	private $where;
	private $params;

  private function __construct() {
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
    $this->facturatie_fee_activity_type_ids = $this->facturatie_fee_custom_group['extends_entity_column_value'];
  }

  /**
   * @return CRM_Caseinvoice_Util_CompleteInvoices
   */
  public static function singleton() {
    if (!self::$singleton) {
      self::$singleton = new CRM_Caseinvoice_Util_CompleteInvoices();
    }
    return self::$singleton;
  }
	
	private function buildQuery($formValues) {
		if (empty($this->select) || empty($this->from) || empty($this->where)) {
			$this->buildSelect($formValues);
			$this->buildFrom($formValues);
			$this->buildWhere($formValues);
		}
	}
	
	private function buildSelect($formValues) {
		$this->select = "a.id as activity_id, a.activity_date_time, a.activity_type_id, a.status_id as activity_status_id, activity_type.label as activity_type_label, activity_status.label as activity_status_label, a.duration,
              c.id as case_id, c.case_type_id, case_type.title as case_type_label, c.status_id as case_status_id, case_status.label AS case_status_label,
              contact.display_name, contact.id as contact_id,
              coach.display_name as coach_display_name, coach.id as coach_id,
              parent_case.id AS parent_case_id, parent_case_type.title as parent_case_type_label, parent_case_status.label AS parent_case_status_label, parent_contact.id as parent_contact_id, parent_contact.display_name as parent_display_name,
              civicrm_value_km.km,
              facturatie_fee.amount as activity_fee_amount,
              coach_invoice_settings.rate_coach AS invoice_settings_rate_coach, 
    					coach_invoice_settings.rate_ondersteuning AS invoice_settings_rate_ondersteuning,
    					coach_invoice_settings.rate_daypart AS invoice_settings_rate_day_part,
    					coach_invoice_settings.rate_day AS invoice_settings_rate_day";
	}
	
	private function buildFrom($formValues) {
		$coachingsinformatie = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'Coachingsinformatie'));
		$this->from = " FROM civicrm_activity a
            INNER JOIN civicrm_case_activity ca on a.id = ca.activity_id 
            INNER JOIN civicrm_case c on ca.case_id = c.id
            INNER JOIN civicrm_case_contact cc on c.id = cc.case_id
            INNER JOIN civicrm_contact contact on contact.id = cc.contact_id
            
            INNER JOIN civicrm_activity_contact ON a.id = civicrm_activity_contact.activity_id AND civicrm_activity_contact.record_type_id = 2
            INNER JOIN civicrm_contact coach on coach.id = civicrm_activity_contact.contact_id
            
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
            LEFT JOIN civicrm_value_facturatie_fee facturatie_fee ON facturatie_fee.entity_id = a.id
            
            LEFT JOIN civicrm_value_case_invoice_settings invoice_settings ON invoice_settings.entity_id = c.id
            LEFT JOIN civicrm_value_case_invoice_settings parent_invoice_settings ON parent_invoice_settings.entity_id = parent_case.id
            LEFT JOIN civicrm_value_coach_invoice_settings coach_invoice_settings ON coach_invoice_settings.entity_id = c.id ";
	}

	private function buildWhere($formValues) {		
		$activity_type_ids = array_merge($this->coachings_activity_type_ids, $this->ondersteunings_activity_type_ids, $this->facturatie_fee_activity_type_ids, $this->day_part_activity_type_ids, $this->day_activity_type_ids);

    $coachingsinformatie = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'Coachingsinformatie'));
    $Chequenummer_kiezen = civicrm_api3('CustomField', 'getsingle', array('name' => 'Chequenummer_kiezen', 'custom_group_id' => $coachingsinformatie['id']));

    $return = array();

    $params = array();
    $paramCount = 1;
    $where = " 1";
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
    $params[$paramCount] = array($this->activity_status_id, 'Integer');
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

    if (!empty($formValues['coach'])) {
      $where .= " AND coach.id IN (".$formValues['coach'].")";
    }
    if (!empty($formValues['client'])) {
      $where .= " AND contact.id IN (".$formValues['client'].")";
    }

    $where .= " AND a.id NOT IN (select entity_id FROM civicrm_value_factuurcoach WHERE gefactureerd = 1)";
		
		$this->where = $where;
		$this->params = $params;
	}

	public function count($formValues) {
		$this->buildQuery($formValues);
    $sql = "SELECT COUNT(*) {$this->from} WHERE {$this->where}
            ";
    return CRM_Core_DAO::singleValueQuery($sql, $this->params);
	}

  public function query($formValues, $offset, $limit) {
    $km = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'km', null, 0.4);
		$this->buildQuery($formValues);
    $sql = "SELECT {$this->select} {$this->from} WHERE {$this->where}
            ORDER BY coach.sort_name, parent_contact.sort_name, contact.sort_name, c.id, a.activity_date_time
            LIMIT {$offset}, {$limit}
            ";
    $dao = CRM_Core_DAO::executeQuery($sql, $this->params);
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
        'coach_id' => $dao->coach_id,
        'coach_display_name' => $dao->coach_display_name,
        'parent_case_id' => $dao->parent_case_id,
        'parent_case_type_label' => $dao->parent_case_type_label,
        'parent_case_status_label' => $dao->parent_case_status_label,
        'parent_contact_id' => $dao->parent_contact_id,
        'parent_display_name' => $dao->parent_display_name,
        'km' => $dao->km,
        'activity_fee_amount' => $dao->activity_fee_amount,
        'checkbox' => CRM_Core_Form::CB_PREFIX . $dao->activity_id,
      );

      $quantity = $this->detemineQuantity($row['activity_type_id'], $dao);
      $rate = $this->determineRate($row['activity_type_id'], $dao);
      $unit = $this->determineUnit($row['activity_type_id'], $dao);
      $row['quantity'] = number_format($quantity, 2, ',', '.'). ' '.$unit;
      $row['to_invoice'] = round($quantity * $rate, 2);
      if (empty($row['to_invoice'])) {
        $row['to_invoice'] = '0.00';
      }
      $row['to_invoice_km'] = round($row['km'] * $km, 2);
      if (empty($row['to_invoice_km'])) {
        $row['to_invoice_km'] = '0.00';
      }

      $return[] = $row;
    }
    return $return;
  }

  protected function determineUnit($activity_type_id, $dao) {
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

  protected function detemineQuantity($activity_type_id, $dao) {
    if (in_array($activity_type_id, $this->coachings_activity_type_ids)) {
      $roundedMinutes = $this->calculateRoundedMinutes($dao->duration, $dao->invoice_settings_rounding);
      $hours = $roundedMinutes > 0 ? ($roundedMinutes / 60) : 0;
      return $hours;
    } elseif (in_array($activity_type_id, $this->ondersteunings_activity_type_ids)) {
      $roundedMinutes = $this->calculateRoundedMinutes($dao->duration, $dao->invoice_settings_rounding);
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

  protected function determineRate($activity_type_id, $dao) {
    if (in_array($activity_type_id, $this->coachings_activity_type_ids)) {
      if (empty($dao->invoice_settings_rate_coach)) {
        return 0.00;
      }
      return (float) $dao->invoice_settings_rate_coach;
    } elseif (in_array($activity_type_id, $this->ondersteunings_activity_type_ids)) {
      if (empty($dao->invoice_settings_rate_ondersteuning)) {
        return 0.00;
      }
      return (float) $dao->invoice_settings_rate_ondersteuning;
    } elseif (in_array($activity_type_id, $this->day_part_activity_type_ids)) {
      if (empty($dao->invoice_settings_rate_day_part)) {
        return 0.00;
      }
      return (float) $dao->invoice_settings_rate_day_part;
    } elseif (in_array($activity_type_id, $this->day_activity_type_ids)) {
      if (empty($dao->invoice_settings_rate_day)) {
        return 0.00;
      }
      return (float) $dao->invoice_settings_rate_day;
    } elseif (in_array($activity_type_id, $this->facturatie_fee_activity_type_ids)) {
      if (empty($dao->activity_fee_amount)) {
        return 0.00;
      }
      return (float) $dao->activity_fee_amount;
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
