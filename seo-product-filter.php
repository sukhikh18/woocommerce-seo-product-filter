<?php
/*
Plugin Name: СЕО оптимизированный Фильтр продуктов
Plugin URI: https://github.com/nikolays93/wp-wc-attr-filter
Description: New plug-in
Author: NikolayS93
Author URI: http://vk.com/nikolay_s93
Text Domain: new-plugin
Domain Path: /languages/
Version: 1.1b
*/

if ( ! defined( 'ABSPATH' ) )
  exit; // disable direct access

define('SEO_FILTER_MARK', 'seo_filter');
define('SPF_META', 'spf_term_meta');

// $plugin_url = trailingslashit(plugins_url(basename( __DIR__ )));
add_action( 'plugins_loaded', 'initialize_seo_product_filter_plugin' );
function initialize_seo_product_filter_plugin() {
    $path = rtrim(plugin_dir_path( __FILE__ ), '/');

    if( is_admin() ) {
        // require_once( $path . '/includes/libs/class-admin-page.php' );
        require_once( $path . '/includes/admin-taxanomy-fields.php' );
    }

    require_once( $path . '/includes/libs/class-form-render.php' );
    require_once( $path . '/includes/set-query.php' );
    require_once( $path . '/includes/widget.php' );

    add_action( 'init', array('Seo_Product_Filter_Query', 'add_routes') );

    add_action( 'pre_get_posts', array('Seo_Product_Filter_Query', 'set_tax_query') );
    add_action( 'pre_get_posts', array('Seo_Product_Filter_Query', '_redirect') );
    add_action( 'pre_get_posts', array('Seo_Product_Filter_Query', 'set_query') );
    add_action( 'pre_get_posts', array('Seo_Product_Filter_Query', 'set_seo_field_values') );

    add_action( 'widgets_init', array( 'Seo_Product_Filter_Widget', 'register_widget' ) );
}

/**
 * Добавляет таксаномиям s на конце во избежание конфликтов с WP Seo от Yoast
 */
add_filter( 'parse_tax_name', 'spf_parse_tax_name', 10, 2 );
function spf_parse_tax_name( $taxname, $is_return = false ) {
    $tax = strtolower($taxname);
    // if( strpos( $tax, 'pa_') === 0 ){
    //     if( $is_return ) {
    //         $tax = str_replace('pa_', '', $tax);
    //     }
    //     else {
    //         $tax = 'pa_' . $tax;
    //     }
    // }
    // else
    if( strpos( $tax, 'pa_') !== 0  ) {
        if( $is_return ) {
            $tax = substr($tax, 0, -1);
        }
        else {
            $tax .= 's';
        }
    }

    return $tax;
}
