<?php

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
        // array(
        //  'id'  => 'h1',
        //  'label' => 'Заголовок H1',
        //  'type'  => 'text'
        //  ),
        );

    return $fields;
}

add_action( 'admin_init', 'seo_filter_taxanomy_actions' );
function seo_filter_taxanomy_actions(){
    // Созданные таксаномии "Атрибуты", "Категории товара", "Тэги товара".
    $taxanomies = array_diff( get_object_taxonomies('product'),
        apply_filters( 'seo_filter_exclude_terms', array('product_type', 'product_shipping_class') )
        );

    if( isset($_GET['taxonomy']) && $tax = $_GET['taxonomy'] ) {
        if( in_array($tax, $taxanomies) ){
            add_action( $tax. '_add_form_fields', 'seo_filter_taxanomy_fields',       10, 2 );
            add_action( $tax.'_edit_form_fields', 'seo_filter_taxanomy_fields_table', 10, 2 );
        }
    }

    if( isset($_POST['taxonomy']) && $tax = $_POST['taxonomy'] ) {
        if( in_array($tax, $taxanomies) ){
            add_action( 'created_'.$tax, 'save_seo_filter_taxanomy_fields', 10, 2 );
            add_action( 'edited_'.$tax,  'save_seo_filter_taxanomy_fields', 10, 2 );
        }
    }
}

/**
 * Callbacks / Views
 */
function seo_filter_taxanomy_fields( $taxonomy, $is_table = false ) {
    $active = array();
    if( isset($_GET['tag_ID']) ) {
        $active = get_term_meta( $_GET['tag_ID'], SPF_META, true );
    }

    DTFilter\DTForm::render(
        apply_filters( 'filter_admin_form_fields', _filter_fields() ),
        $active,
        $is_table,
        array('form_wrap' => array('<table class="table form-table form-field"><tbody>', '</tbody></table>')),
        false);
}

function seo_filter_taxanomy_fields_table( $taxanomy ){

    seo_filter_taxanomy_fields( $taxanomy, true );
}

/**
 * Validate / Save
 */
function save_seo_filter_taxanomy_fields( $term_id ){
    // Проверяем на наличие заполненых атрибутов
    $result = array();
    foreach ( array('title', 'description', 'keywords', 'content') as $id ) { // , 'h1'
        if( ! empty($_POST[ $id ]) ) {
            $result[$id] = $_POST[$id];
        }
    }

    update_term_meta( $term_id, SPF_META, $result );
    //file_put_contents(__DIR__ . '/log.err', print_r($result, 1) );
}