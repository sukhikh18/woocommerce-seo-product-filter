<?php

class Seo_Product_Filter_Query {

	const TAX = 'f_tax';
	const TERM = 'f_term';
	const MARK = 'seo_filter';

	static public  $shop_slug = 'shop';
	static private $taxquery = array();
	static private $taxquery_and = array();
	static private $seo_field_values = array();

	function __construct()
	{
		add_action( 'init', array(__CLASS__, 'add_routes') );

		add_action( 'pre_get_posts', array(__CLASS__, 'set_taxquery') );
		add_action( 'pre_get_posts', array(__CLASS__, '_redirect') );
		add_action( 'pre_get_posts', array(__CLASS__, 'set_query') );
		// add_action( 'pre_get_posts', array(__CLASS__, 'set_seo_field_values') );
	}

	/**
	 * add seo url's rout rules
	 */
	static function add_routes()
	{
		self::$shop_slug = get_post_field( 'post_name', wc_get_page_id('shop') );

		add_rewrite_tag('%'.self::TAX.'%',  '([^&]+)');
		add_rewrite_tag('%'.self::TERM.'%', '([^&]+)');
		add_rewrite_tag('%'.self::MARK.'%', '([^&]+)');

		// пока работаем только по ID
		$reg = '([0-9]{1,})';

		// shop/filter/tax/term/ to wp_redirect( home_url() || 404 );
		add_rewrite_rule( self::$shop_slug
			. '/filter/([a-z0-9-~+_.:%@$|*\'()\[\]\\x80-\\xff]+)/' . $reg . '/?$',
			'index.php?post_type=product&' .self::MARK. '=1&f_tax=$matches[1]&f_term=$matches[2]',
			'top' );

		add_rewrite_rule( self::$shop_slug
			. '/filter/([a-z0-9-~+_.:%@$|*\'()\[\]\\x80-\\xff]+)/' . $reg . '/page/([0-9]{1,})/?$',
			'index.php?post_type=product&' .self::MARK. '=1&f_tax=$matches[1]&f_term=$matches[2]&paged=$matches[3]',
			'top' );
	}

	public static function change_taxanomy_names( $tax, $is_return=false )
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

	public static function is_single_term()
	{
		if( isset(self::$taxquery[1]) || ! isset(self::$taxquery[0]['terms']) ) {
			return false;
		}

		if( isset(self::$taxquery_and[1]) || ! isset(self::$taxquery_and[0]['terms']) ) {
			return false;
		}

		$summary = sizeof(self::$taxquery[0]['terms']) + sizeof(self::$taxquery_and[0]['terms']);
		if( $summary > 1 ) {
			return false;
		}

		return true;
	}

	private static function set_terms_filter($tax, $value)
	{
		if( ! is_array($value) ) {
			$value = array($value);
		}

		$tax = self::change_taxanomy_names($tax, 1);

		$widget_settings = get_option( 'widget_seo_product_filter_widget', array() );
		$relation = 'OR';
		foreach ($widget_settings as $setting) {
			if( ! isset($setting['attribute_id']) )
				continue;

			if( $tax === $setting['attribute_id'] && isset($setting['relation']) ) {
				$relation = $setting['relation'];
				break;
			}
		}

		$tax_query = array(
			'taxonomy' => $tax,
			'field' => 'id',
			'terms' => $value
		);

		if( 'AND' === $relation ) {
			self::$taxquery_and['relation'] = $relation;
			self::$taxquery_and[] = $tax_query;
		}
		else {
			self::$taxquery['relation'] = $relation;
			self::$taxquery[] = $tax_query;
		}
	}

	static function set_taxquery( $query )
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
		if( $query->get( self::MARK ) ) {
			$tax = $query->get( self::TAX );
			$term = $query->get( self::TERM );

			self::set_terms_filter( $tax, $term );
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
				if( $tax === self::MARK ) continue;
				if( in_array($tax, $tax_attributes) && $tax = self::change_taxanomy_names( $tax, 1 ) ) {
					self::set_terms_filter( $tax, $term_id_or_ids );
				}
			}
		}
	}

	/**
	 * Перенаправление на ЧПУ
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
		 * change_taxanomy_names - добавляет таксаномиям s на конце
		 * во избежание конфликтов с WordPress Seo от Yoast
		 */
		if( self::is_single_term() && isset($_GET[ 'filter' ]) ) {
			$tax = self::change_taxanomy_names( self::$taxquery[0]['taxonomy'] ) . '/';
			wp_redirect( '/' . self::$shop_slug . '/filter/' . $tax . current(self::$taxquery[0]['terms']), 302 );
			exit();
		}
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
			$tax_query = self::$taxquery;
			if( sizeof(self::$taxquery_and) >= 1 ) {
				array_push(self::$taxquery_and, self::$taxquery);
				$tax_query = self::$taxquery_and;
			}

			$query->set( 'tax_query', $tax_query );
		}
	}

	static function set_seo_field_values( $query )
	{
		if( ! self::is_single_term() ) return false;

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

		add_action( 'woocommerce_archive_description', array(__CLASS__, 'archive_description'), 100 );
	}

	static function archive_description()
	{
		echo term_description( (int) current( self::$taxquery[0]['terms'] ), self::$taxquery[0]['taxonomy'] );
	}
}
