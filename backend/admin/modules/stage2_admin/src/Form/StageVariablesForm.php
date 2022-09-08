<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

use Drupal\stage2_admin\Classes\SpatialLayer;
use Drupal\stage2_admin\Classes\Variable;

use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_admin\StageFormsCommon;

class StageVariablesForm extends FormBase{

	/**
	* Implements \Drupal\Core\Form\FormInterface::getFormID().
	*/
	public function getFormID(){
		return 'stage_variables_form';
	}

	/**
	* Implements \Drupal\Core\Form\FormInterface::buildForm().
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {

		// Butons that are deisplayed above the tableselect
		$form['import'] = [
			'#type' => 'link',
			'#title' => t('Add variable (Shape & CSV)'),
			'#url' => Url::fromRoute('stage2_admin.batchImport'),
			'#attributes' => [
				'class' => [
					'button'
				]
			],
		];

		$form['import_px'] = [
			'#type' => 'link',
			'#title' =>  t('Add variable (PX)'),
			'#url' => Url::fromRoute('stage2_admin.batchImportPX'),
			'#attributes' => [
				'class' => [
					'button'
				]
			],
		];

		$user = \Drupal::currentUser();
        if ($user->hasPermission('stage2_admin content_administrator')) {
			$form['update_px'] = [
				'#type' => 'link',
				'#title' =>  t('Update variables (PX)'),
				'#url' => Url::fromRoute('stage2_admin.variablesUpdatePX'),
				'#attributes' => [
					'class' => [
						'button'
					]
				],
			];
		}

		$form['delete'] = [
			'#type' => 'submit',
			'#value' => t('Delete selected'),
		];

		$form['publish'] = [
			'#type' => 'submit',
			'#value' => t('Publish selected on date'),
		];

		$form['unpublish'] = [
			'#type' => 'submit',
			'#value' => t('Unpublish selected'),
		];


		// spatial layer selector
		if(!SpatialLayer::countLayers()){
			$form['note'] = [
				'#markup' => t('Variables not available.'),
			];
		}

		$form['table_container']['spatial_layers_selector'] = [
			'#title' => t('Select spatial layer'),
			'#type' => 'select',
			'#options' => SpatialLayer::getAllLayersSelect("- ".t("All")." -"),
			'#default_value' => 0,
			'#ajax' => array(
				'callback' => array($this, 'ajax_spatial_layer_callback'),
				'wrapper' => 'variables_table',
				'method' => 'replace',
				'effect' => 'fade',
			),
		];

		$filter = $form_state->getValue("spatial_layers_selector") ? $form_state->getValue("spatial_layers_selector") : 0;

		// Tableselect header column names
		$header = [
			'name' => t('Name'),
			'short_name' => t('Acronym'),
			'layer' => t('Spatial layer'),
			'published' => t('Published'),
			'counter' => t('Download counter'),
		];


		// read variables from database
		$queryArray = ($filter == 0) ? [] : ["spatial_layer.id" => $filter];
		$variables = Variable::getVariables($queryArray);

		// downloads counter
		$downloads = StageDatabase::countDownloads();
		$downar = [];
		foreach($downloads as $row){
			$downar[$row->var_names_id][$row->id] = $row->count;
		}


		$tree_menu = StageFormsCommon::treeStructure();

		// Options to be displayed in the tableselect
		$options = [];

		StageFormsCommon::getTableOptions($options,$variables,$tree_menu,$downar);

		$form['table'] = [
			'#type' => 'tableselect',
			'#header' => $header,
			'#options' => $options,
			'#id' => 'tableselect_id',
			'#js_select' => false,
			'#empty' => t('No variable has not yet been added'),
			'#prefix' => '<div id="variables_table">',
			'#suffix' => '</div>',
		];


		$form['table_note'] = array(
			'#type' => 'fieldset',
			'#title' => t('Note'),
		);

		$form['table_note']['table_note'] = array(
			'#markup' => t('All variables that have been imported using Shape, CSV or PX are displayed in the table above. Variables are grouped by Variable name and Spatial layer, therefore each row presents a group of all variables with the same name that may have multiple dates defined.
			Actions Delete selected, Publish selected on date and Unpublish selected will affect all variables that correspond to the selected row.')
		);

		$form['#attached']['library'][] = 'stage2_admin/variables';

		return $form;
	}

	public function validateForm(array &$form, FormStateInterface $form_state) {}


	public function submitForm(array &$form, FormStateInterface $form_state) {

		// Do something useful.
		$values = $form_state -> getValues();
		$trigger = $form_state -> getTriggeringElement()["#parents"][0];

		if($trigger == "publish"){

			$parameters_to_pass = array();
			$parameters_to_pass['selected'] = array_filter(array_values($values['table']));
			$parameters_to_pass['attribute'] = 'var_names_id';

			$url = \Drupal\Core\Url::fromRoute('stage2_admin.variablesPublish')
				->setRouteParameters(array('id'=>json_encode($parameters_to_pass)));
			$form_state->setRedirectUrl($url);

		}
		elseif($trigger =='unpublish'){
			$variables = array_filter(array_values($values['table']));
			StageDatabaseSM::unpublishVariablesvar_names_id($variables);
		}
		elseif($trigger == "delete"){
			$variables = array_filter(array_values($values['table']));
			$bla = $form_state->getValues();
			$url = \Drupal\Core\Url::fromRoute('stage2_admin.StageVariableDeleteForm')
			->setRouteParameters(array('id'=>json_encode($variables)));
			$form_state->setRedirectUrl($url);
		}

	}

	function ajax_spatial_layer_callback($form, $form_state) {
		return $form['table'];
	}
}
