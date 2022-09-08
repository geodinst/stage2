<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_admin\StageFormsCommon;
use Drupal\stage2_admin\Form\StageVariablesEditRawForm;

class StageMenuTreeAddForm extends FormBase{

	private $id;
	private $varIds;

	public function getFormID() {
		return 'stage_menu_tree_form';
	}


	/**
	 * [buildForm description]
	 * @param  array              $form       [description]
	 * @param  FormStateInterface $form_state [description]
	 * @param  integer             $id         [Tree node id]
	 * @return [type]                         [description]
	 */
	public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

		if (!is_null($id)) {
			$permission_check = StageDatabaseSM::check_user_permissions_for_id('menu tree', $id);
			if ($permission_check !== true) {
				drupal_set_message("Permission error. You have to be a node owner or get your role assigned unrestricted admin permission to edit this variable settings.", "error");
				return $form;
			}
		}

		$this->id = $id;

		// current variable data
		$entry = array("var_tree_id" => $id);
		$default = StageDatabase::getVariableName($entry);

		// List of all existing all_acronyms
		$all_acronyms = StageDatabaseSM::load_all_acronyms();
		// list of all variables with saved values
		$allVariables = StageDatabase::loadVariables();
		// get all tree subelements of tree element
		$childrens = StageDatabase::getAllTreeChildrens($id);
		// get all variable names of specified tree elements (if none, hide variable properties)
		$variableNames = StageDatabase::loadMenuTree($childrens);
		// get tree structure
		$tree_menu=StageFormsCommon::treeStructure();

		// save variables to array
		$namesIds = array();
		foreach($variableNames as $names){
			array_push($namesIds, $names->id);
		}

		// get all existing variable values of selected variables
		$variables = StageDatabase::loadVariablesNoNames(array("var_names_id" => $namesIds));

		// save variable values ids to array
		$varIds = array();
		foreach($variables as $var){
			array_push($varIds, $var->id);
		}
		$this->varIds = $varIds;

		$form = array(
			'#type' => 'details',
			'#title' => t('Menu tree editor'),
			'#open' => true,
		);

		// prepare containers
		$form['data_container'] = array(
			'#type' => 'data_container',
			'#prefix' => '<div id="data_container">',
			'#suffix' => '</div>',
		);

		$form['left_container'] = array(
			'#type' => 'container',
			'#attributes' => array('class' => array('element-column-left'))
		);

		$form['right_container'] = array(
			'#type' => 'container',
			'#attributes' => array('class' => array('element-column-right'))
		);

		$form['calear_container'] = array(
			'#type'=> 'container',
			'#attributes' => array('class' => array('element-clear-fix'))
		);

		$form['left_container']['path'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Tree path'),
			'#size' => 60,
			'#maxlength' => 128,
			'#description' => t('Variable name path in tree structure.'),
			'#value' => $tree_menu[$this->id]['path'],
			'#disabled' => TRUE,
		);

		$form['left_container']['name'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Name'),
			'#required' => TRUE,
			'#description' => t('Variable name - English name is to be displayed in the client menu.'),
			'#default_value' => $default[0]->name,
		);

		$form['left_container']['acronym_all'] = array(
			'#type' => 'textfield',
			'#name' => 'existing_acronyms',
			'#default_value' => $all_acronyms,
		);

		$form['left_container']['acronym'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Acronym'),
			'#size' => 60,
			'#maxlength' => 10,
			'#required' => TRUE,
			'#default_value' => $default[0]->short_name,
			'#description' => t('Variable acronym to be used when user imports data. The input will be automatically converted to uppercase.'),
		);

		$popup_title = $default[0]->popup_title;

		if ($popup_title ==''){
			$popup_title = $tree_menu[$this->id]['path'];
		}

		$form['left_container']['popup_title'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Popup title'),
			'#default_value' => $popup_title,
			'#description' => t('The name that is to be displayed when user clicks on a poligon.'),
		);

		$form['left_container']['legend_title'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Legend caption'),
			'#default_value' => $default[0]->legend_title,
			'#description' => t('The legend caption.'),
		);

		// Variable description that is not dependent of date and spatial unit
		$form['right_container']['description'] = array(
			'#type' => 'text_format',
			'#default_value' => $default[0]->description,
			'#title' => t('Variable description that is not dependent of date and spatial unit.'),
			'#format'=> 'full_html',
		);

		// The following two fields will be hidden using JS. They are used as reference in the submitt function.
		$form['left_container']['variable_id'] = array(
			'#type' => 'textfield',
			'#size' => 10,
			'#name' =>'variable_id_field',
			'#disabled' => TRUE,
			'#value' => $id,
		);

		$form['left_container']['dependent_variables'] = array(
			'#type' => 'textfield',
			'#name' =>'dependent_variables_field',
			'#size' => 128,
			'#disabled' => TRUE,
			'#value' => $varIds,
		);

		// prepare containers
		$form['picture'] = array(
			'#type' => 'details',
			'#title' => t('Picture'),
			'#open' => false,
		);

		$form['picture']['left_container'] = array(
			'#type' => 'container',
			'#attributes' => array('class' => array('element-column-left'))
		);

		$form['picture']['right_container'] = array(
			'#type' => 'container',
			'#id' => 'picture_preview_container',
			'#attributes' => array('class' => array('element-column-right'))
		);

		$form['picture']['calear_container'] = array(
			'#type'=> 'container',
			'#attributes' => array('class' => array('element-clear-fix'))
		);

		$form['picture']['left_container']['picture_upload_recommendations'] = array(
			'#markup'=>t('Choose the picture that is to be displayed in client variable header section.</br> Recomended picture size 370 x 130 px.'),
		);

		$form['picture']['left_container']['upload'] = array(
			'#type' => 'managed_file',
			'#multiple'=> FALSE,
			'#title' => t('Choose a picture'),
			'#description' => t('Allowed extensions: png., Max. size: 0.5 MB '),
			'#upload_location' => 'public://temp_png_uploads',
			'#upload_validators' => [
				'file_validate_is_image' => [],
				'file_validate_size' => [500000],
				'file_validate_extensions' => ['png'],
			]
		);

		$pic = StageDatabaseSM::fetchVarPic($id); //Fetch pic from the s2.var_names:picture

		$form['picture']['right_container']['picture_preview'] = array(
			'#type' => 'textarea',
			'#name' => 'picture_textarea',
			'#value'=> $pic,
		);

		$form['picture']['right_container']['remove_picture'] = array(
			'#type' => 'button',
			'#name' => 'remove_picture',
			'#value' => t('Remove picture'),
			'#attributes' => array('onclick' => 'return (false);'),
		);

		// Prepare containers
		$form['delineation'] = array(
			'#type' => 'details',
			'#title' => t('Delineation'),
			'#open' => false,
		);

		$form['delineation']['left_container'] = array(
			'#type' => 'container',
			'#attributes' => array('class' => array('element-column-left'))
		);

		$form['delineation']['right_container'] = array(
			'#type' => 'container',
			'#attributes' => array('class' => array('element-column-right')),
			'#prefix' => '<div id="delineation_container_ajax">',
			'#suffix' => '</div>',
		);
		$form['delineation']['calear_container'] = array(
			'#type'=> 'container',
			'#attributes' => array('class' => array('element-clear-fix'))
		);

		$available_acronyms = StageDatabaseSM::stage2GetAvailableAcronyms();
		$delineation_formula = StageDatabaseSM::stage2getdelineationformula($id);
		$tree_menu=StageFormsCommon::treeStructure();

		$opt = [];
		foreach ($available_acronyms as $key => $value) {
			$opt[$value] = $tree_menu[$key]['path'].' {'.$value.'}';
		}

		$form['delineation']['del_var_name'] = array(
			'#type' => 'select',
			'#empty_value' => '--',
			'#options' => $opt,
		);

		$form['delineation']['add_var_submit'] = array(
			'#type' => 'button',
			'#name' => 'update_delineation_formula',
			'#value' => t('Add variable'),
			'#attributes' => array('onclick' => 'return (false);'),
		);

		$form['delineation']['add_var_area'] = array(
			'#type' => 'button',
			'#value' => t('Add area'),
			'#name' => 'add_area',
			'#attributes' => array('onclick' => 'return (false);'),
		);

		$form['delineation']['left_container']['formula'] = array(
			'#type' => 'textarea',
			'#name' => 'delineation_formula',
			'#title' => $this->t('Delineation formula. Alowed input / * - + and variable acronyms in curly braces'),
			'#value' => $delineation_formula,
			'#description' => t('Please test the validity of the formula before publishing the variable.')
		);

		$form['delineation']['right_container']['del_allow'] = array(
			'#type' => 'checkbox',
			'#name' => 'delineation_disabled',
			'#title' => $this->t('Delineation disabled'),
			'#description' => t('If checked delineation chart will not be rendered.')
		);

		//prepare containers - parametrs
		$form['parameters_details'] = array(
			'#type' => 'details',
			'#title' => t('Parameters'),
			'#open' => false,
		);


		// Disable parametrs section if no data is available for the selected variable.
		if (empty($variables)){
			$form['parameters_details']['parameters']['#disabled'] = true;
			$form['parameters_details']['warning'] = array(
				'#markup' => t('Parameters can be changed if at least one variable is assigned to given menu tree entry. Of if the selected menu entry has subordinated nodes.')
			);
		} else {
		//** Change parametrs of the selected menu entry based on the existing parametrs TODO: add option to select spatial unit.

		// get current default id properties
		$did = StageDatabase::getDefaultVariableProperties();
		$defid = isset($did[0])?$did[0]->id:NULL;

		$injectedParametersForm = StageVariablesEditRawForm::getRawForm($form_state,$defid);
		$injectedParametersForm['param_input']['#default_value'] = 3;
		// Load default parameters to start with
		$form['parameters_details']['manual_parameters'] = $injectedParametersForm;
		$form['parameters_details']['manual_parameters']['#type'] = 'container';
		$form['parameters_details']['manual_parameters']['#open'] = TRUE;
		$form['parameters_details']['manual_parameters']['#tree'] = TRUE;


		}

		// Submit button
		$form['submit'] = array(
			'#type' => 'submit',
			'#name' => 'save_btn',
			'#value' => t('Save'),
		);

		// Submit button
		$form['cancel'] = array(
			'#type' => 'link',
			'#title' => 'Cancel',
			'#url' => Url::fromRoute('stage2_admin.menuTree'),
			'#attributes' => array(
				'class' => array('button'),
			),
		);

		$form['#attached']['library'][] = 'stage2_admin/StageMenuTreeAddForm';
		return $form;
	}


	public function validateForm(array &$form, FormStateInterface $form_state) {
	}

	/**
	* Implements \Drupal\Core\Form\FormInterface::submitForm().
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {



		$report = '';

		// save to variables
		$entry = array(
			'text' => $form_state->getValue('name'),
			'acronym' => strtoupper($form_state->getValue('acronym')),
			'description' => $form_state->getValue('description')['value'],
			'popup_title' => $form_state->getValue('popup_title'),
			'legend_title' => $form_state->getValue('legend_title'),
		);

		$variable_id = $form['left_container']['variable_id']['#value'];
		$entry['id'] = $variable_id;
		$count = StageDatabase::renameMenuTree($entry); // Modify name text and description

		$report += $count.' of variables modified in the form menu tree editor.';


		// save/update delineation formula
		$values = $form_state->getUserInput();
		if (isset ($values['delineation_formula'])){
			$formula = $values['delineation_formula'];
		} else {
			$formula = 'DELINEATION_DISABLED';
		}
		StageDatabaseSM::stage2updatedelineationformula($formula,$variable_id);


		// save the picture
		$image = $form_state->getValue('upload');
		if ($image){
			$file = file_load($image[0]);
			$uri = $file->uri;
			$full_filename = $uri->value;
			$path = $full_filename;
			$type = pathinfo($path, PATHINFO_EXTENSION);
			$data = file_get_contents($path);
			$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
			$encoded_img = '<img alt="Embedded Image" src="'.$base64.'" />';
			StageDatabaseSM::stage2picture2blob($encoded_img,$variable_id);
			$report += '; image update';
		}
		$image = $form_state->getValue('upload');

		if(isset($values['picture_textarea']) && $values['picture_textarea']=='picture_removed'){
			StageDatabaseSM::stage2picture2blob(false,$variable_id);
		}

		$propId = \Drupal\stage2_admin\Form\StageVariablesEditRawForm::saveParameters($form, $form_state);

		if($form_state->getValue('dependent_variables') && 	$form_state->getValue('manual_parameters')['param_input'] <> 3){
			// update parameters in var_values
			StageDatabase::updateVariableValues(array("var_properties_id"=>$propId), $form_state->getValue('dependent_variables'));
			StageDatabaseSM::stageLog('menu tree',"Tree item id: $variable_id, parameters $propId");
		}


		//Redirect back to menu_tree
		$url = Url::fromUri('internal:/menu_tree');
		$form_state->setRedirectUrl($url);
	}



	function stage_ii_delineation_container_ajax_callback(array &$form, FormStateInterface $form_state){
		return $form['delineation']['right_container'];
	}

	// custom submit FUNCTIONS
	function update_delineation_formula(array &$form, FormStateInterface $form_state){

		$trugger = $form_state->getTriggeringElement()["#parents"][0];
		$values = $form_state->getUserInput();
		$variable_id = $form['left_container']['variable_id']['#value'];
		$formula = $values['formula'];

		switch($trugger){
			case 'add_var_area':
				$formula = $formula.'{area}';
			break;

			case 'add_var_submit':
				$selected_acronym_id = $values['del_var_name'];
				$selectedacronymtext =  $form['delineation']['left_container']['del_var_name']['#options'][$selected_acronym_id];
				$formula = $formula.'{'.$selectedacronymtext.'}';
			break;

		}
		StageDatabaseSM::stage2updatedelineationformula($formula,$variable_id);
		$form_state->setRebuild(true);
	}
}
