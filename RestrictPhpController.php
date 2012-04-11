<?php
/* Security measure */
if (!defined('IN_CMS')) {
	exit();
}

class RestrictPhpController extends PluginController {

	public function __construct() {
		$this->setLayout('backend');
		$this->assignToLayout('sidebar', new View('../../plugins/restrict_php/views/sidebar'));
	}

	public function documentation() {
		// Check for localized documentation or fallback to the default english and display notice
		$lang = ( $user = AuthUser::getRecord() ) ? strtolower($user->language) : 'en';

		if (!file_exists(PLUGINS_ROOT . DS . 'restrict_php' . DS . 'views/documentation/' . $lang . '.php')) {
			$this->display('restrict_php/views/documentation/en', array('message' => $message));
		}
		else
			$this->display('restrict_php/views/documentation/' . $lang);
	}

//	function settings() {
//		$this->display('restrict_php/views/settings', Plugin::getAllSettings('skeleton'));
//	}
	
	function index() {
		redirect(get_url('plugin/restrict_php/documentation'));
	}	

}