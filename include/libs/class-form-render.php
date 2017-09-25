<?php
// ver 2.3

namespace DTFilter;

function _isset_default(&$var, $default, $unset = false){
  $result = $var = isset($var) ? $var : $default;
  if($unset)
    $var = FALSE;
  return $result;
}

function _isset_false(&$var, $unset = false){
  return _isset_default( $var, false, $unset );
}

function _isset_empty(&$var, $unset = false){
  return _isset_default( $var, '', $unset );
}


if( ! has_filter( 'dt_admin_options' ) ){
  /**
   * change names for wordpress options
   * @param  array  $inputs      rendered inputs
   * @param  string $option_name name of wordpress option (@see get_option())
   * @return array               filtred inputs
   */
  function admin_page_options_filter( $inputs, $option_name = false ){
    if( ! $option_name )
      $option_name = _isset_false($_GET['page']);

    if( ! $option_name )
      return $inputs;

    if( isset($inputs['type']) )
        $inputs = array($inputs);

    foreach ( $inputs as &$input ) {
      if( ! isset($input['name']) )
        $input['name'] = "{$option_name}[{$input['id']}]";

      $input['check_active'] = 'id';
    }
    return $inputs;
  }
  add_filter( 'dt_admin_options', 'DTFilter\admin_page_options_filter', 10, 2 );
}

class DTForm
{
  private static function is_checked( $name, $value, $active, $default ){
    if($active === 'false' || $active === 'off')
      $active = false;

    if($active === 'true' || $active === 'on')
      $active = true;

    if( $active || $default ){
      if( $value ){ // str or bool
        if( is_array($active) ){
          if( in_array($value, $active) )
            return true;
        }
        else {
          if( $value == $active || $value === true )
            return true;
        }
      }
      else {
        if( $active || $default ){
          // if($active != 'false') // str or bool
            return true;
        }
      }
    }
    return false;
  }

  /**
   * Render form items
   * @param  boolean $render_data array with items ( id, name, type, options..)
   * @param  array   $active      selected options from form items
   * @param  boolean $is_table    is a table
   * @param  array   $args        array of args (item_wrap, form_wrap, label_tag, hide_desc) @see $default_args
   * @param  boolean $is_not_echo true = return, false = echo
   * @return html                 return or echo
   */
  public static function render(
    $render_data = false,
    $active = array(),
    $is_table = false,
    $args = array(),
    $is_not_echo = false){

    $html = $hidden = array();

    if( empty($render_data) ){
      if( function_exists('is_wp_debug') && is_wp_debug() )
        echo '<pre> Файл настроек не найден </pre>';
      return false;
    }
    
    if( isset($render_data['type']) )
        $render_data = array($render_data);

    $default_args = array(
      'item_wrap' => array('<p>', '</p>'),
      'form_wrap' => array('<table class="table form-table"><tbody>', '</tbody></table>'),
      'label_tag' => 'th',
      'hide_desc' => false
      );
    $args = array_merge($default_args, $args);

    if( $args['item_wrap'] === false )
      $args['item_wrap'] = array('', '');

    if($args['form_wrap'] === false)
      $args['form_wrap'] = array('', '');

    if( $args['label_tag'] == 'th' && $is_table == false ){
      $args['label_tag'] = 'label';
    }
    /**
     * Template start
     */
    if($is_table)
        $html[] = $args['form_wrap'][0];

    foreach ( $render_data as $input ) {
      $label   = _isset_false($input['label'], 1);
      $before  = _isset_empty($input['before'], 1);
      $after   = _isset_empty($input['after'], 1);
      $default = _isset_false($input['default'], 1);
      $value   = _isset_false($input['value']);
      $check_active = _isset_false($input['check_active'], 1);
      
      if( $input['type'] != 'checkbox' && $input['type'] != 'radio' )
        _isset_default( $input['placeholder'], $default );

      if( isset($input['desc']) ){
        $desc = $input['desc'];
        $input['desc'] = false;
      }
      elseif( isset( $input['description'] ) ) {
        $desc = $input['description'];
        $input['description'] = false;
      }
      else {
        $desc = false;
      }

      if( !isset($input['name']) )
          $input['name'] = _isset_empty($input['id']); //isset($input['id']) ? $input['id'] : '';
      
      /**
       * set values
       */
      $active_name = $check_active ? $input[$check_active] : str_replace('[]', '', $input['name']);
      $active_value = _isset_false($active[$active_name]);

      $entry = '';
      if($input['type'] == 'checkbox' || $input['type'] == 'radio'){
        $entry = self::is_checked( $active_name, $value, $active_value, $default );
      }
      elseif( $input['type'] == 'select' ){
        $entry = ($active_value) ? $active_value : $default;
      }
      else {
        // if text, textarea, number, email..
        $entry = $active_value;
        $placeholder = $default;
      }

      $func = 'render_' . $input['type'];
      $input_html = self::$func($input, $entry, $is_table, $label);

      if( $desc ){
        if( isset($args['hide_desc']) && $args['hide_desc'] === true )
          $desc_html = "<div class='description' style='display: none;'>{$desc}</div>";
        else
          $desc_html = "<div class='description'>{$desc}</div>";
      } else {
        $desc_html = '';
      }
      
      if(!$is_table){
        $html[] = $before . $args['item_wrap'][0] . $input_html . $args['item_wrap'][1] . $after . $desc_html;
      }
      elseif( $input['type'] == 'hidden' ){
        $hidden[] = $before . $input_html . $after;
      }
      else {
        $item = $before . $args['item_wrap'][0]. $input_html .$args['item_wrap'][1] . $after;

        $html[] = "<tr id='{$input['id']}'>";
        $html[] = "  <{$args['label_tag']} class='label'>{$label}</{$args['label_tag']}>";
        $html[] = "  <td>";
        $html[] = "    " .$item;
        $html[] = $desc_html;
        $html[] = "  </td>";
        $html[] = "</tr>";
      }
    } // endforeach
    if($is_table)
      $html[] = $args['form_wrap'][1];

    $result = implode("\n", $html) . "\n" . implode("\n", $hidden);
    if( $is_not_echo )
      return $result;
    else
      echo $result;
  }
  
  public static function render_checkbox( $input, $checked, $is_table, $label = '' ){
    $result = '';

    if( !isset($input['value']) || $input['value'] === false )
      $input['value'] = 'on';

    if( $checked )
      $input['checked'] = 'true';

    $hval = apply_filters( 'clear_checkbox_value', false );
    if( $hval !== false )
      $result .= "<input name='{$input['name']}' type='hidden' value='{$hval}'>\n";

    $result .= "<input";
    foreach ($input as $attr => $val) {
      if($val){
        $attr = esc_attr($attr);
        $val  = esc_attr($val);
        $result .= " {$attr}='{$val}'";
      }
    }
    $result .= ">";

    if(!$is_table && $label)
      $result .= "<label for='{$input['id']}'> {$label} </label>";

    return $result;
  }

  public static function render_select( $input, $active_id, $is_table, $label = '' ){
    $result = '';
    $options = _isset_false($input['options'], 1);
    if(! $options )
      return false;

    if(!$is_table && $label)
      $result .= "<label for='{$input['id']}'> {$label} </label>";

    $result .= "<select";
    foreach ($input as $attr => $val) {
      if( $val ){
        $attr = esc_attr($attr);
        $val  = esc_attr($val);
        $result .= " {$attr}='{$val}'";
      }
    }
    $result .= ">";
    foreach ($options as $value => $option) {
      $active_str = ($active_id == $value) ? " selected": "";
      $result .= "<option value='{$value}'{$active_str}>{$option}</option>";
    }
    $result .= "</select>";

    return $result;
  }

  public static function render_textarea( $input, $entry, $is_table, $label = '' ){
    $result = '';
    // set defaults
    _isset_default($input['rows'], 5);
    _isset_default($input['cols'], 40);

    if(!$is_table && $label)
      $result .= "<label for='{$input['id']}'> {$label} </label>";

    $result .= "<textarea";
    foreach ($input as $attr => $val) {
      if($val){
        $attr = esc_attr($attr);
        $val  = esc_attr($val);
        $result .= " {$attr}='{$val}'";
      }
    }
    $result .= ">{$entry}</textarea>";

    return $result;
  }

  public static function render_text( $input, $entry, $is_table, $label = '' ){
    $result = '';

    if(!$is_table && $label)
      $result .= "<label for='{$input['id']}'> {$label} </label>";
    if( $entry )
      $input['value'] = $entry;

    $result .= "<input";
    foreach ($input as $attr => $val) {
      if( $val ){
        $attr = esc_attr($attr);
        $val  = esc_attr($val);
        $result .= " {$attr}='{$val}'";
      }
    }
    $result .= ">";

    return $result;
  }

  public static function render_hidden( $input, $entry, $is_table, $label = '' ){

     return self::render_text($input, $entry, $is_table, $label);
  }

  public static function render_submit($input, $entry, $is_table, $label = ''){

    return self::render_text($input, $entry, $is_table, $label);
  }

  public static function render_button($input, $entry, $is_table, $label = ''){

    return self::render_text($input, $entry, $is_table, $label);
  }

  public static function render_number($input, $entry, $is_table, $label = ''){
    
    return self::render_text($input, $entry, $is_table, $label);
  }
  
  public static function render_email($input, $entry, $is_table, $label = ''){

    return self::render_text($input, $entry, $is_table, $label);
  }
}