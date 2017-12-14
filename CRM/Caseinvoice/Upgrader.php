<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Caseinvoice_Upgrader extends CRM_Caseinvoice_Upgrader_Base {


  public function install() {
    $this->executeCustomDataFile('xml/case_invoice_settings.xml');
    $this->executeCustomDataFile('xml/km.xml');
    $result = civicrm_api3('OptionValue', 'create', array(
      'name' => 'factureren_fixed_price',
      'label' => 'Factureren fixed price',
      'option_group_id' => 'activity_type',
      'component_id' => 7, // CiviCase
    ));
    $factureren_fixed_price = civicrm_api3('OptionValue', 'getsingle', array('id' => $result['id']));
    $coaching_bedrijven_dossier = civicrm_api3('CaseType', 'getsingle', array('name' => 'coaching_voor_bedrijven'));
    $coaching_bedrijven_dossier['definition']['activityTypes'][] = array('name' => $factureren_fixed_price['name']);
    civicrm_api3('CaseType', 'create', $coaching_bedrijven_dossier);

    $opleiding_dossier = civicrm_api3('CaseType', 'getsingle', array('name' => 'opleiding'));
    $opleiding_dossier['definition']['activityTypes'][] = array('name' => $factureren_fixed_price['name']);
    civicrm_api3('CaseType', 'create', $opleiding_dossier);

    $advies_dossier = civicrm_api3('CaseType', 'getsingle', array('name' => 'advies'));
    $advies_dossier['definition']['activityTypes'][] = array('name' => $factureren_fixed_price['name']);
    civicrm_api3('CaseType', 'create', $advies_dossier);

    $this->executeCustomDataFile('xml/factuur.xml');
  }

  public function upgrade_1001() {
    $this->executeCustomDataFile('xml/km.xml');
    return true;
  }

  public function upgrade_1002() {
    //$this->executeCustomDataFile('xml/case_invoice_settings.xml');
    return true;
  }

  public function upgrade_1003() {
    $result = civicrm_api3('OptionValue', 'create', array(
      'name' => 'factureren_fixed_price',
      'label' => 'Factureren fixed price',
      'option_group_id' => 'activity_type',
      'component_id' => 7, // CiviCase
    ));
    $factureren_fixed_price = civicrm_api3('OptionValue', 'getsingle', array('id' => $result['id']));
    $coaching_bedrijven_dossier = civicrm_api3('CaseType', 'getsingle', array('name' => 'coaching_voor_bedrijven'));
    $coaching_bedrijven_dossier['definition']['activityTypes'][] = array('name' => $factureren_fixed_price['name']);
    civicrm_api3('CaseType', 'create', $coaching_bedrijven_dossier);
    return true;
  }

	public function upgrade_1004() {
		$this->executeCustomDataFile('xml/coach_invoice_settings.xml');
		CRM_Core_DAO::executeQuery("INSERT INTO civicrm_value_coach_invoice_settings (`entity_id`, `rate_coach`) SELECT `entity_id`, `rate_coach` FROM `civicrm_value_case_invoice_settings`");

		$result = civicrm_api3('OptionValue', 'create', array(
			'name' => 'factureren_aanbreng_fee',
			'label' => 'Aanbreng Fee factureren',
			'option_group_id' => 'activity_type',
			'component_id' => 7, // CiviCase
		));
		$factureren_aanbreng_fee = civicrm_api3('OptionValue', 'getsingle', array('id' => $result['id']));

		$result = civicrm_api3('OptionValue', 'create', array(
			'name' => 'factureren_outplacement_fee',
			'label' => 'Outplacement Fee factureren',
			'option_group_id' => 'activity_type',
			'component_id' => 7, // CiviCase
		));
		$factureren_outplacement_fee = civicrm_api3('OptionValue', 'getsingle', array('id' => $result['id']));


		$coaching_bedrijven_dossier = civicrm_api3('CaseType', 'getsingle', array('name' => 'coaching_voor_bedrijven'));
		$coaching_bedrijven_dossier['definition']['activityTypes'][] = array('name' => $factureren_aanbreng_fee['name']);
		$coaching_bedrijven_dossier['definition']['activityTypes'][] = array('name' => $factureren_outplacement_fee['name']);
		civicrm_api3('CaseType', 'create', $coaching_bedrijven_dossier);

		$coachingstraject = civicrm_api3('CaseType', 'getsingle', array('name' => 'coachingstraject'));
		$coachingstraject['definition']['activityTypes'][] = array('name' => $factureren_outplacement_fee['name']);
		civicrm_api3('CaseType', 'create', $coachingstraject);

		$coachingstraject_io = civicrm_api3('CaseType', 'getsingle', array('name' => 'coachingstraject_io'));
		$coachingstraject_io['definition']['activityTypes'][] = array('name' => $factureren_outplacement_fee['name']);
		civicrm_api3('CaseType', 'create', $coachingstraject_io);

		return true;
	}

	public function upgrade_1005() {
		$this->executeCustomDataFile('xml/factureren_fee.xml');
		return true;
	}

	public function upgrade_1006() {
  	$custom_group_id = civicrm_api3('CustomGroup', 'getvalue', array('return' => 'id', 'name' => 'case_invoice_settings'));
		CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET label = 'Uurtarief (coachingsactiviteiten)' WHERE `name` = 'rate' AND custom_group_id = %1", array(1=>array($custom_group_id, 'Integer')));
  	//$this->executeCustomDataFile('xml/case_invoice_settings.xml');

		return true;
	}

	public function upgrade_1007() {
		$this->executeCustomDataFile('xml/coach_invoice_settings.xml');

		$result = civicrm_api3('OptionValue', 'create', array(
			'name' => 'daypart',
			'label' => 'Dagdeel',
			'option_group_id' => 'activity_type',
			'component_id' => 7, // CiviCase
		));
		$day_part = civicrm_api3('OptionValue', 'getsingle', array('id' => $result['id']));

		$result = civicrm_api3('OptionValue', 'create', array(
			'name' => 'day',
			'label' => 'Dag',
			'option_group_id' => 'activity_type',
			'component_id' => 7, // CiviCase
		));
		$day = civicrm_api3('OptionValue', 'getsingle', array('id' => $result['id']));


		$coaching_bedrijven_dossier = civicrm_api3('CaseType', 'getsingle', array('name' => 'coaching_voor_bedrijven'));
		$coaching_bedrijven_dossier['definition']['activityTypes'][] = array('name' => $day_part['name']);
		$coaching_bedrijven_dossier['definition']['activityTypes'][] = array('name' => $day['name']);
		civicrm_api3('CaseType', 'create', $coaching_bedrijven_dossier);

		$coachingstraject = civicrm_api3('CaseType', 'getsingle', array('name' => 'coachingstraject'));
		$coachingstraject['definition']['activityTypes'][] = array('name' => $day_part['name']);
		$coachingstraject['definition']['activityTypes'][] = array('name' => $day['name']);
		civicrm_api3('CaseType', 'create', $coachingstraject);

		$coachingstraject_io = civicrm_api3('CaseType', 'getsingle', array('name' => 'coachingstraject_io'));
		$coachingstraject_io['definition']['activityTypes'][] = array('name' => $day_part['name']);
		$coachingstraject_io['definition']['activityTypes'][] = array('name' => $day['name']);
		civicrm_api3('CaseType', 'create', $coachingstraject_io);

		$advies = civicrm_api3('CaseType', 'getsingle', array('name' => 'advies'));
		$advies['definition']['activityTypes'][] = array('name' => $day_part['name']);
		$advies['definition']['activityTypes'][] = array('name' => $day['name']);
		civicrm_api3('CaseType', 'create', $advies);

		$opleiding = civicrm_api3('CaseType', 'getsingle', array('name' => 'opleiding'));
		$opleiding['definition']['activityTypes'][] = array('name' => $day_part['name']);
		$opleiding['definition']['activityTypes'][] = array('name' => $day['name']);
		civicrm_api3('CaseType', 'create', $opleiding);

		return true;
	}

	public function upgrade_1008() {
		$this->executeCustomDataFile('xml/factuurcoach.xml');
		$activityStatus = CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'status_id', array('flip' => 1, 'labelColumn' => 'name'));
		$betaald_id = $activityStatus['Betaald'];
		$sql = "
					INSERT INTO civicrm_value_factuurcoach (entity_id, gefactureerd) 
					SELECT id, 1 as gefactureerd 
					FROM civicrm_activity
					WHERE status_id = %1 AND is_current_revision = 1 AND is_deleted = 0";
		$params[1] = array($betaald_id, 'Integer');
		CRM_Core_DAO::executeQuery($sql, $params);

		return TRUE;
	}

	public function upgrade_1009() {
  	$custom_field_id = civicrm_api3('CustomField', 'getvalue', array('return' => 'id', 'name' => 'rate_coach', 'custom_group_id' => 'case_invoice_settings'));
  	civicrm_api3('CustomField', 'delete', array('id' => $custom_field_id));
  	return true;
	}

	public function upgrade_1010() {
    $custom_group_id = civicrm_api3('CustomGroup', 'getvalue', array('return' => 'id', 'name' => 'case_invoice_settings'));
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET label = 'Afronding tijdseenheid' WHERE `name` = 'rounding' AND custom_group_id = %1", array(1=>array($custom_group_id, 'Integer')));

    $case_types = array('coachingstraject', 'coaching_voor_bedrijven', 'advies', 'opleiding', 'coachingstraject_io');
    $case_type_ids = array();
    $this->executeCustomDataFile('xml/case_invoice_settings.xml');
    foreach($case_types as $case_type) {
      $case_type_ids[] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_case_type WHERE name = %1", array(1=>array($case_type, 'String')));
    }
    $case_type_ids = CRM_Core_DAO::VALUE_SEPARATOR.implode($case_type_ids, CRM_Core_DAO::VALUE_SEPARATOR).CRM_Core_DAO::VALUE_SEPARATOR;
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_group SET `extends_entity_column_value` = %1, title = 'Facturatieinstellingen Klant' WHERE `name` = 'case_invoice_settings'", array(1=>array($case_type_ids, 'String')));

    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET weight = '1' WHERE `name` = 'fixed_price_hourly_rate' AND custom_group_id = %1", array(1=>array($custom_group_id, 'Integer')));
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET weight = '2' WHERE `name` = 'total_amount' AND custom_group_id = %1", array(1=>array($custom_group_id, 'Integer')));
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET weight = '3' WHERE `name` = 'invoice_setting' AND custom_group_id = %1", array(1=>array($custom_group_id, 'Integer')));
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET weight = '4' WHERE `name` = 'advace_payments' AND custom_group_id = %1", array(1=>array($custom_group_id, 'Integer')));
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET weight = '5' WHERE `name` = 'case_financial_type' AND custom_group_id = %1", array(1=>array($custom_group_id, 'Integer')));
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET weight = '6' WHERE `name` = 'rate' AND custom_group_id = %1", array(1=>array($custom_group_id, 'Integer')));
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET weight = '7' WHERE `name` = 'rate_ondersteuning' AND custom_group_id = %1", array(1=>array($custom_group_id, 'Integer')));
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET weight = '8' WHERE `name` = 'rounding' AND custom_group_id = %1", array(1=>array($custom_group_id, 'Integer')));
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_field SET weight = '9' WHERE `name` = 'invoice_contact' AND custom_group_id = %1", array(1=>array($custom_group_id, 'Integer')));

    return true;
  }

  public function upgrade_1011() {
    $opleiding_dossier = civicrm_api3('CaseType', 'getsingle', array('name' => 'opleiding'));
    $opleiding_dossier['definition']['activityTypes'][] = array('name' => 'factureren_fixed_price');
    civicrm_api3('CaseType', 'create', $opleiding_dossier);

    $advies_dossier = civicrm_api3('CaseType', 'getsingle', array('name' => 'advies'));
    $advies_dossier['definition']['activityTypes'][] = array('name' => 'factureren_fixed_price');
    civicrm_api3('CaseType', 'create', $advies_dossier);
    return true;
  }

  public function upgrade_1012() {
    $activity_types = array('Intakegesprek','Verdiepingsgesprek','Synthese','Online coaching','Groepscoaching','Begeleiding individu','Begeleiding groep','Voorbereiding','Bevraging','Terugkom','Opleiding','Intern overleg','Overleg met organisatie','Verwerking','daypart','day');
    $activity_type_ids = array();
    foreach($activity_types as $activity_type) {
      $activity_type_id = CRM_Core_DAO::singleValueQuery("SELECT value FROM civicrm_option_value WHERE name = %1 and option_group_id = 2", array(1=>array($activity_type, 'String')));
      if (!empty($activity_type_id)) {
        $activity_type_ids[] = $activity_type_id;
      }
    }
    $activity_type_ids = CRM_Core_DAO::VALUE_SEPARATOR.implode($activity_type_ids, CRM_Core_DAO::VALUE_SEPARATOR).CRM_Core_DAO::VALUE_SEPARATOR;
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_group SET `extends_entity_column_value` = %1 WHERE `name` = 'KM'", array(1=>array($activity_type_ids, 'String')));

    return true;
  }

  public function upgrade_1013() {
    $this->executeCustomDataFile('xml/factuur.xml');
    return true;
  }
	
	public function upgrade_1014() {
		$case_types = array('coachingstraject', 'coaching_voor_bedrijven', 'advies', 'opleiding', 'coachingstraject_io');
    $case_type_ids = array();
    foreach($case_types as $case_type) {
      $case_type_ids[] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_case_type WHERE name = %1", array(1=>array($case_type, 'String')));
    }
    $case_type_ids = CRM_Core_DAO::VALUE_SEPARATOR.implode($case_type_ids, CRM_Core_DAO::VALUE_SEPARATOR).CRM_Core_DAO::VALUE_SEPARATOR;
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_group SET `extends_entity_column_value` = %1 WHERE `name` = 'coach_invoice_settings'", array(1=>array($case_type_ids, 'String')));
		return TRUE;
	}
	
	public function upgrade_1015() {
		$case_types = array('coachingstraject', 'coaching_voor_bedrijven', 'advies', 'opleiding', 'coachingstraject_io');
    $case_type_ids = array();
    foreach($case_types as $case_type) {
      $case_type_ids[] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_case_type WHERE name = %1", array(1=>array($case_type, 'String')));
    }
    $case_type_ids = CRM_Core_DAO::VALUE_SEPARATOR.implode($case_type_ids, CRM_Core_DAO::VALUE_SEPARATOR).CRM_Core_DAO::VALUE_SEPARATOR;
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_group SET `extends_entity_column_value` = %1 WHERE `name` = 'caselink_case'", array(1=>array($case_type_ids, 'String')));
		return TRUE;
	}
	
	public function upgrade_1016() {
		$case_types = array('coaching_voor_bedrijven', 'advies', 'opleiding', 'coachingstraject_io');
    $case_type_ids = array();
    foreach($case_types as $case_type) {
      $case_type_ids[] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_case_type WHERE name = %1", array(1=>array($case_type, 'String')));
    }
    $case_type_ids = CRM_Core_DAO::VALUE_SEPARATOR.implode($case_type_ids, CRM_Core_DAO::VALUE_SEPARATOR).CRM_Core_DAO::VALUE_SEPARATOR;
    CRM_Core_DAO::executeQuery("UPDATE civicrm_custom_group SET `extends_entity_column_value` = %1 WHERE `name` = 'caselink_case'", array(1=>array($case_type_ids, 'String')));
		return TRUE;
	}
	
	public function upgrade_1017() {
		$this->executeCustomDataFile('xml/case_invoice_settings.xml');
		return TRUE;
	}

  public function uninstall() {
    try {
      $case_info_settings_gid = civicrm_api3('CustomGroup', 'getvalue', array('name' => 'case_invoice_settings'));
    } catch (Exception $e) {
      return;
    }

    $custom_fields = civicrm_api3('CustomField', 'get', array('custom_group_id' => $case_info_settings_gid));
    foreach($custom_fields['values'] as $custom_field) {
      if (!empty($custom_field['option_group_id'])) {
        try {
          civicrm_api3('Option_Group', 'delete', array('id' => $custom_field['option_group_id']));
        } catch (Exception $e) {
          // Do Nothing
        }
      }
      try {
        civicrm_api3('CustomField', 'delete', array('id' => $custom_field['id']));
      } catch (Exception $e) {
        // Do nothing.
      }
    }
    try {
      civicrm_api3('CustomGroup', 'delete', array('id' => $case_info_settings_gid));
    } catch (Exception $e) {
      // Do nothing.
    }
  }

}
