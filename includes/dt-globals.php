<?php
namespace DTProjects;

if(! function_exists('is_wp_debug')){
	function is_wp_debug(){
		if( WP_DEBUG ){
			if( defined(WP_DEBUG_DISPLAY) && ! WP_DEBUG_DISPLAY){
				return false;
			}
			return true;
		}
		return false;
	}
}

if(! function_exists('form_render')){
	function form_render($render=false, $active=array(), $is_table=false, $item_wrap = array('<p>','</p>'), $form_wrap = array('','') ){

		if(empty($render)){
			echo 'Настроек не обнаружено.';
			return false;
		}

		if($is_table && $form_wrap[0] == '')
			$form_wrap = array('<table valign="top" class="table"><tbody>', '</tbody></table>');

		if(! $item_wrap )
			$item_wrap = array('', '');

		if( isset($render['type']) )
			$render = array($render);
		
		if($is_table)
			$form_wrap[0];
		
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
						if( is_array($active[$active_key]) ){
							if( in_array($input['value'], $active[$active_key]) )
								$checked = 'checked';
						}
						elseif ( $is_default ||  $active[$active_key] != ''){
							$checked = 'checked';
						}
					}
					
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
					
					if(! isset($input['value']))
						$input_html .= " value='on'>";
					else
						$input_html .= " value='{$input['value']}'>";

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

			if(!$is_table){
				echo $before . $item_wrap[0]. $input_html .$item_wrap[1] . $after;
			}
			else {
				echo "\n<tr id='{$input['id']}'><td class='name'>{$label}</td>";
				echo "<td>";
				echo $before . $item_wrap[0]. $input_html .$item_wrap[1] . $after;
				if($desc)
					echo "<div class='description'>{$desc}</div>";
				echo "</td></tr>";
			}
		} // endforeach

		if($is_table)
			echo $form_wrap[1];
	}
}