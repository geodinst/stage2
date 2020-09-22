<?php
namespace Drupal\stage2_admin\Form_support;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\stage2_admin\StageFormsCommon;
use Drupal\Core\Database\Database;

class StageGeospatialLayersFormFunctions{


  public static function stage2_is_geospatiallayer_dependent_variables($id){

    $spatial_layer_id = StageGeospatialLayersFormFunctions::stage2_geo_layer_get_spatial_layer_id($id);
    $from = StageGeospatialLayersFormFunctions::stage2_geo_layer_get_valid_from($id);
    $dependent = StageGeospatialLayersFormFunctions::stage2_get_variables_on_date($from,$spatial_layer_id);
    if ($dependent>0){
      return true;
    }
    else{
      return false;
    }
  }
  
  public static function checkGeoserver($service,$tname){
    return $service->checkGeoserverLayer($tname);
  }

  public static function publishToGeoserver($service,$tname){
    if (self::checkGeoserver($service,$tname)===true) return;
    $schema = Database::getConnection()->schema();

		if ($schema->tableExists('ge.').$tname){

			if (!$schema->fieldExists('ge.'.$tname, 'idgid')){
        $schema->addField('ge.'.$tname, 'idgid', array('type' => 'int'));
      }

			db_update('ge.'.$tname)
        ->expression('idgid','__gid_')
        ->execute();

      $instanceName=StageFormsCommon::getInstanceName();
      $service->publishGeoserverLayer($tname,'stage',$instanceName);
      $service->putGeoserverLayerProperties($tname);
    }
  }

  public static function unpublishGeoserverLayer($service,$tname){
    $service->unpublishGeoserverLayer($tname);
  }

  public static function stage2_is_geospatiallayer_dependent_variables_count($entry){
    $from = $entry->valid_from;
    $spatial_layer_id = $entry->spatial_layer_id;
    $dependent = StageGeospatialLayersFormFunctions::stage2_get_variables_on_date($from,$spatial_layer_id);
    return $dependent;
  }

  public static function stage2_get_variables_on_date($from,$spatial_layer_id){
    $que = db_select('s2.var_values','var_val');
    $que->condition('spatial_layer_id', $spatial_layer_id);
    $que->condition('var_val.valid_from',array($from),'>=');
    $que->orderBy('valid_from', 'ASC');
    $query = $que->execute();
    $query->allowRowCount = TRUE;
    $count = $query->rowCount();
    return($count);
  }
  public static function stage2_geo_layer_get_valid_from($id) {
    $que = db_select('s2.spatial_layer_date','sld');
    $que->fields('sld',array('valid_from'));
    $que->condition('id', $id);
    $que->orderBy('valid_from', 'ASC');
    $result = $que->execute()->fetch();
    return $result->valid_from;
  }
  public static function stage2_geo_layer_get_spatial_layer_id($id) {
    $que = db_select('s2.spatial_layer_date','sld');
    $que->fields('sld',array('spatial_layer_id'));
    $que->condition('id', $id);
    $result = $que->execute()->fetch();
    return $result->spatial_layer_id;
  }

  // Function is used to produce the validity end date in the StageGeospatialLayersForm
	public static function stage2_geo_layer_from_to($entry){
		$from = $entry->valid_from;
    $to = StageGeospatialLayersFormFunctions::geo_layer_valid_to($entry, $from);
    $from_dt = DrupalDateTime::createFromFormat('Y-m-d H:i:s', $from);
    $from = $from_dt->format('Y-m-d');
    $to ? $to = $to->format('Y-m-d'): $to = '';
		return $from.' --> '.$to;
	}

  private static function geo_layer_valid_to($entry, $from){
    $from_dt = DrupalDateTime::createFromFormat('Y-m-d H:i:s', $from);
    $from = $from_dt->format('Y-m-d');
    $to = t('>');
    $que = db_select('s2.spatial_layer_date', 'sl');
    $que->fields('sl',array('id','valid_from','spatial_layer_id'));
    $que->condition('spatial_layer_id', $entry->spatial_layer_id);
    $que->orderBy('valid_from', 'ASC');
    $query = $que->execute();
    $query->allowRowCount = TRUE;
    $count = $query->rowCount();
    if ($count > 1){
      //Get all rows with the same spatial_layer_id
      $entires = $query->fetchAllKeyed();
      // find the element
      $key = array_search($entry->id,array_keys($entires));
      // slice the rest
      $slice = array_slice($entires,$key);
      if (isset($slice[1])){
        $to_dt = DrupalDateTime::createFromFormat('Y-m-d H:i:s', $slice[1]);
        return  $to_dt;
      }
    }
    return false;
  }

}
