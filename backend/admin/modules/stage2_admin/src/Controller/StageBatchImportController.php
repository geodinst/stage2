<?php

namespace Drupal\stage2_admin\Controller;

use Drupal\stage2_admin\StageSettings\database;
use Drupal\Core\Form\FormInterface;

class StageBatchImportController{
  
  private static function initForm(&$form){
    $form['batchimport'] = array(
		  '#type' => 'details',
			'#prefix' => '<div id="batchimport_container">',
      '#suffix' => '</div>',
		  '#open' => TRUE,
		);
  }
  
	function init(){
    self::initForm($form);
		$form['batchimport']['#title'] = t('Batch import of SHP or CSV files');
		$form['batchimport']['form'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StageBatchImportForm',false);
		return $form;
	}

  function initPX(){
    self::initForm($form);
    $form['batchimport']['#title'] = t('Batch import of PX files');
    $form['batchimport']['form'] = \Drupal::formBuilder()->getForm('Drupal\stage2_admin\Form\StagePxImportForm',false);
    return $form;
  }
}
