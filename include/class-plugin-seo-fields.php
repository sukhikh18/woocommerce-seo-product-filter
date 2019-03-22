<?php

namespace NikolayS93\WcFilter;

class PluginSeoFields
{

    static private $seo_field_values = array();

    static function set_seo_field_values( $query )
    {
        // if( self::$terms_count !== 1 ) return false;

        // $settings = self::$seo_field_values = get_term_meta( self::get_first_term_id(), SPF_META, true );

        // if( isset( $settings['title']) )
        //     add_filter( 'wpseo_title', function(){
        //         return esc_attr( Seo_Product_Filter_Query::$seo_field_values['title'] );
        //     }, 100 );

        // if( isset( $settings['description']) )
        //     add_filter( 'wpseo_metadesc', function(){
        //         return esc_attr( Seo_Product_Filter_Query::$seo_field_values['description'] );
        //     }, 100 );

        // if( isset( $settings['keywords']) )
        //     add_filter( 'wpseo_metakey', function(){
        //         return esc_attr( Seo_Product_Filter_Query::$seo_field_values['keywords'] );
        //     }, 100 );

        // // if( isset( $settings['h1']) )
        // //  add_filter( 'woocommerce_page_title', function(){
        // //      return esc_attr( Seo_Product_Filter_Query::$seo_field_values['h1'] );
        // //  }, 100);

        // add_action( 'woocommerce_archive_description', array(__CLASS__, '_archive_description'), 100 );
    }

    static function _archive_description()
    {
        // echo term_description( (int) current( self::$tax_query[0]['terms'] ), self::$tax_query[0]['taxonomy'] );
    }
}