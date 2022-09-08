<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_admin\StageFormsCommon;

class StageVariablesReportForm extends FormBase{

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID(){
    return 'stage_variables_report_form';
	}

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id=null) {
    $ds=StageFormsCommon::getValueDS($id);
    if ($ds===false){
      return $form;
    }
    StageFormsCommon::setReportMessages($ds);
    $valueMD=StageFormsCommon::getValueMD($ds);
    
    if ($valueMD===false) return $form;
    
    $report=StageFormsCommon::report($valueMD);
    drupal_set_message(t('Number of successfully joined rows:').' '.$report[0]['nrows']);
    drupal_set_message(t('Number of unpaired GEO reference IDs (statistical data):').' '.$report[1]['nrows']);
    drupal_set_message(t('Number of unpaired GEO reference IDs (geometry data):').' '.$report[2]['nrows']);
    /////
    
    $header = array(
      array('data' => $this->t('GEO reference ID')),
      array('data' => $this->t('Spatial feature name')),
      array('data' => $this->t('Variable value'))
    );
    $element=0;
    if ($report[$element]['nrows']>0){
      $form['success']['#markup'] = '<h3>'.t('Successfully joined rows').'</h3>';
      $form['success']['table'] = array(
        '#theme' => 'table',
        '#header' =>$header,
        '#rows' => $report[$element]['rows']
      );
      $form['success']['pager'] = array('#type' => 'pager','#element'=>$element);
    }
    
    
    $header = array(
      array('data' => $this->t('GEO reference ID')),
      array('data' => $this->t('Variable value')),
    );
    $element=2;
    if ($report[$element]['nrows']>0){
      $form['orphaned_sta']['#markup'] = '<h3>'.t('STAtistical data: Unpaired GEO reference IDs').'</h3>';
      $form['orphaned_sta']['table'] = array(
        '#theme' => 'table',
        '#header' =>$header,
        '#rows' => $report[$element]['rows']
      );
      $form['orphaned_sta']['pager'] = array('#type' => 'pager','#element'=>$element);
    }
    
    
    
    $header = array(
      array('data' => $this->t('GEO reference ID')),
      array('data' => $this->t('Spatial feature name')),
    );
    
    $element=1;
    if ($report[$element]['nrows']>0) {
      $form['orphaned_ge']['#markup'] = '<h3>'.t('GEospatial data: Unpaired GEO reference IDs').'</h3>';
      $form['orphaned_ge']['table'] = array(
        '#theme' => 'table',
        '#header' =>$header,
        '#rows' => $report[$element]['rows']
      );
      $form['orphaned_ge']['pager'] = array('#type' => 'pager','#element'=>$element);
    }
    
	  return $form;
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
