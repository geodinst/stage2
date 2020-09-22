<?php

namespace Drupal\stage2_admin\Controller;

use Drupal\stage2_admin\StageSettings\database;
use Drupal\Core\Form\FormInterface;
use Drupal\stage2_admin\StageDatabaseSM;

class StageGeospatialLayerEditController{

  /**
  * function initializes the geospatial layer form
  * @param  $id if -1 create new geospatial layer
  *            if positive value $id presents the id of the selected layer
  */
	function init($id=false){

    global $base_path;
  	$module_folder_path = $base_path . drupal_get_path('module', 'stage2_admin');

    // $form['loading'] = array(
    // 	'#type' => 'markup',
    // 	'#markup' => '<img id ="r_image" src="'.$module_folder_path.'/css/loading.gif" width="500px">'
    // );

    $form['geospatial_layer'] = array(
      '#type' => 'details',
      '#prefix' => '<div id="geospatial_layer_container">',
      '#suffix' => '</div>',
      '#open' => TRUE,
    );
    //
    // $pass = array();
    // $layer_parameters = array();
    switch($id){
      case '-1':
        $form['geospatial_layer']['#title'] = t('Create new geospatial layer');
        // $layer_parameters['edit_mode'] = false;
        $form['geospatial_layer']['form'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageGeospatialLayerEditForm',false);
        break;
      default:
    //
        $pass = StageDatabaseSM::stage2GetGeoLayerDeatils($id);
        $form['geospatial_layer']['#title'] = t('Geospatial layer edit mode');
        $form['geospatial_layer']['form'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageGeospatialLayerEditForm',$pass);
        // $layer_parameters['edit_mode'] = true;
        break;
    }

		return $form;
	}

}
