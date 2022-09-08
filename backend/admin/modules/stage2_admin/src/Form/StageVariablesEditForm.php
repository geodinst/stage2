<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Datetime;
use Drupal\stage2_admin\Form\StageVariablesEditRawForm;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_admin\StageFormsCommon;

class StageVariablesEditForm extends FormBase{

	public $variables;
	public $selected_layer;
	public $time_periods = array();
	public $time_table_options = array();

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_geospatial_layers_edit_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

	// Imput patameters from variables form
	$input_data = json_decode($id);
	$id_name = $input_data->id_name;
	$id_spatial_layer = $input_data->id_spatial_layer;

	// get variable name, acrony amd description
	$variable_name = StageDatabase::getVariableName(array("id" => $id_name));
	// Get data to populate tableselect
	$variables = StageDatabaseSM::stage_get_variables_by_id_and_layer($id_name,$id_spatial_layer);
	// get data to populate Geospatial data
	$geospatial_data = StageDatabase::loadCoordinateSpatialUnits();

	// get tree structure
	$tree_menu=StageFormsCommon::treeStructure();

	// chack if geonetwor is enebled
	$gn=json_decode(db_query("SELECT value from s2.advanced_settings where setting='geonetwork'")->fetchField());
	// build options for parametrs date dates are keyed by id
	foreach ($variables as $var) {
		// build options for select in the parametrs time select
		$published_on = isset($var->publish_on) ? '  ('.$var->publish_on.')': '';
		$time_periods[$var->id] = explode(' ',$var->valid_from)[0];
		$time_table_options[$var->id] = array(
			'id' => $var->id,
			'date_column' => Link::fromTextAndUrl($time_periods[$var->id], Url::fromUri('internal:/variables/report/'.$var->id)),
			'status' => array(
				'data' => array(
					'#markup' => StageDatabaseSM::stagegetstatusbyid($var->published).$published_on,
				)
			)
		);
		$var->valid_from;
		if ($gn->enable=='true'){
			$metadata = isset(json_decode(unserialize(base64_decode($var->inspire)),true)['metadataInfos']) ? reset(json_decode(unserialize(base64_decode($var->inspire)),true)['metadataInfos'])[0]['message']:false;
			$link = $metadata ? Url::fromUri('internal:/variables/reportinspire/'.$var->inspire) : Url::fromUri('internal:/variables/reportinspire/-1');
			$time_table_options[$var->id]["inspire"] =
			 Link::fromTextAndUrl(t('more'), $link);

		}
	}

	// prepare geospatial data options
	foreach($geospatial_data as $value){
		$gd_options[$value->id] = $value->name;
	}


	$form['variable'] = array(
	  '#type' => 'details',
	  '#title' => t('Variable'),
	  '#open' => true,
	);

	// prepare containers
	$form['variable']['left_container'] = array(
		'#type' => 'container',
		'#attributes' => array('class' => array('element-column-left'))
	);
	$form['variable']['right_container'] = array(
		'#type' => 'container',
		'#attributes' => array('class' => array('element-column-right'))
	);
	//Clear right allignment
	$form['variable']['calear_container'] = array(
			'#type'=> 'container',
			'#attributes' => array('class' => array('element-clear-fix'))
	);
	$form['variable']['left_container']['path'] = array(
		'#type' => 'textfield',
		'#title' => $this->t('Tree path'),
		'#size' => 60,
		'#maxlength' => 128,
		'#description' => t('Variable name path in tree structure.'),
		'#value' => $tree_menu[$id_name]['path'],
		'#disabled' => TRUE,
	);

	$form['variable']['left_container']['name'] = array(
			'#type' => 'textfield',
			  '#title' => $this->t('Name'),
			  '#disabled' => TRUE,
        '#description' => t('Variable name. The name can be set in the menu tree editor.'),
			  '#value' => $variable_name[0]->name,
			);

	$form['variable']['left_container']['acronym'] = array(
			'#type' => 'textfield',
			  '#title' => $this->t('Acronym'),
        '#description' => t('Variable short name. The short name can be set in the menu tree editor.'),
			  '#disabled' => TRUE,
			  '#value' => $variable_name[0]->short_name,
			);

	$form['variable']['left_container']['geospatial_data'] = array(
			'#type' => 'select',
			  '#title' => $this->t('Geospatial data'),
            '#disabled'=>true,
				"#empty_option"=>t('- Select -'),
				"#required" =>true,
        '#description' => t('Geospatial basis on which the data will be plotted.'),
			  '#options' => $gd_options,
			  '#default_value' => $id_spatial_layer ? $id_spatial_layer: false,
			);
	$form['variable']['left_container']['var_id'] = array(
			'#type' => 'select',
			  '#title' => t('Load parametrs by date'),
				"#empty_option"=>t('- Select -'),
				"#empty_value"=>'-1',
				'#executes_submit_callback' => true,
			  '#options' => $time_periods,
        '#description' => t('Select the time point to set the time-dependent variable settings.'),
				'#ajax' => array(
					'callback' => array($this, 'updateManualSettingsCallback'),
					'method' => 'replace',
					'wrapper' => 'ajax_rebuild_settings',
					'effect' => 'fade',
				),
			);
  // get the description for the selected spatial unit if it enchant_broker_dict_exists



	$form['variable']['right_container']['description_'] = array(
    '#type' => 'details',
    '#title' => t('Time-independent variable description. The value can be set in the menu tree editor.'),
    '#open' => true,
  );


  $form['variable']['right_container']['description_']['description'] = array(
    // '#type' => 'textarea',
    '#title' => $this->t('Description'),
    // '#disabled'=>true,
    '#description' => t('Time-independent variable description. The value can be set in the menu tree editor.'),
    '#markup' => $variable_name[0]->description,//)->type==0 ? json_decode($variable_name[0]->description)->value : json_decode($variable_name[0]->description)->value[$id_spatial_layer],
  );
  $form['variable']['right_container']['more'] = array(
    '#type' => 'details',
    '#title' => t('DB - preview'),
    '#open' => false,
  );
  $form['variable']['right_container']['more']['var_names_id'] = array(
    '#type' => 'textfield',
    '#name'=>'var_names_id',
    '#size' => 10,
    '#description' => $this->t('var_names_id'),
    '#disabled' => TRUE,
    '#value' => $id_name,
  );
  $form['variable']['right_container']['more']['var_spatial_layer_id'] = array(
    '#type' => 'textfield',
    '#name'=>'var_spatial_layer_id',
    '#size' => 10,
    '#description' => $this->t('var_spatialLayer_id'),
    '#disabled' => TRUE,
    '#value' => $id_spatial_layer,
  );

  $form['variable']['right_container']['more']['note_1'] = array(
    '#type' => 'fieldset',
    '#title' => t('Note'),
  );
  $form['variable']['right_container']['more']['note_1']['table_note_param'] = array(
    '#markup' => t('This section is used for administrative purposes.')
  );

	/* TIME PERIODS TABLE */
	$header = array(
		'date_column' =>t('Valid from'),
		'status' =>t('Status'),
		'id' =>t('id'),
	);

	if ($gn->enable=='true'){
		// drupal_set_message('GEONETWORK disabled','warning');
		$header['inspire']=t('INSPIRE');
	}
	// /* TIME PERIODS TABLE */
	$form['variable']['time_periods']['table_select'] = array(
		'#type' => 'tableselect',
		'#js_select' => false,
		'#header' => $header,
		'#options'=>$time_table_options,
		'#empty' => t('No time periods available'),
	);

	$form_state->setValue(['manual_parameters', 'decimals'], '3');
	$form_state->setRebuild();

	$form['parametrs'] = array(
		'#type' => 'details',
		'#title' => t('Parametrs'),
		'#prefix' =>'<div id="ajax_rebuild_settings">',
		'#suffix' =>'</div>',
		'#open' => false,
	);

	// selected variable parameters
	$var_ds_id = isset($form_state->getValues()['var_id'])? $form_state->getValues()['var_id']: false;

	if ($var_ds_id && $var_ds_id <> '-1'){

		$var_properties_id = $variables[$var_ds_id]->var_properties_id; // get the properties id
		$form['parametrs']['#open'] = true;
		$form['parametrs']['manual_parameters'] = StageVariablesEditRawForm::getRawForm($form_state, $var_properties_id, $var_ds_id);
		$form['parametrs']['manual_parameters']['#tree'] = TRUE;
	}
	else{
		$form['parametrs']['not_possible_to change_settings'] = array(
			'#markup' => t('Please select parametrs by date')
		);
	}

	/* last submit */
	$form['save'] = array
	(
		'#type' => 'submit',
		'#value' => t('Save'),
	);

	$form['quit'] = array
	(
		'#type' => 'submit',
		'#value' => t('Quit without saving'),
	);
	$form['delete'] = array
		  (
			'#type' => 'submit',
			'#value' => t('Delete'),
		  );

	$form['unpublish'] = array
		  (
			'#type' => 'submit',
			'#value' => t('Unpublish'),
		  );

	$form['publish_on_date'] = array
		  (
			'#type' => 'submit',
			'#value' => t('Publish on date'),
		  );

	$form['export_data'] = array
		  (
			'#type' => 'submit',
			'#value' => t('Export data'),
		  );

		$form['#attached']['library'][] = 'stage2_admin/variable_edit';
	  return $form;
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate the form values.
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

	// submit buttons handler
	$bla = $form_state->getTriggeringElement();
	$action = $bla["#parents"][0];
	$values = $form_state->getValues();

	// get selected rows
	$selected = array_filter($values['table_select']);
	$selected_count = count($selected);


	if ($action =='quit'){
		$url = \Drupal\Core\Url::fromRoute('stage2_admin.variables');
		$form_state->setRedirectUrl($url);
		return;
	}


	// if nothing selected return error
	if ($selected_count == 0 && $action != 'var_id'){	//var_id is #ajax call
		drupal_set_message(t('Nothing selected.'),'warning');
		$form_state->setRebuild();
	}

	switch($action){
		case 'delete':
			StageDatabaseSM::deleteVariablesbyId($selected);
			drupal_set_message(t('Variables deleted.'));
			$url = \Drupal\Core\Url::fromRoute('stage2_admin.variables');
		  $form_state->setRedirectUrl($url);
		break;
		case 'unpublish':
			StageDatabaseSM::unpublishVariablesvar_ds_id($selected);
			drupal_set_message(t('Variables unpublished.'));
			$form_state->setRebuild();
		break;
		case 'publish_on_date':
				$parameters_to_pass = array();
				$parameters_to_pass['selected'] = $selected;
				$parameters_to_pass['attribute'] = 'id';
				$parameters_to_pass['var_names_id'] = $values['var_names_id'];
				$parameters_to_pass['var_spatial_layer_id'] = $values['var_spatial_layer_id'];
				$url = \Drupal\Core\Url::fromRoute('stage2_admin.variablesPublish')
					->setRouteParameters(array('id'=>json_encode($parameters_to_pass)));
						$form_state->setRedirectUrl($url);
		break;
		case 'export_data':
			drupal_set_message(t('Export data function is under development. It wil be implemented completion of client development.'),'warning');
			$form_state->setRebuild();
		break;

		case 'save':
		// update spatial layer	if changed
			$sid = $values['geospatial_data'];

			$ids = StageDatabaseSM::get_permitted_ids($sid);

			if (count($ids) > 0) {
				$unpermitted = array_diff($selected, $ids);
				$selected = array_diff($selected, $unpermitted);

				if (count($selected) === 0) {
					$form_state->setRebuild();
					return;
				}
			}

			$return = StageDatabaseSM::updateSpatialLayer($selected,$sid);
			$url = \Drupal\Core\Url::fromRoute('stage2_admin.variables');
			$form_state->setRedirectUrl($url);

			// update properties
			$var_ds_id = isset($form_state->getValues()['var_id'])? $form_state->getValues()['var_id']: false;
			$propId = \Drupal\stage2_admin\Form\StageVariablesEditRawForm::saveParameters($form, $form_state, $var_ds_id);
			$return = !empty($selected) ? StageDatabase::updateSpatialLayersCondition(array('var_properties_id' => $propId), $selected):false;

		break;
		case 'var_id':	// empty user input for #ajax proposes
			$input = $form_state->getUserInput();
			$input['manual_parameters'] = array();
			$form_state->setUserInput($input);
			$form_state->setRebuild();
		break;
	}
}

  public function updateManualSettingsCallback(array $form, FormStateInterface &$form_state){
	  return $form['parametrs'];
  }
}
