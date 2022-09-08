<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\colortime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\stage2_admin\Form\StageVariablesEditRawForm;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_client\StageClientSM;
use Drupal\stage2_admin\StageFormsCommon;

class StageVariablesEditRawForm{

	public static function getRawForm(&$form_state, $id = NULL, $varId=NULL) {

		$form['current_id'] = [
			'#type' => 'hidden',
			'#value' => $id,
		];

		if (in_array("administrator", \Drupal::currentUser()->getRoles())){
			$form['current_param_id']=[
				'#type' => 'textfield',
				'#description' => 'Currenr parameters id. Admin only field',
				'#value' => $id,
				'#disabled' => true,
			];
		}

		$form['param_input'] = [
			'#type' => 'radios',
			'#title' => t('Parameters'),
			'#default_value' => 0,
			'#options' => [
				0 => t('Manualy set parameters'),
				1 => t('Load existing parameters'),
				2 => t('Default parameters'),
				3 => t('No change'),
			],
			'#attributes' => [
				'class' => array('param_input_type')
			],
		];


		//  --------- Manualy set parameters  ---------
		$form['manual_param_input'] = [
			'#type' => 'container',
			'#states' => [
				'visible' => [
					'.param_input_type input' => [
						'value' => 0
					],
				],
			],
		];

		// get data to edit
		$currentDataRaw = StageDatabase::getVariableProperties($entry = array("id"=>$id));
		$current = isset($currentDataRaw[0]) ? json_decode($currentDataRaw[0]->data) : json_decode("[]");
		$class_breaks = StageDatabase::getAdvancedSettings(array("setting"=>"class_breaks"));
		$class_methods = StageDatabase::getAdvancedSettings(array("setting"=>"classification_methods"));
		$special_values = StageDatabase::getSpecialValues($id);

		$form['manual_param_input']['color_palette'] = array(
			'#type' => 'color_brewer_element',
			'#default_value' => isset($current->color_palette)?$current->color_palette:"Pastel2",
			'#inverse' => isset($current->inverse_pallete_checkbox)?$current->inverse_pallete_checkbox:false,
			'#title' => "Color palette",
		);

		$form['manual_param_input']['description'] = array(
			'#type'=> 'text_format',
			'#title' => t('Description (time and spatial unit dependent)'),
			'#default_value' => isset($current->description)?$current->description:'',
			'#format'=> 'full_html',
		);

		$form['manual_param_input']['decimals'] = array(
			'#type' => 'select',
			'#title' => t('Number of decimals'),
			'#options' => StageClientSM::stage2_client_get_advanced_settings('decimals_options'),
			'#default_value' => isset($current->decimals)?$current->decimals:0,
		);

		$manual = false;
		$sunotes = [];
		if (!is_null($varId)) {
			$varval = StageClientSM::stage2_get_varval($varId,false,'en',true, true);
			$form['#attached']['drupalSettings']['variableValues'] = $varval;

			$sunotes = StageClientSM::stage2_get_sunotes($varId);
			$form['#attached']['drupalSettings']['suNotes'] = $sunotes;

			if ($varval['cnt']<4) $manual = true;
		}

		$form['manual_param_input']['classification'] = array(
			'#type' => 'radios',
			'#title' => t('Classification'),
			'#options' => array(0 => t('Auto classification'), 1 => t('Manual classification')),
			'#default_value' => isset($current->classification) ? ($manual ? 1 : $current->classification) : ($manual ? 1 : 0),
			'#attributes' => array(
				'class' => array('classification_input')
			),
		);

		$form['manual_param_input']['auto_classification'] = array(
			'#type' => 'fieldset',
			'#title' => t('Classification'),
			'#states' => array(
				'visible' => array(
					'.classification_input input' => array('value' => 0),
				)
			)
		);

		$form['manual_param_input']['auto_classification']['class_breaks'] = array(
			'#prefix' => '<div id="cb">',
			'#suffix' => '</div>',
			'#type' => 'select',
			'#title' => t('Number of class breaks'),
			'#description' => t('Excessive number of may corrupt the choropleth map display.'),
			'#options' => json_decode($class_breaks[0]->value,true),
			'#default_value' => isset($current->auto_classification->class_breaks)?$current->auto_classification->class_breaks:5,
		 );

		$form['manual_param_input']['auto_classification']['categorized']=[
			'#prefix' => '<div id="less_more">',
			'#suffix' => '</div>',
		];

		$form['manual_param_input']['auto_classification']['categorized']['or_less'] = array(
			'#type' => 'textfield',
			'#default_value' => $current->auto_classification->categorized->or_less,
			'#title' => t('Group values less or equal to the following value'),
			'#description' => t('this will be the first item in the legend'),
		 );

		 $form['manual_param_input']['auto_classification']['categorized']['or_more'] = array(
			'#type' => 'textfield',
			'#default_value' => $current->auto_classification->categorized->or_more,
			'#title' => t('Group values more or equal to the following value'),
			'#description' => t('this will be the last item in the legend'),
		 );

		$ac_options = json_decode($class_methods[0]->value,true);
		unset($ac_options[0]);

		$form['manual_param_input']['auto_classification']['interval'] = array(
			'#type' => 'select',
			'#title' => t('Method'),
			'#options' => $ac_options,
			'#default_value' => isset($current->auto_classification->interval)?$current->auto_classification->interval:4,
		);

		$form['manual_param_input']['manual_classification'] = array(
			'#type' => 'fieldset',
			'#title' => t('Manual classification'),
			'#states' => array(
				'visible' => array(
					'.classification_input input' => array('value' => 1),
				)
			)
		);

		if (isset($current->manual_classification->manual_breaks) && is_array($current->manual_classification->manual_breaks)) {
			$current->manual_classification->manual_breaks=implode(',',$current->manual_classification->manual_breaks);
		}

		$form['manual_param_input']['manual_classification']['manual_breaks'] = array(
			'#type' => 'textfield',
			'#default_value' => isset($current->manual_classification->manual_breaks)?$current->manual_classification->manual_breaks:"",
			'#states' => array(
				'required' => array(
					':input[name="parameters"]' => array('value' => 2),
				),
				'required' => array(
					':input[name="classification"]' => array('value' => 1),
				),
			)
		);

		$form['manual_param_input']['auto_classification']['legend']=array(
			'#markup'=> '<div id="auto-legend"></div>'
		);

		$form['manual_param_input']['manual_classification']['legend']=array(
			'#markup'=> '<div id="manual-legend"><div id="settings-legend"></div></div>'
		);
		
		$form['manual_param_input']['legend_edit_container']=array(
			'#markup' => '<div id="interval-editor"></div>'
		);

		/* SU NOTES */
		$form['manual_param_input']['su_notes'] = array(
			'#type' => 'fieldset',
			'#title' => t('Notes on spatial units')
		);

		$form['manual_param_input']['su_notes']['cmp']=array(
			'#markup'=> '<div id="su-notes-cmp"></div>'
		);

		$form['manual_param_input']['su_notes']['notes'] = array(
			'#type' => 'textfield',
			'#default_value' => json_encode($sunotes),
			'#attributes' => array('style' => array('display: none;'))
		);

		/* SPECIAL VALUES */
		$form['manual_param_input']['special_values'] = array(
			'#type' => 'fieldset',
			'#title' => t('Special values'),
			'#tree' => TRUE,
		);

		$header = array(
			'value' => t('Special value'),
			'lc' => t('Legend caption'),
			'col' => t('Color'),
		);


		$form['lc'] = array(
			'#type' => 'value',
		);
		$form['col'] = array(
			'#type' => 'value',
		);

		$options = array();

		// save to form_state first time
		if(null === $form_state->getValue("pass_special_values")){
			// form special values list from database

			foreach ($special_values as $key => $value) {

				$arr = array(
				  'value' => $value->special_value,
				  'lc' => array('data'=>
						array(
							'#type' => 'textfield',
							'#title' => t('Legend caption'),
							'#title_display' => 'invisible',
							'#name' => 'lc['.$key.']',
							'#size' => 30,
							'#maxlength' => 30,
							'#value' => $value->legend_caption,
						),
					),
					'col' => array('data'=>
						array(
							'#type' => 'color',
							'#title' => t('Legend caption'),
							'#title_display' => 'invisible',
							'#name' => 'col['.$key.']',
							'#size' => 5,
							'#maxLengh' => 5,
							'#value' => $value->color,
						),
					),
				);
				$options[$value->id] = $arr;
			  }
		}else{
			$options = $form_state->getValue("pass_special_values");
		}

		$form['manual_param_input']['pass_special_values']= array(
			'#type' => 'hidden',
			'#value' => json_encode($options),
		);

		$form['manual_param_input']['special_values']['special_values_table'] = array(
			'#type' => 'tableselect',
			'#header' => $header,
			'#options' => $options,
			'#empty' => t('No special values found'),
			'#prefix' => '<div id="ajax_remove">',
			'#suffix' => '</div>',
		);

		$form['manual_param_input']['special_values']['delete_special_value_button'] = array(
			'#type' => 'submit',
			'#value' => t('Remove special value'),
			'#submit' => array('Drupal\stage2_admin\Form\StageVariablesEditRawForm::removeSpecialValue'),
			//'#limit_validation_errors' => array(),
			'#ajax' => array(
				// Function to call when event on form element triggered.
				//'callback' => array('Drupal\stage2_admin\Form\StageVariablesEditRawForm::refreshAjax'),
				'callback' => array('Drupal\Stage2_admin\Form\StageVariablesEditRawForm', 'refreshAjax'),
				// Javascript event to trigger Ajax. Currently for: 'onchange'.
				'event' => 'click',
				'wrapper' => 'ajax_remove',
			),
		);

		/* SECIAL VALUES ADD NEW */
		$form['manual_param_input']['special_values']['add_special_value'] = array(
			'#type' => 'details',
			'#title' => t('Add special value'),
			'#open' => FALSE
		);

		$form['manual_param_input']['special_values']['add_special_value']['special_value'] = array(
			'#type' => 'textfield',
			'#title' => t('Special value'),
			'#size' => 30,
			'#maxlength' => 30,
			'#description' => t('Max length: 30'),
		);

		$form['manual_param_input']['special_values']['add_special_value']['legend_text'] = array(
			'#type' => 'textfield',
			'#title' => t('Legend text'),
			'#size' => 30,
			'#maxlength' => 30,
		);

		$form['manual_param_input']['special_values']['add_special_value']['color'] = array(
			'#type' => 'color',
			'#title' => t('Color'),
			'#default_value' => '#ffffff',
		);

		$form['manual_param_input']['special_values']['add_special_value']['add_special_button'] = array(
			'#type' => 'submit',
			'#value' => t('Add'),
			//'#limit_validation_errors' => array(),
			'#submit' => array('Drupal\stage2_admin\Form\StageVariablesEditRawForm::addSpecialValue'),
			'#ajax' => array(
				// Function to call when event on form element triggered.
				'callback' => array('Drupal\stage2_admin\Form\StageVariablesEditRawForm', 'addSpecialValueCallback'),
				// Javascript event to trigger Ajax. Currently for: 'onchange'.
				'event' => 'click',
				'wrapper' => 'ajax_remove',
			),
		);

		$gn=json_decode(db_query("SELECT value from s2.advanced_settings where setting='geonetwork'")->fetchField());

		//  --------- Load existing parameters  ---------
		$form['inherit_param'] = [
			'#type' => 'container',
			'#states' => [
				'visible' => [
					'.param_input_type input' => [
						'value' => 1
					],
				],
			],
		];

		$available_acronyms = StageDatabaseSM::stage2GetAvailableAcronyms();
		$tree_menu=StageFormsCommon::treeStructure();
		$opt = [];

		foreach ($available_acronyms as $key => $value) {
			$opt[$value] = $tree_menu[$key]['path'].' {'.$value.'}';
		}

		$form['inherit_param']['exist_var_name'] = array(
			'#title' => t('Select variable'),
			'#type' => 'select',
			'#options' => $opt,
			'#select2' => [             // you can pass options as defined in document: https://select2.org/configuration/options-api
				'placeholder' => 'Search for option or select one',
				'width' => '200px'
				]
		);

		$exist_var_su_opt =[];
		foreach (StageDatabase::loadCoordinateSpatialUnits() as $key => $value) {
			$exist_var_su_opt[$value->id] = $value->name;
			if ($value->note_id == '1'){
				$exist_var_su_def = $value->id;
			}
		}
		$form['inherit_param']['exist_var_su'] = array(
			'#title' => t('Spatial unit'),
			'#type' => 'select',
			'#options' => $exist_var_su_opt,
			'#select2' => [             // you can pass options as defined in document: https://select2.org/configuration/options-api
				'placeholder' => 'Search for option or select one',
				'width' => '200px'
				]
		);

		// Set date
		 $form['inherit_param']['exist_var_date'] = array(
			'#type' => 'date',
			'#title' => ('Date'),
			'#attributes' => array('type'=> 'date', 'min'=> '-25 years', 'max' => '+5 years' ),
			'#default_value' => date("Y-m-d",strtotime('today')),
		 );

		return $form;
	}

  	/**
	 * addSpecialValue.
	 */
	public static function addSpecialValue(array &$form, FormStateInterface $form_state) {
		$formValues = $form_state->getValues();

		$parent_array = getArray($form, 'special_values');

		// pridobi nove attribute
		$newRow = array(
			'value' => $parent_array['add_special_value']['special_value']['#value'],
			'lc' => array(
				'data'=>array(
					'#type' => 'textfield',
					'#title' => t('Legend caption'),
					'#title_display' => 'invisible',
					'#name' => 'lc['.$key.']',
					'#size' => 30,
					'#maxlength' => 30,
					'#value' => $parent_array['add_special_value']['legend_text']['#value'],
				),
			),
			'col' => array(
				'data'=>array(
					'#type' => 'color',
					'#title' => t('color'),
					'#title_display' => 'invisible',
					'#name' => 'col['.$key.']',
					'#size' => 10,
					'#maxLengh' => 10,
					'#value' => $parent_array['add_special_value']['color']['#value'],
				),
			)
		);

		$opt_array = getArray($formValues, "pass_special_values");
		$options = json_decode($opt_array, true);

		$equalFlag = false;

		$inputs = $form_state->getUserInput();
		$lcs=$inputs['lc'];
		$cols=$inputs['col'];
		$inx=0;

		$lcs = array_values($lcs);
		$cols = array_values($cols);

		foreach($options as $i=>&$option){
			if($option['value'] == $newRow['value']){
				$equalFlag = true;
			}
			$option['lc']['data']['#value']=$lcs[$inx];
			$option['col']['data']['#value']=$cols[$inx];
			$inx++;
		}

		if(!$equalFlag){
			array_push($options, $newRow);
		}else{
			drupal_set_message(t('Special value already exists!'), 'error');
		}

		$form_state->setValue("pass_special_values", $options);
		$form_state->setRebuild();
	}

		/**
		* addSpecialValueAjaxCallback.
		*/
		public static function addSpecialValueCallback(array $form, FormStateInterface $form_state) {
			$parent_array = getArray($form, 'special_values');
			return $parent_array['special_values_table'];
		}

		/**
		* removeSpecialValue.
		*
		* Submit handler which removes selected special values from tableselect element
		*/
		public static function removeSpecialValue(array &$form, FormStateInterface &$form_state) {

			$formValues = $form_state->getValues();

			$opt_array = getArray($formValues, "pass_special_values");
			//$results = getArray($formValues, "special_values_table"); // doesn't work for the first table item (always 0 => 0)
			$results=getArray($form, 'special_values_table');
			if (!isset($results['#value'])) return;

			$options = json_decode($opt_array, true);

			$keys = array_keys($options);

			$inputs = $form_state->getUserInput();

			$lcs=$inputs['lc'];
			$cols=$inputs['col'];

			$lcs = array_values($lcs);
			$cols = array_values($cols);

			foreach($results['#value'] as $key => $value){
				$pos = array_search($key, $keys);
				unset($lcs[$pos]);
				unset($cols[$pos]);
				unset($options[$key]);
			}

			$lcs = array_values($lcs);
			$cols = array_values($cols);

			$i=0;
			foreach($options as $key=>&$option){
				$option['lc']['data']['#value']=$lcs[$i];
				$option['col']['data']['#value']=$cols[$i];
				$i++;
			}

			$form_state->setValue("pass_special_values", $options);
			$form_state->setRebuild();
		}

		/**
		* Refreshes ajax.
		*/
		public static function refreshAjax(array &$form, FormStateInterface $form_state) {
			$sp_array = getArray($form, 'special_values');
			return $sp_array['special_values_table'];
		}

		/*
		* Returns JSON for builded array
		*/

		public static function saveParameters(array &$form, FormStateInterface &$form_state, $var_ds_id=null) {

			// all manual_parameters data
			$formValues = $form_state->getValues();
			$inputs = $form_state->getUserInput();
			$manual_parameters_data = getArray($formValues, 'manual_parameters');

			// dsm($manual_parameters_data);
			switch ($manual_parameters_data['param_input']) {
				case 0:
						$manual_parameters_data = $manual_parameters_data['manual_param_input'];
						unset($manual_parameters_data['special_values']);

						// get table special values from form
						$array = getArray($form, 'special_values_table');

						// add special values to results
						$manual_parameters_data['special_values'] = $array['#options'];

						unset($manual_parameters_data['pass_special_values']);
						unset($manual_parameters_data['special_values']);

						unset($manual_parameters_data['lc']);
						unset($manual_parameters_data['col']);

						$manual_parameters_data['description'] = $manual_parameters_data['description']['value'];
						$id = StageDatabase::updateVariableProperties(json_encode($manual_parameters_data), $var_ds_id);

						// TODO: save special values to new database
						StageDatabase::insertSpecialValues($id, $array['#options'],$inputs);

						if (isset($manual_parameters_data['su_notes'])) {
							if (isset($manual_parameters_data['su_notes']['notes'])) {
								$notes = $manual_parameters_data['su_notes']['notes'];
								StageDatabase::saveSuNotes($id, trim($notes));		
							}
						}

					break;
				case 1:

						$exist_var_name = $manual_parameters_data['inherit_param']['exist_var_name'];
						$exist_var_su = $manual_parameters_data['inherit_param']['exist_var_su'];
						$exist_var_date = $manual_parameters_data['inherit_param']['exist_var_date'];
						// get closest variable value parameters id
						$id = StageDatabaseSM::stage_get_existing_param($exist_var_name,$exist_var_su,$exist_var_date);
						if(!$id){
							drupal_set_message('The parameters were not available for the combination variable name - spatial unit. ','warning');
							$id = $manual_parameters_data['current_id'];
						}
					break;
				case 2:
					$id = StageDatabase::getDefaultVariableProperties()[0]->id;;
					break;
				case 3:
					$id = $manual_parameters_data['current_id'];
					break;

			}

			return $id;
		}
}

/*
* Function finds subarray of array specified by index as associative key
*
* @$array	array	Main array with subarray included
* @$index	string	Associative key of array which defines subarray
*/
function getArray($array, $index) {
	if (!is_array($array)) return null;
	if (isset($array[$index])) return $array[$index];
	foreach ($array as $item) {
		$return = getArray($item, $index);
		if (!is_null($return)) {
			return $return;
		}
	}
	return null;
}
