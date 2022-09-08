<?php

namespace Drupal\stage2_admin\Controller;

use Drupal\stage2_admin\StageSettings\database;
use Drupal\Core\Form\FormInterface;

class StageVariablesUpdateController{
	
	function init(){
		
		$form['variables_update'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageVariablesUpdatePXForm');
			
		return $form;
	}
}