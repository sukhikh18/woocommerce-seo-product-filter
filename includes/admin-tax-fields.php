<?php
namespace DTFilter;

add_filter( 'filter_admin_form_fields', 'DTFilter\_filter_fields' );

/**
 * Settings
 */
function _filter_fields(){
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

	return $fields;
}

/**
 * Callback
 */
function add_seo_filter_fields( $taxonomy, $is_table = false ) {
	$active = array();
	if( isset($_GET['tag_ID']) )
		$active = get_term_meta( $_GET['tag_ID'], DTF_OPTION_NAME, true );

	DTForm::render(
		apply_filters( 'filter_admin_form_fields', array() ),
		$active,
		$is_table,
		array('form_wrap' => array('<table class="table form-table form-field"><tbody>', '</tbody></table>')),
		false);
}
function add_table_seo_filter_fields( $taxanomy ){

	add_seo_filter_fields( $taxanomy, true );
}

/**
 * BackEnd
 */
function add_filter_fields(){
	// Созданные таксаномии "Атрибуты", "Категории товара", "Тэги товара".
	$default_product_type = array('product_type', 'product_shipping_class');
	$tax_attributes = array_diff( get_object_taxonomies('product'), $default_product_type );

	if( isset($_GET['taxonomy']) && $tax = $_GET['taxonomy'] ) {
		if( in_array($tax, $tax_attributes) ){
			add_action( $tax. '_add_form_fields', 'DTFilter\add_seo_filter_fields',       10, 2 );
			add_action( $tax.'_edit_form_fields', 'DTFilter\add_table_seo_filter_fields', 10, 2 );
		}
	}

	if( isset($_POST['taxonomy']) && $tax = $_POST['taxonomy'] ) {
		if( in_array($tax, $tax_attributes) ){
			add_action( 'created_'.$tax, 'DTFilter\save_seo_filter_fields', 10, 2 );
			add_action( 'edited_'.$tax,  'DTFilter\save_seo_filter_fields', 10, 2 );
		}
	}
}
add_action( 'admin_init', 'DTFilter\add_filter_fields' );

function save_seo_filter_fields( $term_id ){
	// Проверяем на наличие заполненых атрибутов
	$result = array();
	foreach ( array('title', 'description', 'keywords', 'h1', 'content') as $id ) {
		if( !empty($_POST[ $id ]) )
			$result[$id] = $_POST[$id];
	}

	$result = update_metadata( 'term', $term_id, DTF_OPTION_NAME, $result );

	//file_put_contents(__DIR__ . '/log.err', print_r($result, 1) );
}