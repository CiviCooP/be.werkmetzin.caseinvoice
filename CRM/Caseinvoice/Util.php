<?php

class CRM_Caseinvoice_Util {

  protected static $coachings_activity_type_ids = NULL;
  protected static $ondersteunings_activity_type_ids = NULL;

  /**
   * Get the settings for invoicing such as the rate etc..
   *
   * @param array $caseIds
   *   Array of caseIds.
   *
   * @return array
   */
  public static function getInvoiceSettingsForCases($caseIds) {
		$taxRates = CRM_Core_PseudoConstant::getTaxRates();
    $settings = array();
    $sql = "SELECT * FROM civicrm_value_case_invoice_settings WHERE entity_id IN (".implode(", ", $caseIds).")";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $settings[$dao->entity_id] = array(
        'rate' => $dao->rate,
        'rate_ondersteuning' => $dao->rate_ondersteuning,
        'rounding' => $dao->rounding,
        'invoice_contact' => $dao->invoice_contact,
        'case_financial_type' => $dao->case_financial_type,
        'invoice_setting' => $dao->invoice_setting,
      );
			if ($settings[$dao->entity_id]['vat_setting'] == 'vat_included') {
				$financial_type_id = $settings[$dao->entity_id]['case_financial_type'];				
				if (array_key_exists($financial_type_id, $taxRates)) {
		    	$taxRate = $taxRates[$financial_type_id];
					$settings[$dao->entity_id]['rate'] =  CRM_Utils_Rule::cleanMoney($settings[$dao->entity_id]['rate']) * 100 / (100 + $taxRate);
					$settings[$dao->entity_id]['rate_ondersteuning'] =  CRM_Utils_Rule::cleanMoney($settings[$dao->entity_id]['rate_ondersteuning']) * 100 / (100 + $taxRate);
				}
			}
    }
    return $settings;
  }

  public static function validInvoiceSettings($activity, $invoiceSetting) {
    $financial_type_id = $invoiceSetting['case_financial_type'];
    if (empty($financial_type_id)) {
      CRM_Core_Session::setStatus('Financial type not set for case: '.$activity['display_name'].' (id = '.$activity['case_id'].') ');
      return false;
    }

    if (in_array($activity['activity_type_id'], self::coachingActivityTypeIds())) {
      if (empty($invoiceSetting['rate'])) {
        CRM_Core_Session::setStatus('Uurtarief (coachingsactiviteiten) not set for case: '.$activity['display_name'].' (id = '.$activity['case_id'].') ');
        return false;
      }
    } elseif (in_array($activity['activity_type_id'], self::ondersteuningsActivityTypeIds())) {
      if (empty($invoiceSetting['rate_ondersteuning'])) {
        CRM_Core_Session::setStatus('Uurtarief (ondersteuningsactiviteiten) not set for case: '.$activity['display_name'].' (id = '.$activity['case_id'].') ');
        return false;
      }
    }
    return true;
  }

  /**
   * Calculate the amount to invoice for this activity.
   *
   * @param $activity
   * @param $invoiceSetting
   *
   * @return float
   */
  public static function calculateInvoiceAmount($activity, $invoiceSetting) {
  	$hours = CRM_Caseinvoice_Util::calculateHours($activity, $invoiceSetting);
    $rate = CRM_Caseinvoice_Util::determineRate($activity, $invoiceSetting);
    if ($rate === false) {
      return false;
    }
    $price = $hours * $rate;
    return $price;
  }
	
	/**
   * Calculate the amount of hours to invoice for this activity.
   *
   * @param $activity
   * @param $invoiceSetting
   *
   * @return float
   */
  public static function calculateHours($activity, $invoiceSetting) {
    $minutes = CRM_Caseinvoice_Util::calculateRoundedMinutes($activity['duration'], $invoiceSetting['rounding']);
    $hours = $minutes > 0 ? ($minutes / 60) : 0;
		return $hours;
  }

  public static function calculateInvoiceAmountLabel($activity, $invoiceSetting, $contact_id) {
    $config = CRM_Core_Config::singleton();
    $minutes = CRM_Caseinvoice_Util::calculateRoundedMinutes($activity['duration'], $invoiceSetting['rounding']);
    $display_name = civicrm_api3('Contact', 'getvalue', array('return' => 'display_name', 'id' => $contact_id));
    $label = CRM_Utils_Date::customFormat($activity['activity_date_time'], $config->dateformatFull) . ' ' . $activity['activity_type_label'] . ' - ' . $display_name . ': ' . $minutes . ' minuten';
    return $label;
  }

  /**
   * Calculate the rounded minutes based on the rounding setting.
   *
   * @param $duration
   * @param $rounding
   *
   * @return float
   */
  public static function calculateRoundedMinutes($duration, $rounding) {
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

  /**
   * Determine the rate for the activity
   *
   * @param array $activity
   *   The activity parameter should have a key of activity_type_id set.
   * @param array $invoiceSetting
   *
   * @return float
   */
  public static function determineRate($activity, $invoiceSetting) {
    if (in_array($activity['activity_type_id'], self::coachingActivityTypeIds())) {
      if (empty($invoiceSetting['rate'])) {
        CRM_Core_Session::setStatus('Uurtarief (coachingsactiviteiten) not set for case: '.$activity['display_name'].' (id = '.$activity['case_id'].') ');
        return false;
      }
      return (float) $invoiceSetting['rate'];
    } elseif (in_array($activity['activity_type_id'], self::ondersteuningsActivityTypeIds())) {
      if (empty($invoiceSetting['rate_ondersteuning'])) {
        CRM_Core_Session::setStatus('Uurtarief (ondersteuningsactiviteiten) not set for case: '.$activity['display_name'].' (id = '.$activity['case_id'].') ');
        return false;
      }
      return (float) $invoiceSetting['rate_ondersteuning'];
    }
    CRM_Core_Session::setStatus('Uurtarief not set for case: '.$activity['display_name'].' (id = '.$activity['case_id'].') ');
    return false;
  }

  /**
   * Returns an array with the activity type ids for coachings activities.
   *
   * @return array|null
   */
  public static function coachingActivityTypeIds() {
    if (is_null(self::$coachings_activity_type_ids)) {
      self::$coachings_activity_type_ids = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'coachings_activity_type_ids', null, 0);
      if (!is_array(self::$coachings_activity_type_ids) || empty(self::$coachings_activity_type_ids)) {
        CRM_Core_Error::fatal('Coachingactiviteiten zijn niet <a href="'.CRM_Utils_System::url('civicrm/admin/form/caseinvoicesettings'). '">ingesteld</a>');
      }
    }
    return self::$coachings_activity_type_ids;
  }

  /**
   * Returns an array with the activity type ids for ondersteunings activities.
   *
   * @return array|null
   */
  public static function ondersteuningsActivityTypeIds() {
    if (is_null(self::$ondersteunings_activity_type_ids)) {
      self::$ondersteunings_activity_type_ids = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'ondersteunings_activity_type_ids', NULL, 0);
      if (!is_array(self::$ondersteunings_activity_type_ids) || empty(self::$ondersteunings_activity_type_ids)) {
        CRM_Core_Error::fatal('Ondersteuningsactiviteiten zijn niet <a href="' . CRM_Utils_System::url('civicrm/admin/form/caseinvoicesettings') . '">ingesteld</a>');
      }
    }
    return self::$ondersteunings_activity_type_ids;
  }

}
