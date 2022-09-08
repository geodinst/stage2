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
use Drupal\user\Entity;

use Drupal\stage2_admin\BackgroundProcess;

class StageAdvancedSettingsForm extends FormBase{

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_advanced_settings_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

		$account = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
		// $uid= $account->get('uid')->value;
    $roles = $account->getRoles();

		// drupal_set_message('in production restrict restrict access','warning');
		// TO DO LIMIT ACCESS
    $form['advanced_container'] = array(
  		'#type' => 'details',
  		'#title' => t('Advanced settings summary'),
  		'#open' => false,
  		'#prefix' => '<div id="languages_container">',
  		'#suffix' => '</div>',
  	);

		$setting_names = StageDatabaseSM::stage_get_available_adv_settings_names();
		$setting_names[-1] = '<-> new <->';
		$data = StageDatabaseSM::stage_get_advanced_settings();

		$form['advanced_container']['setting_name'] = array(
			'#type' => 'select',
			'#empty_value' => '--',
			'#title' => t('Setting name'),
			'#options' => $setting_names,
			'#description' => t('if new is selected <-> new <-> advanced setting record will be created'),
			'#required' => true,
			'#ajax' => array(
				'callback' => '::stage_ii_update_setting_container_ajax_callback',
				'wrapper' =>  'individual_setting'
			)

		);

		$form['advanced_container']['update']=array(
			'#type' => 'container',
			'#prefix' => '<div id="individual_setting">',
			'#suffix' => '</div>',
		);

		$fsv= isset($form_state) ? $form_state->getValues(): false;

		$value_data = isset($fsv['setting_name']) ? $data[$fsv['setting_name']]->value:false;
		$encoded_value_data = json_decode($value_data,true);
		$prety_print_value =  json_encode($encoded_value_data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);

		$form['advanced_container']['update']['name']= array(
			'#type' => 'textfield',
			'#description' => t('set or update setting name'),
			'#value'=> isset($fsv['setting_name']) ? $data[$fsv['setting_name']]->setting: '',
		);
		$form['advanced_container']['update']['description_val']= array(
			'#type' => 'textfield',
			'#description' => t('set or update setting description'),
			'#value'=> isset($fsv['setting_name']) ? $data[$fsv['setting_name']]->description: '',
		);

		$form['advanced_container']['update']['value'] = array(
			'#type' => 'textarea',
			'#description' => t('set or update setting name'),
			'#value'=> isset($fsv['setting_name']) ? $prety_print_value: '',

		);
		$form['advanced_container']['update']['save'] = array(
			'#type' => 'submit',
			'#name' => 'save_adv_setting',
			'#value' => t('Save'),
		);
		$form['advanced_container']['update']['reset'] = array(
			'#type' => 'submit',
			'#name' => 'reset_adv_setting',
			'#value' => t('Reset advanced settings'),
			'#limit_validation_errors' => array(),
			'#submit' => array('::reset_adv_setting'),
		);


		$header = array(
			'setting' => 			array('data' =>t('Setting'),			'field' => 'setting'),
			'value' => 				array('data' =>t('Value'),				'field' => 'value'),
			'description' => 	array('data' =>t('Description'),	'field' => 'description'),
			'access' => 			array('data' =>t('Access'),				'field' => 'access'),
		);

	// Get data to populate log table
	$que = db_select('s2.advanced_settings', 'set')
				->extend('Drupal\Core\Database\Query\TableSortExtender')
				->extend('Drupal\Core\Database\Query\PagerSelectExtender');
	$que 	->fields('set',['id','setting','value','description','access']);
	$entries = $que
		->orderByHeader($header)
		->execute()
		->fetchAll();


	$options = array();
	foreach ($entries as $row) {
		 $options[$row->id] = array(
			 'setting' => $row->setting,
			 'value' => $row->value,
			 'description' => $row->description,
			 'access' => $row->access
		 );
	}

	$form['advanced_container']['settings'] = array(
		'#type' => 'table',
		'#header' => $header,
		'#rows' => $options,
		'#empty' => t('NA')
	);
		$form['advanced_container']['pager'] = array('#type' => 'pager');

    $form['locale'] = array(
      '#type' => 'details',
      '#open' => false,
      '#title' => $this
      ->t('Locale'),
    );
    $client_path = StageDatabase::getAdvancedSettings(array("setting"=>"client_path"));
    if (json_decode($client_path[0]->value, true)["base"]=="") {
      drupal_set_message('Client path cannot be an empty string', 'warning');
    }
    else{

      $locale_path = json_decode($client_path[0]->value, true)["base"].'locale.json';
      $this->locale_path = $locale_path;
      $content = file_get_contents($locale_path);
    }
    $form['locale']['locale_val'] = array(
      '#type' => 'textarea',
      '#description' => t('locale.json'),
      '#value'=> $content,
    );
    $form['locale']['save_locale'] = array(
      '#type' => 'submit',
      '#name' => 'save_locale',
      '#value' => t('Save'),
      '#limit_validation_errors' => array(),
      '#submit' => array('::save_locale'),
    );
    $form['locale']['reset_locale'] = array(
      '#type' => 'submit',
      '#name' => 'reset_locale',
      '#value' => t('Reset locale'),
      '#limit_validation_errors' => array(),
      '#submit' => array('::reset_locale'),
    );

    $instanceName=StageFormsCommon::getInstanceName();
    
    if ($instanceName != 'stage2_test'){
      $form['flush'] = array(
        '#type' => 'details',
        '#open' => false,
        '#title' => $this
        ->t('Flush test environment'),
      );
      
      $pid=db_query("SELECT value from s2.advanced_settings where setting='cloning'")->fetchField();
      
      $form['flush']['flush test environment'] = array(
        '#type' => 'submit',
        '#name' => 'save_locale',
        '#value' => BackgroundProcess::is_process_running($pid)?t('Test database cloning in progress - click to reinitiate process'):t('Clone main database to test database'),
        '#limit_validation_errors' => array(),
        '#submit' => array('::flush'),
      );
    }
    
		return $form;
	}


	//********* Submit functions *******
	public function submitForm(array &$form, FormStateInterface $form_state) {

		$fsv = $form_state->getUserInput();
		// validate json
		$ob = json_decode($fsv['value']);
		if($ob === null) {
		drupal_set_message('Json is not valid','error');
		 // $ob is null because the json cannot be decoded
	 }else{
		 $report = StageDatabaseSM::stage_update_advanced_setting($fsv['setting_name'],$fsv['name'],$fsv['value'],$fsv['description_val']);
		 // StageDatabaseSM::stageLog('Advanced setting update',$report);
	 }

	}

	public function flush(array &$form, FormStateInterface $form_state) {
    $url = \Drupal\Core\Url::fromRoute('stage2_admin.stageflushconfirmform');
    $form_state->setRedirectUrl($url);
	}
	public function reset_adv_setting(array &$form, FormStateInterface $form_state) {
		StageDatabaseSM::stage_reset_advanced_settings();
	}
	public function reset_locale(array &$form, FormStateInterface $form_state) {

    $client_path = StageDatabase::getAdvancedSettings(array("setting"=>"client_path"));
    $locale_path = json_decode($client_path[0]->value, true)["base"].'default_locale.json';
    $def = file_get_contents($locale_path);
    $fsv = $form_state->getUserInput();
    file_put_contents($this->locale_path, $def);

	}
	public function save_locale(array &$form, FormStateInterface $form_state) {

    $fsv = $form_state->getUserInput();
    file_put_contents($this->locale_path, $fsv['locale_val']);

	}
	//*******Ajax return functions *************
	public function stage_ii_update_setting_container_ajax_callback(array &$form, FormStateInterface $form_state){
		return $form['advanced_container']['update'];
	}
}
