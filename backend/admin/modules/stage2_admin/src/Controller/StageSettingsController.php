<?php

namespace Drupal\stage2_admin\Controller;

use Drupal\stage2_admin\StageSettings\database;
use Drupal\Core\Form\FormInterface;

class StageSettingsController{

	public function realContent(){
		// Coordinate systems
		$form['coordinate_systems'] = array(
			'#type' => 'details',
			'#title' => t('Coordinate systems'),
			'#open' => false,
		);

		$form['coordinate_systems']['form'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageCoordinateSystemForm');


		// Spatial units
		$form['coordinate_spatial_units'] = array(
			'#type' => 'details',
			'#title' => t('Spatial units'),
			'#open' => false,
		);

		$form['coordinate_spatial_units']['form'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageCoordinateSpatialUnitsForm');

		$user = \Drupal::currentUser();
		if ($user->hasPermission('stage2_admin content_administrator')) {
			//Default variable parameters
			$form['default_variable_parameters'] = array(
				'#type' => 'details',
				'#title' => t('Default variable parameters'),
				'#open' => false,
			);

			$parameters_form = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageDefaultParametersForm');

			unset($parameters_form['manual_parameters']['param_input'][2]);
			unset($parameters_form['manual_parameters']['param_input'][3]);

			$form['default_variable_parameters']['form'] = $parameters_form;

			$form['default_variable_parameters']['table_note_param'] = array(
				'#type' => 'fieldset',
				'#title' => t('Note'),
			);

			$form['default_variable_parameters']['table_note_param'] ['table_note_param'] = array(
				'#markup' => t('The parameters set in this represent the predefined settings in the batch import form.</br>
				These values are also used as preferred parameters in the Menu tree editor - > default parameters. ')
			);

			//Tile layers
			$form['tile_layers'] = array(
				'#type' => 'details',
				'#title' => t('Tile layers'),
				'#open' => false,
			);

			$form['tile_layers']['form'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageTileLayersForm');

			// Appearance
			$form['appearance'] = array(
				'#type' => 'details',
				'#title' => t('Client appearance'),
				'#open' => false,
			);

			$form['appearance']['form'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageClientSettingsApearanceForm', false);

			// Languages
			$form['languages'] = array(
				'#type' => 'details',
				'#title' => t('Client labels'),
				'#open' => false,
				'#prefix' => '<div id="languages_container">',
				'#suffix' => '</div>',
			);

			$form['languages']['form'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageClientSettingsForm', false);

			// Landing page
			$form['landing_page'] = array(
				'#type' => 'details',
				'#title' => t('Landing pages'),
				'#open' => false,
				'#prefix' => '<div id="landing page">',
				'#suffix' => '</div>',
			);

			$form['landing_page']['form'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageLandingPageForm',false);
		}

		return $form;
	}
}
