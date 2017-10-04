<?php

class Seo_Product_Filter_Query {

	static private $taxquery = array();
	static private $seo_field_values = array();

	function __construct()
	{
		add_action( 'init', array(__CLASS__, 'add_routes') );

		add_action( 'pre_get_posts', array(__CLASS__, 'create_filters') );
		add_action( 'pre_get_posts', array(__CLASS__, 'set_taxquery') );
		add_action( 'pre_get_posts', array(__CLASS__, '_redirect') );
		add_action( 'pre_get_posts', array(__CLASS__, 'set_query') );
		add_action( 'pre_get_posts', array(__CLASS__, 'set_seo_field_values') );
	}

	// seo urls
	public static function add_routes()
	{
		$shop_slug = get_post_field( 'post_name', wc_get_page_id('shop') );

		add_rewrite_tag('%f_tax%',  '([^&]+)');
		add_rewrite_tag('%f_term%', '([^&]+)');
		add_rewrite_tag('%seo_filter%', '([^&]+)');

		// пока работаем только по ID
		$tax = 'id';
		$tax_reg = ($tax === 'id') ? '([0-9]{1,})' : '([a-z]+)';

		// shop/filter/tax/term/ to wp_redirect( home_url() || 404 );
		add_rewrite_rule( $shop_slug . '/filter/([a-z0-9-~+_.:%@$|*\'()\[\]\\x80-\\xff]+)/' . $tax_reg . '/?$', 'index.php?post_type=product&seo_filter=1&f_tax=$matches[1]&f_term=$matches[2]', 'top' );

		add_rewrite_rule( $shop_slug . '/filter/([a-z0-9-~+_.:%@$|*\'()\[\]\\x80-\\xff]+)/' . $tax_reg . '/page/([0-9]{1,})/?$', 'index.php?post_type=product&seo_filter=1&f_tax=$matches[1]&f_term=$matches[2]&paged=$matches[3]', 'top' );
	}

	public static function _redirect( $query )
	{
		if( $query->is_main_query() && ! is_admin() ) {
			if( ! isset(self::$taxquery[1]) && isset(self::$taxquery[0]['terms']) && sizeof(self::$taxquery[0]['terms']) == 1 ) {
				if( ! $query->get('seo_filter') ) {
					$tax = apply_filters( 'change_wc_product_taxs', self::$taxquery[0]['taxonomy'] );
					wp_redirect( '/shop/filter/'. $tax .'/'.self::$taxquery[0]['terms'][0], 302 );
					exit();
				}
			}
		}
	}

	/**
	 * Filters
	 */
	static function create_filters( $query )
	{
		if( $query->is_main_query() ){
			add_filter( 'change_wc_product_taxs', array(__CLASS__, 'change_taxanomy_names'), 10, 2 );
		}
	}

	static function change_taxanomy_names( $tax, $is_return=false )
	{
		if( strpos( strtolower($tax), 'pa_') !== 0 ){
			if( $is_return ) {
				$tax = substr($tax, 0, -1);
			}
			else {
				$tax .= 's';
			}
		}

		return $tax;
	}

	/**
	 * Set tax
	 */
	static function set_terms_filter($tax, $value)
	{
		$value = is_array($value) ? $value : array($value);
		// todo: give choose
		self::$taxquery['relation'] = 'OR';
		self::$taxquery[] = array(
			'taxonomy' => $tax, //apply_filters( 'change_wc_product_taxs', , true ),
			'field' => 'id',
			'terms' => $value
			);
	}

	static function set_taxquery( $query )
	{
		if ( $query->is_main_query() ) :
			// if one term filtred
			if( $query->get('seo_filter') ) {
				if( $query->get('f_tax') && $query->get('f_term') ) {
					self::set_terms_filter( apply_filters( 'change_wc_product_taxs', $query->get('f_tax'), true ),
						$query->get('f_term') );
				}
			}
			elseif( isset($_GET['filter']) && sizeof($_GET) > 1 ) {
				// все атрибуты, категории и тэги
				$default_product_type = array('product_type', 'product_shipping_class');
				$tax_attributes = array_diff(get_object_taxonomies('product'), $default_product_type);

				foreach ($_GET as $tax => $term_id_or_ids) {
					$tax = apply_filters( 'change_wc_product_taxs', $tax, true );
					if( in_array($tax, $tax_attributes) ) { // $tax != 'pa_filter' &&
						self::set_terms_filter( $tax, $term_id_or_ids );
						// foreach ( $tax_attributes as $attr ) {
						// 	if( $tax = apply_filters( 'change_wc_product_taxs', $tax, true ) == $attr ){
						// 		var_dump($tax);
						// 		$this->set_terms_filter($tax, $term_id_or_ids);
						// 	}
						// }
					}
				}
			}
		endif;
	}

	static function set_query( $query )
	{
		if ( $query->is_main_query() ){
			$query->set( 'tax_query', self::$taxquery );
		}
	}

	static function set_seo_field_values(  $query )
	{
			if( !$query->is_main_query() || sizeof(self::$taxquery) != 1 || sizeof(self::$taxquery[0]['terms']) != 1 )
				return false;

			$settings = self::$seo_field_values = get_term_meta( (int)self::$taxquery[0]['terms'][0], DTF_OPTION_NAME, true );

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
			// 	add_filter( 'woocommerce_page_title', function(){
			// 		return esc_attr( SEO_Filter::$seo_field_values['h1'] );
			// 	}, 100);

			add_action( 'woocommerce_archive_description', function(){
				echo term_description(
					(int)SEO_Filter::$taxquery[0]['terms'][0],
					SEO_Filter::$taxquery[0]['taxonomy'] );
				}, 100 );
	}
}
