<?php
/*
Plugin Name: СЕО оптимизированный Фильтр продуктов
Plugin URI: https://github.com/nikolays93/wp-wc-attr-filter
Description: New plug-in
Author: NikolayS93
Author URI: http://vk.com/nikolay_s93
Text Domain: new-plugin
Domain Path: /languages/
Version: 1.0b
*/

if ( ! defined( 'ABSPATH' ) )
  exit; // disable direct access

class Seo_Product_Filter {
    const OPTION = __CLASS__;
    const INCLUDE_DIR = 'include';

    private static $instance;
    private static $options;

    private function __clone(){}
    private function __wakeup(){}
    private function __construct()
    {
        self::define_constants();

        $includes = apply_filters( 'seo_product_filter_includes', array(
            'admin' => array( 'libs/class-admin-page', 'admin-tax-fields' ),
            'public' => array( 'libs/class-form-render', 'widget', 'set-query' ),
            ) );

        if( is_admin() && isset( $includes['admin'] ) ) {
            self::include_required_files( $includes['admin'] );
        }
        self::include_required_files( $includes['public'] );

        add_action( 'widgets_init',   array( 'Seo_Product_Filter_Widget', 'register_widget' ) );
        // new SEO_Filter();
    }

    public static function get_instance()
    {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get_options()
    {
        if( ! self::$options ) {
            self::$options = get_option( self::OPTION, array() );
        }

        return self::$options;
    }

    private static function define_constants()
    {
        define( 'DTF_PLUGIN_URL', trailingslashit(plugins_url(basename( __DIR__ ))) );
        define( 'DTF_PLUGIN_PATH', rtrim(plugin_dir_path( __FILE__ ), '/') );
    }

    private static function include_required_files($paths, $ext = '.php')
    {
        if( ! is_array($paths) ) {
            return false;
        }

        foreach ($paths as $path) {
            $file = DTF_PLUGIN_PATH . '/' . self::INCLUDE_DIR . '/' . $path . $ext;
            if( is_readable( $file ) ){
                require_once( $file );
            }
            else {
                error_log('Файл ('. $path . $ext .') поврежден.');
            }
        }
    }
}
add_filter( 'plugins_loaded', array('Seo_Product_Filter', 'get_instance') );

// Get Options
// $filter = Seo_Product_Filter::get_instance();
// $options = $filter::get_options();
