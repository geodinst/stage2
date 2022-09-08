<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\Core\Datetime\DrupalDateTime;

class StageClientSettingsForm extends FormBase{

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_client_settings_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

		//Get default languages
		$language = \Drupal::service('language.default')->get();
		$default_language = $language->getId();

		// Get all languages available in the drupal translate module
		$language_all = \Drupal::languageManager()->getLanguages();
		// Languages that are registered in the drupal administrative pages
		$available_languages = array_keys($language_all);

		$translatable_languages = array_keys(array_diff_key($language_all,array($default_language=>1)));

		$userRoles = \Drupal::currentUser()->getRoles();

		$form['save_translations'] = array(
			'#type' => 'submit',
			'#name' => 'save_translations',
			'#value' => t('Save translations'),
		);
		$form['repopulate'] = array(
			'#type' => 'submit',
			'#value' => t('Reset'),
			'#access' => in_array("administrator", $userRoles),
			'#submit' => array('::stage2ResetTranslationSubmit'),
		);


	 // set base header elements
	 $header = [
	  	'id' => 'id',
	   	'id_cli' => 'id_cli',
	   	'default' => $default_language,
	 ];
	 // add other available languages to header
	 $header= array_merge($header,$translatable_languages);

	 //*********** Get existing data *******
	 // Get data (var_names) to populate translation table
	 $que = db_select('s2.var_labels', 'var_labels');
	 $que ->fields('var_labels',['id','id_cli','label','language','description']);
	 $all_labels = $que
		 ->execute()
		 ->fetchAll();

		// Build options table
		$options = array();
		foreach ($all_labels as $row) { // add var_names name to options
			if($row->language =='en'){
			$options['var_names_name_'.$row->id_cli] = array(
			 		'id' => $row->id,
					'id_cli' => $row->id_cli,
					'default' => $row->label,
				);
			}
		}

//***********************************************************************************************
$potential_label_translations = array();
// add imput fields for translations
$rows = array();
foreach ($options as $key => $value) {

	foreach ($translatable_languages as $lang_key => $lang_id) {
		$existing_translation = StageDatabaseSM::stage2_client_label_translations(
						$value['id_cli'],$lang_id);

		$translation_fields = array(
			$lang_key =>
			array(
				'data' => array(
					'#type' => 'textarea',
					'#value' => $existing_translation,
					'#rows' => 1,
					'#name' => $key.'_lang_'.$lang_id,
					'#id' => $key.'_lang_'.$lang_id
				)),
			);
		$value= array_merge($value,$translation_fields);

		// array of textarea names used in the submitt function
		$potential_label_translations[$key.'_lang_'.$lang_id] = array(
			'id_cli'=>$value['id_cli'],
			'language_id'=>$lang_id,
		);
	}
	$rows[] = $value;
}

$form_state->set('potential_label_translations', $potential_label_translations);
//***********************************************************************************************

	 //********** Render table **************
 	 $form['table'] = array(
 	   '#type' => 'tableselect',
 	   '#header' => $header,
 	  	'#options' => $rows,
 	   '#empty' => $this->t('Nothing to translate'),
 	   '#id' => 'tableselect_id',
 	 );


 		$form['#attached']['library'][] = 'stage2_admin/client_translations';

		return $form;
	}


	public function submitForm(array &$form, FormStateInterface $form_state) {

		$input = &$form_state->getUserInput();
		$potetntial_translations = $form_state->get('potential_label_translations');

		foreach ($potetntial_translations as $key => $value) {
			// if ($input[$key]) <> ''){
			if (!empty ($input[$key])){
			$return = StageDatabaseSM::stage2_client_label_translations_update(
			 					$value['id_cli'],
			 					$value['language_id'],
								$input[$key]
				);

			}
		}

	}
	public function stage2ResetTranslationSubmit(array &$form, FormStateInterface $form_state) {
		StageDatabaseSM::stageResetClientLabels();
		drupal_set_message('Client labels were reset.');
		$form_state->setRebuild();
	}
}
