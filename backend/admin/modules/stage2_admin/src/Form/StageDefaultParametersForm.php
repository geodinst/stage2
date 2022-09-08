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

class StageDefaultParametersForm extends FormBase{


	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_default_parameters_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

	// variable parameters

	// get current default id
	$did = StageDatabase::getDefaultVariableProperties();
	//error_log(print_r($did,true));

	$defid = isset($did[0])?$did[0]->id:NULL;
	// get raw form
	$form['manual_parameters'] = StageVariablesEditRawForm::getRawForm($form_state, $defid);

	$form['manual_parameters']['#type'] = 'container';
	$form['manual_parameters']['#title'] = $this->t('Set parameters');
	$form['manual_parameters']['#open'] = TRUE;
	$form['manual_parameters']['#tree'] = TRUE;

	/* last submit */

	$form['save'] = array
		  (
			'#type' => 'submit',
			'#value' => t('Save'),
		  );

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
	$id = $bla["#parents"][0];

	if($id == "save"){
		// save tree
		// get values
		$values = $form_state->getValues();
		$inputs = $form_state->getUserInput();
		$name = $values['manual_parameters'];
		//$selected = array_filter(array_values($values['table']));
		//error_log(print_r($name,true));
		$propId = \Drupal\stage2_admin\Form\StageVariablesEditRawForm::saveParameters($form, $form_state);

		StageDatabase::setDefaultProperties($propId);
		StageDatabaseSM::stageLog('default parameters','Default parameters set to id: '.$propId);

	}

	drupal_set_message(t('Default parameters saved', array()));

	return;
  }
}
