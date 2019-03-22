<?php

namespace NikolayS93\WcFilter;

class PluginQueries
{
    use Creational\Singleton;

    // static private $seo_field_values = array();

    /**
     * @var array exclude items from query
     */
    public $default_product_type = array();

    /**
     * Real params from query
     */
    private $query_args = array();

    /**
     * WP_Query['tax_query'] args
     * @var array
     */
    private $tax_query = array();

    /**
     * Count of selected terms
     * @var integer
     */
    private $terms_count = 0;

    function __init()
    {
        $this->default_product_type = apply_filters('wc_fiter_default_product_type', array('product_type', 'product_shipping_class'));
        $this->query_args = wp_parse_args( $_GET, array(
            'filter' => false,
            'product_cat' => '',
        ) );
    }

    /**
     * @hooked pre_get_posts
     * @param  WP_Query $query Wordpress global prop
     */
    function __register( $query )
    {
        /**
         * For main query only
         */
        if ( !$query->is_main_query() || is_admin() ) return;

        $this->parse_query( $query );
        $this->maybeRedirect();

        /**
         * @todo check this!
         * Плагин фильтрует данные на странице товаров ( post_type_archive('product') ).
         * Если на этой странице выбран вывод категорий то,
         * если не следующая команда - фильтр будет показывать все категории,
         * а не отфильтрованный результат.
         */
        if( 1 <= $this->terms_count ) {
            add_filter( 'woocommerce_is_filtered', '__return_true' );
        }

        /**
         * Добавляем фильтр в WP_Query
         */
        $query->set( 'tax_query', $this->tax_query );
    }

    public static function get_first_taxonomy()
    {
        if( isset( $this->tax_query['OR'][0]['taxonomy'] ) ) {
            return $this->tax_query['OR'][0]['taxonomy'];
        }

        if( isset($this->tax_query[0]['taxonomy']) ) {
            return $this->tax_query[0]['taxonomy'];
        }

        return false;
    }

    /**
     * @return Int
     */
    public static function get_first_term_id()
    {
        if( isset( $this->tax_query['OR'][0]['terms'][0] ) ) {
            return (int) $this->tax_query['OR'][0]['terms'][0];
        }

        if( $this->tax_query[0]['terms'][0] ) {
            return (int) $this->tax_query[0]['terms'][0];
        }

        return 0;
    }

    function parse_query( \WP_Query $query )
    {
        /**
         * For main query only
         */
        if ( ! $query->is_main_query() || is_admin() ) {
            return;
        }

        /**
         * Set SEO URL for single term selected only with filter mark
         * @var String $current_tax_name get tax name from URL (query)
         */
        if( $current_tax_name = $query->get( PluginRoutes::TAX ) ) {

            $terms_id = explode(',', $query->get( PluginRoutes::TERM ));

            $this->tax_query = array(
                'taxonomy' => $current_tax_name,
                'field' => 'id',
                'terms' => $terms_id
            );

            $this->terms_count = 1;
        }

        /**
         * Common URL with filter param parse
         */
        elseif ( isset($_REQUEST[ 'filter' ]) && 1 < sizeof($_GET) ) {

            /**
             * @var array get all taxs (without defaults)
             */
            $tax_attributes = array_diff(get_object_taxonomies('product'), $this->default_product_type);

            /**
             * Check query
             */
            foreach ($_REQUEST as $tax_name_potential => $terms_id)
            {
                if( in_array($tax_name_potential, $tax_attributes) ) {
                    /**
                     * @var String
                     */
                    $current_tax_name = $tax_name_potential;

                    $tax_query = array(
                        'taxonomy' => $current_tax_name,
                        'field' => 'id',
                        'terms' => explode(',', $terms_id)
                    );

                    $relation = 'OR';
                    foreach (get_option( 'widget_seo_product_filter_widget', array() ) as $setting)
                    {
                        if( ! isset($setting['attribute_id']) ) continue;

                        if( $current_tax_name === $setting['attribute_id'] && isset($setting['relation']) ) {
                            $relation = $setting['relation'];
                            break;
                        }
                    }

                    if( 'AND' === $relation ) {
                        $this->tax_query['relation'] = 'AND';
                        $this->tax_query[] = $tax_query;
                    }
                    else {
                        $this->tax_query['OR']['relation'] = 'OR';
                        $this->tax_query['OR'][] = $tax_query;
                    }

                    $this->terms_count += count( $terms_id );
                }
            }
        }
    }

    function maybeRedirect()
    {
        /**
         * Is filter not selected (no has active filters)
         */
        if( !$this->query_args['filter'] ) return;

        $PluginRoutes = PluginRoutes::getInstance();
        $category_base = $PluginRoutes->category_base;

        /**
         * Redirect to SEO URL if is term is single
         */
        if( 1 === $this->terms_count ) {
            /**
             * @var String
             */
            $current_tax_name = $this->get_first_taxanomy();

            /**
             * @var $current_term_id = 1 === $this->terms_count ? String : Int
             */
            $current_term_id  = $this->get_first_term_id();

            if( $this->query_args['product_cat'] ) {
                wp_redirect('/' . $category_base .'/' . $this->query_args['product_cat'] . '/filter/' .  $current_tax_name . '/' . $current_term_id, 301 );
            }
            else {
                wp_redirect( '/' . $category_base . '/filter/' . $current_tax_name . '/' . $current_term_id, 301 );
            }

            exit();
        }

        /**
         * @todo Write docs
         */
        elseif( $this->query_args['product_cat'] && count( $_GET ) > 1 ) { // becouse has product_cat
            /**
             * @todo Write docs
             */
            $_QUERY_ARRAY = array();

            foreach ($_GET as $key => $value)
            {
                if( 'product_cat' !== $key ) {
                    if( is_array($value) ) {
                        foreach ($value as $val)
                        {
                            $_QUERY_ARRAY[] = $key . "[]=" . $val;
                        }
                    }
                    else {
                        $_QUERY_ARRAY[] = $key . "=" . $value;
                    }
                }
            }

            wp_redirect( '/'.$category_base.'/'.$this->query_args['product_cat'].'/?' . implode('&', $_QUERY_ARRAY), 301 );
            exit();
        }
    }
}
