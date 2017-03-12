<?php
/*
Plugin Name: SEO FILTER
Plugin URI: 
Description: 
Author: NikolayS93
Author URI: http://vk.com/nikolay_s93
Text Domain: new-plugin
Domain Path: /languages/
Version: 0.3a
*/

/**
* 			
*/
class DTFilter //extends AnotherClass
{
	function __construct(){
		$this->define_constants();
		$this->get_includes( array('dt-form-render', 'widget', 'set-query') );
		if( is_admin() ){
			$this->get_includes( array( 'admin-tax-fields', 'callback-page' ) );
			new DTFilterFields();
			new admin_callback_page();
		}

		add_action( 'widgets_init',   array( 'TaxanomySeoFilterWidget', 'widget_init' ) );
		new SEO_Filter();
	}

	/**
	 * Global functions
	 */
	private function define_constants() {
		define( 'DTF_OPTION_NAME', 'filter-options');
		define( 'DTF_PLUGIN_URL', trailingslashit(plugins_url(basename( __DIR__ ))) );
		define( 'DTF_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
	}
	protected function get_current_admin_taxanomy(){
		if(isset($_GET['taxonomy']))
			return $_GET['taxonomy'];

		if(function_exists('get_current_screen')){
			$screen = get_current_screen();
			if( isset( $screen->taxonomy ) )
				return $screen->taxonomy;
		}
		
		return false;
	}
	function show_admin_notice(){
		if(sizeof($this->errors) == 0)
			return;

		foreach ($this->errors as $error) {
			$type = (isset($error['type'])) ? $error['type'] . ' ' : ' ';
			$msg = ($error['msg']) ? apply_filters('the_content', $error['msg']) : false;
			if($msg)
				echo '<div id="message" class="'.$type.'notice is-dismissible">'.$msg.'</div>';
			else
				echo '
			<div id="message" class="'.$type.'notice is-dismissible">
				<p>Обнаружена неизвестная ошибка!</p>
			</div>';
		}
	}
	protected function set_notice($msg=false, $type='error'){
		$this->errors[] = array('type' => $type, 'msg' => $msg);

		add_action( 'admin_notices', array($this, 'show_admin_notice') );
	}
	private function get_includes($paths){
		$ext = '.php';
		foreach ($paths as $path) {
			$file = DTF_PLUGIN_PATH . 'includes/' . $path . $ext;
			if( is_readable( $file ) ){
				require_once( $file );
			}
			else {
				$this->set_notice('Файл ('. $path . $ext .') поврежден.');
			}
		}
	}
}
new DTFilter();