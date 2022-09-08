<?php

namespace Drupal\stage2_admin;

use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\stage2_admin\StageDatabase;


class StageDatabaseSM {

  /************************	GEOSPATIAL LAYERS ************************/

  /*
  * THE FUNCTION IS USED TO POPULATE THE TABLE SELECT IN THE StageGeospatialLayersForm
  */
  public static function stage_get_geo_layers_and_dates(){
    $que = db_select('s2.spatial_layer_date', 'sld')
          -> extend('Drupal\Core\Database\Query\TableSortExtender');
    $que  ->join('s2.spatial_layer', 'sl', 'sld.spatial_layer_id = sl.id');
    $que  ->orderBy('sl.weight', 'DESC');
    $que  ->orderBy("sld.valid_from", "DESC");
    $que  ->fields('sld',['id','spatial_layer_id','valid_from','modified'])
          ->fields('sl',['name']);
    $que->range(0,100000);

    $geo_layers = $que
      ->execute()
      ->fetchAll();
      return $geo_layers;
  }

  public static function stage_get_variables_by_layer($spatial_layer_id,$valid_from,$header){
    $que = db_select('s2.var_values','var_values')
		// -> extend('Drupal\Core\Database\Query\TableSortExtender')
		-> extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10)->element(1);
    $que-> fields('var_values',array('published','valid_from','publish_on'));
    $que->join ('s2.var_names', 'names', 'var_values.var_names_id = names.id');
    $que->addField('names', 'name','names_name');
    $que->addField('names', 'short_name','short_name');
    $que->addField('names', 'id','id_name');
    $que  ->orderBy('var_values.var_names_id', 'DESC');
    $que  ->orderBy('var_values.valid_from', 'DESC');
    $que->condition('spatial_layer_id',array($spatial_layer_id));
    $que->condition('var_values.valid_from',array($valid_from),'>=');
		$result = $que
			->execute()
			->fetchAll();

   return $result;
  }

  public static function stage2GetAvailableGeoLayers($fields=array('id','proj4text')){
     $que = db_select('s2.crs', 'crs');
     $que->fields('crs',$fields);
     $query = $que->execute();
     $return = $query->fetchAllKeyed();
     return $return;
  }
  public static function stage2GetAvailableSpatialUnits(){
     $que = db_select('s2.spatial_layer', 'su');
     $que->fields('su',array('id','name'));
     $que->orderBy('weight','DESC');
     $query = $que->execute();
     $return = $query->fetchAllKeyed();
     return $return;
  }
  /**
  * Save or update one instance of the geospatial layer
  * @param $entity entity instance of an geo_layer
  * @param $id integer id of the geo layer
  */
  public static function stage2SaveGeoLayer($entry, $id){

    $return_value = NULL;

    // if in editing mode
    if($id && $id <>'-1'){
      $return_value = db_update('s2.spatial_layer_date')
      ->condition('id',$id)
      ->fields($entry)
      ->execute();
      return ('Layer'.$id.' modified. '.json_encode ($entry));
    }

		try {
		  db_insert('s2.spatial_layer_date')
			->fields($entry)
			->execute();
      $return_value = db_query("select max(id) from s2.spatial_layer_date")->fetchField();
		}
		catch (\Exception $e) {
		  drupal_set_message(t('db_insert failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}
		return $return_value;
  }

  public static function get_allowed_geospatial_layer_ids($select="additional_data->>'id'", $action='geospatial_layers', $msg=true) {
    $user = \Drupal::currentUser();
    if (!$user->hasPermission('stage2_admin content_administrator')) {
      $account = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
      $uid= $account->get('uid')->value;
      $params = [
        ':uid'=>$uid
      ];
      
      $res=db_query("SELECT $select from s2.log a where a.user=:uid and action='$action'", $params);

      $ids = $res->fetchCol();

      $a = [-1];
      $a=array_merge($a,$ids);

      if ($msg) {
        drupal_set_message('The operation was restricted to the layers uploaded with this user account only. For the unrestricted access permission contact the system administrator.', 'warning');
      }

      return $a;
    }
    return [];
  }

  // delete entries from s2.spatial_layer_date table
   public static function stage2DeleteGeoLayer($entry){
     $ids = self::get_allowed_geospatial_layer_ids();
     $count = db_delete('s2.spatial_layer_date');
     $count->condition('id', $entry,'IN');
     if (count($ids) > 0) {
      $count -> condition('id',$ids,'IN');
     }
     return $count->execute();
   }
   //
   public static function stage2GetGeoLayerDeatils($id){
    $query = \Drupal::database()->select('s2.spatial_layer_date', 's1');
    $query->fields('s1', ['spatial_layer_id','crs_id','description','borders','valid_from','col_names']);
    $query->condition('s1.id', $id);
    $query->range(0, 1);
    $return = $query->execute()->fetchAssoc();
    $return['id'] = $id;
   return $return;
   }

   // check if layer with same spatial_layer_id exist on the same date
   public function stage2_same_date_exists($entry){

    $que = db_select('s2.spatial_layer_date', 'sl');
    $que->fields('sl',array('valid_from','spatial_layer_id','id'));
    $que->condition('spatial_layer_id', $entry['spatial_layer_id']);
    $que->condition('valid_from', $entry['valid_from']);
    $query = $que->execute();
    $query->allowRowCount = TRUE;
    $count = $query->rowCount();
    $id_selected = $query->fetchAssoc()['id'];
    if ($count<>0){
      if ($id_selected==$entry['id']){
        return false;
      }
      return true;
    }
    return false;
   }

   /************************	BATCH IMPORT ************************/
	 public static function stage2AutoParameterSelection($valid_from, $spatial_layer_id, $var_names_id){

     $var_id = db_query(
       "SELECT var_properties_id, valid_from, @ extract(epoch from age(timestamp '$valid_from', valid_from)) as age FROM s2.var_values as var_values
     where var_values.spatial_layer_id = $spatial_layer_id and var_values.var_names_id = $var_names_id
     order by age limit 1")->fetch();

     if (isset($var_id->var_properties_id)){
       return $var_id->var_properties_id;
     }else{
       return false;
     }
     //
		 // $que = db_select('s2.var_values','var_values');
		 // $que->fields('var_values',array('var_properties_id','valid_from'));
		 // $que->condition('var_values.spatial_layer_id', array($spatial_layer_id));
		 // $que->condition('var_values.var_names_id', array($var_names_id));
		 // $que->condition('var_values.valid_from', array($valid_from),'<');
     // $que->orderBy('valid_from','DESC');
     // $query = $que->execute();
		 // $query->allowRowCount = TRUE;
     // $count = $query->rowCount();
     // if ($count>0){
			//  $result = $query->fetch()->var_properties_id;
			//  return $result;
		 // }
		 // else{
			//  return false;
		 // }

	 }

   public static function stage2_GetAvailableVariables(){
     $que = db_select('s2.var_tree','var_tree');
     $que->join ('s2.var_names', 'names', 'var_tree.id = names.id');
     $que->fields('names',array('id','short_name','name','var_tree_id'));
		 $que->orderBy('var_tree.position','ASC');
     $que->condition('var_tree.parent_id', array(0), 'NOT IN');
     $query = $que->execute();
     $return = $query->fetchAll();
     return $return;
   }

	 public static function stage2_get_variable_parents($id,$count = false){
		 $input = array();
		 $all = StageDatabaseSM::stage2_GetParentVariableNodes($id,$input);
		 if ($count){
			 return count($all);
		 }
		 else{
			 return $all;
		 }
	 }


	 public static function stage2_GetParentVariableNodes($id, &$result){
		 foreach (StageDatabaseSM::stage2_parent_variable($id) as $f) {
			 $result[] = $f;
				StageDatabaseSM::stage2_GetParentVariableNodes($f,$result); // here is the recursive call
		 }
		 return $result;
	 }

	 public static function stage2_parent_variable($id){
		 $que = db_select('s2.var_tree','var_tree');
     $que->fields('var_tree',array('parent_id'));
		 $que->condition('var_tree.id', $id);
		 $query = $que->execute();
		 $return = $query->fetchAllKeyed(0,0);
		 return array_keys($return);
	 }

   /**
    * Function creates new variable in the var_values table
    * @author Sebastjan <sebastjan.meza@gis.si>
    */
   public static function stageCreateVariable($variable,$tree_menu =false){
    $return_value = NULL;
		$transaction = db_transaction();
     try {
      db_query("delete from s2.var_values where var_names_id=? and spatial_layer_id=? and valid_from=?",[$variable['var_names_id'],$variable['spatial_layer_id'],$variable['valid_from']]);
 		  $return_value = db_insert('s2.var_values')
 			->fields($variable)
 			->execute();
			$return_value = $varid = StageDatabase::lastInsertedId('id', 's2.var_values');
 		}
 		catch (\Exception $e) {
      $transaction->rollback();
 		  drupal_set_message('db_insert failed. Message = %message, query= %query', array(
 			'%message' => $e->getMessage(),
 			'%query' => $e->query_string,
 		  )
 		  , 'error');
     }
     
     if (!is_null($return_value)) {
      self::stage_inspire('save',$varid,$variable,$tree_menu);
     }

    return $return_value;
   }

   public static function StageGetDefaultPropertiesid(){
     $que = db_select('s2.var_properties','prop');
     $que->fields('prop',array('id'));
     $que->condition('prop.default',1);
     $result = $que->execute()->fetch()->id;
     return $result;
   }
   /************************ Client settings************************/

   // populate Default client strings
   public static function stageResetClientLabels(){
    $required_client_labels = array();
    $required_client_labels[] = array('id_cli'=> '1','label'=>'Variables','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '2','label'=>'Info','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '3','label'=>'View settings','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '4','label'=>'Share','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '5','label'=>'Delineation','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '6','label'=>'Export image','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '7','label'=>'Share link','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '8','label'=>'Embed map','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '9','label'=>'Export SHP (selected variable)','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '10','label'=>'Export SHP (all variables)','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '11','label'=>'Export TSV','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '12','label'=>'Color scheme','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '13','label'=>'Classification','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '14','label'=>'Reset','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '15','label'=>'Colors','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '16','label'=>'Transparency','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '17','label'=>'Class count','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '18','label'=>'Apply settings','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '19','label'=>'Auto classification','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '20','label'=>'Comma separated class breaks','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '21','label'=>'Choose field','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '22','label'=>'Content','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '23','label'=>'Save','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '24','label'=>'Description','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '25','label'=>'Footer','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '26','label'=>'Scale','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '27','label'=>'Legend','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '28','label'=>'Link:','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '29','label'=>'Embed:','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '30','label'=>'Number of class breaks (excessive number of may corrupt display)','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '31','label'=>'Classification method','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '32','label'=>'Help','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '33','label'=>'Map','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '34','label'=>'Picture','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '35','label'=>'Animate','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '36','label'=>'Settings','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '37','label'=>'Export','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '38','label'=>'Download SHP','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '39','label'=>'Download SHP (all variables)','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '40','label'=>'Download TSV','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '41','label'=>'Add','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '42','label'=>'Clear','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '43','label'=>'Delineation statistics','language'=>'en','description'=>'dummy content');
    $required_client_labels[] = array('id_cli'=> '44','label'=>'Delineation correleation','language'=>'en','description'=>'dummy content');
	  $required_client_labels[] = array('id_cli'=> '45','label'=>'Manual','language'=>'en','description'=>'dummy content');
		$required_client_labels[] = array('id_cli'=> '46','label'=>'Subordinated','language'=>'en','description'=>'dummy content');
		$required_client_labels[] = array('id_cli'=> '47','label'=>'Parent','language'=>'en','description'=>'dummy content');
		$required_client_labels[] = array('id_cli'=> '48','label'=>'Download TSV (all variables)','language'=>'en','description'=>'dummy content');
		$required_client_labels[] = array('id_cli'=> '49','label'=>'or less','language'=>'en','description'=>'dummy content');
		$required_client_labels[] = array('id_cli'=> '50','label'=>'or more','language'=>'en','description'=>'dummy content');
		$required_client_labels[] = array('id_cli'=> '51','label'=>'.','language'=>'en','description'=>'decimal seperator');
		$required_client_labels[] = array('id_cli'=> '52','label'=>'Publish parameter settings changes','language'=>'en','description'=>'decimal seperator');
		$required_client_labels[] = array('id_cli'=> '53','label'=>'Save parametrs','language'=>'en','description'=>'dummy');
		$required_client_labels[] = array('id_cli'=> '54','label'=>'Publish variable','language'=>'en','description'=>'dummy');
		$required_client_labels[] = array('id_cli'=> '55','label'=>'Download','language'=>'en','description'=>'dummy');
		$required_client_labels[] = array('id_cli'=> '56','label'=>'Add all','language'=>'en','description'=>'dummy');
		$required_client_labels[] = array('id_cli'=> '57','label'=>'Child units','language'=>'en','description'=>'dummy');
		$required_client_labels[] = array('id_cli'=> '58','label'=>'Parent units','language'=>'en','description'=>'dummy');
    $required_client_labels[] = array('id_cli'=> '59','label'=>',','language'=>'en','description'=>'dummy');
    $required_client_labels[] = array('id_cli'=> '60','label'=>'Input a value from the closed interval','language'=>'en','description'=>'dummy');
    $required_client_labels[] = array('id_cli'=> '61','label'=>'Invalid value. Please check if the value falls in the respective interval!','language'=>'en','description'=>'dummy');
    $required_client_labels[] = array('id_cli'=> '62','label'=>'The selected interval length is equal to the accuracy of a variable.','language'=>'en','description'=>'dummy');
    $required_client_labels[] = array('id_cli'=> '63','label'=>'Cancel','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '64','label'=>'Finish','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '65','label'=>'Delete last point drawn','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '66','label'=>'Finish drawing','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '67','label'=>'Cancel drawing','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '68','label'=>'Click to start drawing shape.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '69','label'=>'Click and drag to draw rectangle.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '70','label'=>'Click and drag to draw circle.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '71','label'=>'Click map to place marker.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '72','label'=>'Click and drag to draw rectangle.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '73','label'=>'Click and drag to draw circle.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '74','label'=>'Click to continue drawing shape.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '75','label'=>'Click first point to close this shape.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '76','label'=>'Generate chart link','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '77','label'=>'Embed chart','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '78','label'=>'Download as image','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '79','label'=>'Languages','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '80','label'=>'Options','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '81','label'=>'Home','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '82','label'=>'Map title','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '83','label'=>'Map description','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '84','label'=>'Copy','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '85','label'=>'Level','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '86','label'=>'Delineation not alailable. Please select a variable.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '87','label'=>'show legend','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '88','label'=>'hide legend','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '89','label'=>'Select element on the map.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '90','label'=>'Spatial layer name is not translated therefore the data are not going to be shown.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '91','label'=>'Back','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '92','label'=>'Filter chart elements','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '93','label'=>'Display all chart elements','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '94','label'=>'Time delineation','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '95','label'=>'Sort alphabetically','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '96','label'=>'Sort numerically','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '97','label'=>'Only 50 results displayed in a single query.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '98','label'=>'Showing','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '99','label'=>'of','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '100','label'=>'results','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '101','label'=>'Child unit does not exist','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '102','label'=>'Parent unit does not exist','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '103','label'=>'Delineation statistics is not possible for the selected set of variables.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '104','label'=>'Time delineation is only available for published variables. Taking into account time dependence.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '105','label'=>'There is no subordinate polygon to cover at least 90% with the parent unit.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '106','label'=>'Data for Slovenia: ','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '107','label'=>'selected','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '108','label'=>'Only 12 elements allowed.','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '109','label'=>'Filter','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '110','label'=>'About','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '111','label'=>'Add new variable','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '112','label'=>'Share file','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '113','label'=>'Export picture','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '114','label'=>'Remove','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '115','label'=>'filter','language'=>'en','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '116','label'=>'Edit interval','language'=>'en','description'=>'Edit legend interval title');
    $required_client_labels[] = array('id_cli'=> '117','label'=>'Unclassified values','language'=>'en','description'=>'unclassified');
    $required_client_labels[] = array('id_cli'=> '118','label'=>'no data','language'=>'en','description'=>'no data');
    $required_client_labels[] = array('id_cli'=> '119','label'=>'Reset settings','language'=>'en','description'=>'resets legend');

    /*
    view settings - legend settings
    delineation - spatial query (gumb in ime kartice)
    add - new variable (v delineaciji gumb)
    filter - select
    time delineation - time series
    */

    $required_client_labels[] = array('id_cli'=> '120','label'=>'Legend settings','language'=>'en','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '121','label'=>'Spatial query','language'=>'en','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '122','label'=>'New variable','language'=>'en','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '123','label'=>'Select','language'=>'en','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '124','label'=>'Time series','language'=>'en','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '125','label'=>'Spatial query statistics','language'=>'en','description'=>'resets legend');

	$required_client_labels[] = array('id_cli'=> '126','label'=>'Reverse color order','language'=>'en','description'=>'Inverse colors');
	$required_client_labels[] = array('id_cli'=> '127','label'=>'Do you really want to remove selected interval?','language'=>'en','description'=>'Inverse colors');
	$required_client_labels[] = array('id_cli'=> '128','label'=>'Do you really want to remove selected row?','language'=>'en','description'=>'Inverse colors');
	$required_client_labels[] = array('id_cli'=> '129','label'=>'Remove selected','language'=>'en','description'=>'Inverse colors');
	$required_client_labels[] = array('id_cli'=> '130','label'=>'Year','language'=>'en','description'=>'Inverse colors');

    $required_client_labels[] = array('id_cli'=> '500','label'=>'General','language'=>'en','description'=>'help_level1');

    // Slovenščina
    $required_client_labels[] =  array('id_cli'=> '1','label'=>'Spremenljivke','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '2','label'=>'Info','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '3','label'=>'Nastavitve pogleda','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '4','label'=>'Deli','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '5','label'=>'Delineacija','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '6','label'=>'Izvozi sliko','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '7','label'=>'Skupna raba povezave','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '8','label'=>'Vdelava zemljevida','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '9','label'=>'Izvoz SHP (izbrana spremenljvka)','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '10','label'=>'Izvoz SHP (vse spremenljivke)','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '11','label'=>'Izvoz TSV','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '12','label'=>'Barvna shema','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '13','label'=>'Klasifikacija','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '14','label'=>'Ponastavi','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '15','label'=>'Barve','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '16','label'=>'Prosojnost','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '17','label'=>'Å tevilo razredov (preveliko Å¡tevilo lahko pokvari prikaz)','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '18','label'=>'Potrdi spremembe','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '19','label'=>'Automatska klasifikacija','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '20','label'=>'Z vejico ločeni razredi','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '21','label'=>'Izberite področje','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '22','label'=>'Vsebina','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '23','label'=>'Shrani','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '24','label'=>'Opis','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '25','label'=>'Noga','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '26','label'=>'Merilo','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '27','label'=>'Legenda','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '28','label'=>'Povezava:','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '29','label'=>'Vstavi v HTML stran:','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '30','label'=>'Å tevilo razredov','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '31','label'=>'Metoda klasifikacije','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '32','label'=>'Pomoč','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '33','label'=>'Karta','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '34','label'=>'Slika','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '35','label'=>'Animacija','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '36','label'=>'Nastavitve','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '37','label'=>'Izvozi','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '38','label'=>'Prenesi SHP','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '39','label'=>'Prenesi SHP (vse spremenljivke)','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '40','label'=>'Prenesi TSV','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '41','label'=>'Dodaj','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '42','label'=>'Počisti','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '43','label'=>'Statistika prostorske poizvedbe','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '44','label'=>'Korelacijska poizvedba','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '45','label'=>'Ročno','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '46','label'=>'Podrejeno','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '47','label'=>'Nadrejeno','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '48','label'=>'Prenesi TSV (vse spremenljivke)','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '49','label'=>'ali manj','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '50','label'=>'ali več','language'=>'sl','description'=>'testna vsebina');
    $required_client_labels[] =  array('id_cli'=> '51','label'=>',','language'=>'sl','description'=>'decimalno ločilo');
    $required_client_labels[] =  array('id_cli'=> '52','label'=>'Objavi spremembe nastavitev','language'=>'sl','description'=>'');
    $required_client_labels[] =  array('id_cli'=> '53','label'=>'Shrani parametre','language'=>'sl','description'=>'');
    $required_client_labels[] =  array('id_cli'=> '54','label'=>'Objavi spremenljivko','language'=>'sl','description'=>'');
    $required_client_labels[] =  array('id_cli'=> '55','label'=>'Prenesi','language'=>'sl','description'=>'');
    $required_client_labels[] =  array('id_cli'=> '56','label'=>'Dodaj vse','language'=>'sl','description'=>'');
    $required_client_labels[] =  array('id_cli'=> '57','label'=>'Podrejeno','language'=>'sl','description'=>'');
    $required_client_labels[] =  array('id_cli'=> '58','label'=>'Nadrejeno','language'=>'sl','description'=>'');
    $required_client_labels[] =  array('id_cli'=> '59','label'=>'.','language'=>'sl','description'=>'dummy');
    $required_client_labels[] =  array('id_cli'=> '60','label'=>'Vnesi vrednost iz zaprtega intervala','language'=>'sl','description'=>'dummy');
    $required_client_labels[] =  array('id_cli'=> '61','label'=>'Napačna vrednost. Vrednost mora biti iz ustreznega intervala!','language'=>'sl','description'=>'dummy');
    $required_client_labels[] =  array('id_cli'=> '62','label'=>'DolÅ¾ina izbranega intervala je manjÅ¡a ali enaka natančnosti indikatorja, zato v ta interval ni mogoče vstavljati vrednosti ali mu spreminjati meje.','language'=>'sl','description'=>'dummy');
    $required_client_labels[] = array('id_cli'=> '63','label'=>'Prekliči','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '64','label'=>'Zaključi','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '65','label'=>'IzbriÅ¡i zadnjo točko','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '66','label'=>'Zaključi','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '67','label'=>'Prekliči','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '68','label'=>'Izberi prvo točko mnogogotnika','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '69','label'=>'Izberi točko in povleci.','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '70','label'=>'Izberi točko in povleci radij','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '71','label'=>'Izberi točko na karti','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '72','label'=>'Izberi točko na karti in povleci','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '73','label'=>'Izberi točko na karti in povleci','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '74','label'=>'Izberi naslednjo točko mnogokotnika','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '75','label'=>'Za zaključek izbora izberi prvo točko mnogokotnika.','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '76','label'=>'Ustvari povezavo do vgnezdenega grafa','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '77','label'=>'Vstavi v HTML stran','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '78','label'=>'Prenesi kot sliko','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '79','label'=>'Jeziki','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '80','label'=>'Dodatno','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '81','label'=>'Domov','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '82','label'=>'Naslov karte','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '83','label'=>'Opis karte','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '84','label'=>'Kopiraj','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '85','label'=>'Raven prikaza','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '86','label'=>'Delineacija ni na voljo. Prosim izberite spremenljivko.','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '87','label'=>'PprikaÅ¾i legendo','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '88','label'=>'Skrij legendo','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '89','label'=>'Izberi točko na karti','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '90','label'=>'Manjka prevod za ime prostorskega sloja, zato podatki ne bodo prikazani.','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '91','label'=>'Nazaj','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '92','label'=>'Filtriraj elemente na grafu','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '93','label'=>'Prikaži vse','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '94','label'=>'Časovna poizvedba','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '95','label'=>'Razvrsti po abecedi','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '96','label'=>'Razvrsti po velikosti','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '97','label'=>'V eni poizvedbi je mogoče prikazazi zgolj 50 elementov.','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '98','label'=>'Prikazano','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '99','label'=>'od','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '100','label'=>'zadetkov','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '101','label'=>'Podrejena enota ne obstaja','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '102','label'=>'Nadrejena enota ne obstaja','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '103','label'=>'Statistika prostorske poizvedbe ni mogoča za izbran set podatkov.','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '104','label'=>'Ćasovna delineacija ni mogoča za izbran set podatkov.','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '105','label'=>'Podrejenega poligona, z minimalno 90% prekrivanjem ni mogoča najti.','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '106','label'=>'Vrednost za Slovenijo','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '107','label'=>'izbranih','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '108','label'=>'Maksimalno 12 enot.','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '109','label'=>'Filter','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '110','label'=>'O aplikaciji','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '111','label'=>'Dodaj novo spremenljivko','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '112','label'=>'Deli datoteko','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '113','label'=>'Izvozi sliko','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '114','label'=>'Odstrani','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '115','label'=>'filter','language'=>'sl','description'=>'Leaflet draw component');
    $required_client_labels[] = array('id_cli'=> '116','label'=>'Urejanje intervala','language'=>'sl','description'=>'Edit legend interval title');
    $required_client_labels[] = array('id_cli'=> '117','label'=>'Neklasificirane vrednosti','language'=>'sl','description'=>'unclassified');
    $required_client_labels[] = array('id_cli'=> '118','label'=>'ni podatka','language'=>'sl','description'=>'no data');
    $required_client_labels[] = array('id_cli'=> '119','label'=>'Ponastavi legendo','language'=>'sl','description'=>'resets legend');

    $required_client_labels[] = array('id_cli'=> '120','label'=>'Nastavitve legende','language'=>'sl','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '121','label'=>'Prostorska poizvedba','language'=>'sl','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '122','label'=>'Nova spremenljivka','language'=>'sl','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '123','label'=>'Izbira','language'=>'sl','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '124','label'=>'Časovna vrsta','language'=>'sl','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '125','label'=>'Statistika prostorske poizvedbe','language'=>'sl','description'=>'resets legend');

    $required_client_labels[] = array('id_cli'=> '126','label'=>'Obrnjena barvna lestvica','language'=>'sl','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '127','label'=>'Ali res želite odstraniti izbrani interval','language'=>'sl','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '128','label'=>'Ali res želite odstraniti izbrano vrstico','language'=>'sl','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '129','label'=>'Odstrani izbiro','language'=>'sl','description'=>'resets legend');
    $required_client_labels[] = array('id_cli'=> '130','label'=>'Leto','language'=>'sl','description'=>'resets legend');

    // CLIENT HELP
    $required_client_labels[] =  array('id_cli'=> '500','label'=>'SploÅ¡no','language'=>'sl','description'=>'help_level1');

    $id_clis=[];
    foreach($required_client_labels as $label) {
      $id_clis[$label['id_cli']] = true;
    }

    db_delete('s2.var_labels') 
      -> condition('id_cli',array_keys($id_clis),'IN')
      -> execute();

     foreach ($required_client_labels as $key => $entry) {
     $return_value = db_insert('s2.var_labels')
      -> fields($entry)
      -> execute();
    }
    return false;
   }

   public static function check_user_permissions_for_id($action, $id, $cname='id') {
     $user = \Drupal::currentUser();
     if ($user->hasPermission('stage2_admin content_administrator')) return true;

    $account = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $uid= $account->get('uid')->value;

     $additional_data = db_query("SELECT additional_data from s2.log a where a.user=:user 
                                  and action=:action and additional_data->>'$cname'=:id",
      [':user'=>$uid, ':action'=>$action, ':id'=>$id])->fetchField();

     if ($additional_data !== false) {
      return true;
     }
     
     return false;
   }


   /**** Log *****/
   public static function stageLog($action,$report,$additional_data=null){
      $account = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
  		$uid= $account->get('uid')->value;

      $entity = array();
      $entity['user'] = $uid;
      $entity['action'] = $action;
      $entity['report'] = $report;
      $entity['additional_data'] = $additional_data;
      $entity['modified'] = DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s');


     $return_value = db_insert('s2.log')
     ->fields($entity)
     ->execute();
    return false;
   }

   // *********** Menu tree ******************

   /**
   * @param $pic_base64 encoded string
   * @param $var_id
   */
   public static function stage2picture2blob($pic_base64,$var_tree_id){

     $que = db_update('s2.var_names');
     $que->condition('var_tree_id',array($var_tree_id));

		 if ($pic_base64){
			 $que ->fields(array(
				 'picture'=> $pic_base64,
			 ))
			 ->execute();
		 }
		 else {
				 db_query("UPDATE s2.var_names SET picture = NULL WHERE var_tree_id = ".$var_tree_id);
		 }
   }
   public static function fetchVarPic($var_tree_id){
     $que = db_select('s2.var_names','names');
     $que->addField('names', 'picture', 'picture');
     $que->condition('names.var_tree_id',$var_tree_id);
     $result = $que->execute()->fetch();
     return $result->picture ? $result->picture :t('The picture is not available.');
   }

   public static function stage2GetAvailableAcronyms(){
      $que = db_select('s2.var_names', 'vn');
      $que->fields('vn',array('id','short_name'));
      $query = $que->execute();
      $return = $query->fetchAllKeyed();
      return $return;
   }
   public static function stage2getdelineationformula($id){

     $que = db_select('s2.var_names','var_names');
     $que->condition('var_tree_id',array($id));
     $que->fields('var_names',array(
       'delineation'
     ));
     $query = $que->execute();

    $formula = $query->fetchAssoc()['delineation'];

     return $formula;
   }
   public static function stage2updatedelineationformula($formula,$id){

     $que = db_update('s2.var_names');
     $que->condition('var_tree_id',array($id));
     $que ->fields(array(
       'delineation'=>  $formula,
     ))
     ->execute();

     return false;
   }
	 public static function load_all_acronyms(){

		 $que = db_select('s2.var_names','var_names');
		 $que->fields('var_names',array(
			 'short_name'
		 ));
     $que  ->orderBy('var_names.id', 'ASC');
		 $query = $que->execute();

		 $result = json_encode((array_keys($query->fetchAllAssoc('short_name'))));
		//  $result = '';//$query->fetchAssoc()['short_name'];

		 return $result;
	 }

   // ************ Variables ******************

   public static function  loadVariables(){
     $que = db_select('s2.var_values','var_values');
     $que->range(0, 1000000);
     $que-> fields('var_values',array('spatial_layer_id','published'));
     $que->join ('s2.var_names', 'names', 'var_values.var_names_id = names.id');
     $que->join ('s2.spatial_layer', 'spatial_layer', 'var_values.spatial_layer_id = spatial_layer.id');
     $que->addField('names', 'id','id_name');
     $que->addField('names', 'name','names_name');
     $que->addField('names', 'short_name','names_hort_name');
     $que->addField('spatial_layer', 'name','spatial_layer_name');
     $que->addField('spatial_layer', 'id','id_spatial_layer');
     $que->addField('var_values', 'modified','modified');
     $que  ->orderBy('spatial_layer.weight', 'DESC');
    //  $que->fields('names', array('id'))
    //      ->fields('names', array('name'));

    return $que->execute()->fetchAll();
   }

   /**
     * Function deletes selected variables from 's2.var_values' used in form delete variables
     */
   public static function deleteVariables($entry = array()) {

     foreach($entry as $row){
       $var_names_id = explode('_',$row)[0];
       $spatial_layer_id = explode('_',$row)[1];

       $ids = self::get_permitted_ids($spatial_layer_id);

       $que = db_delete('s2.var_values');
       $que->condition('var_names_id', $var_names_id);

       if (count($ids) > 0) {
         $que -> condition('id',$ids,'IN');
       }

       $que->condition('spatial_layer_id', $spatial_layer_id);
       $que->execute();
     }
   }

   public static function deleteVariablesbyId($entry = array()) {
     $ids = self::get_permitted_ids();
     foreach($entry as $key => $value){
       $que=db_delete('s2.var_values');
       $que->condition('id', $value);
       if (count($ids) > 0) {
        $que -> condition('id',$ids,'IN');
       }
       $que->execute();
     }
   }

   public static function unpublishVariablesvar_ds_id($var_ds_id){
    $ids = self::get_permitted_ids();
     foreach($var_ds_id as $key => $value){
       $que = db_update('s2.var_values');
       $que->condition('id', $value);
       if (count($ids) > 0) {
        $que -> condition('id',$ids,'IN');
       }
       $que ->fields(array(
         'published'=>  0,
         'publish_on'=>  null,
         //  'modified'=>  DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s'), // TODO UNCOMENT WHEN modified column is created
       ))
       ->execute();
     }

   }

   public static function get_permitted_ids($spatial_layer_id=null) {
    $user = \Drupal::currentUser();
    if (!$user->hasPermission('stage2_admin content_administrator')) {
      $account = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
      $uid= $account->get('uid')->value;
      $params = [
        ':uid'=>$uid
      ];
      $sid="";
      if (!is_null($spatial_layer_id)) {
        $sid="and additional_data->>'spatial_layer_id'=:spatial_layer_id";
        $params[':spatial_layer_id'] = $spatial_layer_id;
      }
      $res=db_query("SELECT additional_data->>'ids' from s2.log a where a.user=:uid and action='batch import' or action='px import' $sid", $params);

      $ids = $res->fetchCol();

      $a = [-1];
      foreach($ids as $ida) {
        $a=array_merge($a,json_decode($ida,false));
      }

      drupal_set_message('The operation was restricted to the variables uploaded with this user account only. For the unrestricted access permission contact the system administrator.', 'warning');

      return $a;
    }
    return [];
   }

   // publish variable based on the spatial_layer_id && var_names_id
   public static function publish_status_update_layer_and_name($variables,$date){

    foreach ($variables as $key => $value) {

      $var_names_id = explode('_',$value)[0];
      $spatial_layer_id = explode('_',$value)[1];

      $ids = self::get_permitted_ids($spatial_layer_id);

      $que =  db_update('s2.var_values');
      $que -> condition('spatial_layer_id',array($spatial_layer_id));
      $que -> condition('var_names_id',array($var_names_id));

      if (count($ids) > 0) {
        $que -> condition('id',$ids,'IN');
      }
      $que -> fields(array(
        'publish_on'=>  $date,
        'published'=>  1,
        'modified'=>  DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s'),
      ))
      ->execute();

			$qu = db_select('s2.var_values','var_values');
			$qu -> condition('spatial_layer_id',array($spatial_layer_id));
      $qu -> condition('var_names_id',array($var_names_id));
      if (count($ids) > 0) {
        $qu -> condition('id',$ids,'IN');
      }
 			$qu-> fields('var_values',array('id','var_properties_id'));
 			$result = $qu->execute()->fetch();
      $var_val_id = $result->id;

			self::stage_inspire('publish',$var_val_id);
    }

   }

   public static function publishstatusupdatevar($var_names_id,$date,$column){
    $ids = self::get_permitted_ids();
    foreach ($var_names_id as $key => $value) {
       $que =  db_update('s2.var_values');
       $que -> condition($column,array($value));
       if (count($ids) > 0) {
         $que -> condition('id',$ids,'IN');
       }
       $que -> fields(array(
         'publish_on'=>  $date,
         'published'=>  1,
        //  'modified'=>  DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s'), // TODO UNCOMENT WHEN modified column is created
       ))
       ->execute();

			 $qu = db_select('s2.var_values','var_values');
       $que -> condition($column,array($value));
  			$qu-> fields('var_values',array('id','var_properties_id'));
  			$result = $qu->execute()->fetch();
 			$var_val_id = $result->id;

 			self::stage_inspire('publish',$var_val_id);


    }
   }

   public static function unpublishVariablesvar_names_id($var_names_id){
     foreach ($var_names_id as $key => $value) {

       $var_names_id = explode('_',$value)[0];
       $spatial_layer_id = explode('_',$value)[1];

       $ids = self::get_permitted_ids($spatial_layer_id);

       $que = db_update('s2.var_values');
       $que->condition('var_names_id',array($var_names_id));
       if (count($ids) > 0) {
        $que -> condition('id',$ids,'IN');
       }
       $que->condition('spatial_layer_id',array($spatial_layer_id));
       $que ->fields(array(
         'published'=>  0,
         'publish_on'=>  null,
        //  'modified'=>  DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s'), // TODO UNCOMENT WHEN modified column is created
       ))
       ->execute();


			 // self::stage_inspire('unpublish',$var_val_id); TODO unpublish
     }
   }

   public static function stagegetstatusbyid($id){

       switch ($id) {
          case 0:
              return t("Unpublished");
              break;
          case 1:
              return t("published");
              break;
          case 2:
              echo t("new values available");
              break;
      }
   }

   public static function stage_get_variables_by_id_and_layer($id_name,$id_spatial_layer){
     $que = db_select('s2.var_values','var_values');
     $que-> fields('var_values',array('id','spatial_layer_id','published','valid_from','var_properties_id','publish_on'));
     $que->join ('s2.var_names', 'names', 'var_values.var_names_id = names.id');
     $que->join ('s2.spatial_layer', 'spatial_layer', 'var_values.spatial_layer_id = spatial_layer.id');
     $que->orderBy('var_values.valid_from','DESC');
     $que->addField('names', 'id','id_name');
     $que->addField('names', 'name','names_name');
     $que->addField('spatial_layer', 'name','spatial_layer_name');
     $que->addField('spatial_layer', 'id','id_spatial_layer');
     $que->addField('var_values', 'modified','modified');
     $que->addField('var_values', 'inspire','inspire');
     $que->condition('var_names_id',array($id_name));
     $que->condition('spatial_layer_id',array($id_spatial_layer));

    return $que->execute()->fetchAllAssoc('id');
   }

    public static function get_publish_status($id_name,$id_spatial_layer){

      // get total number
      $que = db_select('s2.var_values','var_values');
      $que->condition('var_names_id',array($id_name));
      $que->condition('spatial_layer_id',array($id_spatial_layer));
      $que->addExpression('COUNT(*)', 'count');
      $result = $que->execute()->fetchAll();
      $total = $result[0]->count;

      // get number of published
      $que = db_select('s2.var_values','var_values');
      $que->condition('var_names_id',array($id_name));
      $que->condition('spatial_layer_id',array($id_spatial_layer));
      $que->condition('published',array('1'));
      $que->addExpression('COUNT(*)', 'count');
      $result = $que->execute()->fetchAll();
      $published = $result[0]->count;

      if ($published == $total){
        return 'published';
      }
      elseif ($published < $total && $published>0) {
        return 'partialy published';
      }
      elseif ($published == 0) {
        return 'unpublished';
      }
      else{
        return 'na';
      }
    }

    public static function updateSpatialLayer($var_ds_id,$sid){
      $ids = self::get_permitted_ids($sid);
      foreach($var_ds_id as $key => $value){
        $que = db_update('s2.var_values');
        $que->condition('id', $value);
        if (count($ids) > 0) {
          $que -> condition('id',$ids,'IN');
        }
        $que ->fields(array(
          'spatial_layer_id'=>  $sid,
          //  'modified'=>  DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s'), // TODO UNCOMENT WHEN modified column is created
        ))
        ->execute();
      }
        return false;
    }



   //************ Translations ****************
   //Get existing client label translations
   public static function stage2_client_label_translations($id_cli,$language_id){

     $que = db_select('s2.var_labels', 'var_labels');
  	 $que ->fields('var_labels',['id','id_cli','label','language','description']);
     $que->condition('id_cli', $id_cli);
     $que->condition('language', $language_id);
     $query = $que->execute();
     $query->allowRowCount = TRUE;
     $count = $query->rowCount();
     if ($count<>0){
       $return = $query->fetchAssoc()['label'];

       return $return;
     }
     return false;
   }
   // Update client label translations
   public static function stage2_client_label_translations_update($id_cli,$language_id,$label){

     $que = db_select('s2.var_labels', 'trans');
     $que->condition('id_cli',array($id_cli));
     $que->condition('language',array($language_id));
     $query = $que->execute();
     $query->allowRowCount = TRUE;
     $count = $query->rowCount();

    if ($count ==0 ){
      $return_value = db_insert('s2.var_labels')
      ->fields(array(
        'id_cli'=>   $id_cli,
        'language'=> $language_id,
        'label'=> $label,
      ))
       ->execute();
      return false;
    }
    else {
      $que = db_update('s2.var_labels');
      $que->condition('id_cli',array($id_cli));
      $que->condition('language',array($language_id));
      $que ->fields(array(
        'id_cli'=>  $id_cli,
        'label'=> $label,
        'language'=> $language_id,
      ))
      ->execute();
      return false;
    }

   }
   // Return translated string if it exists else return false
   public static function stage2_user_translations($table_name,$clumn_name,$orig_id,$language_id){
    $que = db_select('s2.translations', 'trans');
    $que->fields('trans',array('translation'));
    $que->condition('table_name', $table_name);
    $que->condition('column_name', $clumn_name);
    $que->condition('orig_id', $orig_id);
    $que->condition('language_id', $language_id);

    $query = $que->execute();
    $query->allowRowCount = TRUE;
    $count = $query->rowCount();
    if ($count<>0){
      $return = $query->fetchAssoc()['translation'];

      return $return;
    }
    return false;
   }

   public static function stage2_user_translations_update($table_name,$clumn_name,$orig_id,$language_id,$translation){

     $que = db_select('s2.translations', 'trans');
    //  $que->fields('labels',array('id_cli','language'));
     $que->condition('table_name',array($table_name));
     $que->condition('column_name',array($clumn_name));
     $que->condition('orig_id',array($orig_id));
     $que->condition('language_id',array($language_id));
     $query = $que->execute();
     $query->allowRowCount = TRUE;
     $count = $query->rowCount();

    if ($count ==0 ){
      $return_value = db_insert('s2.translations')
      ->fields(array(
        'table_name' =>  $table_name,
        'column_name' => $clumn_name,
        'orig_id'=> $orig_id,
        'language_id' => $language_id,
        'translation' => $translation,
      ))
       ->execute();
    }
    else{
      $que = db_update('s2.translations');
      $que->condition('table_name',array($table_name));
      $que->condition('column_name',array($clumn_name));
      $que->condition('orig_id',array($orig_id));
      $que->condition('language_id',array($language_id));
      $que ->fields(array(
        'table_name'=>  $table_name,
        'column_name'=> $clumn_name,
        'orig_id'=>     $orig_id,
        'language_id'=> $language_id,
        'translation'=> $translation,
      ))
      ->execute();
    }
   }

   public static function check_duplicate($table_name,$clumn_name,$orig_id,$language_id,$translation){

     // find duplicates to translate
     if ($table_name == 'var_properties' && $clumn_name=='data') {
      $que = db_query("SELECT DISTINCT s1.id from s2.{$table_name} s1
						INNER JOIN s2.{$table_name} s2
						ON s1.".str_replace(" ","_",$clumn_name)."->>'description' = s2.".str_replace(" ","_",$clumn_name)."->>'description'
						WHERE s1.id <> s2.id
							AND s1.id <> {$orig_id}
							AND s1.".str_replace(" ","_",$clumn_name)."->>'description' = (	SELECT ".str_replace(" ","_",$clumn_name)."->>'description'
											FROM s2.{$table_name}
                      WHERE id = {$orig_id})::TEXT");
     }
     else {
	    $que = db_query("SELECT DISTINCT s1.id from s2.{$table_name} s1
						INNER JOIN s2.{$table_name} s2
						ON s1.".str_replace(" ","_",$clumn_name)." = s2.".str_replace(" ","_",$clumn_name)."
						WHERE s1.id <> s2.id
							AND s1.id <> {$orig_id}
							AND s1.".str_replace(" ","_",$clumn_name)."::TEXT = (	SELECT ".str_replace(" ","_",$clumn_name)."
											FROM s2.{$table_name}
                      WHERE id = {$orig_id})::TEXT");
     }
		$result = $que->fetchAll();
		foreach($result as $duplicat){
			self::stage2_user_translations_update($table_name,$clumn_name,$duplicat->id,$language_id,$translation);
		}
   }
   //******************* settings **************************************

   /**
   * Update s2.spatial_layer set note_id to null in all table rows
   */
   public static function stage_update_spatial_layer(){
     $que = db_update('s2.spatial_layer');
     $que ->fields(array(
       'note_id'=>  null,
     ))
     ->execute();
     return false;
   }

   /**
   * Get coordinate systems (projections from the s2.crs)
   */
   public static function stage_get_crs(){
     $que = db_select('s2.crs','crs');
     $que->fields('crs',array('id','epsg_srid','proj4text','type'));
     $query = $que->execute();
     $existing = $query->fetchAll();
     return $existing;
   }
   /**
   * Get Available predefined srid codes
   */

   public static function stage_get_predefined_srid($limit = true){

    // get projectionts that are already defined
    $que = db_select('s2.crs','crs');
    $que->fields('crs',array('id','proj4text'));
    $query = $que->execute();
    $existing = $query->fetchAllKeyed();

    // add the slovenian projection that cannot be deleted
    $existing['3912'] = '';

    $que1 = db_select('spatial_ref_sys','proj');
    $que1->fields('proj',array('srid','auth_srid'));
    $que1->condition ('proj.auth_name',array('EPSG'));
    $que1->orderBy('srid','ASC');
    $query1 = $que1->execute();
    $result = $query1->fetchAllKeyed();

    $limit ? $return = array_diff_key ($result,$existing) : $return=$result;
    return $return;
   }

   public static function stage_generate_custom_srid_options($limit=true){
     // get projectionts that are already defined
     $que = db_select('s2.crs','crs');
     $que->fields('crs',array('id','proj4text'));
     $query = $que->execute();
     $existing = $query->fetchAllKeyed();

     // add the slovenian projection that cannot be deleted
     $existing['3912'] = '';
     $optional_cs = array_combine(range(1,50),range(1,50));

     $limit ?  $return = array_diff_key ($optional_cs,$existing) : $return =  $optional_cs;
     return $return;
   }

   // get crs details to populate edit Form
   public static function stage_get_crs_details($epsg_srid){
     $que = db_select('s2.crs','crs');
     $que  ->join('spatial_ref_sys', 'sr', 'sr.srid = crs.epsg_srid');
     $que->fields('crs',array('id','proj4text','epsg_srid','type'));
    //  $que->fields('sr',array('srtext','proj4text'));
     $que->addField('sr', 'srtext','srtext');
     $que->addField('sr', 'srtext','sr_srtext');
     $que->addField('sr', 'proj4text','sr_proj4text');
     $que->condition ('crs.epsg_srid',array($epsg_srid));
     $query = $que->execute();
     $details = $query->fetch();
     return $details;
   }

   // Save crs to the tables s2.cs & public.spatial_ref_sys
   public static function stage_create_new_crs($entry){

       db_insert('s2.crs')
         ->fields(array(
           'id'=> $entry['epsg_srid'],
           'epsg_srid'=> $entry['epsg_srid'],
           'proj4text'=> $entry['proj4text_crs'],
           'type'=> $entry['type'],
         ))->execute();

     if ($entry['type'] == 'manual'){

       db_insert('spatial_ref_sys')
       ->fields(array(
         'srid'=> $entry['epsg_srid'],
         'auth_name'=> 'stage2',
         'auth_srid'=> $entry['epsg_srid'],
         'srtext'=> $entry['srtext'],
         'proj4text'=> $entry['proj4text'],
       ))->execute();

      }
   }
    public static function stage_edit_crs($entry){

      $que = db_update('s2.crs');
      $que->condition('id',array($entry['epsg_srid']));
      $que ->fields(array(
        'proj4text'=> $entry['proj4text_crs'],
      ));
      $que->execute();
     if ($entry['type'] == 'manual'){
       $que = db_update('spatial_ref_sys');
       $que->condition('srid',array($entry['epsg_srid']));
       $que ->fields(array(
         'srtext'=> $entry['srtext'],
         'proj4text'=> $entry['proj4text'],
       )
     );
     $que->execute();
     }

    }

    public static function stage_get_crs_predefined_details($epsg_srid_predefined){

      $return = array(
        'proj4text' => false,
        'srtext' => false
      );
      // return false;
      if ($epsg_srid_predefined || $epsg_srid_predefined == '--'){


      $que = db_select('spatial_ref_sys','sr');
      $que->addField('sr', 'proj4text','proj4text');
      $que->addField('sr', 'srtext','srtext');
      $que->condition ('sr.srid',array($epsg_srid_predefined));
      $query = $que->execute();
      $result = $query->fetch();


        $return['proj4text'] = $result->proj4text;
        $return['srtext'] = $result->srtext;
        return $return;
      }else {
        return $return;

      }

    }
   //****************** Advances settings **********************************

   public static function stage_reset_advanced_settings(){
     // deleta all from advanced settings table
      db_delete('s2.advanced_settings') ->execute();
      // ** populate class breaks and classification_methods **
      $cb=[3,4,5,6,7,8,9];
      $class_breaks_values = array_combine($cb,$cb);
      $classification_method_values = array(0 => 'Manual',
                                            1 => 'Quantiles',
                                            2 => 'Equal intervals',
                                            4 => 'Jenks (natural breaks)');

      	db_query("INSERT INTO s2.advanced_settings (setting, value, description, access) VALUES ('class_breaks', :val, 'number of class breaks (colors) in map overlay', 0)",[':val'=>json_encode($class_breaks_values)]);
      	db_query("INSERT INTO s2.advanced_settings (setting, value, description, access) VALUES ('classification_methods', :val, 'classification methods', 0)",[':val'=>json_encode($classification_method_values)]);

				db_query("INSERT INTO s2.advanced_settings (setting, value, description, access)
		 							VALUES ('geonetwork', :val, 'geonetwork settings', 0)",[':val'=>json_encode(["proxy"=>"","enable"=>"false", "un"=>"admin", "pass"=>"admin","path"=>"http://localhost:8080"])]);

        // decimal places parametrs
        db_insert('s2.advanced_settings')
                  ->fields(array(
                   'setting'=>   'decimals_options',
                   'value'=> json_encode(array(
                     '0'=>0,
                     '1'=>1,
                     '2'=>2,
                     '3'=>3,
                     '4'=>4
                   )),
                   'description'=> 'decimals options in the parametrs form',
                   'access'=> 0,
                  ))->execute();

        // ** Fake populate client help
        $dummy = "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum";

        $encoded_help='{ "en": { "What is the app about?": "STAGE is a freely accessible interactive cartographic application to use indicators data on a mobile phone, tablet or on the web. This user-friendly application can display and evaluate statistics in different time units and spatial units. With STAGE you can also create custom statistics and compare the data as you want with few steps only. All data (images, files, charts) can be exported and used for further work.", "How can I help myself with the app?": "Are you involved in a spatial planning or governance and searching for a tool to display and analyze all kinds of statistics on the maps? Do you need to do some market research before you start your business? Are you simply interested to learn something new about your country and its attractiveness potential? ", "Who is the app intended for? ": "The application is intended for national and regional bodies dealing with space (development and protection), developmental and environmental agencies, municipalities, universities and research institutes, NGO, market research companies, tourist organizations, as well as all individuals for accessible and friendly use of statistical data. ", "How do I start using the app to get a map with the selected data?": "On the landing page of an application select the button \"variables\" or click on the menu, then \"variables\". In the menu choose the desired indicator. Then select a preferred time and spatial unit and the map will display initially. In the mobile version you first select the variable, then press icon \"i\" to select the spatial and time unit of the selected variable. If something is unclear, return to the section “about” to help you find the answers.", "About variables": "<b>I have already selected my variable. How to choose another variable? </b><br>To select a new variable follow the procedure as for the first variable. Click the variable icon (#) or open the menu bar where you select the \"variables\" and select your spatial and time unit.<br><br><b>Is it possible to play time animation?</b><br>Yes, it is possible to have a look at variables with a time animation. Animation is possible for each indicator if time units are available. For the animation select the variable (icon \"i\") and choose \"options\", then “animation”.", "View settings": "<b>How do I change a color palette of the map?</b><br>Go to the selected variable (icon \"i\") and select the desired color palette in the \"colours\" tab. <br><br><b>Can I make my displayed statistics transparent?</b><br>Of course, go to the selected variable (icon \"i\") and press \"settings\" - \"colours\" to choose the desired transparency. Transparency will be visible on the map immediately.<br><br><b>In how many class breaks can I classify desired variables?</b><br>Class breaks depend on the value of each variable, but normally from 3 to 9 classes per individual variable.<br><br><b>How can I classify the values of the variable (classification methods)? </b><br>There are several methods available. Quantile divides classes where each class contains an equal number of features. Equal interval divides the range of attribute values into equal-sized subranges. Natural break divide into classes with major differences between values (for specific data). Manual interval is for defining your own classes where you can manually add class breaks.<br><br><b>Can I change my background layer to get a white background or any other backgrounds?</b><br>Of course, press the icon \"layers\" on the right side of the map. You can choose different backgrounds: Mapbox, white background or Open Street Map.<br><br><b>Can I highlight borders of my displayed spatial unit?</b><br>You can highlight borders if you press the icon \"layers\" on the right side of the map and choose your layer.<br>", "Export": "<b>In which formats I can export maps and/ or statistics?</b><br>It is possible to export maps as images (PNG format), export SHP (shapefile) file to import into GIS or TSV file to import data tables in Microsoft Excel. If needed, you can also share the link on other websites.<br><br><b>What you need to be careful about when exporting an image?</b><br>To export an image (PNG format) select the desired variable (icon \"i\") and press \"options\", “image”. In the new display image title will be shown and under options you can define which of the following (scale, level, legend, description or footer) would like to use. Press the button “download as image”.<br>", "Delineation": "<b>How to display values (variables) on a chart?</b><br>All values are displayed in a bar chart using function delineation. First you need to choose one indicator (variable), press »delineation«, »add all« and chart will be displayed.<br><br><b>How to sort a chart?</b><br>Charts can be sorted numerically or alphabetically. Look at the icons above the chart.<br><br><b>The chart does not show me all the data that are displayed on the map, what should I do?</b><br>If you have many spatial data, not all chart elements will be displayed unless pressing the icon (...) for displaying all data. Afterwards, chart can be downloaded.<br><br><b>How to filter the elements that you would like to use in a chart?</b><br>With the function “filter chart elements” which can be found above the chart, you can draw specific elements that will be shown and compared in the chart. Elements can be defined using different methods: drawing points, circles, rectangles or other polygons.<br><br><b>Can I see how variables are changing through different time units?</b><br>Yes, you can make a time delineation of all chart elements with available time units. Press the icon “clock” (”time delineation”) to get a chart.<br><br><b>What is a child/ parent unit and how to use them?</b><br>Child/ parent unit represents spatial levels that correspond with your selected spatial unit (eg. countries – regions – municipalities). Select element on the map and press child/ parent unit if unit is available to get chart elements about your chosen area.<br><br><b>What does delineation statistics mean?</b><br>With delineation statistics it is possible to sum up the values in the charts that you made before. To use the function you need to delineate first. In one chart more spatial unit can be presented.<br>", "About": "<b>STAGE II.</b><br> Version: beta 0.1 <br>Licence: <a href=\"http://ec.europa.eu/idabc/eupl.html\">EUPL</a> " }, "sl": { "Kaj je Stage II?": "Stage II (STAtistika - Geografija) je interaktivna kartografska aplikacija, ki posameznika na dostopen in prijazen način vpelje v svet pregledovanja statističnih podatkov preko mobilnega telefona ali spleta. Aplikacija omogoča prikazovanje statističnih podatkov na različnih prostorskih ravneh (npr. na drÅ¾avni, regionalni in lokalni ravni) in v različnih časovnih obdobjih. Ustvarjamo pa lahko tudi statistiko po meri in združujemo posamezne prostorske oziroma časovne enote, kot želimo sami. Vse podatke lahko v obliki karte ali grafov izvozimo in uporabimo pri nadaljnjem delu.", "Kako si lahko z aplikacijo pomagam?": "Razmišljam o novem podjetju, pa želimo pred tem narediti raziskavo trga?<br><br>Te preprosto zanimajo zanimivi podatki o Sloveniji in njenih prebivalcih, npr. kateri del Slovenije je najmanj poseljen? Ali pa, kako se predvidena Å¾ivljenjska doba v Sloveniji skozi leta spreminja? <br><br>Se ukvarjaÅ¡ z načrtovanjem in upravljanjem prostora in potrebuješ orodje za prikaz statističnih podatkov?<br><br>Želiš primerjati statistične podatke med leti, glede na spol, posamezna prostorska območja, itd.?", "Komu je aplikacija namenjena?": "Aplikacija je namenjena nacionalnim in regionalnim organom, ki se ukvarjajo s prostorom (razvoj in varovanje), razvojnim in okoljskim agencijam, lokalni samoupravi , univerzam in raziskovalnim inštitucijam, podjetjem za tržne raziskave, organizacijam, kot tudi vsem posameznikom za dostopno in prijazno uporabo statističnih podatkov.", "Kako začnem uporabljati aplikacijo, da dobim karto z izbranimi podatki?": "Ko vstopimo v aplikacijo, na prvi strani izberemo gumb \"spremenljivke\" ali klik na ikono \"menu\", nato \"spremenljivke\" . Prikaže se menu spremenljivk, v katerem izberemo željeno. Nato izberemo prostorsko in časovno enoto, ki nas zanima, na karti pa se izršejo podatki. V mobilni verziji se po izboru spremenljivke prikaže karta, s klikom na ikono \"i\" v menujski vrstici pa je mogoče izbrati prostorsko in časovno enoto izbrane spremenljivke. ", "Določitev spremenljivke": "\t<b>Kako začnem uporabljati aplikacijo, da dobim karto z izbranimi podatki?<\/b><br>Ko vstopimo v aplikacijo, na prvi strani izberemo gumb \"spremenljivke\" ali klik na ikono \"menu\", nato \"spremenljivke\" . Prikaže se menu spremenljivk, v katerem izberemo željeno. Nato izberemo prostorsko in časovno enoto, ki nas zanima, na karti pa se izrišejo podatki. V mobilni verziji se po izboru spremenljivke prikaže karta, s klikom na ikono \"i\" v menujski vrstici pa je mogoče izbrati prostorsko in časovno enoto izbrane spremenljivke. <br><br><b>Imam Že izbrano spremenljivko. Zanima me, kako izberem drugo spremenljivko oz. spremenim časovno ali prostorsko enoto spremenljivke?<\/b><br>Za izbor nove spremenljivke izberemo ikono spremenljivke (#) na levi strani, oziroma odpremo menijsko vrstico, kjer izberemo vrstico \"spremenljivke\", nato ponovimo korake kot pri prvi spremenljivki (izbor prostorske in časovne enote).<br><br><b>Ali si lahko ogledam določeno spremenljivko skozi časovne enote v obliki animacije? <\/b><br>Da, za ogled animacije izberemo izbrano spremenljivko (ikona \"i\"), nato pod funkcijo \"možnosti\" izberemo animacijo. Z animacijo si lahko ogledamo pregled vseh časovnih enot, ki so na voljo za izbrano spremenljivko.<br>", "Nastavitve pogleda": "<b>Kako spremenim barvno shemo karte?<\/b><br>\tGremo na izbrano spremenljivko (ikona \"i\") in v zavihku izberemo željeno barvno paleto. Ne pozabimo na gumb \"Dodaj spremembe\" na dnu strani.<br><br><b>Ali lahko naredim karto transparentno?<\/b><br>\tSeveda, gremo na izbrano spremenljivko (ikona \"i\") in v zavihku nastavitve\" - \" izberemo željeno transparentnost. Transparentnost bo na karti vidna takoj.<br><br>\t<b>V koliko razredov lahko razvrstim vrednosti spremenljivke?<\/b><br> Razredi so odvisni od vrednosti posamezne spremenljivke, vendar lahko običajno določimo od 3 do 9 razredov za posamezno spremenljivko. Več kot 9, manj kot 3???<br><br><b>Na kakšne načine lahko razvrstim vrednosti spremenljivke (metode klasifikacije)?<\/b><br>\tNa voljo je več metod.<br><br>\tKvantili: delitev v razrede, kjer ima vsak razred enako število vrednosti<br><br>Enaki intervali:\tNaravne meje: delitev v razrede, kjer so večje razlike med vrednostmi (za specifične podatke)<br><br>\tRočni intervali: določitev lastnih razredov<br><br>\t<b>Potrebujem belo podlago na karti, ali lahko kartografsko podlago spremenim?<\/b><br>Povsem preprosto, podlago spremenimo s klikom na ikono \"sloji\"na desni strani karte. Na voljo je pogled Mapbox z označbo mest, belo ozadje ali pogled Open Street Map (karta, ki je nastavljena avtomatsko).<br>", "Izvoz vsebine": "<b>Na katere načine lahko izvozim karto in statistične podatke?<\/b><br>\tNa voljo je izvoz karte kot slike (PNG format), izvoz SHP (shapefile) datoteke za uvoz v sistem GIS ali TSV datoteke za uvoz podatkovnih tabel v Microsoft Excell. Prav tako je mogoče povezavo spletne strani in karte vključiti v druge spletne vsebine. Karto in podatke lahko izvozimo ob izboru izbrane spremenljivke (ikona Â»iÂ«), nato izberemo zavihek \"možnosti.\"<br><br><b>želim izvoziti sliko karte, na kaj moram paziti?<\/b><br>Za izvoz slike izberemo izbrano spremenljivko (ikona \"i\") in pod \"možnostmi\" izberemo \"slika\". V novem prikazu napišemo naslov slike ter označimo, kaj od naštetega (merilo, prostorska enota, legenda, opis slike ali noga) želimo imeti prikazano na karti. Nato izberemo gumb \"izvozi sliko\".<br>", "Različica": "<b>STAGE II.<\/b><br> Različica: beta 0.1 <br>Licenca: <a href=\"http:\/\/ec.europa.eu\/idabc\/eupl.html\">EUPL<\/a> " } }';

        db_insert('s2.advanced_settings')
        ->fields(array(
          'setting'=>   'help',
          'value'=> $encoded_help,
          'description'=> 'client help',
          'access'=> 0,
        ))
         ->execute();

         // load essential clinet picture export settings
         db_insert('s2.advanced_settings')
           ->fields(array(
            'setting'=>   'export_img_settings',
            'value'=> json_encode(array('measurements'=>array(
                                          'cw' => 2970,
                                          'ch' => 2100
                                        ),
                                        'labels'=>array(
                                          'en' => array(
                                            'description_label' => 'DESCRIPTION',
                                            'content_label' => 'CONTENT',
                                            'legend_label' => 'LEGEND',
                                            'level_label' => 'LEVEL',
                                            'copyright' => '© Statistical Office of the Republic of Slovenia. Use and publication of data is allowed provided the source is acknowledged.',
                                            'max_signs' => 'caharacter limit: ',
                                            'map_title' => 'MAP TITLE',
                                            'no_title' => 'Please enter map title',
                                            'footer_txt' => 'Land and maritime border between the Republic of Slovenia and the Republic of Croatia is a matter of ongoing arbitration proceedings (in accordance with the Arbitration agreement between the Government of the Republic of Slovenia and the Government of the Republic of Croatia signed on 4 November 2009). Therefore this application STAGE is without prejudice to the border between the Republic of Slovenia and the Republic of Croatia.'
                                          ),
                                          'sl'=> array(
                                            'description_label' => 'OPIS',
                                            'content_label' => 'VSEBINA',
                                            'legend_label' => 'LEGENDA',
                                            'copyright' => '© Statistični urad Republike Slovenije. Uporaba in objava podatkov dovoljeni le z navedbo vira.',
                                            'max_signs' => 'maksimalno število znakov: ',
                                            'level_label' => 'RAVEN PRIKAZA',
                                            'map_title' =>  'NASLOV KARTE',
																						'no_title' => 'Vpišite naslov karte',
                                            'footer_txt' => 'Meja med Republiko Slovenijo in Republiko Hrvaško na kopnem in morju je predmet arbitražnega postopka (v skladu z Arbitražnim sporazumom med Vlado Republike Slovenije in Vlado Republike Hrvaške z dne 4. novembra 2009). Nič v aplikaciji STAGE ne pomeni prejudica meje med Republiko Slovenijo in Republiko Hrvaško.'

                                          )
                                        )
                                      ),JSON_UNESCAPED_UNICODE),
            'description'=> 'client picture export settings',
            'access'=> 0,
           ))->execute();

      // *** reset geo srver settings
      db_insert('s2.advanced_settings')
              ->fields(array(
               'setting'=>   'gsrv',
               'value'=> json_encode(array('port'=>8080,
                                           'hostname'=>'stage2-geoserver',
                                           'protocol'=>'http',
                                           'path'=>'geoserver',
                                           'username'=>'admin',
                                           'password'=>'myawesomegeoserver'),JSON_UNESCAPED_UNICODE),
               'description'=> 'geoserver connection properties',
               'access'=> 0,
              ))->execute();

     drupal_set_message('check geoserver port and password','warning');
      // *** reset footer settings
      db_insert('s2.advanced_settings')
              ->fields(array(
               'setting'=>   'footer_wm',
               'value'=> json_encode(array('en'=>'Land and maritime border between the Republic of Slovenia and the Republic of Croatia is a matter of ongoing arbitration proceedings (in accordance with the Arbitration agreement between the Government of the Republic of Slovenia and the Government of the Republic of Croatia signed on 4 November 2009). Therefore this application STAGE is without prejudice to the border between the Republic of Slovenia and the Republic of Croatia.',
                                           'sl'=>'Meja med Republiko Slovenijo in Republiko HrvaÅ¡ko na kopnem in morju je predmet arbitraÅ¾nega postopka (v skladu z ArbitraÅ¾nim sporazumom med Vlado Republike Slovenije in Vlado Republike HrvaÅ¡ke z dne 4. novembra 2009). Nič v aplikaciji STAGE ne pomeni prejudica meje med Republiko Slovenijo in Republiko HrvaÅ¡ko.'
                                         ),JSON_UNESCAPED_UNICODE),
               'description'=> 'client footer',
               'access'=> 0,
              ))->execute();

    // default language
    db_insert('s2.advanced_settings')
              ->fields(array(
               'setting'=>   'default_lc',
               'value'=> json_encode(array('lc'=>'en')),
               'description'=> 'default language code',
               'access'=> 0,
              ))->execute();


	// tile layers
	$layers[0] = ["name" => "Mapbox",
				"url" => "https://api.mapbox.com/styles/v1/smeza/cizzc9jam00g52spf0rdv0204/tiles/256/{z}/{x}/{y}?access_token=pk.eyJ1Ijoic21lemEiLCJhIjoiQU4wbE5WNCJ9.8v4KP1SfruAS68XBfB3uQQ",
				"enabled" => 1
			];
	$layers[1] = ["name" => "White background",
				"url" => "white.png",
				"enabled" => 1
			];
 $layers[2] = ["name" => "Open Street Map",
			"url" => "http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
			"attribution" => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
			"subdomains" => ['a','b','c'],
			"enabled" => 1
		];

	db_insert('s2.advanced_settings')
              ->fields(array(
               'setting'=>   'tile_layers',
               'value'=> json_encode($layers),
               'description'=> 'tile layers shown in client app',
               'access'=> 0,
              ))->execute();



	db_insert('s2.advanced_settings')
              ->fields(array(
               'setting'=>   'client_path',
               'value'=> json_encode(["base"=>""]),
               'description'=> 'tile layers shown in client app',
               'access'=> 0,
              ))->execute();


	//***********************************************************************
	// ** Populate landing page

	$landing_page= array(
		'sl'=>array(
			'<img src="logos/STAGE_cover.png" alt="Mountain View" class="front_img"><br>Stage II je interaktivna kartografska aplikacija za prikazovanje statističnih podatkov o Sloveniji ter orodje za spremljanje stanja v prostoru.<br><br><button type="button" name="variables_link" id="btn_variables" class="style_button">Spremenljivke</button><button type="button" id="btn_about" class="style_button">O stage</button>'
		),
		'en'=>array(
			'<img src="logos/STAGE_cover.png" alt="Mountain View" class="front_img"><br>Stage II is an interactive cartographic application for displaying statistical data about Slovenia. It provides interactive web map viewer as well as provides powerfull analytical tools for spatial querying.<br><br><button type="button" name="variables_link" id="btn_variables" class="style_button">Variables</button><button type="button" id="btn_about" class="style_button">About</button>'
		)
);
	$encoded_landing_page = json_encode($landing_page,JSON_UNESCAPED_UNICODE);
	db_insert('s2.advanced_settings')
	->fields(array(
		'setting'=>   'landing_page',
		'value'=> $encoded_landing_page,
		'description'=> 'landing page content',
		'access'=> 0,
	))
	 ->execute();
	//***********************************************************************
   }

   public static function stage_get_available_adv_settings_names(){
     $que = db_select('s2.advanced_settings','adv');
     $que->fields('adv',array('id','setting'));
     $query = $que->execute();
     $return = $query->fetchAllKeyed();
     return $return;

   }

   public static function stage_get_advanced_settings(){
     $que = db_select('s2.advanced_settings','adv');
     $que->fields('adv',array('id','setting','value','description'));
     $query = $que->execute();
     $return = $query->fetchAllAssoc('id');
     return $return;
   }

   public static function stage_update_advanced_setting($name,$setting_name,$value,$description){

     if ($name == -1){

       $entity = array();
       $entity['setting'] = $setting_name;
       $entity['value'] = $value;
       $entity['description'] = $description;
       $entity['access'] = 0;

      $return_value = db_insert('s2.advanced_settings')
      ->fields($entity)
      ->execute();

     }
     else{
       $que = db_update('s2.advanced_settings');
       $que->condition('setting',array($setting_name));
       $que ->fields(array(
        //  'value'=>  json_encode(json_decode($value,true),JSON_UNESCAPED_UNICODE),
         'value'=>  $value,
         'description'=>  $description,
       ));
      //  ->execute();
       $return_value = $que->execute();
     }
   }

	 // Get duplicated layer id's
	 public static function stage_get_duplicated_layers(){

     $que = db_query("SELECT * FROM s2.spatial_layer_date au where (select count (*) from s2.spatial_layer_date inf where au.spatial_layer_id = inf.spatial_layer_id and au.valid_from::DATE = inf.valid_from::DATE) >1")->fetchAllKeyed();
		 if (!count($que)>0){
			 return array();
		 }
		 return $que;

	 }

  public static function stage_get_existing_param ($exist_var_name,$exist_var_su,$exist_var_date){

    return db_query("SELECT vv.var_properties_id FROM s2.var_values vv JOIN s2.var_names vn on vv.var_names_id = vn.id
      where vv.spatial_layer_id = $exist_var_su AND vn.short_name ='$exist_var_name' ORDER BY abs(vv.valid_from::date - '$exist_var_date'::date)")->fetchField();

  }

//********** INSPIRE ***************
	 /**
	 * $id integer the id from the s2.var_properties table
	 */
	 public static function stage_inspire($task,$var_val_id,$variable = false,$tree_menu=[]){


		 $constructGN = new add_gnXML();

     $gn=json_decode(db_query("SELECT value from s2.advanced_settings where setting='geonetwork'")->fetchField());
		 if ($gn->enable!=='true'){
      // drupal_set_message('GEONETWORK disabled','warning');
			 return;
		 }
		 $url = $gn->path;
		 $un = $gn->un;
		 $pass = $gn->pass;
		 switch ($task) {
			 case 'get':

			 return $constructGN->gnGET_RECORD($var_val_id,$url);

			 break;
		 	case 'save':

      $name = $tree_menu[$variable['var_names_id']]['path'].'; '.$variable['valid_from'];

			$inspireXML = '<gmd:MD_Metadata xmlns:gmd="http://www.isotc211.org/2005/gmd" xmlns:gco="http://www.isotc211.org/2005/gco" xmlns:srv="http://www.isotc211.org/2005/srv" xmlns:gml="http://www.opengis.net/gml" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.isotc211.org/2005/gmd http://www.isotc211.org/2005/gmd/gmd.xsd http://www.isotc211.org/2005/gmx http://www.isotc211.org/2005/gmx/gmx.xsd http://www.isotc211.org/2005/srv http://schemas.opengis.net/iso/19139/20060504/srv/srv.xsd ">
				<gmd:fileIdentifier>
				<gco:CharacterString>'.$var_val_id.'</gco:CharacterString>
				</gmd:fileIdentifier>
				<gmd:language>
				<gco:CharacterString>sl</gco:CharacterString>
				</gmd:language>
				<gmd:hierarchyLevel>
				<gmd:MD_ScopeCode codeSpace="ISOTC211/19115" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#MD_ScopeCode" codeListValue="dataset"/>
				</gmd:hierarchyLevel>
				<gmd:contact>
				<gmd:CI_ResponsibleParty>
				<gmd:organisationName>
				<gco:CharacterString>SURS</gco:CharacterString>
				</gmd:organisationName>
				<gmd:contactInfo>
				<gmd:CI_Contact>
				<gmd:address>
				<gmd:CI_Address>
				<gmd:electronicMailAddress>
				<gco:CharacterString>info@surs.si</gco:CharacterString>
				</gmd:electronicMailAddress>
				</gmd:CI_Address>
				</gmd:address>
				</gmd:CI_Contact>
				</gmd:contactInfo>
				<gmd:role>
				<gmd:CI_RoleCode codeSpace="ISOTC211/19115" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_RoleCode" codeListValue="pointOfContact"/>
				</gmd:role>
				</gmd:CI_ResponsibleParty>
				</gmd:contact>
				<gmd:dateStamp>
				<gco:DateTime>2017-11-13T13:53:15</gco:DateTime>
				</gmd:dateStamp>
				<gmd:metadataStandardName>
				<gco:CharacterString>ISO 19139/19115 Metadata for Datasets</gco:CharacterString>
				</gmd:metadataStandardName>
				<gmd:metadataStandardVersion>
				<gco:CharacterString>2003</gco:CharacterString>
				</gmd:metadataStandardVersion>
				<gmd:identificationInfo>
				<gmd:MD_DataIdentification>
				<gmd:citation>
				<gmd:CI_Citation>
				<gmd:title>
				<gco:CharacterString>'.$name.'</gco:CharacterString>
				</gmd:title>
				<gmd:date>
				<gmd:CI_Date>
				<gmd:date>
				<gco:Date>2014-10-22</gco:Date>
				</gmd:date>
				<gmd:dateType>
				<gmd:CI_DateTypeCode codeSpace="ISOTC211/19115" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_DateTypeCode" codeListValue="publication"/>
				</gmd:dateType>
				</gmd:CI_Date>
				</gmd:date>
				</gmd:CI_Citation>
				</gmd:citation>
				<gmd:pointOfContact>
				<gmd:CI_ResponsibleParty>
				<gmd:organisationName>
				<gco:CharacterString>SURS</gco:CharacterString>
				</gmd:organisationName>
				<gmd:contactInfo>
				<gmd:CI_Contact>
				<gmd:address>
				<gmd:CI_Address>
				<gmd:electronicMailAddress gco:nilReason="missing">
				<gco:CharacterString/>
				</gmd:electronicMailAddress>
				</gmd:CI_Address>
				</gmd:address>
				</gmd:CI_Contact>
				</gmd:contactInfo>
				<gmd:role>
				<gmd:CI_RoleCode codeSpace="ISOTC211/19115" codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/ML_gmxCodelists.xml#CI_RoleCode" codeListValue="pointOfContact"/>
				</gmd:role>
				</gmd:CI_ResponsibleParty>
				</gmd:pointOfContact>
				<gmd:descriptiveKeywords>
	      </gmd:descriptiveKeywords>
				<gmd:language>
				<gco:CharacterString>sl</gco:CharacterString>
				</gmd:language>
				<gmd:extent>
				<gmd:EX_Extent>
				<gmd:geographicElement>
				<gmd:EX_GeographicBoundingBox>
				<gmd:westBoundLongitude>
				<gco:Decimal>13.153788</gco:Decimal>
				</gmd:westBoundLongitude>
				<gmd:eastBoundLongitude>
				<gco:Decimal>16.640718</gco:Decimal>
				</gmd:eastBoundLongitude>
				<gmd:southBoundLatitude>
				<gco:Decimal>45.399476</gco:Decimal>
				</gmd:southBoundLatitude>
				<gmd:northBoundLatitude>
				<gco:Decimal>46.892593</gco:Decimal>
				</gmd:northBoundLatitude>
				</gmd:EX_GeographicBoundingBox>
				</gmd:geographicElement>
				</gmd:EX_Extent>
				</gmd:extent>
				</gmd:MD_DataIdentification>
				</gmd:identificationInfo>
				</gmd:MD_Metadata>';


				$data = $constructGN->gnPUT_RECORD_XML($un,$pass,$url,$inspireXML);

				if ($data){
          $data = base64_encode(serialize($data));
          db_query("UPDATE s2.var_values
            SET inspire='$data'
            WHERE id = $var_val_id");

				}else{
            drupal_set_message('Geonetwork error',"warning");
        }
		 		break;
		 	case 'publish':

			$data = $constructGN->gnPUT_SHARE_RECORD($un,$pass,$url,$var_val_id);
		 		break;
		 	case 'unpublish':
		 		# code...
		 		break;

		 }
	 }

}
