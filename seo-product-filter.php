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

    add_action( 'widgets_init', array( 'Seo_Product_Filter_Widget', 'register_widget' ) );
    new Seo_Product_Filter_Query();
}
