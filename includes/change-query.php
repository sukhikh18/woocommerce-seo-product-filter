<?php
/**
* 
*/
class SEO_Filter extends GlobalClassName
{
	static public $taxquery = array();
	static public $seo_settings = array();

	function __construct()
	{
		add_action( 'pre_get_posts', array($this, 'set_taxquery') );
		add_action( 'pre_get_posts', array($this, 'set_seo_settings') );
		add_action( 'init', array($this, 'addRoutes') );
	}

	function get_all_attribs(){
		$attribs = get_object_taxonomies('product'); //, 'objects');
		$default_product_type = array(//'product_cat', 'product_tag'
			'product_type', 'product_shipping_class');
		$attribs = array_diff($attribs, $default_product_type);

		return $attribs;
	}

	/**
	 * Set tax
	 */
	function set_taxanomy($tax, $value){
		$value = is_array($value) ? $value : array($value);
		self::$taxquery[] = array(
			'taxonomy' => $tax,
			'field' => 'id',
			'terms' => $value
			);
	}
	function set_taxquery( $query ){
		if ( $query->is_main_query() ):
			if( $query->get( 'seo_filter' ) ){
				$tax = $query->get( 'f_tax' );
				if( $tax && $tax != 'product_cat' && $tax != 'product_tag' )
					$tax = 'pa_'.$tax;

				$this->set_taxanomy( $tax, $query->get( 'f_term' ) );
			}
			elseif(isset($_GET['filter']) && sizeof($_GET) >= 1){
				$attribs = $this->get_all_attribs();

				foreach ($_GET as $tax => $term_id_or_ids) {
					$tax = apply_filters( 'change_wc_product_taxs', $tax, true );

					if( in_array($tax, $attribs) && $tax != 'pa_filter' )
						$this->set_taxanomy($tax, $term_id_or_ids);
				}
			}

			$query->set( 'tax_query', self::$taxquery );
		endif;
	}
	function set_seo_settings( $query ){
		if( $query->is_main_query() ):
			$taxquery = self::$taxquery;

			if(sizeof($taxquery) != 1 || sizeof($taxquery[0]['terms']) != 1)
				return false;

			if(! $query->get('seo_filter') ){
				$tax = str_replace('pa_', '', $taxquery[0]['taxonomy']);
				wp_redirect( '/shop/filter/'.$tax.'/'.$taxquery[0]['terms'][0], 302 );
				exit();
			}

			$settings = self::$seo_settings = get_term_meta( (int)$taxquery[0]['terms'][0], 'seo-settings', true );

			if( isset( $settings['title']) )
				add_filter( 'wpseo_title', function(){
					return esc_attr( SEO_Filter::$seo_settings['title'] );
				}, 100 );

			if( isset( $settings['description']) )
				add_filter( 'wpseo_metadesc', function(){
					return esc_attr( SEO_Filter::$seo_settings['description'] );
				}, 100 );

			if( isset( $settings['keywords']) )
				add_filter( 'wpseo_metakey', function(){
					return esc_attr( SEO_Filter::$seo_settings['keywords'] );
				}, 100 );

			if( isset( $settings['h1']) )
				add_filter( 'woocommerce_page_title', function(){
					return esc_attr( SEO_Filter::$seo_settings['h1'] );
				}, 100);

			//if( isset( $settings['content']) )
			add_action( 'woocommerce_archive_description', function(){
				echo term_description( SEO_Filter::$taxquery[0]['terms'][0], SEO_Filter::$taxquery[0]['taxonomy'] );
			}, 100 );
		endif;
	}

	// seo urls
	function addRoutes() {
		add_rewrite_tag('%f_tax%',  '([^&]+)');
		add_rewrite_tag('%f_term%', '([^&]+)');
		add_rewrite_tag('%seo_filter%', '([^&]+)');

		$tax = 'id';
		$tax_reg = ($tax == 'id') ? '([0-9]{1,})' : '([a-z]+)';

		add_rewrite_rule('shop/filter/([a-z_1-9]+)/' . $tax_reg . '/?$', 'index.php?post_type=product&seo_filter=1&f_tax=$matches[1]&f_term=$matches[2]', 'top');

		add_rewrite_rule('shop/filter/([a-z_1-9]+)/' . $tax_reg . '/page/([0-9]{1,})/?$', 'index.php?post_type=product&seo_filter=1&f_tax=$matches[1]&f_term=$matches[2]&paged=$matches[3]', 'top');
	// shop/filter/tax/term/ wp_redirect( home_url() ); || 404
	}
}