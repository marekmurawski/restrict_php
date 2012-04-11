<?php

/* Security measure */
if (!defined('IN_CMS')) {
	exit();
}

/**
 * The skeleton plugin serves as a basic plugin template.
 *
 *
 * @package Plugins
 * @subpackage restrict_php
 *
 * @author Marek Murawski <http://marekmurawski.pl>
 * @copyright Marek Murawski, 2012
 * @license http://www.gnu.org/licenses/gpl.html GPLv3 license
 */
Plugin::setInfos(array(
		'id' => 'restrict_php',
		'title' => __('Restrict PHP'),
		'description' => __('Provides PHP code restriction in page parts based on roles and/or permissions'),
		'version' => '0.0.1',
		'license' => 'GPL',
		'author' => 'Marek Murawski',
		'website' => 'http://www.wolfcms.org/',
		//'update_url' => 'http://www.wolfcms.org/plugin-versions.xml',
		'require_wolf_version' => '0.7.5'
));

Plugin::addController('restrict_php', __('Restrict PHP'), 'administrator', true);

Observer::observe('part_edit_before_save', 'restrict_php_part');
Observer::observe('part_add_before_save', 'restrict_php_part');

Observer::observe('page_edit_after_save', 'show_restrict_php_edit_error');
Observer::observe('page_add_after_save', 'display_restrict_php_add_error');

function show_restrict_php_edit_error($page) {
	if ($restr_parts = Flash::get('php_restricted_parts')) {
		Flash::set('error', __("You CAN'T edit") . '<br/><strong>' .
		  implode('<br/>', $restr_parts) . '</strong><br/>' .
		  __('page parts because they contain PHP code.<br/>') .
		  __('Contact site administrator if you need to edit PHP code in page parts.')
		);
	}
	return $page;
	die;
}

function display_restrict_php_add_error($page) {
	if ($restr_parts = Flash::get('php_restricted_parts')) {
		Flash::set('error', __("You CAN'T add PHP code into page parts. The following parts were cleared:") . '<br/><strong>' .
		  implode('<br/>', $restr_parts) . '</strong><br/>' .
		  __('Contact site administrator if you need to edit PHP code in page parts.')
		);
	}
	return $page;
	die;
}

function restrict_php_part(&$part) {
	$oldpart = PagePart::findByIdFrom('PagePart', $part->id);
	$codeFound = FALSE;
	
	// SEARCHING FOR VARIANTS OF < script language = php > PHP opening tags
	// WARNING!!! This is not guaranteed to be safe!!!
	// IF YOU FIND ANY VULNERABILITIES PLEASE LET ME KNOW	
	$pattern = '#\<[\s]*script[\s]+lang.*=.*[\'"\s]*php[\s\'"]*\>#si';
	if (preg_match($pattern, $part->content) ||
	  preg_match($pattern, $oldpart->content)) {
		$codeFound = TRUE;
		Flash::set('info','found SCRIPT tag');
		//die;
	}

	// SEARCHING FOR standard and short and ASP style PHP opening tags
	if ( (strpos($part->content,'<?') !== false) || 
	  (strpos($part->content,'<%') !== false) ) {
		$codeFound = TRUE;
		Flash::set('info','found standard or asp tag');
		//die;
	}
	if ($codeFound) {
		if ($oldpart->content !== $part->content) { // the content has changed
			if (!AuthUser::hasPermission('edit_parts_php')) {
				$restrParts = Flash::get('php_restricted_parts');
				$restrParts[] = $part->name;
				Flash::setNow('php_restricted_parts', $restrParts);
				$part->content = $oldpart->content; //set original page part content
			}
		}
	}
	return $part;
}