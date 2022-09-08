<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

class StageCoordinateSystemAddForm extends FormBase{

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_coordinate_system_add_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

	$form['#title'] = $this->t('Add new coordinate system');

	// $form['name'] = array(
	// 		'#type' => 'textfield',
	// 		  '#title' => $this->t('Coordinate system name'),
	// 		  '#size' => 60,
	// 		  '#maxlength' => 128,
	// 			'#required' => TRUE,
  //       '#description' =>  'The coordinate reference systems code.'
	// 		);

	$form['definition'] = array(
			'#type' => 'textfield',
			  '#title' => $this->t('Coordinate system definition'),
			  '#size' => 100,
			  '#maxlength' => 256,
				'#required' => TRUE,
			);

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
    // Do something useful.
	$bla = $form_state->getValues();
	$name = $bla['name'];

	// redirect
	$url = \Drupal\Core\Url::fromRoute('stage2_admin.realContent');
    $form_state->setRedirectUrl($url);

	return;
  }
}
