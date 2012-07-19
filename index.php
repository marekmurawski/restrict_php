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
		'version' => '0.0.3',
		'license' => 'GPL',
		'author' => 'Marek Murawski',
		'website' => 'http://marekmurawski.pl/',
		'update_url'  => 'http://marekmurawski.pl/static/wolfplugins/plugin-versions.xml',
		'require_wolf_version' => '0.7.3' // 0.7.5SP-1 fix -> downgrading requirement to 0.7.3
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
		Flash::set('error', __('You cannot delete all page-parts. At least one must remain!'));
		redirect(get_url('page/edit/') . $page->id);
	}
	
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
					$info = Flash::get('info');
					$info .= __('Restored part - :part', array(':part'=>$old_part->name))  . '<br/>';
					Flash::set('info',$info);
				}
			}
		}
	}
	if (isset($new_post_parts)) {
		$new_post_parts = array_merge($new_post_parts, $post_parts);
	}


	$_POST['part'] = $new_post_parts; // RESTORE $_POST['part'] so it again contains deleted but forbidden page parts
		
	return $page;
}

function show_restrict_php_edit_error($page) {
	if ($restr_parts = Flash::get('php_restricted_parts')) {
		Flash::set('error', __("You can't edit") . '<br/><strong>' .
		  implode('<br/>', $restr_parts) . '</strong><br/>' .
		  __('page parts because they contain PHP code.') . '<br/>' .
		  __('Contact site administrator if you need to edit PHP code in page parts.')
		);
	}
	return $page;
}

function show_restrict_php_add_error($page) {
	if ($restr_parts = Flash::get('php_restricted_parts')) {
		Flash::set('error', __("You CAN'T add PHP code into page parts. The following parts were cleared:") . '<br/><strong>' .
		  implode('<br/>', $restr_parts) . '</strong><br/>' .
		  __('Contact site administrator if you need to edit PHP code in page parts.')
		);
	}
	return $page;
}


function restrict_php_part(&$part) {
	$oldpart = PagePart::findByIdFrom('PagePart', $part->id);
	$codeFound = FALSE;
	$codeExisted = has_php_code($oldpart->content);
	$codeAdded = has_php_code($part->content);
	$codeFound = $codeAdded || $codeExisted;
	
	if ($codeFound) {
		if ($oldpart->content !== $part->content) { // the content has changed
			if (!AuthUser::hasPermission('edit_parts_php') ) { // if user CANNOT edit php
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
	// WARNING!!! This is not guaranteed to be safe!!!
	// IF YOU FIND ANY VULNERABILITIES PLEASE LET ME KNOW	
	try {
		$codeFound = FALSE;
		
		// SEARCHING FOR VARIANTS OF "script language=php" PHP opening tags
		$pattern1 = '#\<[\s]*script[\s]+lang.*=.*[\'"\s]*php[\s\'"]*\>#si';

		// SEARCHING FOR standard and short and ASP style PHP opening tags
		// omitting xml opening tags 
		// only exact "<?xml" will pass (without space beetween ? and "xml")
		$pattern2 = '#\<(\?|%)(?!xml)#si';

		$result1 = preg_match($pattern1, $text);
		if (($result1 === 1) ||  // found occurence of long opening tag
			($result1 === FALSE)) // or search error occurred
		{
			$codeFound = TRUE;
		}


		$result2 = preg_match($pattern2, $text);
		if (($result2 === 1) ||  // found occurence of standard/short opening tag
			($result2 === FALSE)) // or search error occurred
		{
			$codeFound = TRUE;
		}
		return $codeFound;
	} catch (Exception $exc) {	// something went wrong
	//echo $exc->getTraceAsString();
	return TRUE;			// so we assume the code was found!
	}
}
