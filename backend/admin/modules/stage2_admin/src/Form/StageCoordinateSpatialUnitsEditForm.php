<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;

class StageCoordinateSpatialUnitsEditForm extends FormBase{

	private $id;

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_coordinate_spatial_units_edit_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

	$this->id = $id;
	$this->edit = ($id > -1)?true:false;

	if($this->edit){
		$entry = array("id" => $id);
		$default = StageDatabase::loadCoordinateSpatialUnits($entry);
	}

	$form['main'] = array(
		'#type' => 'details',
		'#title' => ($this->edit) ? $this->t('Edit spatial unit') : $this->t('Add new spatial unit'),
		'#open' => true,
	);

	$form['main'] ['name'] = array(
			'#type' => 'textfield',
			  '#title' => $this->t('Spatial unit name'),
			  '#size' => 30,
			  '#maxlength' => 30,
				'#required' => TRUE,
				'#default_value' => ($this->edit)?($default[0]->name):"",
			);

	$form['main'] ['weight'] = array(
			'#type' => 'number',
			  '#title' => $this->t('Spatial unit weight'),
			  '#size' => 5,
			  '#maxlength' => 5,
				'#required' => TRUE,
				'#default_value' => ($this->edit)?($default[0]->weight):"",
			);
	$form['main'] ['prefered_su'] = array(
			'#type' =>'checkbox',
			'#title' => t('Make this a prefered spatial unit'),
			'#default_value' => ($this->edit)?($default[0]->note_id):""
	);
	$form['main'] ['tsuv'] = array(
			'#type' =>'checkbox',
			'#title' => t('Make this a reference spatial unit'),
			'#default_value' => ($this->edit)?($default[0]->tsuv):""
	);

		$form['main']['add'] = array
		  (
			'#type' => 'submit',
			'#value' => t('Save'),
		  );

		$form['main']['cancel'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Cancel'),
				'#access' => TRUE,
				 '#limit_validation_errors' => array(),
				 '#submit' => array("::cancelFunction"),
			);

	  return $form;
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate the form values.
  }

  public function cancelFunction(array &$form, FormStateInterface $form_state){
	  $url = \Drupal\Core\Url::fromRoute('stage2_admin.realContent');
		$form_state->setRedirectUrl($url);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // check witch button was clicked
	$bla = $form_state->getTriggeringElement();
	$clicked = $bla["#parents"][0];

	$fs = $form_state->getValues();
	$prefered = $fs['prefered_su'];

	// set all note_id to 0 -> therefore no spatial unit is default
	// do it only, if current will be set as default
	if($prefered){
		StageDatabaseSM::stage_update_spatial_layer();
	}

	if($clicked=="add"){
		$name = $fs['name'];

		$entry = array(
		  'name' => $form_state->getValue('name'),
		  'weight' => $form_state->getValue('weight'),
		  'note_id' => $form_state->getValue('prefered_su'),
		  'tsuv' => $form_state->getValue('tsuv')
		);

		if($this->edit){
			$entry['id'] = $this->id;

			if (StageDatabaseSM::check_user_permissions_for_id('spatial units', $entry['id'])===false) {
				drupal_set_message('You are not allowed to save changes to spatial units added by other users.','warning');
				$form_state->setRebuild();
				return;
			}

			$count = StageDatabase::updateCoordinateSpatialUnit($entry);
			StageDatabaseSM::stageLog('spatial units','Spatial unit edited with id: '.$this->id);

		}else{
			$return = StageDatabase::addCoordinateSpatialUnit($entry);
			if ($return) {
			  drupal_set_message(t('Created entry @entry', array('@entry' => print_r($entry, TRUE))));
			}
			StageDatabaseSM::stageLog('spatial units','New spatial unit created',"{\"id\":\"$return\"}");
		}
	}

	// redirect
	$url = \Drupal\Core\Url::fromRoute('stage2_admin.realContent');
    $form_state->setRedirectUrl($url);

	return;
  }
}
