<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\Core\Datetime\DrupalDateTime;

class StageReportsForm extends FormBase{

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
			'user' => 		array('data' =>t('User'),			'field' => 'user'),
			'action' => array('data' =>t('Action'),	'field' => 'action'),
			'report' => array('data' =>t('Report'),	'field' => 'report'),
			'modified' => 	array('data' =>t('Time'),	'field' => 'modified'),
	  );

		// Get data to populate log table
		$que = db_select('s2.log', 'log')
			-> extend('Drupal\Core\Database\Query\TableSortExtender')
			-> extend('Drupal\Core\Database\Query\PagerSelectExtender');
		// $que ->join('s2.spatial_layer', 'sl', 'sld.spatial_layer_id = sl.id');
		$que ->fields('log',['user','action','report','modified']);

		$log_entries = $que
			->orderByHeader($header)
			->execute()
			->fetchAll();

	$options = array();
	foreach ($log_entries as $row) {

		$account = \Drupal\user\Entity\User::load($row->user); // pass your uid
		$name = $account->getUsername();
		 $options[] = array(
			 'user' => $name,
			 'action' => $row->action,
			 'report' => $row->report,
			 'modified' => $row->modified
		 );
	}


	$form['import_filter_table'] = array(
		'#type' => 'table',
		'#header' => $header,
		'#rows' => $options,//array(array('Admin','new coordinate system', 'New coordinate system named Gauss-Kruger – Slovenija created. ' ,'15. 12. 2016, 17:53')),
		'#empty' => t('NA')
	);
		$form['pager'] = array('#type' => 'pager');
		return $form;
	}

	public function submitForm(array &$form, FormStateInterface $form_state) {

	}
}
