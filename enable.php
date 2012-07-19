<?php

/* Security measure */
if (!defined('IN_CMS')) {
	exit();
}

/**
 * Restrict PHP Plugin for Wolf CMS.
 * Provides PHP code restriction in page parts based on roles and/or permissions
 * 
 * 
 * @package Plugins
 * @subpackage restrict_php
 *
 * @author Marek Murawski <http://marekmurawski.pl>
 * @copyright Marek Murawski, 2012
 * @license http://www.gnu.org/licenses/gpl.html GPLv3 license
 */
$success = true;

if (!Permission::findByName('edit_parts_php')) {
	$perm = new Permission(array('name' => 'edit_parts_php'));
	if (!$perm->save()) {
		$success = false;
		$errorMessages[] = __('Could not create edit_parts_php permission!');
	} else $infoMessages[] = __('Created edit_parts_php permission!');
} else {
	$infoMessages[] = __('edit_parts_php permission already exists!');
}

if (!Role::findByName('php editor')) {
	$role = new Role(array('name' => 'php editor'));
	if (!$role->save()) {
		$success = false;
		$errorMessages[] = __('Could not create Php Editor role!');
	} else $infoMessages[] = __('Created Php Editor role!');
} else {
	$infoMessages[] = __('Php Editor role already exists!');
}

$perm = Permission::findByName('edit_parts_php');
$role = Role::findByName('php editor');
if (($role && $perm)) {
	$rp = new RolePermission(array('permission_id' => $perm->id, 'role_id' => $role->id));
	if (!$rp->save()) {
		$success = false;
		$errorMessages[] = __('Could not assign edit_parts_php permission to Php Editor role!');
	} else $infoMessages[] = __('Assigned edit_parts_php permission to Php Editor role!');
}

if ($developerRole = Role::findByName('developer')) {

	$perm = Permission::findByName('edit_parts_php');
	$rp = RolePermission::findPermissionsFor($developerRole->id);
	if (!RolePermission::findOneFrom('RolePermission', 'role_id=? AND permission_id=?', array($developerRole->id, $perm->id))) {
		$rp[] = $perm;
		if (!RolePermission::savePermissionsFor($developerRole->id, $rp)) {
			$success = false;
			$errorMessages[] = __('Could not assign edit_parts_php permission to Developer role!');
		} else $infoMessages[] = __('Assigned edit_parts_php permission to Developer role!');
	}
} else {
	$infoMessages[] = __('Developer role not found!');
}

if ($success) {
	Flash::set('success', __('Successfully activated Restrict PHP plugin!'));
	if (isset($infoMessages)) {
		Flash::set('info',implode('<br/>', $infoMessages));
	}
} else {
	Flash::set('error', __('Problems occured while activating restrict PHP plugin:') . '<br/>' .
	  implode('<br/>', $errorMessages));
}

exit();