<?php
/* Security measure */
if (!defined('IN_CMS')) {
	exit();
}

$success = true;

if (!Permission::findByName('edit_parts_php')) {
	$perm = new Permission(array('name' => 'edit_parts_php'));
	if (!$perm->save()) {
		$success = false;
		$errorMessages[] = __('Could not create edit_parts_php permission!');
	}
} else {
	$infoMessages[] = 'edit_parts_permission already exists!';
}

 if (!Role::findByName('php editor')) {
	$role = new Role(array('name' => 'php editor'));
	if (!$role->save()) {
		$success = false;
		$errorMessages[] = __('Could not create Php Editor role!');
	}
} else {
	$infoMessages[] = 'Php Editor role already exists!';
}

$perm = Permission::findByName('edit_parts_php');
$role = Role::findByName('php editor');
if (! ($role && $perm)) {
	$rp = new RolePermission(array('permission_id' => $perm->id, 'role_id' => $role->id));
	if (!$rp->save()) {
		$success = false;
		$errorMessages[] = __('Could not assign edit_parts_php permission to Php Editor role!');		
	}
}

if ($developerRole = Role::findByName('developer')) {

	$perm = Permission::findByName('edit_parts_php');
	$rp = RolePermission::findPermissionsFor($developerRole->id);
	if (!RolePermission::findOneFrom('RolePermission', 'role_id=? AND permission_id=?', array($developerRole->id, $perm->id))) {
		$rp[] = $perm;
		if (!RolePermission::savePermissionsFor($developerRole->id, $rp)) {
			$success = false;
			$errorMessages[] = __('Could not assign edit_parts_php permission to Developer role!');
		}
	}
} else {
	$infoMessages[] = 'Developer role not found already exists!';
}

if ($success) {
	Flash::set('success', __('Successfully enabled PHP restrict plugin!') . '<br/>'
	  . __('The new role "php editor" has been created!') . '<br/>'
	  . __('The developer role has been granted "edit_parts_php" permission!')
	);
} else {
	Flash::set('error', __('A problems occured while enabling restrict PHP plugin:'). '<br/>'.
	implode('<br/>', $errorMessages));
}

exit();