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

class StageVariableDatesList extends FormBase{

	public $variables;
	public $selected_layer;
	public $time_periods = array();
	public $time_table_options = array();

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_variable_dates_list';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
		
		$input_data = json_decode($id);
		$id_name = $input_data->id_name;
		$id_spatial_layer = $input_data->id_spatial_layer;
	
		// get variable name, acrony amd description
		$variable_name = StageDatabase::getVariableName(array("id" => $id_name));
		$tree_menu=StageFormsCommon::treeStructure();
		
		// Get data to populate tableselect
		$variables = StageDatabaseSM::stage_get_variables_by_id_and_layer($id_name,$id_spatial_layer);
		
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
		}
		
		drupal_set_message('Variable name: '.$tree_menu[$id_name]['path']);
		drupal_set_message('Variable acronym: '.$variable_name[0]->short_name);
		drupal_set_message('Spatial layer name: '.$var->spatial_layer_name);
		
		
		$header = array(
			'date_column' =>t('Valid from'),
			'status' =>t('Status'),
			'id' =>t('id'),
		);
	
		$form['variable']['time_periods']['table_select'] = array(
			'#type' => 'tableselect',
			'#js_select' => false,
			'#header' => $header,
			'#options'=>$time_table_options,
			'#empty' => t('No time periods available'),
		);
		
		return $form;
	}

	public function submitForm(array &$form, FormStateInterface $form_state) {
		
	}
}