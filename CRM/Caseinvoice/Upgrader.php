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
  }

  public function upgrade_1001() {
    $this->executeCustomDataFile('xml/km.xml');
    return true;
  }

  public function upgrade_1002() {
    $this->executeCustomDataFile('xml/case_invoice_settings.xml');
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
  	$this->executeCustomDataFile('xml/case_invoice_settings.xml');

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
