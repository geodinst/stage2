<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;

class StageCoordinateSystemEditForm extends FormBase{

	private $id;

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_coordinate_system_edit_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

	$this->id = $id;
	$this->edit = ($id > -1) ? true:false;

  $avalilable_existing_cs = StageDatabaseSM::stage_get_predefined_srid();
  $optional_cs = StageDatabaseSM::stage_generate_custom_srid_options();

	if($this->edit){
    $epsg_srid = array("id" => $id)['id'];
    $crs_details = StageDatabaseSM::stage_get_crs_details($epsg_srid);
    // get type
    $crs_details->type == 'predefined' ? $crs_input_type = 0 : $crs_input_type = 1;
    $entry = array("id" => $id);
		$default = StageDatabase::loadCoordinateSystems($entry);

    $avalilable_existing_cs = StageDatabaseSM::stage_get_predefined_srid(false);
    $optional_cs = StageDatabaseSM::stage_generate_custom_srid_options(false);
	}

  $form['main_container'] = array(
    '#type' => 'details',
    '#open' => true,
    '#title' => ($this->edit) ? $this->t('Edit projection') : $this->t('Add projection'),
  );
  // switchinput type
  $form['main_container']['imput_method'] = array(
    '#type' => 'radios',
    '#prefix' => '<div id="radios_input_method">',
    '#suffix' => '</div>',
    '#disabled' => isset($crs_details) ? true:false,
    '#default_value' => isset($crs_details) ? $crs_input_type : 0,
    '#options' => array(0 => $this->t('Predefined PosGIS projections'), 1 => $this->t('Manual projection')),
  );

  $form['main_container']['proj4text_crs'] = array(
    '#type' => 'textfield',
    '#title' => t('Coordinate system name'),
    '#description' => t('Name to be displayed in the administrative interface. The name does not effect the coordinate system function.'),
    '#size' => 40,
    '#maxlength' => 256,
    '#required' => TRUE,
    '#default_value' => isset($crs_details) ? $crs_details->proj4text : '',
  );
  /*  Display input method based on load method switch */
  $form['main_container']['predefined'] = array(
    '#type' => 'container',
    '#title' => $this->t('Select predefined projection'),
    '#states' => array(
      'visible' => array(
        array(
          ':input[name="imput_method"]' => array('value' => 0),
        ),
      )
    )
  );
  $form['main_container']['new'] = array(
    '#type' => 'container',
    '#title' => $this->t('Available predefined projections'),
    '#states' => array(
      'visible' => array(
        array(
          ':input[name="imput_method"]' => array('value' => 1),
        ),
      )
    )
  );

  $form['main_container']['predefined']['epsg_srid_predefined'] = array(
    '#type' => 'select',
    // '#empty_value' => '--',
    '#required' => TRUE,
    '#title' => t('Coordinate system code EPSG SRID'),
    '#disabled' => isset($crs_details) ? true:false,
    '#default_value' => isset($crs_details) ? $crs_details->epsg_srid : array_values($avalilable_existing_cs)[0],
    '#description' => t('List of projections predefined by the PostGIS database extention.'),
    '#options' => $avalilable_existing_cs,
    '#ajax' => array(
      'callback' => '::stage_ii_update_predefined_projections_container_ajax_callback',
      'wrapper' =>  'predefined_projections_container'
    )
  );

  //*****************************************************

  $form['main_container']['predefined']['predefined_projections_container'] = array(
    '#prefix' => '<div id="predefined_projections_container">',
    '#suffix' => '</div>',
  );

  $fsv= isset($form_state) ? $form_state->getValues(): false;
  $epsg_srid_predefined = isset($fsv['epsg_srid_predefined']) ? $fsv['epsg_srid_predefined'] :array_values($avalilable_existing_cs)[0];
  $stage_get_crs_predefined_details = StageDatabaseSM::stage_get_crs_predefined_details($epsg_srid_predefined);

  $form['main_container']['predefined']['predefined_projections_container']['proj4text'] = array(
      '#type' => 'textarea',
        '#title' => $this->t('proj4text'),
        '#size' => 100,
        '#maxlength' => 2048,
        '#default_value' => $stage_get_crs_predefined_details['proj4text'],
        '#disabled' => true,
        '#access' => $stage_get_crs_predefined_details['proj4text']
      );

  // $form['main_container']['predefined']['predefined_projections_container']['srtext'] = array(
  //     '#type' => 'textarea',
  //       '#title' => $this->t('srtext'),
  //       '#size' => 100,
  //       '#maxlength' => 2048,
  //       '#default_value' => $stage_get_crs_predefined_details['srtext'],
  //       '#disabled' => true,
  //       '#access' => $stage_get_crs_predefined_details['srtext']
  //     );
  //*****************************************************

  $form['main_container']['new']['epsg_srid_manual'] = array(
    '#type' => 'select',
    '#empty_value' => '--',
    '#required' => TRUE,
    '#disabled' => isset($crs_details) ? true:false,
    '#title' => $this->t('Custom coordinate system code'),
    '#default_value' => isset($crs_details) ? $crs_details->epsg_srid : array_values($optional_cs)[0],
    '#options' => $optional_cs,
  );

	$form['main_container']['new']['proj4text_n'] = array(
			'#type' => 'textarea',
			  '#title' => $this->t('proj4text'),
			  '#size' => 100,
			  '#maxlength' => 2048,
        '#default_value' => isset($crs_details) && $crs_details->type == 'manual'? $crs_details->sr_proj4text : '',
			);

	// $form['main_container']['new']['srtext_n'] = array(
	// 		'#type' => 'textarea',
	// 		  '#title' => $this->t('srtext'),
	// 		  '#size' => 100,
	// 		  '#maxlength' => 2048,
  //       '#default_value' => isset($crs_details) && $crs_details->type == 'manual' ? $crs_details->sr_srtext : '',
	// 		);

		$form['main_container']['add'] = array
		  (
			'#type' => 'submit',
			'#value' => t('Save'),
		  );

		$form['main_container']['cancel'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Cancel'),
				'#access' => TRUE,
				 '#limit_validation_errors' => array(),
				 '#submit' => array("::cancelFunction"),
			);

    $form['main_container']['add_cs'] = array(
      '#type' => 'fieldset',
      '#title' => t('Note'),
    );
    $form['main_container']['add_cs']['add_cs_text'] = array(
      '#markup' => t('
			<u>Two types of projection settings are available:</u>
			<ol type="1">
			<li>
			Predefined PosGIS projections are projections that are already predefined in the PostGIS extension of the Postgres database which is used as core database in the aplication STAGE. Available predefined projections settings can be loaded via the selection of the EPSG SRID code.
			</li>
			<li>
				User can manually create new projection by entering the proj4text.
			</li>
			</ol>

			</br>
      <u>More info abaut GIS projections:</u></br>
      <li>
      <a href = "http://spatialreference.org/">Spatial reference</a><br>
      </li>
      <li>
       <a href = "http://docs.geoserver.org/latest/en/user/configuration/crshandling/index.html">GEO Server settings</a></br>
      </li>
      ')
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

	if($clicked=="add"){
		$bla = $form_state->getValues();

    $entry = array(
      'epsg_srid' =>  $form_state->getValue('name'),
      'proj4text_crs' =>  $form_state->getValue('proj4text_crs'),
    );

    switch ($bla['imput_method']){
      case 0:
      $entry['type'] = 'predefined';
      $entry['epsg_srid'] = $form_state->getValue('epsg_srid_predefined');
        break;

      case 1:
      $entry['type'] = 'manual';
      $entry['epsg_srid'] = $form_state->getValue('epsg_srid_manual');
      // $entry['srtext'] = $form_state->getValue('srtext_n');
      $entry['proj4text'] = $form_state->getValue('proj4text_n');
        break;
    }
		if($this->edit){

      if (StageDatabaseSM::check_user_permissions_for_id('coordinate systems', $entry['epsg_srid'], 'srid')===false) {
        drupal_set_message('You are not allowed to save changes to the CRS added by other users.','warning');
        $form_state->setRebuild();
        return;
      }

      $return = StageDatabaseSM::stage_edit_crs($entry);
      StageDatabaseSM::stageLog('coordinate systems','Coordinate system: '.$form_state->getValue('name').' modified.');
		}else{

		$return = StageDatabaseSM::stage_create_new_crs($entry);
		StageDatabaseSM::stageLog('coordinate systems','New coordinate system: '.$form_state->getValue('name').' created.',"{\"srid\":\"{$entry['epsg_srid']}\"}");
		}
	}

	// redirect
	$url = \Drupal\Core\Url::fromRoute('stage2_admin.realContent');
    $form_state->setRedirectUrl($url);

	return;
  }
  //*******Ajax return functions *************
	public function stage_ii_update_predefined_projections_container_ajax_callback(array &$form, FormStateInterface $form_state){
		return $form['main_container']['predefined']['predefined_projections_container'];
	}

}
