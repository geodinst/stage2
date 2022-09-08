<?php

namespace Drupal\stage2_admin\Controller;

use Drupal\stage2_admin\StageSettings\database;
use Drupal\Core\Form\FormInterface;

class StageReportsController{

	function init(){

		$form['reports'] = array(
		  '#type' => 'details',
			'#prefix' => '<div id="batchimport_container">',
      '#suffix' => '</div>',
		  '#open' => FALSE,
		);
    //
		$form['reports']['#title'] = t('Log');
		$form['reports']['form1'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageReportsForm');

		$form['reports2'] = array(
			'#type' => 'details',
			'#prefix' => '<div id="batchimport_container">',
			'#suffix' => '</div>',
			'#open' => FALSE,
		);
		$form['reports2']['#title'] = t('Download log');
		$form['reports2']['form'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageReportsDownloadForm');

		return $form;

	}
}
