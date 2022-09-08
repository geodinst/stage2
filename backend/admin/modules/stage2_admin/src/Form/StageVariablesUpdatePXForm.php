<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;

use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_admin\Form\StageVariablesUpdatePxUrl;
use Drupal\stage2_admin\StageStatDataImporter;

use Drupal\stage2_admin\StageFormsCommon;

use Drupal\Core\Url;

class StageVariablesUpdatePXForm extends FormBase{

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_update_px_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
		$header = array(
			'url' => 		t('PX URL'),
			'status' => t('status')
	  );

    $options = array();

    $result=db_query("select distinct dsname::json->>'url' url from s2.var_ds where ispx=true and update_status=0 order by url");

    foreach($result as $record){
        $url=$record->url;
        $options[$url]=['url'=>Link::fromTextAndUrl($url, Url::fromUri('internal:/variables/update/'.base64_encode($url))),'status'=>'idle'];
    }

    $result=db_query("select distinct dsname::json->>'url' url from s2.var_ds where ispx=true and update_status<>0 order by url");
    //povozi idle kadar obstaja isti url, ki je in progress
    foreach($result as $record){
        $url=$record->url;
        $options[$url]=['url'=>Link::fromTextAndUrl($url, Url::fromUri('internal:/variables/update/'.base64_encode($url))),'status'=>'in progress'];
    }

    $form['update_selected'] = array(
			'#type' => 'submit',
			'#value' =>  t('Update selected'),
			'#submit' => array('::update')
		);

    $form['cancel_selected'] = array(
			'#type' => 'submit',
			'#value' =>  t('Cancel selected updates'),
			'#submit' => array('::cancel')
		);

    $form['settings'] = array(
        '#type' => 'details',
        '#title' => t('Settings'),
        '#open' => FALSE,
    );

    $form['settings']['refresh_existing'] = array(
          '#type' => 'checkbox',
          '#title' => 'refresh all previously imported variables'
      );

    $form['settings']['add_new'] = array(
          '#type' => 'checkbox',
          '#title' => 'add new dates from PX file for all the previously imported variables',
          '#default_value'=>true
      );

    $form['settings']['only_yearly'] = array(
          '#type' => 'checkbox',
          '#title' => 'add/refresh only data with the date YYYY-01-01',
          '#default_value'=>true
      );

    $form['file_selector'] = array(
        '#type' => 'details',
        '#title' => t('Imported PX files'),
        '#open' => TRUE,
    );

    $form['file_selector']['status_table'] = array(
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $options,
        '#empty' => t('NA')
      );

		return $form;
	}

  public function cancel(array &$form, FormStateInterface $form_state) {

  }

  private static function datesForImport($px, $refreshExisting, $addNew, $onlyYearly){
    //dates for import
    $dates=[];
    if ($refreshExisting===true) {
      foreach($px->importedDates as $date){
        $key=array_search($date,$px->dates);
        if ($key!==FALSE){
          $dates[$key]=$date;
        }
      }
    }

    if ($addNew===true){
      $dates=$dates+array_diff($px->dates,$px->importedDates);
    }

    if ($onlyYearly===true){
      foreach($dates as $key=>$date){
        if (substr($date,-6)!=='-01-01'){
          unset($dates[$key]);
        }
      }
    }
    return $dates;
  }

  public static function importPx($url, $refreshExisting, $addNew, $onlyYearly, $tree_menu){
    $px=StageVariablesUpdatePxUrl::px($url);
    if ($px===FALSE) return;
    $px=(object)$px;

    $dates=self::datesForImport($px,$refreshExisting, $addNew, $onlyYearly);
    if (count($dates)==0) return;

    $dataImporter=new StageStatDataImporter($url,$px->headers); //saves the PX to the temporary data table
    $dataImporter->setPxCondition($px->headers,$px->pg_geocode,$px->pg_date); //prepares array template for selecting from temporary px data table
    
    //filter out new variables (variables without scode)
    foreach ($px->importedVariables as $scode=>$var_names_id) {
      if (!isset($px->rxheaders[$scode])) {
        unset($px->importedVariables[$scode]);
      }
    }

    foreach($dates as $dcode=>$date){
      $attr=[];
      $c=1;

      foreach ($px->importedVariables as $scode=>$var_names_id) {
          $attr[]='v'.($c++);
      }

      $tcodes=array_map('key',current($px->rxheaders));
  
      $gcode='c'.$px->pg_geocode;
      $attr[]=$gcode;
      $dcode=key($dates);
  
      $tname=$dataImporter->prepareFilteredPxDataTable($attr,$tcodes,$dcode,$gcode);
  
      $var_ds_id=$dataImporter->saveDatasourceData($gcode,-1,json_encode(['url'=>$url,'pg_geocode'=>$px->pg_geocode,'pg_date'=>$px->pg_date,'headers'=>$px->headers]),$tname);
  
      $spatial_layer_id=$px->spatial_layer_id;
      $c=1;
      
      foreach ($px->importedVariables as $scode=>$var_names_id) {
        $tcodes=array_map('key',$px->rxheaders[$scode]);
        $variable = array();
        $variable['published']= 0;
        $cvname='v'.($c++);
        $dataImporter->updatePxDataTable($tname,$cvname,$tcodes,$dcode,$gcode); //update sta.tname table
        $variable['data']=json_encode([$cvname,-1]);
        $variable['var_ds_id'] = $var_ds_id;
        $variable['spatial_layer_id'] = $spatial_layer_id;
        $variable['var_properties_id'] = StageDatabaseSM::stage2AutoParameterSelection($date, $spatial_layer_id, $var_names_id);
        $variable['var_names_id'] = $var_names_id;
        $variable['valid_from'] = $date;
        $var_values_add[] =  StageDatabaseSM::stageCreateVariable($variable,$tree_menu);
      }
    }

    // Log that something has been changed in the DB
    StageDatabaseSM::stageLog('px auto import ('.$url.')',count ($var_values_add).' variable(s) created.');
  }

  public function update(array &$form, FormStateInterface $form_state) {
    $checkedItems=$form['file_selector']['status_table']['#value'];
    //$checkedItems=['http://localhost/05C1008S.px'];
    if (empty($checkedItems)) return;
    $userInput=$form_state->getUserInput();
    
    $tree_menu=StageFormsCommon::treeStructure();

    foreach($checkedItems as $url) {
      db_query("UPDATE s2.var_ds SET update_status=1 where dsname::json->>'url'=?",[$url]);
      self::importPx($url,
                      isset($userInput['refresh_existing'])?true:false,
                      isset($userInput['add_new'])?true:false,
                      isset($userInput['only_yearly'])?true:false, $tree_menu
                      );
      db_query("UPDATE s2.var_ds SET update_status=0 where dsname::json->>'url'=?",[$url]);
    }
  }

	public function submitForm(array &$form, FormStateInterface $form_state) {

	}
}
