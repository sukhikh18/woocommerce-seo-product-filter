<?php

/*
 * Plugin Name: Woocommerce Filter
 * Plugin URI: https://github.com/nikolays93
 * Description: New SEO optimized Woocommerce product filter by Nikolay
 * Version: 0.2.0
 * Author: NikolayS93
 * Author URI: https://vk.com/nikolays_93
 * Author EMAIL: NikolayS93@ya.ru
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: _plugin
 * Domain Path: /languages/
 */

namespace NikolayS93\WcFilter;

use NikolayS93\WPAdminPage as Admin;

if ( !defined( 'ABSPATH' ) ) exit('You shall not pass');

if (version_compare(PHP_VERSION, '5.4') < 0) {
    throw new \Exception('Plugin requires PHP 5.4 or above');
}

require_once ABSPATH . "wp-admin/includes/plugin.php";
require_once __DIR__ . '/include/Creational/Singleton.php';

class Plugin
{
    use Creational\Singleton;

    protected $data;
    protected $options;

    /**
     * Get option name for a options in the Wordpress database
     */
    public static function get_option_name()
    {
        return apply_filters("get_{DOMAIN}_option_name", DOMAIN);
    }

    function __init()
    {
        $this->data = get_plugin_data(__FILE__);

        if( !defined(__NAMESPACE__ . '\DOMAIN') )
            define(__NAMESPACE__ . '\DOMAIN', $this->data['TextDomain']);

        if( !defined(__NAMESPACE__ . '\PLUGIN_DIR') )
            define(__NAMESPACE__ . '\PLUGIN_DIR', __DIR__);

        if( !defined(__NAMESPACE__ . '\TERM_META') )
            define(__NAMESPACE__ . '\TERM_META', __DIR__);

        load_plugin_textdomain( DOMAIN, false, basename(PLUGIN_DIR) . '/languages/' );

        $autoload = __DIR__ . '/vendor/autoload.php';
        if( file_exists($autoload) ) include $autoload;

        require PLUGIN_DIR . '/include/utils.php';
        require PLUGIN_DIR . '/include/class-plugin-queries.php';
        require PLUGIN_DIR . '/include/class-plugin-routes.php';
        require PLUGIN_DIR . '/include/class-plugin-seo-fields.php';
        require PLUGIN_DIR . '/include/class-plugin-widget.php';

        /**
         * ## @todo think about Plugin prefix ##
         */

        $PluginRoutes = PluginRoutes::getInstance();
        add_action( 'init', array($PluginRoutes, '__register') );


        $PluginQueries = PluginQueries::getInstance();
        add_action( 'pre_get_posts', array($PluginQueries, '__register') );

        // $PluginSeoFields = PluginSeoFields::getInstance();
        // add_action( 'pre_get_posts', array($PluginSeoFields, '__register') );

        add_action( 'widgets_init', array(__NAMESPACE__ . '\PluginWidget', '__register') );
    }

    static function activate() { add_option( self::get_option_name(), array() ); }
    static function uninstall() { delete_option( self::get_option_name() ); }

    // public static function _admin_assets()
    // {
    // }

    public function admin_menu_page()
    {
        $page = new Admin\Page(
            Utils::get_option_name(),
            __('New Plugin name Title', DOMAIN),
            array(
                'parent'      => false,
                'menu'        => __('Example', DOMAIN),
                // 'validate'    => array($this, 'validate_options'),
                'permissions' => 'manage_options',
                'columns'     => 2,
            )
        );

        // $page->set_assets( array(__CLASS__, '_admin_assets') );

        $page->set_content( function() {
            Utils::get_admin_template('menu-page.php', false, $inc = true);
        } );

        $page->add_section( new Admin\Section(
            'Section',
            __('Section'),
            function() {
                Utils::get_admin_template('section.php', false, $inc = true);
            }
        ) );

        $metabox1 = new Admin\Metabox(
            'metabox1',
            __('metabox1', DOMAIN),
            function() {
                Utils::get_admin_template('metabox1.php', false, $inc = true);
            },
            $position = 'side',
            $priority = 'high'
        );

        $page->add_metabox( $metabox1 );

        $metabox2 = new Admin\Metabox(
            'metabox2',
            __('metabox2', DOMAIN),
            function() {
                Utils::get_admin_template('metabox2.php', false, $inc = true);
            },
            $position = 'side',
            $priority = 'high'
        );

        $page->add_metabox( $metabox2 );
    }
}

add_action( 'plugins_loaded', array( __NAMESPACE__ . '\Plugin', 'getInstance' ), 10 );
// add_action( 'plugins_loaded', array( $Plugin, 'admin_menu_page' ), 10 );
// add_action( 'admin_init', 'seo_filter_taxanomy_actions' );

// register_activation_hook( __FILE__, array( __NAMESPACE__ . '\Plugin', 'activate' ) );
// register_uninstall_hook( __FILE__, array( __NAMESPACE__ . '\Plugin', 'uninstall' ) );
// register_deactivation_hook( __FILE__, array( __NAMESPACE__ . '\Plugin', 'deactivate' ) );




/**
 * Добавляет таксаномиям s на конце во избежание конфликтов с WP Seo от Yoast
 * @status disabled
 */
add_filter( 'parse_tax_name', __NAMESPACE__ . '\spf_parse_tax_name', 10, 2 );
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



function seo_filter_taxanomy_actions() {
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
        $active = get_term_meta( $_GET['tag_ID'], TERM_META, true );
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

    update_term_meta( $term_id, TERM_META, $result );
    //file_put_contents(__DIR__ . '/log.err', print_r($result, 1) );
}

function _filter_fields() {
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

add_action( 'NikolayS93\WcFilter\PluginWidget\get_attribute_values', function( $terms ) {
    $sorted_terms = array();

    foreach ($terms as $term)
    {
        echo "<pre>";
        var_dump( $term->parent );
        echo "</pre>";
        if( empty( $term->parent ) ) {
            $sorted_terms[] = $term;
        }
    }

    return $sorted_terms;
}, $priority = 10, $accepted_args = 1 );