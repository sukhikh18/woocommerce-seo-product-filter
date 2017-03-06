<?php
/**
* 
*/
class SEO_Filter //extends AnotherClass
{
	static public $taxquery = array();
	static public $seo_settings = array();

	function __construct()
	{
		$this->create_filters();
		add_action( 'pre_get_posts', array($this, 'set_taxquery') );
		add_action( 'pre_get_posts', array($this, 'set_seo_settings') );
		
	}

	/**
	 * Filters
	 */
	function create_filters(){
		add_filter( 'change_wc_product_taxs', array($this, 'change_factory_tax'), 10, 2 );
		add_filter( 'change_wc_product_taxs', array($this, 'change_wc_attr_tax'), 10, 2 );
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
		if(! in_array($tax, array('product_cat', 'product_tag')) )  
			$tax = ( $is_return === true ) ? 'pa_' . $tax : str_replace( 'pa_', '', $tax );

		return $tax;
	}

	/**
	 * Set tax
	 */
	function get_all_attribs(){
		$attribs = get_object_taxonomies('product'); //, 'objects');
		$default_product_type = array(//'product_cat', 'product_tag'
			'product_type', 'product_shipping_class');
		$attribs = array_diff($attribs, $default_product_type);

		return $attribs;
	}
	function set_taxanomy($tax, $value){
		self::$taxquery[] = array(
			'taxonomy' => $tax,
			'field' => 'id',
			'terms' => is_array($value) ? $value : array($value)
			);
	}
	function set_taxquery( $query ){
		if ( !$query->is_main_query() && !isset($_GET['set_filter']) && sizeof($_GET) <= 1 )
			return false;

		$attribs = $this->get_all_attribs();
		foreach ($_GET as $tax => $term_id_or_ids) {
			$tax = apply_filters( 'change_wc_product_taxs', $tax, true );

			if( in_array($tax, $attribs) && $tax != 'pa_set_filter' )
				$this->set_taxanomy($tax, $term_id_or_ids);
		}

		$query->set( 'tax_query', self::$taxquery );
	}

	function set_seo_settings(){
		$taxquery = self::$taxquery;
		if(sizeof($taxquery) != 1)
			return false;

		if( sizeof($taxquery[0]['terms']) == 1 ){
			$settings = self::$seo_settings = get_term_meta( (int)$taxquery[0]['terms'][0], 'seo-settings', true );

			if( isset( $settings['title']) )
				add_filter( 'wpseo_title', array($this, 'set_seo_title'), 100 );

			if( isset( $settings['description']) )
				add_filter( 'wpseo_metadesc', array($this, 'set_seo_description'), 100 );

			if( isset( $settings['keywords']) )
				add_filter( 'wpseo_metakey', array($this, 'set_seo_keywords'), 100 );

			if( isset( $settings['h1']) )
				add_filter( 'woocommerce_page_title', array($this, 'set_seo_h1_title'), 100);

			if( isset( $settings['content']) )
				add_action( 'woocommerce_archive_description', array($this, 'set_seo_content'), 100 );
		}
	}

	function set_seo_title($title){

		return esc_attr( self::$seo_settings['title'] );
	}
	function set_seo_description($desc){

		return esc_attr( self::$seo_settings['description'] );
	}
	function set_seo_keywords($keywords){

		return esc_attr( self::$seo_settings['keywords'] );
	}
	function set_seo_h1_title($h1){

		return esc_attr( self::$seo_settings['h1'] );
	}
	function set_seo_content($content){

		echo apply_filters( 'the_content', self::$seo_settings['content'] );
	}

}

// && ($taxquery[0]['taxonomy'] == 'product_cat' || $taxquery[0]['taxonomy'] == 'product_tag')
// wp_redirect( get_term_link( (int)$taxquery[0]['terms'][0], $taxquery[0]['taxonomy'] ) );