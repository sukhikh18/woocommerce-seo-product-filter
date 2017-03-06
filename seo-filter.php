<?php
/*
Plugin Name: SEO FILTER
Plugin URI: 
Description: 
Author: NikolayS93
Author URI: http://vk.com/nikolay_s93
Text Domain: new-plugin
Domain Path: /languages/
Version: 0.0.1
*/

/**
* 			
*/
class ClassName //extends AnotherClass
{
	public $option_name = 'seo-settings';
	protected $values = false;

	function __construct(){
		$this->define_constants();
		$this->get_includes();

		if( isset($_GET['tag_ID']) )
			$this->values = get_term_meta($_GET['tag_ID'], $this->option_name, true );

		if(is_admin())
			add_action('admin_init', array($this, 'add_filter_fields') );

		if( isset($_GET) )
			new SEO_Filter();

		add_action( 'widgets_init', array($this, 'widget_init'));


	}

	protected function get_current_taxanomy(){
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
	private function define_constants() {
		define( 'DTF_PLUGIN_URL', trailingslashit(plugins_url(basename( __DIR__ ))) );
		define( 'DTF_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
	}
	private function get_includes(){
		$ext = '.php';
		$paths =  array('dt-globals', 'widget', 'change-query');
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

	function widget_init(){

		register_widget( 'TaxanomySeoFilter' );
	}

	// form templates
	private function filter_fields_template($type=false, $name=false, $label=false, $desc='', $value=false){
		if($name === false || $label == false)
			return;

		if($this->values){
			$val = explode('-', $name);
			$val = $val[1];
			$value = isset($this->values[$val]) ?  $this->values[$val] : '';
		}

		switch ($type) {
			case 'textarea':
				echo "
				<div class='form-field term-{$name}-wrap'>
					<label for='{$name}'>{$label}</label>
					<textarea name='{$name}' id='{$name}' rows='3' cols='40'>{$value}</textarea>
					{$desc}
				</div>
				";
				break;
			
			default:
				echo "
				<div class='form-field term-{$name}-wrap'>
					<label for='{$name}'>{$label}</label>
					<input name='{$name}' id='{$name}' type='text' value='{$value}' size='40'>
					{$desc}
				</div>
				";
				break;
		}
	}
	function add_seo_filter_fields($taxonomy) {
		$this->filter_fields_template( 'text', 'seo-title', _('Заголовок браузера') );
		$this->filter_fields_template( 'textarea', 'seo-description', _('Описание (тэг description)') );
		$this->filter_fields_template( 'textarea', 'seo-keywords', _('Ключевые слова') );
		$this->filter_fields_template( 'text', 'seo-h1', _('Заголовок H1') );
		$this->filter_fields_template( 'textarea', 'seo-content', _('SEO контент') );
	}
	function save_seo_filter_fields( $term_id ){
		$result = array();
		// Проверяем на наличие заполненых атрибутов
		foreach ( array('seo-title', 'seo-description',
			'seo-keywords', 'seo-h1', 'seo-content') as $id ) {
			if(!empty($_POST[ $id ])){
				$now_id = explode('-', $id);
				$now_id = $now_id[1];
				$result[$now_id] = $_POST[$id];
			}
		}
		// Сохраняем аттрибуты
		add_term_meta( $term_id, $this->option_name, $result, true );
		// file_put_contents( plugin_dir_path( __FILE__ ) . '/debug.log', print_r($_POST, 1) );
	}
	function add_filter_fields(){
		$default_product_type = array('product_type', 'product_cat', 'product_tag',	'product_shipping_class');
		// Созданные таксаномии "Атрибуты" (Без стандартных)
		$tax_attributes = array_diff(get_object_taxonomies('product'), $default_product_type);
		$tax = $this->get_current_taxanomy();

		if($tax){
			if(in_array($tax, $tax_attributes)){
				add_action( $tax.'_add_form_fields', array($this, 'add_seo_filter_fields'), 10, 2 );
				add_action( $tax.'_edit_form_fields', array($this, 'add_seo_filter_fields'), 10, 2 );
			}
		}

		foreach ($tax_attributes as $tax) {
			// При сохранении не удается определитьт $tax'аномию
			add_action( 'created_'.$tax, array($this, 'save_seo_filter_fields'), 10, 2 );
			add_action( 'edited_'.$tax,  array($this, 'save_seo_filter_fields'), 10, 2 );
		}
	}
}

new ClassName();
