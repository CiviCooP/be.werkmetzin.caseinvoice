<?php
/**
 * @author Jaap Jansma (CiviCooP) <jaap.jansma@civicoop.org>
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 */

/**
 * This class is used by the Search functionality.
 *
 *  - the search controller is used for building/processing multiform
 *    searches.
 *
 * Typically the first form will display the search criteria and it's results
 *
 * The second form is used to process search results with the associated actions
 *
 */
class CRM_Caseinvoice_Controller_GenerateInvoices extends CRM_Core_Controller {

  /**
   * Class constructor.
   *
   * @param null $title
   * @param bool $modal
   * @param int|mixed|null $action
   */
  public function __construct($title = NULL, $modal = TRUE, $action = CRM_Core_Action::NONE) {

    parent::__construct($title, $modal);

    $this->_stateMachine = new CRM_Caseinvoice_StateMachine_GenerateInvoices($this, $action);

    // Create and instantiate the pages.
    $this->addPages($this->_stateMachine, $action);

    // Add all the actions.
    $this->addActions();
  }

  /**
   * Getter for selectorName.
   *
   * @return mixed
   */
  public function selectorName() {
    return $this->get('selectorName');
  }

}
