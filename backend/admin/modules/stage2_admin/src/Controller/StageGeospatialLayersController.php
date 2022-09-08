<?php

namespace Drupal\stage2_admin\Controller;

use Drupal\stage2_admin\StageSettings\database;
use Drupal\Core\Form\FormInterface;

class StageGeospatialLayersController{

	function init(){

		$form['form'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageGeospatialLayersForm');

	return $form;
	}
}
