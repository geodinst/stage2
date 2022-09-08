<?php

namespace Drupal\stage2_admin;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_admin\Form\StageVariablesEditRawForm;
use Drupal\Core\Database\Database;

class StageFormsCommon {

  private static $encodings;

  public static function form_unset_errors($form_state, $names) {
    $errors = $form_state->getErrors();

    foreach($names as $name){
      if (array_key_exists($name,$errors)) {
        unset($errors[$name]);
      }
    }

    $form_state->clearErrors();

    foreach($errors as $name=>$err){
      $form_state->setErrorByName($name,$err);
    }
  }
  
  public static function selectSpatialUnit(&$form){
    $available_su = StageDatabaseSM::stage2GetAvailableSpatialUnits(); // load available spatial units
    $form['general']['spatial_layer_id'] = array(
    	'#type' => 'select',
      '#empty_value' => '--',
      '#required' => TRUE,
      '#description' => t('Code list can be modified under Settings > Spatial units.'),
    	'#title' => t('Spatial unit'),
    	'#options' => $available_su
    );
  }
  
  public static function getInstanceName(){
    return "stage2";
    $rootFolder=strrchr(DRUPAL_ROOT,'/');
    $instancePath=str_replace($rootFolder,'',DRUPAL_ROOT);
    return trim(strrchr($instancePath,'/'),'/');
  }

  public static function removeFileNamesFromList($form_state,$all_uploaded_files,$erroneous_file_names){
    $user_input=$form_state->getUserInput();
    $all_uploaded_files=array_values(array_diff($all_uploaded_files,$erroneous_file_names));
    $user_input['uploaded_files_names']=json_encode($all_uploaded_files);
    $form_state->setUserInput($user_input);
    $form_state->setRebuild();
    return $all_uploaded_files;
  }
  
  public static function getTableOptions(&$options,&$variables,&$tree_menu,&$downar,$link='/variables/edit'){
   // Loop throught variables that are in the database
   foreach ($variables as $key => $var) {
    $array_key = ($var->id_name).'_'.($var->id_spatial_layer);
    if (!array_key_exists($array_key,$options)){
       $push_parametrs =	json_encode(array(
        'id_name'=>$var->id_name,
        'id_spatial_layer'=>$var->id_spatial_layer,
       ));
       
       $row = array(
        'name'	=>	Link::fromTextAndUrl($tree_menu[$var->id_name]['path'], Url::fromUri('internal:'.$link.'/'.$push_parametrs)),
        'short_name' => $var->names_hort_name <> '' ? $var->names_hort_name:'',
        'published' => StageDatabaseSM::get_publish_status($var->id_name,$var->id_spatial_layer),
        'counter' => empty($downar)? 0 : (isset($downar[$var->id_name][$var->id_spatial_layer])?$downar[$var->id_name][$var->id_spatial_layer]:'0'),
        'layer' => $var->spatial_layer_name,
        '#attributes' => array('title' => array($tree_menu[$var->id_name]['path'])),
       );
       
       $options[$array_key] = $row;
    }
   }
  }

  public static function parametersSection(&$form,$form_state){
    $form['data_container']['parameters_details'] = array(
   '#type' => 'details',
   '#title' => t('Parameters'),
   '#open' => true,
   '#prefix' => '<div id="parameters_details_container">',
   '#suffix' => '</div>',
   '#access' => isset($form_state->getValues()['display_table']) && $form_state->getValues()['display_table'] ? $form_state->getValues()['display_table'] : false
 );

	 $form['data_container']['parameters_details']['parameters'] = array(
		 '#type' => 'radios',
		 '#title' => t('Parameters'),
		 '#default_value' => -1,
		 '#options' => array(-1=> t('Automatically set parameters'), 0 => t('Default parameters'), 1 => t('Load existing parameters')),
	 );

	 $form['data_container']['parameters_details']['table_note_param'] = array(
		 '#type' => 'fieldset',
		 '#title' => t('Note'),
	 );
	 $form['data_container']['parameters_details']['table_note_param'] = array(
		 '#markup' => t('When applying the option Automatically set parameters: each variable will set with the parameters of the variable with the same name in the same spatial unit with the nearest date. If such a variable does not exist default parameters will be set.')
	 );

	 /*
	 existing parameters
	 */

	 $form['existing_parameters'] = array(
		 '#type' => 'fieldset',
		 '#title' => t('Load existing parameters'),
     '#access' => isset($form_state->getValues()['display_table']) && $form_state->getValues()['display_table'] ? $form_state->getValues()['display_table'] : false,
		 '#states' => array(
		 'visible' => array(
			 ':input[name="parameters"]' => array('value' => 1),
		 )
		 ),
		 '#tree' => TRUE,
	 );

	 // set variable options
	 $varOptions = array();
 	 $allVariables = StageDatabase::loadVariables();
	 foreach($allVariables as $var){
		 $varOptions[$var->id] = t($var->name);
	 }

	 $form['existing_parameters']['variable_name'] = array(
		 '#type' => 'select',
		 '#title' => t('Variable name'),
			 '#options' => $varOptions,
		);

		// set date
		$form['existing_parameters']['variable_year'] = array(
			'#type' => 'date',
		 '#title' => t('Date'),
		 '#default_value' => date("Y-m-d",strtotime('today')),
		);

    /*
		manual parameters
		*/
		// get current default id
		$did = StageDatabase::getDefaultVariableProperties();
		$defid = isset($did[0])?$did[0]->id:NULL;
		// Load default parameters to start with
		$form['manual_parameters'] = StageVariablesEditRawForm::getRawForm($form_state,$defid);
		$form['manual_parameters']['#type'] = 'fieldset';
	  $form['manual_parameters']['#title'] = t('Set manual parameters');
		$form['manual_parameters']['#states'] = array(
			'visible' => array(
				':input[name="parameters"]' => array('value' => 2),
			)
		);
		$form['manual_parameters']['#tree'] = TRUE;
    $form['manual_parameters']['#access']  = isset($form_state->getValues()['display_table']) && $form_state->getValues()['display_table'] ? $form_state->getValues()['display_table'] : false;
  }

	/**
  * @param  $var_names_id  the id from the table s2.var_names
	*/
  public static function getPropId(&$form,$form_state,$fsv, $valid_from =false, $spatial_layer_id=false, $var_names_id=false){
    // load default parameters just in case if something is not OK with the STAGEVariablesEditRawForm
      $propId = StageDatabaseSM::StageGetDefaultPropertiesid();
      switch($fsv['parameters']){
        case -1:
				if ($valid_from && $spatial_layer_id && $var_names_id){
					StageDatabaseSM::stage2AutoParameterSelection($valid_from, $spatial_layer_id, $var_names_id) ? ($propId = StageDatabaseSM::stage2AutoParameterSelection($valid_from, $spatial_layer_id, $var_names_id)) :false;
				}
        break;
        case 0:
        // do nothing default already selected
        break;
        case 1:
        $varId = $fsv['existing_parameters']['variable_name'];
        $date = $fsv['existing_parameters']['variable_year'];
        // get closest variable value parameters id
        $propId = StageDatabase::getClosestVariableValue($varId,$date)[0]->var_properties_id;
        break;
        case 2:
        $propId = StageVariablesEditRawForm::saveParameters($form, $form_state);
        break;
      }
      return $propId;
  }

  public static function variableInputValidation($form_state,$import_data){
    $input = $form_state->getUserInput();
    $fsv= $form_state->getValues();
    $selected = array_filter($fsv['import_filter_table']);

    foreach($selected as $key=>$value){
      if ($input['select_var_'.$value]==-1){
        $form_state->setErrorByName('var'.$key,"Variable tree item name for the variable {$import_data[$key]['header_id']['data']['#value']} is unset.");
      }
    }
  }

  public static function populateCheckBoxTable(&$table,$keys,$data){
    $i=1;
    foreach($data as $key=>$value){
      $table[$i]['checked'] = array('#type' => 'checkbox');
      foreach($keys as $k){
        $table[$i][$k] = $value[$k]['data'];
      }
      $i++;
    }
  }

  public static function validateImportFilterTable(&$form,$form_state){
    $fsv= $form_state->getValues();
    $variables = $fsv['import_filter_table'];

    $checked=false;
    foreach ($variables as $key=>$value){
      if ($value['checked']==1) {
        $checked=true;
        if ($value['variable_name']==-1){
          $form_state->setError($form['data_container']['import_filter']['import_filter_table'][$key]['variable_name'],
                                t('Variable name for column "'.$value['header_id'].'" is unset.'));
        }
      }
    }
    if (!$checked){
      $form_state->setErrorByName('import_filter', t('At least one variable has to be selected.'));
    }
  }

  private static function initEncodings(){
    if (empty(self::$encodings)){
      self::$encodings=mb_list_encodings();
      self::$encodings=array_diff(self::$encodings,['pass','auto']);
    }
  }

  public static function getEncodingOptionText($value){
    self::initEncodings();
    return self::$encodings[$value];
  }

  public static function getEncodingOptionValue($text){
    self::initEncodings();
    return array_search($text,self::$encodings);
  }

  public static function encodingSelect(&$a){
    self::initEncodings();
    $a=array(
    	'#type' => 'select',
      '#name' => 'select_encoding',
      '#default_value' => self::getEncodingOptionValue('UTF-8'),
      '#required' => TRUE,
      '#description' => t('Select character encoding of the uploaded files'),
    	'#title' => t('Character encoding'),
    	'#options' => self::$encodings
    );
  }
  
  /**
  * Function returns parents and path of every variable in menu tree
  * @param  $lang_code	Return paths in given language if translation exists
  */
  public static function treeStructure($lang_code = null){
	$tree_data = StageDatabase::LoadMenuTree(array(), true);
	// pridobi vse prevode "var_names" za podani jezik in sestavi array na osnovi tree_id (orig_id)
	$translations = ($lang_code != null)?StageDatabase::getTreeTranslation($lang_code):null;
	// v spodnji zanki preveri, če obstaja prevod, sestavi prevod
    $tree_menu=[];
		foreach($tree_data as $menu){
			$tree_id = $menu->id;
			$name = isset($translations[$menu->id])?$translations[$menu->id]['translation']:$menu->name;
			$parent = intval($menu->parent_id);

			while($tree_id != 0){
				$tree_menu[$menu->name_id]['parents'][] = $tree_id;
				$tree_menu[$menu->name_id]['path'] = $name.(isset($tree_menu[$menu->name_id]['path'])?$tree_menu[$menu->name_id]['path']:'');
				if($parent > 1){
					$tree_id = $tree_data[$parent]->id;
					$name = isset($translations[$tree_data[$parent]->id])?$translations[$tree_data[$parent]->id]['translation']:$tree_data[$parent]->name;
					$parent = intval($tree_data[$parent]->parent_id);
					$tree_menu[$menu->name_id]['path'] = " > ".$tree_menu[$menu->name_id]['path'];
				}else{
					$tree_id = 0;
				}
			}
		}

    return $tree_menu;
  }

  public static function getValueDS($id){
    $ds=db_query('select date(var_values.valid_from) as var_values_date,ispx,dsname,
                 table_name, col_names,georef,var_values.data as data,tname,
                 spatial_layer.name as slname,
                 date(sld.valid_from) as sld_valid_from,
                 var_values.var_names_id as var_names_id
                 from
                 s2.var_ds var_ds,
                 s2.var_values var_values,
                 s2.spatial_layer_date sld,
                 s2.spatial_layer as spatial_layer
                 where var_ds.id=var_values.var_ds_id and
                 var_values.spatial_layer_id=sld.spatial_layer_id and
                 var_values.id=:id and
                 spatial_layer.id=sld.spatial_layer_id and
                 var_values.valid_from >= sld.valid_from
                 order by sld.valid_from desc limit 1
                 ',array(':id'=>$id))->fetchObject();
    if (empty($ds)){
      $ds=db_query('select date(var_values.valid_from) as var_values_date,
                 spatial_layer.name as slname
                 from
                 s2.var_ds var_ds,
                 s2.var_values var_values,
                 s2.spatial_layer as spatial_layer
                 where var_ds.id=var_values.var_ds_id and
                 var_values.spatial_layer_id=spatial_layer.id and
                 var_values.id=:id',array(':id'=>$id))->fetchObject();

      drupal_set_message(self::mu(t('There are no spatial layer').' <b>"'.$ds->slname.'"</b> '.t('dates with the start date less or equal than the date of the variable').' (<b>'.$ds->var_values_date.'</b>).'),'warning');
      return false;
    }
    return $ds;
  }

  public static function mu($html){
    return \Drupal\Core\Render\Markup::create($html);
  }

  public static function getValueMD($ds){
    $ge_cnames=array_map('strtolower',json_decode($ds->col_names,true));
    $sta_georef=strtolower($ds->georef);
    $sta_cnames=json_decode($ds->data,true);
    $value_cname=strtolower($sta_cnames[0]);

    return (object)array('ge_tname'=>$ds->table_name,'ge_cnames'=>$ge_cnames,
                   'sta_tname'=>$ds->tname,'sta_georef'=>$sta_georef,'sta_value_cname'=>$value_cname,'ispx'=>$ds->ispx,'var_data'=>$sta_cnames);
  }

  public static function setReportMessages($ds,$echo=true){
    $tree_menu=StageFormsCommon::treeStructure();
    $report=[];
    $report['variable_name']=$tree_menu[$ds->var_names_id]['path'];
    $report['variable_date']=$ds->var_values_date;
    $report['geospat_name']=t($ds->slname?$ds->slname:"");
    $report['geospat_date']=$ds->sld_valid_from;
    $dsname=json_decode($ds->dsname);

    if ($ds->ispx){
      $report['pxurl']=$dsname->url;
    }

    if ($echo){
      drupal_set_message(t('Variable:').' '.$report['variable_name']);
      if (!empty($report['pxurl'])) drupal_set_message(t('PX URL:').' '.$report['pxurl']);
      drupal_set_message(t('Variable date:').' '.$report['variable_date']);
      drupal_set_message(\Drupal\Core\Render\Markup::create('<hr>'));
      drupal_set_message(t('Geospatial layer:').' '.$report['geospat_name']);
      drupal_set_message(t('Geospatial layer valid from date:').' '.$report['geospat_date']);
      drupal_set_message(\Drupal\Core\Render\Markup::create('<hr><hr>'));
    }
  }

  public static function validateEmptyAcronyms(&$form,$form_state,$available_variables){
    $fsv= $form_state->getValues();
    $variables = $fsv['import_filter_table'];

    foreach ($variables as $key=>$value){
      if ($value['checked']==1){

        if (empty($available_variables[$key]->short_name)){
          self::form_unset_errors($form_state,['import_filter_table]['.$key.'][acronym']);
          $form_state->setError($form['data_container']['import_filter']['import_filter_table'][$key]['acronym'],
                                t('The selected column acronym is empty.'));
        }
      }
    }
  }

  public static function shortReport($var_values_id){
    $ds=self::getValueDS($var_values_id);
    setReportMessages($ds,false);
  }

  public static function report($valueMD){
    $report=[];
    for ($i=0;$i<3;++$i){
      $report[]=['nrows'=>[],'rows'=>[]];
    }

    $ge_cnames=$valueMD->ge_cnames;
    $sta_georef=$valueMD->sta_georef;

    $cond=[];

    $element=0;
    $fields=['ge'=>$valueMD->ge_cnames,'sta'=>[$valueMD->sta_value_cname]];
    $report[$element]['nrows']=self::tableJoin($report[$element]['rows'],$valueMD,$fields,$element,array_merge($cond,['ge.'.$ge_cnames['geo_code']=>['is not',null],'sta.'.$sta_georef=>['is not',null]]));

    $element=1;
    $fields=['sta'=>[$valueMD->sta_georef,$valueMD->sta_value_cname]];
    $report[$element]['nrows']=self::tableJoin($report[$element]['rows'],$valueMD,$fields,$element,array_merge($cond,['ge.'.$ge_cnames['geo_code']=>['is',null]]));

    $element=2;
    $fields=['ge'=>$valueMD->ge_cnames];
    $report[$element]['nrows']=self::tableJoin($report[$element]['rows'],$valueMD,$fields,$element,array_merge($cond,['sta.'.$sta_georef=>['is',null]]));

    return $report;
  }

	private static function tableJoin(&$rows,$valueMD,$fields,$element,$conditions=array()) {
    $query=db_select("ge.{$valueMD->ge_tname}",'ge')
          ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->element($element)->limit(10);
    $query->addJoin('FULL OUTER', "sta.{$valueMD->sta_tname}",'sta',"ge.{$valueMD->ge_cnames['geo_code']}=sta.$valueMD->sta_georef");

    foreach($fields as $talias=>$field_name_array){
      $query->fields($talias,$field_name_array);
    }

    foreach ($conditions as $field => $value) {
		  $query->condition($field, $value[1],$value[0]);
		}

    $count=$query->countQuery()->execute()->fetchField();

    $result = $query->execute();
    $rows=$result->fetchAll(\PDO::FETCH_ASSOC);
    return $count;
  }

  public static function dsReport($var_ds_id,$variableCount,$variableValueCount){
    $result=db_query("SELECT array_agg(var_values_valid_from) as var_value_dates,array_agg(id) as var_value_ids,sld_valids_from[1] as sld_valid_from,sl_name from (
    SELECT date(var_values.valid_from) as var_values_valid_from,var_values.id,array_agg(sld.id order by sld.valid_from desc) as sld_ids,
    array_agg(date(sld.valid_from) order by sld.valid_from desc) as sld_valids_from,sl.name as sl_name
    from s2.var_values var_values,s2.spatial_layer_date sld, s2.spatial_layer sl where
    sl.id=sld.spatial_layer_id and
    var_values.spatial_layer_id=sld.spatial_layer_id and
    var_values.var_ds_id=:var_ds_id and
    var_values.valid_from>=sld.valid_from
    group by var_values.valid_from,var_values.id,sl.name)a group by sld_valids_from[1], sl_name",array(':var_ds_id'=>$var_ds_id));

    drupal_set_message(t("Number of imported variables:").' '.$variableCount);
    drupal_set_message(t("Number of imported variables by the date of variable:"));

    $variablesByDate=db_query("select date(valid_from) as valid_from,count(*) as c from s2.var_values where var_values.var_ds_id=:var_ds_id group by valid_from",array(':var_ds_id'=>$var_ds_id))->fetchAll();

    foreach($variablesByDate as $row){
      drupal_set_message(StageFormsCommon::mu('<b>'.$row->valid_from.'</b> '.$row->c.' '.t('variable(s)')));
    }

    drupal_set_message(StageFormsCommon::mu('<hr>'));
    drupal_set_message(StageFormsCommon::mu('<h2><b>Table join report</b></h2>'));

    $tableJoin=$result->fetchAll();

    drupal_set_message(StageFormsCommon::mu(t('Number of variable values:').' <b>'.$variableValueCount.'</b>.'));
    if (count($tableJoin)==0) {
      drupal_set_message(StageFormsCommon::mu('<h2><b>Table join report - warnings</b></h2>'),'warning');
      drupal_set_message(t('There are <b>no spatial layers</b> with the date equal or less then the date of variables.'),'warning');
    }
    else{
      drupal_set_message(StageFormsCommon::mu(t('Imported statistical variables are linked to the').'<b> "'.$tableJoin[0]->sl_name.'" </b>'.t('geospatial layer').'.'));
    }

    foreach($tableJoin as $inx=>$row){
      $ds=StageFormsCommon::getValueDS(explode(',',trim(trim($row->var_value_ids,'{'),'}'))[0]);
      if ($ds===false) continue;
      $valueMD=StageFormsCommon::getValueMD($ds);
      $report=StageFormsCommon::report($valueMD);

      $type='status';

      if ($report[1]['nrows']!=0 || $report[2]['nrows']!=0) {
        $type='warning';
        drupal_set_message(StageFormsCommon::mu('<h2><b>Table join report - warnings</b></h2>'),$type);
      }

      drupal_set_message(StageFormsCommon::mu(t('Variables with the date(s)').' <b>'.$row->var_value_dates.'</b> '.t('are linked to the geospatial layer valid from').' <b>'.$row->sld_valid_from.'</b>. '.t('Number of successfully joined rows:').' <b>'.$report[0]['nrows'].'</b>.'),$type);

      if ($report[1]['nrows']!=0 || $report[2]['nrows']!=0) {
        drupal_set_message(StageFormsCommon::mu(t('<span data-id="'.$row->sld_valid_from.'"></span>Number of unpaired GEO reference IDs (statistical data table):').' <b>'.$report[1]['nrows'].'</b>.'),$type);
        drupal_set_message(StageFormsCommon::mu(t('<span data-id="'.$row->sld_valid_from.'"></span>Number of unpaired GEO reference IDs (geometry data table):').' <b>'.$report[2]['nrows'].'</b>.'),$type);
      }
    }

  }
  
  /**
  * Function deletes file which has been automaticaly uploaded in process of geospatial layer or variables data import
  * @param $file_name	string		Name of file to delete
  * @param $url			boolean 	Path type; true - full path, false - file name only (optional, default false)
  */
  public static function deleteUploadedFile($file_name){
		$realpath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");		
		$path = $realpath.'\\temp_shp_uploads\\'.$file_name;
		if(file_exists($path)){
			unlink($path);
		}
  }

}

