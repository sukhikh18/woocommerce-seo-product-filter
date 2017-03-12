<?php
class DTFilterFields // extends DTFilter
{
	function __construct()
	{
		add_action( 'admin_init', array( $this, 'add_filter_fields' ) );
	}

	function _fields(){
		$fields = array(
			array(
				'id'  => 'title',
				'label' => 'Заголовок браузера',
				'type'  => 'text',
				),
			array(
				'id'  => 'description',
				'label' => 'Описание (тэг description)',
				'type'  => 'textarea'
				),
			array(
				'id'  => 'keywords',
				'label' => 'Ключевые слова',
				'type'  => 'textarea'
				),
			array(
				'id'  => 'h1',
				'label' => 'Заголовок H1',
				'type'  => 'text'
				),
			);

		return apply_filters( 'get_filter_fields', $fields );
	}

	function add_seo_filter_fields( $taxonomy, $is_table = false ) {
		$active = array();

		if( isset($_GET['tag_ID']) )
			$active = get_term_meta( $_GET['tag_ID'], DTF_OPTION_NAME, true );

		DTProjects\form_render($this->_fields(), $active, $is_table, array('<div class="form-field term-wrap">', '</div>'), false);
	}
	function add_table_seo_filter_fields( $taxanomy ){

		$this->add_seo_filter_fields( $taxanomy, true );
	}

	function add_filter_fields(){
		$default_product_type = array('product_type', 'product_shipping_class');
		// Созданные таксаномии "Атрибуты", "Категории товара", "Тэги товара".
		$tax_attributes = array_diff( get_object_taxonomies('product'), $default_product_type );
		
		if( isset($_GET['taxonomy']) && $tax = $_GET['taxonomy'] ){
			if(in_array($tax, $tax_attributes)){
				add_action( $tax.'_add_form_fields', array($this, 'add_seo_filter_fields'), 10, 2 );
				add_action( $tax.'_edit_form_fields', array($this, 'add_table_seo_filter_fields'), 10, 2 );
			}
			unset($tax);
		}

		foreach ($tax_attributes as $tax) {
			add_action( 'created_'.$tax, array($this, 'save_seo_filter_fields'), 10, 2 );
			add_action( 'edited_'.$tax,  array($this, 'save_seo_filter_fields'), 10, 2 );
		}
	}
	function save_seo_filter_fields( $term_id ){
		$result = array();
		// Проверяем на наличие заполненых атрибутов
		foreach ( array('title', 'description', 'keywords', 'h1', 'content') as $id ) {
			if( !empty($_POST[ $id ]) ){
				$result[$id] = $_POST[$id];
			}
		}
		add_term_meta( $term_id, DTF_OPTION_NAME, $result, true );
	}
}