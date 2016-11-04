<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Caseinvoice_Upgrader extends CRM_Caseinvoice_Upgrader_Base {


  public function install() {
    $this->executeCustomDataFile('xml/case_invoice_settings.xml');
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
