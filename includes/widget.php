<?php
class TaxanomySeoFilterWidget extends WP_Widget {

	public static $widget_id = 'TaxanomySeoFilterWidget';

	function __construct() {
		// Регистрация виджета в базе WP - WooCommerce Фильтр по атрибутам
		parent::__construct(self::$widget_id, 'Фильтр', array( 'description' => 'Показывает аттрибуты, которые позволяют выбирать из списков товары по атрибуту.' ));

		add_action( 'before_sidebar', array( $this, 'sidebar_wrapper_start' ), 20 );
		add_action( 'after_sidebar',  array( $this, 'sidebar_wrapper_end' ), 5 );
	}

	static function widget_init(){

		register_widget( self::$widget_id );
	}
	function sidebar_wrapper_start(){

		echo '<form action="'.get_permalink( wc_get_page_id('shop') ).'" method="get">';
	}
	function sidebar_wrapper_end(){

		echo '</form>';
	}

	// Widget FrontEnd
	public function widget( $args, $instance ) {
		// title, attribute_id, logical, type..
		extract($instance);
		$option = get_option( 'filter_settings', false );

		if( $widget == 'filter' ){
			// if not set - use default
			$type = isset($type) ? $type : 'checkbox';
			
			// set widget title
			$title = apply_filters( 'widget_title', $title );
			$title_html = ( $title ) ? $args['before_title'] . $title . $args['after_title'] : '';

			// empty bugfix
			if( $attribute_id == 'product_cat' || $attribute_id == 'product_tag' ){
				$tax_args = ( isset($option['show_hidden']) ) ? array('hide_empty' => false) : array();
				if( wp_count_terms( $attribute_id, $tax_args) < 1 )
					return false;
			}

			$result = (! isset($option['show_hidden']) ) ?
				self::get_attribute_values( $attribute_id, 'id', true ) :
				self::get_attribute_values( $attribute_id );
			
			// is not found
			if( sizeof( $result ) < 1 )
				return false;

			echo $args['before_widget'];
			echo $title_html;

			$filters = array();
			foreach ($result as $term) {
				$label = ( isset($option['show_count']) ) ? $term->name . ' (' .$term->count. ')' : $term->name;

				$name = apply_filters( 'change_wc_product_taxs', $attribute_id );
				$name .= '[]';

				$filters[] = array(
					'id'  => $term->slug,
					'name' =>  $name,
					'value' => $term->term_id,
					'label' => $label,
					'type'  => $type
					);
			}

			global $wp_query;

			if( $tax = apply_filters( 'change_wc_product_taxs', $wp_query->get('f_tax')) ){
				$active = array( $tax => (int)$wp_query->get('f_term') );
			}
			else {
				$active = $_GET;
			}

			// var_dump($active);
			DTFilter\DTForm::render($filters, $active);
		}
		else {
			echo $args['before_widget'];
			DTFilter\DTForm::render(array(
				array(
					'type'  => 'submit',
					'value' => 'Показать'
				),
				array(
					'type'  => 'hidden',
					'value' => '1',
					'name'  =>'filter'
					)
			));
		}
		echo $args['after_widget'];
	}
	
	public static function get_attribute_values( $taxonomy = '', $order_by = 'id', $hide_empty = false ) {
        if ( ! $taxonomy ) return array();
        $re = array();
        if( $hide_empty ) {
            global $wp_query, $post, $wp_the_query;
            $old_wp_the_query = $wp_the_query;
            $wp_the_query = $wp_query;
            if( method_exists('WC_Query', 'get_main_tax_query') && method_exists('WC_Query', 'get_main_tax_query') && 
            class_exists('WP_Meta_Query') && class_exists('WP_Tax_Query') ) {
                $args = array(
                    'orderby'    => $order_by,
                    'order'      => 'ASC',
                    'hide_empty' => false,
                );
                $re = get_terms( $taxonomy, $args );
                global $wpdb;
                $meta_query = WC_Query::get_main_meta_query();
                $args      = $wp_the_query->query_vars;
                $tax_query = array();
                if ( ! empty( $args['product_cat'] ) ) {
                    $tax_query[ 'product_cat' ] = array(
                        'taxonomy' => 'product_cat',
                        'terms'    => array( $args['product_cat'] ),
                        'field'    => 'slug',
                    );
                }

                $meta_query      = new WP_Meta_Query( $meta_query );
                $tax_query       = new WP_Tax_Query( $tax_query );
                $meta_query_sql  = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
                $tax_query_sql   = $tax_query->get_sql( $wpdb->posts, 'ID' );
                $term_ids = wp_list_pluck( $re, 'term_id' );

                // Generate query
                $query           = array();
                $query['select'] = "SELECT COUNT( DISTINCT {$wpdb->posts}.ID ) as term_count, terms.term_id as term_count_id";
                $query['from']   = "FROM {$wpdb->posts}";
                $query['join']   = "
                    INNER JOIN {$wpdb->term_relationships} AS term_relationships ON {$wpdb->posts}.ID = term_relationships.object_id
                    INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy USING( term_taxonomy_id )
                    INNER JOIN {$wpdb->terms} AS terms USING( term_id )
                    " . $tax_query_sql['join'] . $meta_query_sql['join'];
                $query['where']   = "
                    WHERE {$wpdb->posts}.post_type IN ( 'product' )
                    AND {$wpdb->posts}.post_status = 'publish'
                    " . $tax_query_sql['where'] . $meta_query_sql['where'] . "
                    AND terms.term_id IN (" . implode( ',', array_map( 'absint', $term_ids ) ) . ")
                ";
                $query['group_by'] = "GROUP BY terms.term_id";
                $query             = apply_filters( 'woocommerce_get_filtered_term_product_counts_query', $query );
                $query             = implode( ' ', $query );
                $results           = $wpdb->get_results( $query );
                $results           = wp_list_pluck( $results, 'term_count', 'term_count_id' );
                $term_count = array();
                $terms = array();
                foreach($re as &$res_count) {
                    if( ! empty($results[$res_count->term_id] ) ) {
                        $res_count->count = $results[$res_count->term_id];
                    } else {
                        $res_count->count = 0;
                    }
                    if( $res_count->count > 0 ) {
                        $terms[] = $res_count;
                    }
                }
                $re = $terms;
            } else {
                $terms = array();
                $q_args = $wp_query->query_vars;
                $q_args['posts_per_page'] = 2000;
                $q_args['post__in']       = '';
                $q_args['tax_query']      = '';
                $q_args['taxonomy']       = '';
                $q_args['term']           = '';
                $q_args['meta_query']     = '';
                $q_args['attribute']      = '';
                $q_args['title']          = '';
                $q_args['post_type']      = 'product';
                $q_args['fields']         = 'ids';
                $paged                    = 1;
                do{
                    $q_args['paged'] = $paged;
                    $the_query = new WP_Query($q_args);
                    if ( $the_query->have_posts() ) {
                        foreach ( $the_query->posts as $post_id ) {
                            $curent_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
                            foreach ( $curent_terms as $t ) {
                                if ( ! in_array( $t,$terms ) ) {
                                    $terms[] = $t;
                                }
                            }
                        }
                    }
                    $paged++;
                } while($paged <= $the_query->max_num_pages);
                unset( $q_args );
                unset( $the_query );
                wp_reset_query();
                $args = array(
                    'orderby'           => $order_by,
                    'order'             => 'ASC',
                    'hide_empty'        => false,
                );
                $terms2 = get_terms( $taxonomy, $args );
                foreach ( $terms2 as $t ) {
                    if ( in_array( $t->term_id, $terms ) ) {
                        $re[] = $t;
                    }
                }
            }
            $wp_the_query = $old_wp_the_query;
            return $re;
        } else {
            $args = array(
                'orderby'           => $order_by,
                'order'             => 'ASC',
                'hide_empty'        => false,
            );
            return get_terms( $taxonomy, $args );
        }
    }

	// Widget Backend
    function _widget_settings( $submit=false ){
		$tax_attributes = array();
		$form = array();
		// Созданные таксаномии "Атрибуты" (Без стандартных woocommerce)
		$attribs = get_object_taxonomies('product', 'objects');
		$default_product_type = array('product_type',
			//'product_cat', 'product_tag',
			'product_shipping_class');
		if(sizeof($attribs) != 0){
			foreach ($attribs as $attr) {
				$attr_name = $attr->name;
				if(! in_array($attr_name, $default_product_type) ){
					$tax_attributes[$attr_name] = $attr->label;
				}
			}
		}
		
		if(! $submit){
			$form = array(
				array(
					'label' => 'Заголовок',
					'data-title' => 'title',
					'id'    => $this->get_field_id( 'title' ),
					'name'  => $this->get_field_name( 'title' ),
					'type'  => 'text',
					'class' => 'widefat'
					),
				array(
					'label'   => 'Аттрибут',
					'data-title' => 'attribute_id',
					'id'      => $this->get_field_id( 'attribute_id' ),
					'name'    => $this->get_field_name( 'attribute_id' ),
					'type'    =>'select',
					'options' => $tax_attributes,
					'class'   =>'widefat'
					),
				// array(
				// 	'label'   => 'Логика',
				// 	'data-title' => 'logical',
				// 	'id'      => $this->get_field_id( 'logical' ),
				// 	'name'    => $this->get_field_name( 'logical' ),
				// 	'type'    =>'select',
				// 	'options' => array('or' => 'OR', 'and' => 'AND'),
				// 	'class'   =>'widefat'
				// 	),
				// array(
				// 	'label'   => 'Тип фильтра',
				// 	'data-title' => 'type',
				// 	'id'      => $this->get_field_id( 'type' ),
				// 	'name'    => $this->get_field_name( 'type' ),
				// 	'type'    =>'select',
				// 	'options' => array('select' => 'Выбор', 'checkbox' => 'Чекбокс', 'radio' => 'Радио-кнопки'),
				// 	'class'   =>'widefat'
				// 	),
				);
		} else {
			$form[] = array(
				'id'    => $this->get_field_id( 'title' ),
				'name'  => $this->get_field_name( 'title' ),
				'type'  => 'hidden',
				'class' => 'widefat',
				'value' => 'Кнопка "Показать"'
				);
		}

		$form[] = array(
			'label'   => 'Тип виджета',
			'data-title' => 'widget',
			'id'      => $this->get_field_id( 'widget' ),
			'name'    => $this->get_field_name( 'widget' ),
			'type'    =>'select',
			'options' => array('filter' => 'Фильтр', 'submit' => 'Кнопка фильтра продуктов'),
			'class'  =>'widefat',
			'before' => $submit ? '<strong>' : '<hr><strong>',
			'after'  => '</strong>'
			);

		return $form;
	}
	public function form( $instance ) {
		$form_instance = array();
		foreach ($instance as $key => $value) {
			$id = $this->get_field_name( $key );
			$form_instance[$id] = $value;
		}
		$submit = (end($form_instance) == 'submit') ? true : false;
		DTFilter\DTForm::render($this->_widget_settings($submit), $form_instance);
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		if($new_instance['widget'] != 'submit'){
			foreach ($this->_widget_settings() as $value) {
				$id = $value['data-title'];
				if( isset( $new_instance[$id] ) )
					$instance[$id] = $new_instance[$id];
			}
		} else {
			$instance['widget'] = 'submit';
		}

		// file_put_contents( DTF_PLUGIN_PATH . 'debug.log', print_r($new_instance, 1) );
		return $instance;
	}
}