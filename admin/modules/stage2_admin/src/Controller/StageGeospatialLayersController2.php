<?php

namespace Drupal\stage2_admin\Controller;

use Drupal\stage2_admin\StageSettings\database;
use Drupal\Core\Form\FormInterface;

class StageGeospatialLayersController{
	
	function init(){
		
		$form['geospatial_layers'] = array(
		  '#type' => 'details',
		  '#title' => t('Geospatial layers'),
		  '#open' => TRUE,
		);
			
		return $form;
	}
}