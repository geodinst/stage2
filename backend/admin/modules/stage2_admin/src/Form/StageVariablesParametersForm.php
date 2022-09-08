<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Datetime;
use Drupal\stage2_admin\Form\StageVariablesEditRawForm;
use Drupal\stage2_admin\StageDatabase;

class StageVariablesParametersForm extends FormBase{

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
		
	// variable parameters
	
	$form['manual_parameters'] = StageVariablesEditRawForm::getRawForm();
	
	$form['manual_parameters']['#type'] = 'fieldset';
	$form['manual_parameters']['#title'] = $this->t('Set manual parameters');
		
	/* last submit */
	
	$form['add'] = array
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
	
	if($id == "add"){
		
		// add shapefile
		
	}elseif($id == "upload"){
	
		// upload shapefile
		
	}
	
	// get values
	$bla = $form_state->getValues();
	$name = $bla['name'];
		
	// redirect	
	$url = \Drupal\Core\Url::fromRoute('stage2_admin.realContent');
    $form_state->setRedirectUrl($url);	
		
	return;
  }
}