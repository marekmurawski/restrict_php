<?php
if ( !defined('IN_CMS') )
    exit();


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
?>
<p class="button">
    <a href="<?php echo get_url('plugin/restrict_php/documentation'); ?>">
        <img src="<?php echo PLUGINS_URI . 'restrict_php/icons/help-32.png'; ?>" align="middle" title="<?php echo __('Documentation'); ?>" alt="<?php echo __('Documentation'); ?>" />
<?php echo __('Documentation'); ?>
    </a>
</p>