<?php

require_once 'caseinvoice.civix.php';

function caseinvoice_civicrm_buildForm($formName, &$form) {
  if ($form instanceof CRM_Contribute_Form_ContributionView) {
    $lineItem = CRM_Price_BAO_LineItem::getLineItems($form->get('id'), 'contribution', NULL, TRUE, TRUE);
    if (!empty($lineItem)) {
      $lineItems = array();
      foreach($lineItem as $key => $item) {
        if ($item['entity_table'] == 'civicrm_activity') {
          $lineItems[0][$key] = $item;
        }
      }
      if (!empty($lineItems)) {
        $form->assign('lineItem', $lineItems);
      }
    }
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function caseinvoice_civicrm_config(&$config) {
  _caseinvoice_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function caseinvoice_civicrm_xmlMenu(&$files) {
  _caseinvoice_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function caseinvoice_civicrm_install() {
  _caseinvoice_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function caseinvoice_civicrm_uninstall() {
  _caseinvoice_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function caseinvoice_civicrm_enable() {
  _caseinvoice_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function caseinvoice_civicrm_disable() {
  _caseinvoice_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function caseinvoice_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _caseinvoice_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function caseinvoice_civicrm_managed(&$entities) {
  _caseinvoice_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function caseinvoice_civicrm_caseTypes(&$caseTypes) {
  _caseinvoice_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function caseinvoice_civicrm_angularModules(&$angularModules) {
_caseinvoice_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function caseinvoice_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _caseinvoice_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function caseinvoice_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function caseinvoice_civicrm_navigationMenu(&$menu) {
  $item = array (
    'name'          =>  ts('Generate invoices'),
    'url'           =>  CRM_Utils_System::url('civicrm/case/generateinvoice', 'reset=1', true),
    'permission'    => 'access CiviContribute,edit contributions,access all cases and activities',
    'operator'      => 'AND',
  );
  _caseinvoice_civix_insert_navigation_menu($menu, 'Cases', $item);

  _caseinvoice_civix_navigationMenu($menu);
}
