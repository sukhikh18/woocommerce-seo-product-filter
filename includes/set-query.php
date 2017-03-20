<?php
/**
* 
*/
class SEO_Filter extends DTFilter
{
	static public $taxquery = array();
	static public $seo_field_values = array();

	function __construct()
	{
		add_action( 'init', array($this, 'addRoutes') );

		add_action( 'pre_get_posts', array($this, 'create_filters') );
		add_action( 'pre_get_posts', array($this, 'set_taxquery') );
		add_action( 'pre_get_posts', array($this, '_redirect') );
		add_action( 'pre_get_posts', array($this, 'set_query') );
		add_action( 'pre_get_posts', array($this, 'set_seo_field_values') );
	}

	function _redirect( $query ){
		if( $query->is_main_query() ){
			if( sizeof(self::$taxquery) == 1 && sizeof(self::$taxquery[0]['terms']) == 1 ){
				if(! $query->get('seo_filter')){
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
	function create_filters( $query ){
		if( $query->is_main_query() ){
			add_filter( 'change_wc_product_taxs', array($this, 'change_factory_tax'), 10, 2 );
			add_filter( 'change_wc_product_taxs', array($this, 'change_wc_attr_tax'), 10, 2 );
		}
	}
	function change_factory_tax( $tax, $is_return=false ){
		$cats = array( 'product_cats', 'product_cat' );
		$tags = array( 'product_tags', 'product_tag' );

		if( $is_return ){
			if( in_array($tax, $cats) )
				$tax = str_replace($cats[0], $cats[1], $tax);
			else
				$tax = str_replace($tags[0], $tags[1], $tax);
		}
		else {
			if( in_array($tax, $cats) )
				$tax = str_replace($cats[1], $cats[0], $tax);
			else
				$tax = str_replace($tags[1], $tags[0], $tax);
		}

		return $tax;
	}
	function change_wc_attr_tax( $tax, $is_return=false ){
		if( !$tax )
			return false;

		if(! in_array($tax, array('product_cat', 'product_tag', 'product_cats', 'product_tags')) ){
			if( $is_return && !preg_match("/^pa_/", $tax) )
				$tax = 'pa_' . $tax;
			elseif ( !$is_return && preg_match("/^pa_/", $tax) )
				$tax = str_replace( 'pa_', '', $tax );
		}

		//var_dump($tax);
		return $tax;
	}
	/**
	 * Set tax
	 */
	function set_terms_filter($tax, $value){
		$value = is_array($value) ? $value : array($value);
		// todo: give choose
		self::$taxquery['relation'] = 'OR';
		self::$taxquery[] = array(
			'taxonomy' => $tax, //apply_filters( 'change_wc_product_taxs', , true ),
			'field' => 'id',
			'terms' => $value
			);
	}
	function set_taxquery( $query ){
		if ( $query->is_main_query() ):
			// if one term filtred
			if( $query->get('seo_filter') ){
				if( $query->get('f_tax') && $query->get('f_term') )
					$this->set_terms_filter( apply_filters( 'change_wc_product_taxs', $query->get('f_tax'), true ),
						$query->get('f_term') );
			}
			elseif( isset($_GET['filter']) && sizeof($_GET) > 1 ){
				// все атрибуты, категории и тэги
				$default_product_type = array('product_type', 'product_shipping_class');
				$tax_attributes = array_diff(get_object_taxonomies('product'), $default_product_type);

				foreach ($_GET as $tax => $term_id_or_ids) {
					$tax = apply_filters( 'change_wc_product_taxs', $tax, true );
					//var_dump($tax);
					if( in_array($tax, $tax_attributes) ){ // $tax != 'pa_filter' &&
						$this->set_terms_filter($tax, $term_id_or_ids);
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

	function set_query( $query ){
		if ( $query->is_main_query() ){
			// _dump(self::$taxquery);
			$query->set( 'tax_query', self::$taxquery );
		}
	}

	function set_seo_field_values(  $query ){
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

			if( isset( $settings['h1']) )
				add_filter( 'woocommerce_page_title', function(){
					return esc_attr( SEO_Filter::$seo_field_values['h1'] );
				}, 100);

			add_action( 'woocommerce_archive_description', function(){
				echo term_description(
					(int)SEO_Filter::$taxquery[0]['terms'][0],
					SEO_Filter::$taxquery[0]['taxonomy'] );
				}, 100 );
	}

	// seo urls
	function addRoutes() {
		$shop_slug = get_post_field( 'post_name', woocommerce_get_page_id('shop') );

		add_rewrite_tag('%f_tax%',  '([^&]+)');
		add_rewrite_tag('%f_term%', '([^&]+)');
		add_rewrite_tag('%seo_filter%', '([^&]+)');

		$tax = 'id';
		$tax_reg = ($tax == 'id') ? '([0-9]{1,})' : '([a-z]+)';

		// shop/filter/tax/term/ to wp_redirect( home_url() || 404 );
		add_rewrite_rule( $shop_slug . '/filter/([a-z_1-9]+)/' . $tax_reg . '/?$', 'index.php?post_type=product&seo_filter=1&f_tax=$matches[1]&f_term=$matches[2]', 'top' );

		add_rewrite_rule( $shop_slug . '/filter/([a-z_1-9]+)/' . $tax_reg . '/page/([0-9]{1,})/?$', 'index.php?post_type=product&seo_filter=1&f_tax=$matches[1]&f_term=$matches[2]&paged=$matches[3]', 'top' );
	}
}