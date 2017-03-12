<?php
class admin_callback_page// extends AnotherClass
{
  public $pagename = 'filter_settings';
  protected $fields;

  function __construct()
  {
    $this->set_fields();
    add_action( 'admin_menu', array($this, 'add_admin_page'), 60 );
    add_action( 'admin_init', array($this, 'register_settings') );
  }

  private function set_fields(){
    $this->fields = array(
      array(
        'type'    => 'checkbox',
        'label'   => 'Показывать кол-во товаров',
        'data-title' => 'show_count',
        'id'      => 'show_count',
        ),
      array(
        'type'    => 'checkbox',
        'label'   => 'Показывать пустые аттрибуты',
        'data-title' => 'show_hidden',
        'id'      => 'show_hidden',
        )
      );
  }

  function add_admin_page() {
    $pagename = $this->pagename;
    add_submenu_page( 'woocommerce', 'Настройки фильтра таксаномий', 'Настройки фильтра', 'manage_options', $pagename, array($this, 'admin_page') );
  }

  function admin_page() {
    echo '<div class="wrap">';
    echo '<form method="post" enctype="multipart/form-data" action="options.php">';
    echo '<h2>'.get_admin_page_title().'</h2>';

    $active = get_option( $this->pagename );
    // DTProjects\form_render( $this->fields, $active, true, false );
    
    settings_fields( $this->pagename );
    do_settings_sections( $this->pagename );
    echo get_submit_button( 'Сохранить' );

    echo "</form>";
    echo "</div>";
  }

  function register_settings() {
    register_setting( $this->pagename, $this->pagename, array($this, 'validate_settings') );
    add_settings_section( 'section_'.$this->pagename, '', '', $this->pagename );
    
    $active = get_option( $this->pagename );
    foreach ( $this->fields as $field ) {
      $render = apply_filters( 'dt_admin_page_render', $field, $this->pagename, $active );

      add_settings_field( $field['id'], $field['label'], 'DTProjects\form_render', $this->pagename, 'section_'.$this->pagename, array( 'render' => $render ) );
    }
  }

  function validate_settings($input) {
    // file_put_contents(DTF_PLUGIN_PATH . "debug.log", print_r($input, 1) );
    foreach($input as $k => $v) {
      if($v)
        $valid_input[$k] = $v;
    }
    return $valid_input;
  }
}