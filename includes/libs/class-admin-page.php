<?php

namespace DTFilter;

class dtAdminPage
{
	public $page = '';
	public $screen = '';
	public $option_name = '';

	protected $args = array(
		'parent' => 'options-general.php',
		'title' => '',
		'menu' => 'New page',
		'permissions' => 'manage_options'
		);
	protected $page_content_cb = '';
	protected $page_valid_cb = '';

	function __construct( $page_slug, $args, $page_content_cb, $option_name = false, $valid_cb = false )
	{
		// slug required
		if( !$page_slug )
			wp_die( 'You have false slug in admin page class', 'Slug is false or empty' );

		$this->page = $page_slug;
		$this->args = array_merge( $this->args, $args );
		$this->page_content_cb = $page_content_cb;
		if( $option_name )
			$this->option_name = $option_name;
		else
			$this->option_name = $this->page;

		$this->page_valid_cb = ($valid_cb) ? $valid_cb : array($this, 'validate_options');

		add_action('admin_menu', array($this,'add_page'));
		add_action('admin_init', array($this,'register_option_page'));
	}

	function add_page(){
		$this->screen = add_submenu_page(
			$this->args['parent'],
			$this->args['title'],
			$this->args['menu'],
			$this->args['permissions'],
			$this->page,
			array($this,'render_page'), 10);

		add_action('load-'.$this->screen, array($this,'page_actions'),9);
		add_action('admin_footer-'.$this->screen, array($this,'footer_scripts'));
	}

	function page_actions(){
		do_action('add_meta_boxes_'.$this->screen, null);
		do_action('add_meta_boxes', $this->screen, null);

		$columns = apply_filters( $this->page . '_columns', 1 );
		add_screen_option('layout_columns', array('max' => $columns, 'default' => $columns) );

		// Enqueue WordPress' script for handling the metaboxes
		wp_enqueue_script('postbox');
	}

	function footer_scripts(){
		
		echo "<script> jQuery(document).ready(function($){ postboxes.add_postbox_toggles(pagenow); });</script>";
	}
	function register_option_page(){

		register_setting( $this->option_name, $this->option_name, $this->page_valid_cb );
	}
	function render_page(){
		?>

		<div class="wrap">

			<?php screen_icon(); ?>
			<h2> <?php echo esc_html($this->args['title']);?> </h2>
			
			<?php do_action( $this->page . '_after_title'); ?>

			<form id="ccpt" enctype="multipart/form-data" action="options.php" method="post">  
				<?php do_action( $this->page . '_before_form_inputs'); ?>
				<?php
				/* Used to save closed metaboxes and their order */
				wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
				wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>

				<div id="poststuff">

					<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>"> 

						<div id="post-body-content">
							<?php call_user_func($this->page_content_cb); ?>
						</div>    

						<div id="postbox-container-1" class="postbox-container">
							<?php
								do_meta_boxes('','side',null); 
								submit_button();
							?>
						</div>    

						<div id="postbox-container-2" class="postbox-container">
							<?php do_meta_boxes('','normal',null);  ?>
							<?php do_meta_boxes('','advanced',null); ?>
						</div>	     					
						
					</div> <!-- #post-body -->
				</div> <!-- #poststuff -->
				<?php
					// add hidden settings
					settings_fields( $this->option_name );
				?>
				<?php do_action( $this->page . '_after_form_inputs'); ?>
			</form>

		</div><!-- .wrap -->
		
		<div class="clear" style="clear: both;"></div>

		<?php do_action( $this->page . '_after_page_wrap'); ?>
		
		<?php
	}

	function validate_options($input){
		// file_put_contents( plugin_dir_path( __FILE__ ) .'/debug.log', print_r($_GET, 1) );

		return apply_filters( 'validate_admin_' . $this->page, $input );
	}
}