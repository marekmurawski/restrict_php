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
Plugin::setInfos(array(
		'id' => 'restrict_php',
		'title' => __('Restrict PHP'),
		'description' => __('Provides PHP code restriction in page parts based on roles and/or permissions'),
		'version' => '0.0.2',
		'license' => 'GPL',
		'author' => 'Marek Murawski',
		'website' => 'http://www.marekmurawski.pl/',
		//'update_url' => 'http://www.wolfcms.org/plugin-versions.xml',
		'require_wolf_version' => '0.7.5'
));

Plugin::addController('restrict_php', __('Restrict PHP'), 'administrator', true);

Observer::observe('part_edit_before_save', 'restrict_php_part');
Observer::observe('part_add_before_save', 'restrict_php_part');

Observer::observe('page_edit_before_save', 'restrict_part_deleting');

Observer::observe('page_edit_after_save', 'show_restrict_php_edit_error');
Observer::observe('page_add_after_save', 'show_restrict_php_add_error');

function restrict_part_deleting(& $page) {

	$post_parts = $_POST['part'];
	if (count($post_parts) < 1) {
		Flash::set('error', __('You cannot delete all page-parts'));
		redirect(get_url('page/edit/') . $page->id);
	}
	
	$dodie = false;

	$old_parts = PagePart::findByPageId($page->id);

	foreach ($old_parts as $old_part) { // traverse all existing parts
		unset($old_part->content_html); // delete content_html, it's not needed
		$not_in = true;
		foreach ($post_parts as $part_id => $data) {
			$data['name'] = trim($data['name']);
			if ($old_part->name == $data['name']) {
				$not_in = false; // PART FOUND ->> Not deleted
				$new_post_parts[$part_id] = $post_parts[$part_id]; // save it
				unset($post_parts[$part_id]);

				break;
			}
		}

		if ($not_in) { // current $old_part seems to be pending deletion
			if (has_php_code($old_part->content)) { // if it has PHP code
				if (!AuthUser::hasPermission('edit_parts_php')) { // and user can't edit PHP code
					$new_post_parts[] = get_object_vars($old_part); //restore the part
					$dodie = true;
				}
			}
		}
	}
	if (isset($new_post_parts)) {
		$new_post_parts = array_merge($new_post_parts, $post_parts);
	}


	$_POST['part'] = $new_post_parts; // RESTORE $_POST['part'] so it has deleted, but forbidden page parts again
		
	return $page;
}

function show_restrict_php_edit_error($page) {
	if ($restr_parts = Flash::get('php_restricted_parts')) {
		Flash::set('error', __("You CAN'T edit") . '<br/><strong>' .
		  implode('<br/>', $restr_parts) . '</strong><br/>' .
		  __('page parts because they contain PHP code.') . '<br/>' .
		  __('Contact site administrator if you need to edit PHP code in page parts.')
		);
	}

	Flash::set('info', __('Some page parts were not updated due to "PHP edit" permission restrictions!'));	
	return $page;
	die;
}

function show_restrict_php_add_error($page) {
	if ($restr_parts = Flash::get('php_restricted_parts')) {
		Flash::set('error', __("You CAN'T add PHP code into page parts. The following parts were cleared:") . '<br/><strong>' .
		  implode('<br/>', $restr_parts) . '</strong><br/>' .
		  __('Contact site administrator if you need to edit PHP code in page parts.')
		);
	}

	Flash::set('info', __('Some page parts were not updated due to "PHP edit" permission restrictions!'));	
	return $page;

}


function restrict_php_part(&$part) {
	$oldpart = PagePart::findByIdFrom('PagePart', $part->id);
	$codeFound = FALSE;

	$codeFound = (has_php_code($part->content) || has_php_code($oldpart->content));

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

function has_php_code($text) {
	$codeFound = TRUE;

	// SEARCHING FOR VARIANTS OF "script language=php" PHP opening tags
	// WARNING!!! This is not guaranteed to be safe!!!
	// IF YOU FIND ANY VULNERABILITIES PLEASE LET ME KNOW	
	$pattern = '#\<[\s]*script[\s]+lang.*=.*[\'"\s]*php[\s\'"]*\>#si';
	if (preg_match($pattern, $text) !== 1) {
		$codeFound = FALSE;
	}

	// SEARCHING FOR standard and short and ASP style PHP opening tags	
	$pattern = '#\<(\?|%)(?!xml)#si';

	if (preg_match($pattern, $text) !== 1) {
		$codeFound = FALSE;
	}
	return $codeFound;
}
