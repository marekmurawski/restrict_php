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
		'version' => '0.0.1',
		'license' => 'GPL',
		'author' => 'Marek Murawski',
		'website' => 'http://www.marekmurawski.pl/',
		//'update_url' => 'http://www.wolfcms.org/plugin-versions.xml',
		'require_wolf_version' => '0.7.5'
));

Plugin::addController('restrict_php', __('Restrict PHP'), 'administrator', true);



Observer::observe('part_edit_before_save', 'restrict_php_part');
Observer::observe('part_add_before_save', 'restrict_php_part');

Observer::observe('page_edit_before_save', 'restrict_php_page_before_save');


Observer::observe('page_edit_after_save', 'show_restrict_php_edit_error');
Observer::observe('page_add_after_save', 'display_restrict_php_add_error');

function restrict_php_page_before_save(& $page) {
//	$old_parts = PagePart::findByPageId($page->id);
//	//$post_parts = $_POST['part'];
//		foreach ($_POST['part'] as $post_part) {
//			$tmp_part = new PagePart($post_part);
//			$tmp_part->page_id = $page->id;
//			
//			$post_parts[] = $tmp_part;
//		}
	
            $post_parts = $_POST['part'];
	    if (count($post_parts) < 1) {
		    Flash::set('error',__('You cannot delete all page-parts'));
		    redirect(get_url('page/edit/').$page->id);
	    }
            //Flash::set('post_parts_data', (object) $post_parts);
            $old_parts = PagePart::findByPageId($page->id);

                // check if all old page part are passed in POST
                // if not ... we need to delete it!
		foreach ($old_parts as $old_part) {
                    unset($old_part->content_html);
		    $not_in = true;
                    foreach ($post_parts as $part_id => $data) {
                        $data['name'] = trim($data['name']);
                        if ($old_part->name == $data['name']) {
                            $not_in = false; // PART FOUND ->> Not deleted
			    //$new_post_parts[$part_id] = get_object_vars($old_part);
			    
			    $new_post_parts[$part_id] = $post_parts[$part_id];
                            
			    // this will not really create a new page part because
                            // the id of the part is passed in $data
                            // $part = new PagePart($data);
                            // $part->page_id = $id;

                            unset($post_parts[$part_id]);

                            break;
                        }
                    }

                    if ($not_in) { // PART DELETED
                        //$old_part->delete();
			    echo $old_part->name . " - DELETED ";
			    if (has_php_code($old_part->content)) { // @todo check if user could delete it????
				    // echo "and it HAD PHP code";
				    if (!AuthUser::hasPermission('edit_parts_php')) {
					// echo "but user " . AuthUser::getUserName() . " can't delete it, so it's restored";
					//$post_parts[$part_id] = $old_part;
					$new_post_parts[] = get_object_vars($old_part);
				    }
			    } else { echo 'but it had no php code';};
			    echo '<br/>';
			}
                }
		if (isset($new_post_parts)) { 
		$new_post_parts = array_merge($new_post_parts, $post_parts);
		}

	
	
//	echo '<table style="vertical-align: top; width: 100%"><tr><td><h1>POST PARTS:</h1><pre>';
//	print_r($post_parts);
//	echo '</pre></td><td>';
//	echo '<h1>EXISTING PARTS:</h1><pre>';
//	print_r($old_parts);
//	echo '</pre></td><td>';
//	echo '<h1>POST-MODIFIED PARTS:</h1><pre>';
//	print_r($new_post_parts);
//	echo '</pre></td></tr></table>';
//	$_POST['part'] = $new_post_parts; // RESTORE $_POST['part'] so it has deleted, but forbidden page parts
//	echo '<h1>NEW POST PARTS:</h1><pre>';
//	print_r($_POST['part']);
//	echo '</pre>';
	
		//die;
		
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
function has_php_code($text) {
	$codeFound = FALSE;

	// SEARCHING FOR VARIANTS OF "script language=php" PHP opening tags
	// WARNING!!! This is not guaranteed to be safe!!!
	// IF YOU FIND ANY VULNERABILITIES PLEASE LET ME KNOW	
	$pattern = '#\<[\s]*script[\s]+lang.*=.*[\'"\s]*php[\s\'"]*\>#si';
	if (preg_match($pattern, $text)) {
		$codeFound = TRUE;
	}

	// SEARCHING FOR standard and short and ASP style PHP opening tags
	if ((strpos($text, '<?') !== false) ||
	  (strpos($text, '<%') !== false)) {
		$codeFound = TRUE;
	}
	return $codeFound;
}

function restrict_php_part(&$part) {
	$oldpart = PagePart::findByIdFrom('PagePart', $part->id);
	$codeFound = FALSE;

	// SEARCHING FOR VARIANTS OF "script language=php" PHP opening tags
	// WARNING!!! This is not guaranteed to be safe!!!
	// IF YOU FIND ANY VULNERABILITIES PLEASE LET ME KNOW	
	$pattern = '#\<[\s]*script[\s]+lang.*=.*[\'"\s]*php[\s\'"]*\>#si';
	if (preg_match($pattern, $part->content) ||
	  preg_match($pattern, $oldpart->content)) {
		$codeFound = TRUE;
	}

	// SEARCHING FOR standard and short and ASP style PHP opening tags
	if ((strpos($part->content, '<?') !== false) ||
	  (strpos($part->content, '<%') !== false)) {
		$codeFound = TRUE;
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