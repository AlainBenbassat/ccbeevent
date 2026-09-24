<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'ccbeevent.civix.php';
// phpcs:enable

use CRM_Ccbeevent_ExtensionUtil as E;

function ccbeevent_civicrm_postCommit(string $op, string $objectName, int $objectId, mixed $objectRef = NULL, ?array $params = NULL): void {
  if ($op == 'create' && $objectName == 'Participant' && $objectRef) {
    $cf = new CRM_Ccbeevent_CountryFiller();
    $cf->fillCountryOfParticipant($objectId, (int)$objectRef->contact_id);
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function ccbeevent_civicrm_config(\CRM_Core_Config $config): void {
  _ccbeevent_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function ccbeevent_civicrm_install(): void {
  _ccbeevent_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function ccbeevent_civicrm_enable(): void {
  _ccbeevent_civix_civicrm_enable();
}
