<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_admin\StageFormsCommon;
use Drupal\stage2_admin\StageStatDataImporter;

class StagePXImportForm extends FormBase{
  protected $pxvars=array();
  protected $import_data=array();
  protected $px_url;
  protected $dates=array();
  protected $pg_date;
  protected $pg_geocode;
  protected $xheaders;
	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_px_import_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'stage2_admin/px_import';
    $form['#attached']['library'][] = 'stage2_admin/StageFormsCommon';

    $form['files'] = array(
			'#type' => 'fieldset'
		);

		$form['files']['px_url'] = array(
			'#type' =>'url',
			'#title'=> 'PX url',
      '#required' => TRUE,
			// '#default_value'=> 'http://pxweb.stat.si/pxweb/Database/Dem_soc/05_prebivalstvo/10_stevilo_preb/10_05C20_prebivalstvo_stat_regije/05C2002S.px',
      //'#default_value'=> 'http://localhost/work/stage2/data/05C2002S.px',
      //'#default_value'=> 'http://localhost/work/stage2/data/05C4002S.px'
		);

		$form['files']['get_px_headers'] = array(
			'#type' => 'submit',
			'#name' => 'get_px_headers',
			'#value' => t('Get PX headers'),
			'#submit' => array('::getPxHeaders'),
		);

    if (count($this->pxvars) > 0) {

      $form['general'] = array(
        '#type' => 'fieldset',
        '#prefix' => '<div id="general_container">',
        '#suffix' => '</div>',
      );

      StageFormsCommon::selectSpatialUnit($form);

      $form['data_container'] = array(
        '#type' => 'fieldset',
        '#prefix' => '<div id="data_container">',
        '#suffix' => '</div>',
      );

      $headers=array_keys($this->pxvars);

      $form['data_container']['variables'] = array(
          '#type' => 'container',
          '#prefix' => '<div id="variables_container">',
          '#suffix' => '</div>'
        );

      $form['data_container']['variables']['left_container'] = array(
        '#type' => 'container',
        '#prefix' => '<div id="variables_left_container">',
        '#suffix' => '</div>',
        '#attributes' => array('class' => array('element-column-left'))
      );
      $form['data_container']['variables']['right_container'] = array(
        '#type' => 'container',
        '#prefix' => '<div id="variables_right_container">',
        '#suffix' => '</div>',
        '#attributes' => array('class' => array('element-column-right'))
      );
      $form['data_container']['variables']['clear_left_2'] = array(
          '#type'=> 'container',
          '#attributes' => array('class' => array('element-clear-fix'))
      );

      $form['data_container']['variables']['left_container']['px_date'] = array(
        '#type' => 'select',
        '#empty_value' => '--',
        '#title' => $this->t('PX date variable'),
        '#required' => TRUE,
        '#description' => t('Select the variable which designates the date.'),
        '#options' => $headers
      );

      $form['data_container']['variables']['left_container']['px_geocode'] = array(
        '#type' => 'select',
        '#empty_value' => '--',
        '#required' => TRUE,
        '#title' => $this->t('PX GEO reference variable'),
        '#description' => t('Select the variable which will be used for joining the imported PX variables with the selected spatial unit.'),
        '#options' => $headers
      );

      $form['data_container']['variables']['left_container']['get_px_variables'] = array(
        '#type' => 'submit',
        '#name' => 'get_px_headers',
        '#value' => t('Get PX variables'),
        '#submit' => array('::getPxVariables'),
        '#validate' => array('::beforePxVariablesValidate')
      );

      $form['data_container']['variables']['right_container']['dates'] = array(
          '#type' => 'container',
          '#prefix' => '<div id="dates_container">',
          '#suffix' => '</div>'
      );

      $form_state->setValue('display_table',true);

      $form['data_container']['import_filter'] = array(
        '#type' => 'container',
        '#prefix' => '<div id="import_filter" class="twrapper">',
        '#suffix' => '</div>',
      );

      $header = array(
        'checked'=>array('#type' => 'checkbox'),
        'header_id' => $this->t('Column'),
        'variable_name' => $this->t('Variable name'),
        'acronym' => $this->t('Acronym')
      );

      $form['data_container']['import_filter']['import_filter_table'] = array(
        '#type' => 'table',
        '#header' => $header,
        '#empty' => t('NA')
      );

      array_shift($header);
      StageFormsCommon::populateCheckBoxTable($form['data_container']['import_filter']['import_filter_table'],
                                      array_keys($header),
                                      $this->import_data);

      if (count($this->import_data)>0){
        /* link px variables with acronyms */
        $form['#attached']['drupalSettings']['stage2_admin']['links'] = db_query('SELECT codes,acronym from s2.var_links')->fetchAll();

        $datesContainer=&$form['data_container']['variables']['right_container']['dates'];

        $datesContainer['check_all'] = array(
          '#type' => 'checkbox',
          '#id' => 'cb-check-all',
          '#title' => t('check all dates')
        );

        $datesContainer['cbd']=array(
            '#type' => 'container',
            '#id' => 'cb-dates',
            '#attributes' => array('style'=>"border:1px solid #ccc; height: 11em; overflow-y: scroll;",
                                   'title'=>t('The format of a date is YYYY-MM-DD.')
                                   )
        );

        $datesOptions=array();
        foreach ($this->dates as $dcode=>$dstr){
          $datesOptions[$dcode]=$dstr;
        }

        $datesContainer['cbd']['cb_dates']=array(
                  '#type' => 'checkboxes',
                  '#required'=>true,
                  '#options' => $datesOptions
        );

        StageFormsCommon::parametersSection($form,$form_state);
        //************** SUBMIT SECTION **************
        $form['save'] = array(
          '#type' => 'submit',
          // '#limit_validation_errors' => array(),
          '#value' => t('Save'),
          );
      }
    }

    $form['cancel'] = array(
      '#type' => 'link',
      '#title' => 'Cancel',
      '#attributes' => array(
        'class' => array('button'),
      ),
      '#url' => Url::fromRoute('stage2_admin.variables'),
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
		$fsv= $form_state->getValues();

    $variables = $fsv['import_filter_table'];
    $dates=array_filter($fsv['cb_dates']);
    if (count($dates)==0 || count($variables)==0) return;

    $headers=array_keys($this->pxvars);
    /*****************************
     Array
    (
        [0] => STATISTIČNA REGIJA //$this->pg_geocode: 0
        [1] => ŠTEVILO MOŠKIH
        [2] => ŠTEVILO ŽENSK
        [3] => LETO               //$this->pg_date: 3
        [4] => TIP GOSPODINJSTVA
    )
    ******************************/

    $dataImporter=new StageStatDataImporter($this->px_url,$headers); //saves the PX to the temporary data table
    $dataImporter->setPxCondition($headers,$this->pg_geocode,$this->pg_date); //prepares array template for selecting from temporary px data table

    $selectedVariables=[];
    $attr=[];

    $c=1;
    /***********************
     $dates: selected dates
     Array ( [2011H1] => 2011H1 [2011H2] => 2011H2 [2012H1] => 2012H1 )
    ********************
    $this->dates: all dates from the px file
    Array
    (
        [2011] => 2011-01-01
        [2015] => 2015-01-01
    )
    *********************
    $variables: all the variables
    ***********************/

    foreach($dates as $dcode){
      $date=$this->dates[$dcode];
      foreach ($variables as $key => $value) {
          if ($value['checked']!=1) continue;
          $selectedVariables[$key]=$value;
          $attr[]='v'.($c++);
      }
    }

    /*****************************
     $selectedVariables: selected subset of $variables
     [9] => Array
      (
          [checked] => 1
          [header_id] => Gospodinjstva - SKUPAJ | brez žensk | Enočlansko
          [variable_name] => 3
          [acronym] => 3
      )
     ******************************/

    $tcodes=array_map('key',$this->xheaders[$key-1]);
    /*******************************
     $tcodes: last xheader codes:
     e.g. last xheader
     [174] => Array
        (
            [0] => Array
                (
                    [4] => 3+ moški
                )

            [1] => Array
                (
                    [4] => 3+ ženske
                )

            [2] => Array
                (
                    [6] => Veččlansko dvo- ali večdružinsko razširjeno
                )

        )

     $tcodes: //has nothing to do with the selected values
     Array
      (
          [0] => 4
          [1] => 4
          [2] => 6
      )
     ******************************/

    $gcode='c'.$this->pg_geocode;
    $attr[]=$gcode;
    /**********************
     *dcode: last selected date code
    //dcode: 2015 gcode: c0
    **********************/
    /**********************************
     *prepares sta.tname table, $dcode is needed to form the query as the tname is created with "INSERT into (gcode) SELECT gcode from px_temp_table where $dcode=... ...
     *sta.tname table size: count(spatial units) X (count(selected variables) + 1(geo column) + 1(__gid))
     *********************************/
    /***
     *attributes:
    Array
    (
        [0] => v1 //consecutive number of selected variables
        [1] => c0 //geo column
    )
    *****/
    $tname=$dataImporter->prepareFilteredPxDataTable($attr,$tcodes,$dcode,$gcode);

    $var_ds_id=$dataImporter->saveDatasourceData($gcode,-1,json_encode(['url'=>$this->px_url,'pg_geocode'=>$this->pg_geocode,'pg_date'=>$this->pg_date,'headers'=>$headers]),$tname);

    $spatial_layer_id=$fsv['spatial_layer_id'];
    $tree_menu=StageFormsCommon::treeStructure();
    $c=1;
    foreach($dates as $dcode){
      $date=$this->dates[$dcode];

      foreach ($selectedVariables as $key => $value) {
        $tcodes=array_map('key',$this->xheaders[$key-1]);
        /**************************
         $tcodes: codes from the selected xheader
         e.g.
         selected xheader:
         [8] => Array
        (
            [0] => Array
                (
                    [99] => Gospodinjstva - SKUPAJ
                )

            [1] => Array
                (
                    [1] => brez žensk
                )

            [2] => Array
                (
                    [1] => Enočlansko
                )

        )

        results in the following tcodes:
        Array
        (
            [0] => 99
            [1] => 1
            [2] => 1
        )
         *************************/
        $variable = array();
        $variable['published']= 0;
        $cvname='v'.($c++);
        $dataImporter->updatePxDataTable($tname,$cvname,$tcodes,$dcode,$gcode); //update sta.tname table
        $variable['data']=json_encode([$cvname,$key-1]);
        $variable['var_ds_id'] = $var_ds_id;
        $variable['spatial_layer_id'] = $fsv['spatial_layer_id'];
        $variable['var_properties_id'] = StageFormsCommon::getPropId($form,$form_state,$fsv, $date,$spatial_layer_id,$value['variable_name']);
        $variable['var_names_id'] = $value['variable_name'];
        $variable['valid_from'] = $date;
        $var_values_add[] =  StageDatabaseSM::stageCreateVariable($variable,$tree_menu);
      }
    }

    $source=basename($this->px_url);
    foreach ($selectedVariables as $key => $value) {
				$tvalues=array_map('current',$this->xheaders[$key-1]);
        sort($tvalues);
        $scodes=implode("|",$tvalues);
        $acronym=$form['data_container']['import_filter']['import_filter_table'][1]['acronym']['#options'][$value['acronym']];
        db_query("DELETE from s2.var_links where acronym=:acronym or codes=:codes",[':acronym'=>$acronym, ':codes'=>$scodes]);
        db_query("INSERT into s2.var_links (codes,acronym) values (:codes,:acronym)",[':codes'=>$scodes,':acronym'=>$acronym]);
    }

    StageFormsCommon::dsReport($var_ds_id,count ($var_values_add),$dataImporter->getDataCount($tname));
    // Log that something has been changed in the DB
    StageDatabaseSM::stageLog('px import',count ($var_values_add).' variable(s) created.',json_encode(['ids'=>$var_values_add,
      'spatial_layer_id'=>$fsv['spatial_layer_id']
    ]));

    $url = Url::fromUri('internal:/variables');
    $form_state->setRedirectUrl($url);

	/**
	* WARNINGS FOR DUPLICATED INPUTS
	*/

		$sli= $fsv['spatial_layer_id'];
		$layerName = $form['general']['spatial_layer_id']['#options'][$sli];

		//duplicate acronym selection
		$acronyms = array();
		  foreach ($selectedVariables as $key=>$value){
			  if($value['acronym'] > -1){
				  if(!in_array($value['acronym'], $acronyms)){
					  array_push($acronyms, $value['acronym']);
				  }else{
					  $message = t('Acronym "@acronym" has been selected on multiple rows!',
						array('@acronym' => $form['data_container']['import_filter']['import_filter_table'][1]['acronym']['#options'][$value['acronym']]));
            drupal_set_message(StageFormsCommon::mu('<h2><b>'.t('Warnings for duplicated inputs').'</b></h2>'),'warning');
						drupal_set_message($message, 'warning');
				  }
			  }
		  }

		// duplicated on date check
		$result = StageDatabase::getDuplicateValuesOnDate($sli);

		// foreach($result as $value){
		// 	$message = t('There are multiple inputs saved for acronym "@acronym" on same date (@date) and same geospatial layer (@layer)!',
		// 			array('@acronym' => $value->short_name, '@date' => $value->valid_from, '@layer' => $layerName));
		// 	drupal_set_message($message, 'warning');
		// }
    //
		// // duplicated on year check
		// $resultY = StageDatabase::getDuplicateValuesOnYear($sli);
    //
		// foreach($resultY as $value){
		// 	$message = t('There are multiple inputs saved for acronym "@acronym" on same year (@date) and same geospatial layer (@layer)!',
		// 			array('@acronym' => $value->short_name, '@date' => $value->date_part, '@layer' => $layerName));
		// 	drupal_set_message($message, 'warning');
		// }
}

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $el=$form_state->getTriggeringElement();
    if ($el['#name']==='get_px_headers'){
      $urlElement=$form['files']['px_url'];
      $err=$form_state->getError($urlElement);
      $form_state->clearErrors();
      if (!empty($err)){
        $form_state->setError($urlElement,$err);
      }
    }
    else if ($el['#name']==='get_px_variables'){
        StageFormsCommon::form_unset_errors($form_state,['spatial_layer_id']);
    }
    else{
      $errors = $form_state->getErrors();
      if (array_key_exists('cb_dates',$errors)) {
        StageFormsCommon::form_unset_errors($form_state,['cb_dates']);
        $form_state->setErrorByName('cb_dates', t('At least one PX date has to be selected.'));
      }

      StageFormsCommon::validateImportFilterTable($form,$form_state);
    }
  }

  public function beforePxVariablesValidate(array &$form, FormStateInterface $form_state) {
    StageFormsCommon::form_unset_errors($form_state,['spatial_layer_id','cb_dates']);
    $fsv= $form_state->getValues();
    $pg_date = $fsv['px_date'];

    if (isset($pg_date)){
      $pxvars=$this->pxvars;
      $headers = array_keys($pxvars);
      $dates=$pxvars[$headers[$pg_date]];
      $dates=array_combine($dates['codes'],$dates['values']);

      foreach($dates as $code=>&$value){
        $date=self::parsePxDate($value);
        if ($date===false){
          $form_state->setErrorByName('px_date', $this->t('The selected variable does not contain valid PX dates.'));
          return;
        }
        $value=$date;
      }
      $this->dates=$dates;
    }
  }

  public function getPxHeaders(array &$form, FormStateInterface $form_state) {
    $fsv= $form_state->getValues();
    $px_url = $fsv['px_url'];
    $this->px_url=$px_url;
    $service = \Drupal::service('gi_services');
    $this->pxvars = $service->getPxHeader($px_url);
    /*******************************************************
     *$this->pxvars:
     *
     *['stub1'=>['values'=>[v1,v2,v3,...],'codes'=>['c1','c2','c3',...],'notes'=>[...],'dnotes'=>[...]],
     *'stub2'=> ...
     *...
     *'heading1'=>...
     *'heading2'=>...
     *
     *]
     ******************************************************/
    $form_state->setRebuild();
  }
  
  private static function parseSchoolYearDate($date){
    $a=explode('/',$date);
    
    if (count($a)>0) {
      return ['s1'=>trim($a[0]),'add'=>0];
    }
    
    return false;
  }
  
  private static function parsePxFormatDate($date){
    $a=array(strpos($date,'H'),strpos($date,'Q'),strpos($date,'M'),strpos($date,'W'));
    $a=array_filter($a);
    if (count($a)>1) return false;
    $pos=current($a);
    $s2=null;
    if (empty($pos)) {
      $pos=strlen($date);
      $s2='H1';
    }

    $s1=substr($date,0,$pos);
    if (strval((int)$s1)!==$s1) return false;
    if (is_null($s2)) $s2=substr($date,$pos);
    $type=$s2[0];
    $m=intval(substr($s2,1))-1;

    if ($type=='H')
      $add=(6*$m)." month";
    else if ($type=='Q')
      $add=(3*$m)." month";
    else if ($type=='M')
      $add=$m." month";
    else if ($type=='W')
      $add=$m." week";
    else
      return false;
    
    return ['s1'=>$s1,'add'=>$add];
  }

  public static function parsePxDate($date)
	{
      $date=trim($date);
      $r=false;
      
      if (strpos($date,'/')!==FALSE) {
        $r=self::parseSchoolYearDate($date);
      }
      else{
        $r=self::parsePxFormatDate($date);
      }
      
      if ($r==false) return false;
      
      $s1=$r['s1'];
      $add=$r['add'];
      
      if (!isset($s1) || !isset($add)) return false;

      $dt = \DateTime::createFromFormat('Y-m-d', $s1.'-01-01');
      $dt->add(date_interval_create_from_date_string($add));
      return $dt->format('Y-m-d');
  }

  public function getPxVariables(array &$form, FormStateInterface $form_state) {
		// get available variables names
		$available_variables = StageDatabaseSM::stage2_GetAvailableVariables();
		$name = array();
		$short_name = array();
		$name[-1] = "-Select -";
		$short_name[-1] = "-Select-";
		foreach ($available_variables as $key => $value){
        $name[$value->id] = str_repeat ('>', StageDatabaseSM::stage2_get_variable_parents($value->var_tree_id,true)-2).$value->name;
			$short_name[$value->id] = $value->short_name;
		}

    $fsv= $form_state->getValues();

    $this->pg_date=$pg_date = $fsv['px_date'];
    $this->pg_geocode=$pg_geocode = $fsv['px_geocode'];

    $pxvars=$this->pxvars;
    $headers = array_keys($pxvars);
    /*******************************
     Array
      (
          [0] => STATISTIČNA REGIJA
          [1] => ŠTEVILO MOŠKIH
          [2] => ŠTEVILO ŽENSK
          [3] => LETO
          [4] => TIP GOSPODINJSTVA
      )
     ******************************/

    unset($headers[$pg_geocode]);
    unset($headers[$pg_date]);

    $vcarr=array();
    foreach($headers as $head){
      $vcarr[]=array_combine($pxvars[$head]['codes'],$pxvars[$head]['values']);
    }

    /*************************************
     $vcarr size: count($headers)-2 //count($vcarr)
     Array(
     ['header1.code1'=>'header1.v1',...], //count(header1)
     ['header2.code1'=>'header2.v1',...], //count(header2)
     ['header3.code1'=>'header3.v1',...], //count(header3)
     ...
     )
     ************************************/

    $service = \Drupal::service('gi_services');
    $this->xheaders=$xheaders=$service->cartesian($vcarr,true);

    /*************************************
     $xheaders size: count(header1)*count(header2)* ... *count(headerN)
     Array(
      [['header1.code1'=>'header1.v1'],['header2.code1'=>'header2.v1'],['header3.code1'=>'header3.v1']],
      [['header1.code1'=>'header1.v1'],['header2.code1'=>'header2.v1'],['header3.code2'=>'header3.v2']],
      [['header1.code1'=>'header1.v1'],['header2.code1'=>'header2.v1'],['header3.code3'=>'header3.v3']],
      ...
     )
     ************************************/

    $date = '22-01-2017'; // TODO datum mora biti natanko v taki obliki

		$import_data = array();

    foreach ($xheaders as $key => $value) { /*$key is zero based index*/
      $tvalues=array_map('current',$value);
      /************************************************
       $tvalues
       extracted values from the current xheader
       e.g.
       Array(
        [0] => 'header1.v1',
        [1] => 'header2.v1',
        [2] => 'header3.v1',
       )
       ***********************************************/
			$tvalue=implode(' | ',$tvalues);
      $tcodes=array_map('key',$value);
      /************************************************
       $tcodes
       extracted keys from the current xheader
       e.g.
       Array(
        [0] => 'header1.code1',
        [1] => 'header2.code1',
        [2] => 'header3.code1',
       )
       ***********************************************/
      sort($tvalues);
      $scodes=implode("|",$tvalues);
      $key = $key+1; // key is increased because of the way Drupal filters selected rows in the tableselect
      $import_data[$key] = array(
      'id'=>$key,
      'header_id' =>
        array(
          'data' => array(
            '#type' => 'textfield',
            '#default_value' => $tvalue,
            '#attributes' => array('readonly' => TRUE,'title'=>$tvalue,'data-codes'=>$scodes,'class'=>array('header_name_var')),
          )),
        'variable_name' =>
          array(
              'data' => array(
              '#type' => 'select',
              '#options' => $name,
              '#attributes' => array('class'=>array('select_var')),
            )),
        'acronym' =>
          array(
                'data' => array(
                '#type' => 'select',
                '#options' => $short_name,
                '#attributes' => array('class'=>array('select_acr')),
              )),
      );
    }

		$this->import_data=$import_data;
		$form_state->setRebuild();
	}
}
