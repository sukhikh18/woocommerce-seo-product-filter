<?php
namespace DTProjects;

if(! function_exists('admin_page_render')){
  function admin_page_render($field, $page_name, $active){
    $field['name'] = $page_name . "[" . $field['id'] . "]";

    if( isset($field['desc']) ){
      $field['label'] = $field['desc'];
      unset( $field['desc'] );
    } else {
      unset( $field['label'] );
    }

    if( isset($active[$field['id']]) ){
      if($field['type'] == 'checkbox')
        $field['checked'] = 'checked';
      $field['value'] = $active[$field['id']];
    }

    return $field;
  }
  add_filter( 'dt_admin_page_render', 'DTProjects\admin_page_render', 10, 3 );
}

if(! function_exists('form_render')){
  function form_render(
    $render=false,
    $active=array(),
    $is_table=false,
    $item_wrap = array('<p>', '</p>'),
    $form_wrap = array('<table class="table form-table"><tbody>', '</tbody></table>', 'th'),
    $is_not_echo = false){
    $html = array();
    if( empty($render) )
      return false;

    if(! isset($item_wrap[1]) )
      $item_wrap = array('', '');
    
    if(! isset($form_wrap[1]) )
      $form_wrap = array('', '', 'th');

    if( isset($render['type']) )
      $render = array($render);
    
    if($is_table)
      $html[] = $form_wrap[0];
    
    foreach ($render as $input) {
      $input_html = $entry = '';

      $label  = (isset($input['label'])) ? _($input['label']) : false;
      $desc   = (isset($input['desc'])) ? _($input['desc']) : false;
      $before = (isset($input['before'])) ? $input['before'] : '';
      $after  = (isset($input['after'])) ? $input['after'] : '';
      unset($input['label']);
      unset($input['desc']);
      unset($input['before']);
      unset($input['after']);

      if( !isset($input['name']) )
        $input['name'] = isset($input['id']) ? $input['id'] : '';

      $is_default = isset($input['default']) ? true : false;
      switch ($input['type']) {
        case 'checkbox':
          $active_key = str_replace('[]', '', $input['name']);
          $checked = '';

          if(isset($active[$active_key])){
            if( isset($input['value']) ){
              if( is_array($active[$active_key]) ){
                if( in_array($input['value'], $active[$active_key]) )
                  $checked = 'checked';
              }
              else {
                if( $input['value'] == $active[$active_key] )
                  $checked = 'checked';
              }
            }
            else {
              if($is_default || $active[$active_key] != '')
                $checked = 'checked';
            }
          }
          if(! isset($input['value']) )
            $input['value'] = 'on';

          // ["product_cats"]=> 8

          unset($input['default']);

          if(isset( $input['data-clear']) && $input['data-clear'] == 'true' )
            $input_html .= "
            <input name='{$input['name']}' type='hidden' value=''>";

          $input_html .= "
          <input {$checked}";
          foreach ($input as $attr => $val) {
            $attr = esc_attr($attr);
            $val  = esc_attr($val);
            $input_html .= " {$attr}='{$val}'";
          }
          $input_html .= ">";

          if(!$is_table && $label)
            $input_html .= "<label for='{$input['id']}'> {$label} </label>";
        break;

        case 'select':
          $options = $input['options'];
          if( isset($active[$input['name']]) ){
            $entry = $active[$input['name']];
          }
          elseif($is_default){
            $entry = $input['default'];
            unset($input['default']);
          }
          unset($input['options']);

          if(!$is_table && $label)
            $input_html .= "<label for='{$input['id']}'> {$label} </label>";
          
          $input_html .= "<select";
          foreach ($input as $attr => $val) {
            $attr = esc_attr($attr);
            $val  = esc_attr($val);
            $input_html .= " {$attr}='{$val}'";
          }
          $input_html .= ">";
          foreach ($options as $value => $option) {
            $active_str = ($entry == $value) ? " selected": "";
            $input_html .= "<option value='{$value}'{$active_str}>{$option}</option>";
          }
          $input_html .= "</select>";
        break;

        case 'textarea':
          if( isset($active[$input['name']]) ){
            $entry = $active[$input['name']];
          }
          elseif($is_default){
            $input['placeholder'] = $input['default'];
            unset($input['default']);
          }

          // set defaults
          if(!isset($input['rows'])) $input['rows'] = 5;
          if(!isset($input['cols'])) $input['cols'] = 40;

          if(!$is_table && $label)
            $input_html .= "<label for='{$input['id']}'> {$label} </label>";

          $input_html .= "<textarea";
          foreach ($input as $attr => $val) {
            $attr = esc_attr($attr);
            $val  = esc_attr($val);
            $input_html .= " {$attr}='{$val}'";
          }
          $input_html .= " >{$entry}</textarea>";
        break;

        default:
          if( isset($active[$input['name']]) ){
            $entry = $active[$input['name']];
          }
          elseif($is_default){
            $input['placeholder'] = $input['default'];
            unset($input['default']);
          }

          if(!$is_table && $label)
            $input_html .= "<label for='{$input['id']}'> {$label} </label>";

          $input_html .= "<input";
          foreach ($input as $attr => $val) {
            $attr = esc_attr($attr);
            $val  = esc_attr($val);
            $input_html .= " {$attr}='{$val}'";
          }
          $input_html .= " value='{$entry}'>";
        break;
      }

      $item = $before . $item_wrap[0]. $input_html .$item_wrap[1] . $after;
      if(!$is_table){
        $html[] = $item;
      }
      else {
        $col = $form_wrap[2];
        $html[] = "<tr id='{$input['id']}'>";
        $html[] = "  <$col class='name'>{$label}</$col>";
        $html[] = "  <td>";
        $html[] = "    " .$item;
        if($desc)
          $html[] = "    <div class='description'>{$desc}</div>";
        $html[] = "  </td>";
        $html[] = "</tr>";
      }
    } // endforeach

    if($is_table)
      $html[] = $form_wrap[1];

    $result = implode("\n", $html);
    if( $is_not_echo )
      return $result;
    else
      echo $result;
  }
}