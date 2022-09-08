<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Language;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\Core\Datetime\DrupalDateTime;

class StageTranslationsForm extends FormBase{

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_translations_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

		self::checkDuplicates();

		//Get default languages
		$language = \Drupal::service('language.default')->get();
		$default_language = $language->getId();

		// Get all languages available in the drupal translate module
		$language_all = \Drupal::languageManager()->getLanguages();
		// Languages that are registered in the drupal administrative pages
		$available_languages = array_keys($language_all);

		$translatable_languages = array_keys(array_diff_key($language_all,array($default_language=>1)));

	 // set base header elements
	 $header = [
	  // 'id' => 'id',
	   'group' => 'group',
	   'default' => $default_language,
	 ];
	 // add other available languages to header
	 $header= array_merge($header,$translatable_languages);

	// Get data (var_names) to populate translation table
	$que = db_select('s2.var_names', 'var_names');
	$que ->fields('var_names',['id','name']);
	$var_names_name = $que
		->execute()
		->fetchAll();

	// Get data (short_name) to populate translation table
	$que = db_select('s2.var_names', 'var_names');
	$que ->fields('var_names',['id','short_name']);
	$var_names_short_name = $que
		->execute()
		->fetchAll();

	// Get data (popup title) to populate translation table
	$que = db_select('s2.var_names', 'var_names');
	$que ->fields('var_names',['id','popup_title']);
	$var_popup_title = $que
		->execute()
		->fetchAll();

  // Get data (legend title) to populate translation table
	$que = db_select('s2.var_names', 'var_names');
	$que ->fields('var_names',['id','legend_title']);
	$var_legend_title = $que
		->execute()
		->fetchAll();

	// Get data (description) to populate translation table
	$que = db_select('s2.var_names', 'var_names');
	$que ->fields('var_names',['id','description']);
	$var_names_description = $que
		->execute()
		->fetchAll();

	// Get data (Spatial unit name) to populate translation table
	$que = db_select('s2.spatial_layer', 'spatial_layer');
	$que ->fields('spatial_layer',['id','name']);
	$spatial_layer_name = $que
		->execute()
		->fetchAll();

	// Get data (Spatial unit description associated with specific date) to populate translation table
	$que = db_select('s2.spatial_layer_date', 'spatial_layer_date');
	$que ->fields('spatial_layer_date',['id','description']);
	$spatial_layer_description = $que
		->execute()
		->fetchAll();

	// Get data (time dependent description) to populate translation table
	$que = db_select('s2.var_properties', 'var_prop');
	$que ->fields('var_prop',['id','data']);
	$td_desc_description = $que
		->execute()
		->fetchAll();

	// Get data (special values) to populate translation table
	$que = db_select('s2.special_values', 'sv');
	$que ->fields('sv',['id','special_value']);
	$special_values = $que
		->execute()
		->fetchAll();

	// Get data (legend caption of special values) to populate translation table
	$que = db_select('s2.special_values', 'sv');
	$que ->fields('sv',['id','legend_caption']);
	$legend_caption = $que
		->execute()
		->fetchAll();

		//********** Build options ******************
	$options = array();
	$list = array();
	foreach ($var_names_name as $row) { // add var_names name to options
		if($row->name <>'' && !in_array($row->name, $list)){
			$options['var_names_name_'.$row->id] = array(
				'id' => $row->id,
				'group' =>'var_names: Name',
				'table_name' =>'var_names',
				'column_name' =>'name',
				'name' =>'var_names_name',
				'default' => $row->name,
				'table_name' => 'var_names',
				'column_name' => 'name',
			);
			$list[] = $row->name;
		};
	}
	// foreach ($var_names_short_name as $row) { // add var_names short name to options
	// 	if($row->short_name <>''){
	// 		$options['var_names_short_name'.$row->id] = array(
	// 			'id' => $row->id,
	// 			'group' =>'var_names: Acronym',
	// 			'table_name' =>'var_names',
	// 			'column_name' =>'short_name',
	// 			'name' =>'var_names_short_name',
	// 			'default' => $row->short_name,
	// 			'table_name' => 'var_names',
	// 			'column_name' => 'short_name',
	// 		);
	// 	};
	// }
	foreach ($var_popup_title as $row) { // add var_names short name to options
		if($row->popup_title <>''){
			$options['var_names_short_name'.$row->id] = array(
				'id' => $row->id,
				'group' =>'var_names: popup_title',
				'table_name' =>'var_names',
				'column_name' =>'popup_title',
				'name' =>'popup_title',
				'default' => $row->popup_title,
				'table_name' => 'var_names',
				'column_name' => 'popup_title',
			);
		};
	}

  foreach ($var_legend_title as $row) { // add var_names short name to options
		if($row->legend_title <>''){
			$options['var_legend_title'.$row->id] = array(
				'id' => $row->id,
				'group' =>'var_names: legend_title',
				'table_name' =>'var_names',
				'column_name' =>'legend_title',
				'name' =>'legend_title',
				'default' => $row->legend_title,
				'table_name' => 'var_names',
				'column_name' => 'legend_title',
			);
		};
	}

	$list = array();
	foreach ($var_names_description as $row) { // add var_names description to options
		if($row->description <>'' && !in_array($row->description, $list)){

			$options['var_names_description'.$row->id] = array(
				'id' => $row->id,
				'group' =>'var_names: Description',
				'table_name' =>'var_names',
				'column_name' =>'description',
				'name' =>'var_names_description',
				'default' => $row->description,
				'table_name' => 'var_names',
				'column_name' => 'description',
			);
			$list[] = $row->description;
		};
	}
	$list = array();
	foreach ($spatial_layer_name as $row) { // add spatial_layer_name to options
		if($row->name <>'' && !in_array($row->name, $list)){
			$options['spatial_layer_name'.$row->id] = array(
				'id' => $row->id,
				'group' =>'spatial_layer: name',
				'table_name' =>'spatial_layer',
				'column_name' =>'name',
				'name' =>'spatial_layer_name',
				'default' => $row->name,
				'table_name' => 'spatial_layer',
				'column_name' => 'name',
			);
			$list[] = $row->name;
		};
	}
	// foreach ($spatial_layer_description as $row) { // add var_names description to options
	// 	if($row->description <>''){
	// 		$options['spatial_layer_description'.$row->id] = array(
	// 			'id' => $row->id,
	// 			'group' =>'spatial_layer: description',
	// 			'table_name' =>'spatial_layer',
	// 			'column_name' =>'description',
	// 			'name' =>'spatial_layer_description',
	// 			'default' => $row->description,
	// 			'table_name' => 'spatial_layer',
	// 			'column_name' => 'description',
	// 		);
	// 	};
	// }

	foreach ($td_desc_description as $row) { // add prop_description description to options
		if($row->data <>'' && json_decode($row->data)->description <>''){
			$options['time_dependent_description'.$row->id] = [
				'id' => $row->id,
				'group' =>'properties: time_dependent_description',
				'table_name' =>'var_properties',
				'column_name' => 'data',
				'name' =>'var_properties',
				'default' => json_decode($row->data)->description,
			];

		};
	}

	$list = array();
	foreach ($special_values as $row) { // add special_values to options
		if($row->special_value <>'' && !in_array($row->special_value, $list)){
			$options['special_values'.$row->id] = array(
				'id' => $row->id,
				'group' =>'special_values: value',
				'table_name' =>'special_values',
				'column_name' =>'special_value',
				'name' =>'special_values_value',
				'default' => $row->special_value,
			);
			$list[] = $row->special_value;
		};
	}
	$list = array();
	foreach ($legend_caption as $row) { // add legend captions of special values to options
		if($row->legend_caption <>'' && !in_array($row->legend_caption, $list)){
			$options['legend_caption'.$row->id] = array(
				'id' => $row->id,
				'group' =>'special_values: legend caption',
				'table_name' =>'special_values',
				'column_name' =>'legend caption',
				'name' =>'special_values_legend_caption',
				'default' => $row->legend_caption,
			);
			$list[] = $row->legend_caption;
		};
	}

	$filter = $form_state->getValue("filter");

	$groupFilterDataRaw = array();

	$potential_label_translations = array();
	// add imput fields for translations
	$rows = array();
	foreach ($options as $key => $value) {
		array_push($groupFilterDataRaw, $value['group']);
		foreach ($translatable_languages as $lang_key => $lang_id) {
			$existing_translation = StageDatabaseSM::stage2_user_translations(
			$value['table_name'],$value['column_name'],$value['id'],$lang_id);
			$translation_fields = array(
				$lang_key =>
				array(
					'data' => array(
						'#type' => 'textarea',
						'#value' => $existing_translation,
						// '#rows' => 5,
						'#cols' => 400,
						'#name' => $key.'_lang_'.$lang_id,
						'#id' => $key.'_lang_'.$lang_id
					)),
				);
			$value= array_merge($value,$translation_fields);

			// array of textarea names used in the submitt function
			$potential_label_translations[$key.'_lang_'.$lang_id] = array(
				'table_name'=>$value['table_name'],
				'column_name'=>$value['column_name'],
				'orig_id'=>$value['id'],
				'language_id'=>$lang_id,
				'translation'=>$existing_translation
			);
		}

		if($filter == 2){
			$i = 0;
			do{
				if(empty($value[$i]['data']['#value']))
				{
					$rows[] = $value;
					break;
				}
				$i++;
			}while(isset($value[$i]));
		}else{
			$rows[] = $value;
		}
	}

	// groupFilter
	$groupFilterData = array_unique($groupFilterDataRaw);
	array_unshift($groupFilterData, "All groups");

	$groupFilter = $form_state->getValue("groupFilter");
	if(isset($groupFilter) && $groupFilter > 0){
		foreach($rows as $value){
			if($value['group'] == $groupFilterData[$groupFilter]){
				$newRows[] = $value;
			}
		}
		$rows = $newRows;
	}


	$form_state->set('potential_label_translations', $potential_label_translations);

	$form['save_translations'] = array(
		'#type' => 'submit',
		'#name' => 'save_translations',
		'#value' => t('Save translations'),
		'#attributes' => [
			'style' => "float:left;margin-right:10px;",
		],

	);

	$form['filter'] = [
		 '#type' => 'select',
		'#options' => [
				'1' => $this->t('Filter: None'),
				'2' => $this->t('Filter: Untranslated'),
		],
		'#default_value' => 1,
		'#attributes' => [
			'style' => "float:left;margin-right:10px;",
		],
		 '#ajax' => array(
		  'callback' => '::ajax_filter_callback',
		  'wrapper' => 'translation_table',
		),
	];

	$form['groupFilter'] = [
		 '#type' => 'select',
		'#options' => $groupFilterData,
		'#default_value' => 0,
		'#attributes' => [
			'style' => "float:left;margin-right:10px;",
		],
		 '#ajax' => array(
		  'callback' => '::ajax_group_filter_callback',
		  'wrapper' => 'translation_table',
		),
	];

	//********** Render table **************
	 $form['table'] = array(
	   '#type' => 'tableselect',
	   '#header' => $header,
	   '#options' => $rows,
 		 //'#js_select' => false,
	   '#empty' => $this->t('Nothing to translate'),
	   '#id' => 'tableselect_id',
	   '#prefix' => '<div id="translation_table">',
		'#suffix' => '</div>',
	 );


		$form['#attached']['library'][] = 'stage2_admin/StageTranslationsForm';
		return $form;
	}

	function ajax_filter_callback($form, $form_state) {
	  return $form['table'];
	}

	function ajax_group_filter_callback($form, $form_state) {
	  return $form['table'];
	}

	public static function checkDuplicates() {
		$rows = db_query("SELECT distinct table_name, column_name from s2.translations")->fetchAll();

		$keys = [];
		$duplicates = [];

		foreach($rows as $row) {
			$subfield='';
			$row->column_name = str_replace(" ","_",$row->column_name);
			if ($row->table_name == 'var_properties') $subfield="->>'description'";
			$res = db_query("SELECT {$row->column_name}$subfield n, translation t from s2.{$row->table_name} tn,s2.translations trans WHERE 
			trans.orig_id = tn.id and trans.table_name = :table_name and trans.column_name=:column_name",
			[':table_name'=>$row->table_name, ':column_name'=>$row->column_name])->fetchAll();
			foreach($res as $r) {
				$key = $row->table_name.'-'.$row->column_name.'-'.$r->n;
				
				if (empty($keys[$key])) {
					$keys[$key] = $r->t;
				}
				else {
					if ($keys[$key] !== $r->t) {
						if (!$duplicates[$key]) {
							$duplicates[$key] = [$keys[$key]];
						}
						$duplicates[$key][] = $r->t;
					}
				}
			}
		}

		$duplicate_keys = array_unique(array_keys($duplicates));

		if (count($duplicate_keys)===0) return;

		foreach ($duplicates as $key => $duplicate) {
			drupal_set_message($key.'------------------------------------------------'.count($duplicate),'error');
			foreach($duplicate as $d) {
				drupal_set_message($key.' --- '.$d,'error');
			}
		}
	}

	public function submitForm(array &$form, FormStateInterface $form_state) {

		$input = &$form_state->getUserInput();
		$potential_label_translations = $form_state->get('potential_label_translations');

		foreach ($potential_label_translations as $key => $value) {
			// if ($input[$key]) <> ''){
			if (!empty ($input[$key]) && $input[$key]!=$value['translation']){
				
				$return = StageDatabaseSM::stage2_user_translations_update(
								$value['table_name'],
								$value['column_name'],
								$value['orig_id'],
								$value['language_id'],
								$input[$key]);

				$return2 = StageDatabaseSM::check_duplicate(
								$value['table_name'],
								$value['column_name'],
								$value['orig_id'],
								$value['language_id'],
								$input[$key]);

			}
		}
	}
}
