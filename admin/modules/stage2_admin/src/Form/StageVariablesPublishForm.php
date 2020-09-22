<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Datetime;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\stage2_admin\Form\StageVariablesEditRawForm;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;

class StageVariablesPublishForm extends FormBase{

	private $id;

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_variables_publish_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
	$this->id = $id;

	$form['base_conntainer'] = array(
		'#type' => 'details',
		'#title' => t('Publish options'),
		'#open' => TRUE,
	);
	
	$date = DrupalDateTime::createFromTimestamp(time());
    $datetime = $date->format('Y-m-d');
	
	$form['base_conntainer']['date'] = array(
                  '#type' => 'textfield',
                  '#size' => 10,
                  '#default_value' => $datetime,
				  '#title' => t('Publish selected on date'),
                  '#attributes' => array('class'=>array('date_var'),'title'=>t('The format of a date is YYYY-MM-DD.')),
                );
				
	$form['base_conntainer']['time'] = array(
                  '#type' => 'textfield',
                  '#size' => 5,
                  '#default_value' => "00:00",
				  '#title' => t('Publish selected on time'),
                  '#attributes' => array('class'=>array('time_var'),'title'=>t('The format of a time is HH:mm.'),),
                );

	// checkboxes ne rabimo
	// $form['base_conntainer']['publish_options'] = array(
	//   '#type' => 'checkboxes',
	//   '#options' => array('data' => $this->t('data'), 'prop' => $this->t('properties')),
	// 	'#default_value' => array('data', 'prop'),
	// 	'#disabled'=> true,
	//   '#title' => $this->t('What to publish?'),
	// );

	/* last submit */

	$form['save'] = array
		  (
			'#type' => 'submit',
			'#value' => t('Save'),
		  );

	$form['cancel'] = array
		  (
			'#type' => 'submit',
			'#value' => t('Cancel'),
		  );

	$form['#attached']['library'][] = 'stage2_admin/datepicker';
		 
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
    // get data
	// get values
	$values = $form_state->getValues();
	$publish_date = $values['date']." ".$values['time'];
	
	$variables = json_decode($this->id)->selected;
	$column = json_decode($this->id)->attribute;

	// submit buttons handler
	$bla = $form_state->getTriggeringElement();
	$option = $bla["#parents"][0];

	if($option == "save"){

		switch($column){
			case 'var_names_id';

				StageDatabaseSM::publish_status_update_layer_and_name($variables, $publish_date);
				// redirect
				$url = \Drupal\Core\Url::fromRoute('stage2_admin.variables');
				$form_state->setRedirectUrl($url);
				break;
			case 'id';
				StageDatabaseSM::publishstatusupdatevar($variables, $publish_date,'id');
				$var_names_id = json_decode($this->id)->var_names_id;
				$var_spatial_layer_id = json_decode($this->id)->var_spatial_layer_id;

				// redirect
				$url = \Drupal\Core\Url::fromRoute('stage2_admin.variablesEdit')
							->setRouteParameters(array('id_name'=>json_encode(intval ($var_names_id)),
																				 'id_spatial_layer'=>'1'	));

			 $push_parametrs =	json_encode(array(
															'id_name'=>$var_names_id,
															'id_spatial_layer'=>$var_spatial_layer_id,
														));
				$url = Url::fromUri('internal:/variables/edit/'.$push_parametrs);
				$form_state->setRedirectUrl($url);
				break;

		}
	}

  }
}
