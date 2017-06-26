<?php

class CRM_Caseinvoice_Form_Settings extends CRM_Core_Form {
	public function buildQuickForm() {
		$this->add('text', 'km', ts('KM Vergoedeing'), array(),true);

		$this->addFormRule(array('CRM_Caseinvoice_Form_Settings', 'checkKm'));

		$this->addSelect('payment_instrument_id',
			array('entity' => 'contribution', 'label' => ts('Payment Method'), 'option_url' => NULL, 'placeholder' => ts('- select -')),
			true //Required
		);

		$statusValues = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'contribution_status_id');
		$this->add('select', 'contribution_status_id',
			ts('Contribution Status'), $statusValues,
			TRUE, array('class' => 'crm-select2', 'multiple' => false)
		);

		$this->addSelect('coachings_activity_type_ids', array(
				'label'       => ts('Coachingactiviteiten'),
				'entity'      => 'activity',
				'field'       => 'activity_type_id',
				'multiple'    => 'multiple',
				'option_url'  => NULL,
				'placeholder' => ts('- any -')
			), true
		);

		$this->addSelect('ondersteunings_activity_type_ids', array(
				'label'       => ts('Ondersteuningsactiviteiten'),
				'entity'      => 'activity',
				'field'       => 'activity_type_id',
				'multiple'    => 'multiple',
				'option_url'  => NULL,
				'placeholder' => ts('- any -')
			), TRUE
		);

		$this->addSelect('day_part_activity_type_ids', array(
				'label'       => ts('Dagdeel activiteiten'),
				'entity'      => 'activity',
				'field'       => 'activity_type_id',
				'multiple'    => 'multiple',
				'option_url'  => NULL,
				'placeholder' => ts('- any -')
			), TRUE
		);

		$this->addSelect('day_activity_type_ids', array(
				'label'       => ts('Dag activiteiten'),
				'entity'      => 'activity',
				'field'       => 'activity_type_id',
				'multiple'    => 'multiple',
				'option_url'  => NULL,
				'placeholder' => ts('- any -')
			), TRUE
		);

		$this->addSelect('activity_status_id', array(
				'entity' => 'activity',
				'field' => 'status_id',
				'label' => ts('Activiteitsstatus'),
				'option_url' => NULL,
				'placeholder' => ts('- any -')
			),
			true
		);

		// add buttons
		$this->addButtons(array(
			array('type' => 'next', 'name' => ts('Save'), 'isDefault' => true,),
			array('type' => 'cancel', 'name' => ts('Cancel')),
		));
		parent::buildQuickForm();
	}

	public function setDefaultValues() {
		$defaults = array();
		$defaults['km'] = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'km', null, 0.4);
		$defaults['payment_instrument_id'] = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'payment_instrument_id', null, 0);
		$defaults['contribution_status_id'] = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'contribution_status_id', null, 0);
		$defaults['activity_status_id'] = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'activity_status_id', null, 0);

		$defaults['coachings_activity_type_ids'] = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'coachings_activity_type_ids', null, 0);
		if (!is_array($defaults['coachings_activity_type_ids'])) {
			$defaults['coachings_activity_type_ids'] = array();
		}
		$defaults['ondersteunings_activity_type_ids'] = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'ondersteunings_activity_type_ids', null, 0);
		if (!is_array($defaults['ondersteunings_activity_type_ids'])) {
			$defaults['ondersteunings_activity_type_ids'] = array();
		}
		$defaults['day_part_activity_type_ids'] = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'day_part_activity_type_ids', null, 0);
		if (!is_array($defaults['day_part_activity_type_ids'])) {
			$defaults['day_part_activity_type_ids'] = array();
		}
		$defaults['day_activity_type_ids'] = CRM_Core_BAO_Setting::getItem('be.werkmetzin.caseinvoice', 'day_activity_type_ids', null, 0);
		if (!is_array($defaults['day_activity_type_ids'])) {
			$defaults['day_activity_type_ids'] = array();
		}


		return $defaults;
	}

	public function postProcess() {
		CRM_Core_BAO_Setting::setItem((float) $this->_submitValues['km'], 'be.werkmetzin.caseinvoice', 'km');
		CRM_Core_BAO_Setting::setItem($this->_submitValues['payment_instrument_id'], 'be.werkmetzin.caseinvoice', 'payment_instrument_id');
		CRM_Core_BAO_Setting::setItem($this->_submitValues['contribution_status_id'], 'be.werkmetzin.caseinvoice', 'contribution_status_id');
		CRM_Core_BAO_Setting::setItem($this->_submitValues['activity_status_id'], 'be.werkmetzin.caseinvoice', 'activity_status_id');
		CRM_Core_BAO_Setting::setItem($this->_submitValues['coachings_activity_type_ids'], 'be.werkmetzin.caseinvoice', 'coachings_activity_type_ids');
		CRM_Core_BAO_Setting::setItem($this->_submitValues['ondersteunings_activity_type_ids'], 'be.werkmetzin.caseinvoice', 'ondersteunings_activity_type_ids');
		CRM_Core_BAO_Setting::setItem($this->_submitValues['day_part_activity_type_ids'], 'be.werkmetzin.caseinvoice', 'day_part_activity_type_ids');
		CRM_Core_BAO_Setting::setItem($this->_submitValues['day_activity_type_ids'], 'be.werkmetzin.caseinvoice', 'day_activity_type_ids');
		CRM_Core_Session::setStatus('', ts('Case invoice Settings saved'), 'success');
		parent::postProcess();
	}

	public static function checkKm($fields) {
		$km = self::convertToFloat($fields['km']);
		$errors = array();
		if (!is_numeric($km)) {
			$errors['km'] = ts('Enter a valid amount');
		}
		if (count($errors)) {
			return $errors;
		}
		return true;
	}

	static function convertToFloat($money) {
		$config = CRM_Core_Config::singleton();
		$currency = $config->defaultCurrency;
		$_currencySymbols = CRM_Core_PseudoConstant::get('CRM_Contribute_DAO_Contribution', 'currency', array('keyColumn' => 'name', 'labelColumn' => 'symbol'));
		$currencySymbol = CRM_Utils_Array::value($currency, $_currencySymbols, $currency);
		$replacements = array(
			$currency => '',
			$currencySymbol => '',
			$config->monetaryThousandSeparator => '',
		);
		$return =  trim(strtr($money, $replacements));
		$decReplacements = array(
			$config->monetaryDecimalPoint => '.',
		);
		$return = trim(strtr($return, $decReplacements));
		return $return;
	}
}