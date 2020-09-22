<?php
/**
 * @file
 * Contains \Drupal\stage2_admin\Element\ColorBrewerElement.
 */

namespace Drupal\stage2_admin\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;

/**
 * Provides an example element.
 *
 * @FormElement("color_brewer_element")
 */
class ColorBrewerElement extends FormElement {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderMyColorBrewerElement'],
      ],
	  '#process' => [
			[$class, 'processColorBrewerElement']
		],
		'#attached' => [
			'library' => [
			  'stage2_admin/jsColorBrewer',
			  'stage2_admin/jsColorBrewerPicker',
			  'stage2_admin/jsColorBrewerPickerInternal'
			],
		],
	];
  }

  /**
   * Prepare the render array for the template.
   */
  public static function preRenderMyColorBrewerElement($element) {

	// jstree_element
	$element['color_brewer'] = [
		'#type' => 'hidden',
		'#prefix' => '<label>'.$element['#title'].'</label><div id="colorBrewerContainer" style="overflow:auto;padding:10px"></div>',
		'#suffix' => '',
		'#attributes' => array(
			'id' => 'colorBrewerInput',
      		'data-menu' => isset($element['#js_data'])?json_encode($element['#js_data']):"",
			'value' => $element['#default_value'],
			'name' =>'color',
		),
		//'#default_value' => $element['#default_value'],
	];

	$element['inverse_pallete_checkbox'] = [
		'#type' => 'checkbox',
		'#title' => t('Inverse colors'),
		'#checked' => isset($element['#inverse']) ? $element['#inverse']: false,
		'#attributes' => [
			'id' => 'inverse_pallete_checkbox',
			'name' =>'inverse_pallete_checkbox',
			'return_value' => 'value',
		]
	];

	return $element;
  }

  /**
  * Copy the user inputs to the parent field value.
  */
 public static function processColorBrewerElement(&$element, FormStateInterface $form_state, &$complete_form) {

                // Get all form inputs
                $inputs = $form_state->getUserInput();
                $values = $form_state->getValues();

				// dsm($element);
                // Get path of subarray
                $path = array_key_path("color_palette", $values);

                // Set results in parent element
                if(isset($inputs['color'])){
					$form_state->setValue($path, $inputs['color']);
                }

				if(isset($inputs['inverse_pallete_checkbox'])){
					$form_state->setValue([
						0 =>'manual_parameters',
						1 =>'manual_param_input',
						2 =>'inverse_pallete_checkbox',
					],
						$inputs['inverse_pallete_checkbox']);
				}
                return $element;
 }
}

/**
* Search for a key in an array, returning a path to the entry.
*
* @param $needle
*   A key to look for.
* @param $haystack
*   A keyed array.
* @param $forbidden
*   A list of keys to ignore.
* @param $path
*   The intermediate path. Internal use only.
* @return
*   The path to the parent of the first occurrence of the key, represented as an array where entries are consecutive keys.
*/
function array_key_path($needle, $haystack, $forbidden = array(), $path = array()) {
 foreach ($haystack as $key => $val) {
   if (in_array($key, $forbidden)) {
     continue;
   }
   if (is_array($val) && is_array($sub = array_key_path($needle, $val, $forbidden, array_merge($path, (array)$key)))) {
     return $sub;
   }
   elseif ($key === $needle) {
     return array_merge($path, (array)$key);
   }
 }
 return FALSE;
}
