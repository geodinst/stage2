<?php
/**
 * @file
 * Contains \Drupal\stage2_admin\Element\GimJsTreeElement.
 */
 
namespace Drupal\stage2_admin\Element;

use Drupal\Core\Form\FormStateInterface; 
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
 
/**
 * Provides an example element.
 *
 * @FormElement("jstree_element")
 */
class GimJsTreeElement extends FormElement {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderMyElement'],
      ],
	  '#process' => [
			[$class, 'processGimJsTreeElement']
		],
		'#attached' => [
			'library' => [
			  'stage2_admin/jsTree',
			  'stage2_admin/jsTreeInternal',
			],
		],
	];
  }
  
  /**
   * Prepare the render array for the template.
   */
  public static function preRenderMyElement($element) {  

	// jstree_element
	$element['jstree'] = [
		'#title' => t('Menu tree'),
		'#type' => 'hidden',
		'#prefix' => '',
		'#suffix' => '<div id="jstree"></div>',
		'#attributes' => array(
			'id' => 'jsTreeData',
			'data-menu' => json_encode($element['#js_data']),
		),				
	];
	
	//error_log(print_r(json_encode($element['#js_data'][0]),true));
	
	return $element;
  }
	
	 /**
   * Copy the user inputs to the parent field value.
   */
  public static function processGimJsTreeElement(&$element, FormStateInterface $form_state, &$complete_form) {
	  // Get all form inputs
		$inputs = $form_state->getUserInput();
		
		//error_log(print_r($inputs,true));
						
		return $element;
  }
	
}