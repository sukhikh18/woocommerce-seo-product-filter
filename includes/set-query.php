<?php

class Seo_Product_Filter_Query {
    const TAX = 'f_tax';
    const TERM = 'f_term';

    static public $shop_slug = 'shop';
    static private $tax_query = array();

    static public $terms_count = 0;

    private function __construct() {}

    /**
     * add seo url's rout rules
     */
    static function add_routes()
    {
        self::$shop_slug = get_post_field( 'post_name', wc_get_page_id('shop') );

        add_rewrite_tag('%'.self::TAX.'%',  '([^&]+)');
        add_rewrite_tag('%'.self::TERM.'%', '([^&]+)');

        // пока работаем только по ID
        $reg = '([0-9]{1,})';

        // shop/filter/tax/term/ to wp_redirect( home_url() || 404 );
        add_rewrite_rule( self::$shop_slug
            . '/filter/([a-z0-9-~+_.:%@$|*\'()\[\]\\x80-\\xff]+)/' . $reg . '/?$',
            'index.php?post_type=product&f_tax=$matches[1]&f_term=$matches[2]',
            'top' );

        add_rewrite_rule( self::$shop_slug
            . '/filter/([a-z0-9-~+_.:%@$|*\'()\[\]\\x80-\\xff]+)/' . $reg . '/page/([0-9]{1,})/?$',
            'index.php?post_type=product&f_tax=$matches[1]&f_term=$matches[2]&paged=$matches[3]',
            'top' );
    }

    /**
     * Перенаправление на ЧПУ.
     * Перед этой функцией set_tax_query собирает данные о запросе
     *
     * @param WP_Query $query глобальная переменная WordPress
     */
    static function _redirect( $query )
    {
        /**
         * Используем фильтр только для главного, публичного запроса
         */
        if( ! $query->is_main_query() || is_admin() ) {
            return;
        }

        /**
         * Если термин всего один и стоит маркер фильтра направляем на ЧПУ адрес
         */

        if( self::$terms_count === 1 && isset($_GET[ 'filter' ]) ) {
            $tax = apply_filters( 'parse_tax_name', self::$tax_query[0]['taxonomy'] ) . '/';
            wp_redirect( '/' . self::$shop_slug . '/filter/' . $tax . current(self::$tax_query[0]['terms']), 302 );
            exit();
        }
    }

    static function set_tax_query( $query )
    {
        /**
         * Используем фильтр только для главного, публичного запроса
         */
        if ( ! $query->is_main_query() || is_admin() ) {
            return;
        }

        /**
         * Если в фильтре только один термин WP должен перенаправить на ЧПУ
         * Из которого можно получить маркер фильтра
         * Если терминов несколько ищем маркер (о том что нужно фильтровать)
         * в GET запросе
         */
        if( $tax = $query->get( self::TAX ) ) {
            $term = $query->get( self::TERM );

            self::$terms_count = 1;
            self::set_terms_filter( apply_filters( 'parse_tax_name', $tax, 1 ), $term, true );
        }
        elseif( isset($_GET[ 'filter' ]) && sizeof($_GET) > 1 ) {
            $default_product_type = array('product_type', 'product_shipping_class');
            /**
             * @var $tax_attributes array все таксаномии товара кроме $default_product_type
             */
            $tax_attributes = array_diff(get_object_taxonomies('product'), $default_product_type);

            /**
             * Проверяем на наличие таксаномий в GET запросе
             */
            foreach ($_GET as $tax => $term_id_or_ids) {
                if( $tax === 'filter' ) continue;

                $tax = apply_filters( 'parse_tax_name', $tax, 1 );
                if( in_array($tax, $tax_attributes) ) {
                    self::set_terms_filter( $tax, $term_id_or_ids );
                }
            }
        }
    }

    private static function set_terms_filter($tax, $value, $just_one = false)
    {
        if( ! is_array($value) ) {
            $value = array($value);
        }

        $tax_query = array(
            'taxonomy' => $tax,
            'field' => 'id',
            'terms' => $value
            );

        if( $just_one === true ) {
            self::$tax_query = array( $tax_query );
            return;
        }

        $relation = 'OR';
        foreach (get_option( 'widget_seo_product_filter_widget', array() ) as $setting) {
            if( ! isset($setting['attribute_id']) )
                continue;

            if( $tax === $setting['attribute_id'] && isset($setting['relation']) ) {
                $relation = $setting['relation'];
                break;
            }
        }

        if( 'AND' === $relation ) {
            self::$tax_query['relation'] = 'AND';
            self::$tax_query[] = $tax_query;
        }
        else {
            self::$tax_query['OR']['relation'] = 'OR';
            self::$tax_query['OR'][] = $tax_query;
        }

        self::$terms_count += sizeof( $value );
        // echo "<pre>";
        // var_dump( self::$terms_count );
        // print_r(self::$tax_query);
        // echo "</pre><hr>";
    }

    /**
     * Добавляем фильтр в WP_Query
     *
     * @param WP_Query $query глобальная переменная WordPress
     */
    static function set_query( $query )
    {
        /**
         * Используем фильтр только для главного, публичного запроса
         */
        if ( $query->is_main_query() && ! is_admin() ){
            // echo "<pre>";
            // var_dump(self::$tax_query);
            // echo "</pre>";
            $query->set( 'tax_query', self::$tax_query );
        }
    }
}

class Seo_Product_Filter_Query2 {
    static private $seo_field_values = array();

    static function set_seo_field_values( $query )
    {
        if( ! self::is_single_term() ) return false;

        $settings = self::$seo_field_values = get_term_meta( (int)self::$tax_query[0]['terms'][0], DTF_OPTION_NAME, true );

        if( isset( $settings['title']) )
            add_filter( 'wpseo_title', function(){
                return esc_attr( SEO_Filter::$seo_field_values['title'] );
            }, 100 );

        if( isset( $settings['description']) )
            add_filter( 'wpseo_metadesc', function(){
                return esc_attr( SEO_Filter::$seo_field_values['description'] );
            }, 100 );

        if( isset( $settings['keywords']) )
            add_filter( 'wpseo_metakey', function(){
                return esc_attr( SEO_Filter::$seo_field_values['keywords'] );
            }, 100 );

        // if( isset( $settings['h1']) )
        //  add_filter( 'woocommerce_page_title', function(){
        //      return esc_attr( SEO_Filter::$seo_field_values['h1'] );
        //  }, 100);

        add_action( 'woocommerce_archive_description', array(__CLASS__, 'archive_description'), 100 );
    }

    static function archive_description()
    {
        echo term_description( (int) current( self::$tax_query[0]['terms'] ), self::$tax_query[0]['taxonomy'] );
    }
}
