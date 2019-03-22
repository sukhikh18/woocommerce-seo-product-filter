<?php

namespace NikolayS93\WcFilter;

/**
 * add seo url's rout rules
 *
 * {yourdomain.com}/{$shop_slug}/filter/{taxanomy}/{term_id}/
 * {yourdomain.com}/{$shop_slug}/filter/{taxanomy}/{term_id}/page/{pagenum}/
 * {yourdomain.com}/{self::$category_base}/{category-slug}/filter/{taxanomy}/{term_id}/
 * {yourdomain.com}/{self::$category_base}/{category-slug}/filter/{taxanomy}/{term_id}/page/{pagenum}/
 */
class PluginRoutes
{
    use Creational\Singleton;

    const TAX = 'f_tax';
    const TERM = 'f_term';

    /**
     * @var WP_Post Woocommerce shop page (products list)
     */
    private $shop_page;

    /**
     * @var String Primary cat taxonomy name
     */
    private $category_base;

    /**
     * @todo write flush routes method
     */
    function flush()
    {
    }

    function __init()
    {
        $this->shop_page = get_post( wc_get_page_id( 'shop' ) );
        $this->category_base = get_option( 'category_base', 'product-cat' );
    }

    function __register()
    {
        add_rewrite_tag('%'.self::TAX.'%',  '([^&]+)');
        add_rewrite_tag('%'.self::TERM.'%', '([^&]+)');

        $int = '([0-9]{1,})';
        add_rewrite_rule( $this->shop_page->post_name . '/filter/(.+?)/'.$int.'/?$',
            'index.php?post_type=product&f_tax=$matches[1]&f_term=$matches[2]',
            'top' );

        // Paged
        add_rewrite_rule( $this->shop_page->post_name . '/filter/(.+?)/'.$int.'/page/'.$int.'/?$',
            'index.php?post_type=product&f_tax=$matches[1]&f_term=$matches[2]&paged=$matches[3]',
            'top' );

        add_rewrite_rule( $this->category_base . '/(.+?)/filter/(.+?)/'.$int.'/?$',
            'index.php?product_cat=$matches[1]&f_tax=$matches[2]&f_term=$matches[3]',
            'top' );

        // Paged
        add_rewrite_rule( $this->category_base . '/(.+?)/filter/(.+?)/'.$int.'/page/'.$int.'/?$',
            'index.php?product_cat=$matches[1]&f_tax=$matches[2]&f_term=$matches[3]&paged=$matches[4]',
            'top' );
    }
}
