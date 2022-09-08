<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\stage2_admin\StageFormsCommon;

class StageReportsDownloadForm extends FormBase{

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_reports_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {


		$header = array(
			'var_name'   =>  array('data' =>t('Variable name'),	'field' => 'var_values_id' ),
			'ip'   =>        array('data' =>t('IP'),	           'field' => 'ip'),
			'time'   =>      array('data' =>t('Time'),	          'field' => 'time'),
      'var_values_id'    => 		array('data' =>t('Variable values ID'),		 'field' => 'var_values_id'),
	  );

		// Get data to populate log table
		$que = db_select('s2.var_download', 'dl')
			-> extend('Drupal\Core\Database\Query\TableSortExtender')
			-> extend('Drupal\Core\Database\Query\PagerSelectExtender');

    $que  -> join('s2.var_values', 'var_values', 'var_values.id = dl.var_values_id');
		$que ->fields('dl',['var_values_id','ip','time']);
		$que ->fields('var_values',['var_names_id']);

		$log_entries = $que
			->orderByHeader($header)
			->execute()
			->fetchAll();

	$options = array();
	$tree_menu=StageFormsCommon::treeStructure();
	foreach ($log_entries as $row) {

		 $options[] = array(
       'var_name' => isset($tree_menu[$row->var_names_id]['path']) ? $tree_menu[$row->var_names_id]['path']: t('[The menu tree entry removed]'),
       'ip' => $row->ip,
       'time' => $row->time,
       'var_values_id' => $row->var_values_id,

		 );
	}


	$form['import_filter_table'] = array(
		'#type' => 'table',
		'#header' => $header,
		'#rows' => $options,//array(array('Admin','new coordinate system', 'New coordinate system named Gauss-Kruger – Slovenija created. ' ,'15. 12. 2016, 17:53')),
		'#empty' => t('NA')
	);
		$form['pager'] = array('#type' => 'pager','#element'=>1);
		return $form;
	}

	public function submitForm(array &$form, FormStateInterface $form_state) {

	}
}
