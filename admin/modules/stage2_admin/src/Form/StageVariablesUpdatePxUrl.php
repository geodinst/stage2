<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageFormsCommon;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_admin\Form\StagePxImportForm;

class StageVariablesUpdatePxUrl extends FormBase{

  public function getFormID(){
    return 'stage_variables_update_px_url_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state, $url=null) {
    if (empty($url)) return;
    $url=base64_decode($url);
    drupal_set_message(StageFormsCommon::mu("<h1><a href='$url'>$url</a></h1>"));
    $service = \Drupal::service('gi_services');
    drupal_set_message(StageFormsCommon::mu('<h2>PX file metadata</h2><hr>'));
    drupal_set_message(StageFormsCommon::mu('<pre>'.print_r($service->getPxMetadata($url),true).'</pre><hr>'));
    $px=self::px($url);
    if ($px===FALSE) {
      drupal_set_message('The header values of this PX do not match saved header values; can not find the geocode field string or date field string.');
      return $form;
    }
    drupal_set_message(StageFormsCommon::mu('The dates of imported variables from this px file: <b>'.implode(', ',$px['importedDates']).'</b><hr>'));
    drupal_set_message(StageFormsCommon::mu('The dates available in this px file: <b>'.implode(', ',$px['dates']).'</b><hr>'));
    drupal_set_message(StageFormsCommon::mu('New dates in this px file: <b>'.implode(', ',array_diff($px['dates'],$px['importedDates'])).'</b><hr>'));
    $varDiff=array_diff($px['variables'],array_keys($px['importedVariables']));
    if (!empty($varDiff)) {
      drupal_set_message(StageFormsCommon::mu('New variables in this px file: <b>'.implode(', ',array_keys($varDiff)).'</b><hr>'));
    }
    
    $varDiff=array_diff(array_keys($px['importedVariables']),$px['variables']);
    if (!empty($varDiff)) {
      drupal_set_message(StageFormsCommon::mu('Orphaned variables: <b>'.implode(', ',$varDiff).'</b><hr>'));
    }
    
    // read spatial_layers
		$spatial_layers = StageDatabase::loadSpatialLayers();
		$variables=array();
		
    // read variables from database
    $queryArray = array("spatial_layer.id" => $px['spatial_layer_id']);
    $variables = StageDatabase::loadVariables2($queryArray);
    
    foreach($variables as $key=>$variable){
      if (!in_array($variable->id_name,$px['importedVariables'])){
        unset($variables[$key]);
      }
    }
    
    $tree_menu=StageFormsCommon::treeStructure();

		// Options to be displayed in the tableselect
		$options = array();
		
   StageFormsCommon::getTableOptions($options,$variables,$tree_menu,$downar,'/variables/update/report');
    $header = array(
			'name' => t('Name'),
			'short_name' => t('Acronym'),
			'layer' => t('Spatial layer'),
			'published' => t('Published')
		);
    
    $form['publish'] = array(
			'#type' => 'submit',
			'#value' => t('Publish selected on date'),
		);

		$form['unpublish'] = array(
			'#type' => 'submit',
			'#value' => t('Unpublish selected'),
		);
    
	  $form['table'] = array(
			'#type' => 'tableselect',
			'#header' => $header,
			'#options' => $options,
			'#id' => 'tableselect_id',
			'#empty' => t('No variable has not yet been added'),
			'#prefix' => '<div id="variables_table">',
			'#suffix' => '</div>',
	  );
    
    return $form;
  }
  
  public static function px($url){
    $service = \Drupal::service('gi_services');
    $pxvars = $service->getPxHeader($url);
    $headers = array_keys($pxvars);
    
    $result=db_query("select var_values.var_names_id var_names_id, codes,tname,data,valid_from::date valid_from, var_ds.id id, dsname,var_values.spatial_layer_id sld from
                     s2.var_ds var_ds,
                     s2.var_values var_values,
                     s2.var_links var_links,
                     s2.var_names var_names
                     where lower(var_links.acronym)=lower(var_names.short_name) and
                     var_names.id=var_values.var_names_id and
                     var_values.var_ds_id=var_ds.id and
                     ispx=true and
                     dsname::json->>'url' = ?",[$url]);
    
    $importedDates=[];
    $importedVariables=[];
    $check_if_header_values_exist=true;
    foreach($result as $record){
      if ($check_if_header_values_exist){
        $dsname=json_decode($record->dsname);
        $importedHeaders=$dsname->headers;
        $pg_geocode_string=$importedHeaders[$dsname->pg_geocode];
        $pg_date_string=$importedHeaders[$dsname->pg_date];
        $uppercaseHeaders=array_map('strtoupper', $headers);
        $pg_geocode=array_search(strtoupper($pg_geocode_string),$uppercaseHeaders);
        $pg_date=array_search(strtoupper($pg_date_string),$uppercaseHeaders);
        if ($pg_geocode===FALSE || $pg_date===FALSE){
          return FALSE;
        }
        $check_if_header_values_exist=false;
      }
      
      $importedDates[]=$record->valid_from;
      $importedVariables[$record->codes]=$record->var_names_id;
    }
    
    $rval=[];
    $rval['spatial_layer_id']=$record->sld;
    $rval['headers']=$headers;
    
    $rval['importedVariables']=$importedVariables;
    $rval['importedDates']=array_unique($importedDates);
    
    //dates
    $dates=$pxvars[$headers[$pg_date]];
    $dates=array_combine($dates['codes'],$dates['values']);

    foreach($dates as $code=>&$value){
      $value=StagePxImportForm::parsePxDate($value);
    }
    
    unset($value);
    
    $rval['dates']=$dates;
    
    $rval['pg_geocode']=$pg_geocode;
    $rval['pg_date']=$pg_date;

    unset($headers[$pg_geocode]);
    unset($headers[$pg_date]);

    $vcarr=array();
    foreach($headers as $head){
      $vcarr[]=array_combine($pxvars[$head]['codes'],$pxvars[$head]['values']);
    }

    $xheaders=$service->cartesian($vcarr,true);
    $rxheaders=[];
    $variables=[];
    foreach ($xheaders as $key => $value) {
      $tvalues=array_map('current',$value);
			$tvalue=implode(' | ',$tvalues);
      sort($tvalues);
      $scodes=implode("|",$tvalues);
      $variables[$tvalue]=$scodes;
      if (isset($importedVariables[$scodes])) {
        $rxheaders[$scodes]=$value;
      }
    }
    
    $rval['rxheaders']=$rxheaders;
    $rval['variables']=$variables;
    
    return $rval;
  }
  
   public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do something useful.
      $values = $form_state->getValues();
    
      $bla = $form_state->getTriggeringElement();
      $id = $bla["#parents"][0];
    
      if($id == "publish"){
    
        $parameters_to_pass = array();
        $parameters_to_pass['selected'] = array_filter(array_values($values['table']));
        $parameters_to_pass['attribute'] = 'var_names_id';
    
        $url = \Drupal\Core\Url::fromRoute('stage2_admin.variablesPublish')
            ->setRouteParameters(array('id'=>json_encode($parameters_to_pass)));
        $form_state->setRedirectUrl($url);
      }
      elseif($id =='unpublish'){
        $variables = array_filter(array_values($values['table']));
        StageDatabaseSM::unpublishVariablesvar_names_id($variables);
      }
  }
}
