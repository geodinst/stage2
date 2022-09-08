<?php

namespace Drupal\stage2_client;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\stage2_admin\StageFormsCommon;
use Drupal\stage2_admin\StageDatabase;
use \PDO;

class StageClientSM {


  public static function stage2_get_labels($language){
    $que = db_select('s2.var_labels', 'labels');

    if ($language !='en'){
      $que->fields('labels',array('id_cli','label'));
      $que->condition('language', $language);
      $trans_keyed = $que->execute()->fetchAllKeyed();

      $que_en = db_select('s2.var_labels', 'labels');
      $que_en->fields('labels',array('id_cli','label'));
      $que_en->condition('language', 'en');
      $en_keyed = $que_en->execute()->fetchAllKeyed();

      $trans_trans = array();
      foreach ($trans_keyed as $key => $value) {
        $trans_trans[$en_keyed[$key]] = $value;
      }
      return $trans_trans;

    }

    $que->condition('language', 'en');
    $que->fields('labels',array('label','label','language'));
    $eng_lab = $que->execute()->fetchAllKeyed();
    return $eng_lab;
  }


  public static function stage2_get_tree($language,$unpublished){

    $que = db_select('s2.var_tree','tree');
    $que->join('s2.var_names','names','tree.id = names.var_tree_id');
    $que->fields('tree',array('id','parent_id'));
    $que->orderBy("tree.position", "ASC");
    // get translations if language is not 'en'
    if ($language !='en'){
      $que->join('s2.translations','translations','names.id = translations.orig_id');
      $que->condition('translations.table_name','var_names');
      $que->condition('translations.column_name','name');
      $que->condition('translations.language_id',$language);
      $que->addField('translations', 'translation', 'name');
    }
    else {
      $que->fields('names',array('name'));
    }
    $que->orderBy('names.var_tree_id','ASC');
    $result = $que->execute()->fetchAll();
    // var_dump($result);
    $arr = array();
    foreach ($result as $key => $value) {

      // check if variable is already published exception is the root it is always passed
      $is_published = StageClientSM::stage2_is_variable_published($value->id);
      $has_published_children = StageClientSM::stage2_has_published_children($value->id);
      if ($is_published || $has_published_children ||$value->parent_id == 0 || $unpublished){
        $arr[] = array(
          'id'=>$value->id,
          'parentid'=>$value->parent_id,
          'name'=>$value->name
        );
      }
    }
    $tree = array();
    $new = array();

    $new = array();
    foreach ($arr as $a){
        $new[$a['parentid']][] = $a;
    }
    $tree = StageClientSM::createTree($new, array($arr[0]));
    return array_values($tree)[0];
  }


  public static function createTree(&$list, $parent){
      $tree = array();
      foreach ($parent as $k=>$l){
          if(isset($list[$l['id']])){
              $l['children'] = StageClientSM::createTree($list, $list[$l['id']]);
          }
          $tree[] = $l;
      }
      return $tree;
  }

  /**
  * function checks if the variable has subordinated nodes
  * @param $id integer id from the s2.var_tree
  */
  public static function stage2_has_published_children($id){
		// var_dump($id.' : ID spremenljivke za katero gledamo ali ima objavljene otroke: stage2_has_published_children.');
    $add_vatiable = false;
		$all_children = StageClientSM::stage2_all_subordinated_variable($id);
		if ($all_children){
			foreach ($all_children as $key => $value) {
				if(StageClientSM::stage2_is_variable_published($value)){
					$add_vatiable = true;
				}
			}
		}

    else{
      $add_vatiable = false;
    }
    return $add_vatiable;
  }


	function stage2_all_subordinated_variable($id, &$result = []) {
	    foreach (StageClientSM::stage2_subordinated_variable($id) as $f) {
				$result[] = $f;
	       StageClientSM::stage2_all_subordinated_variable($f,$result); // here is the recursive call
	    }

			return $result;
	}

	/** Return the array of id's of the subordinated variables (children)*/
	public static function stage2_subordinated_variable($id){
		$all_children = array();
		$que = db_select('s2.var_tree','values');
		$que->fields('values',array('id'));
    $que->orderBy("values.position", "ASC");
		$que->condition('parent_id',array($id));
		$query = $que->execute();
		$query->allowRowCount = TRUE;
		$count = $query->rowCount();
		if ($count>0){
			$children = $query->fetchAll();
			foreach ($children as $key => $value) {
				// var_dump($value->id);
				$all_children[] = $value->id;
			}
			return $all_children;
		}
		else{
			return false;
		}
	}



  /**
  * function checks if variable with selected id exists and its publish date has alleready passed and is therefore ready to be displayed in the client
  * @param $id integer id from the s2.var_tree
  */
  public static function stage2_is_variable_published($id){

    $now = DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s');
    $que = db_select('s2.var_values','values');
    $que->fields('values',array('id'));
    $que->condition('var_names_id',array($id));
    $que->condition('published',array(1));
    $que->condition('publish_on',array($now),'<');
    $query = $que->execute();
    $query->allowRowCount = TRUE;
    $count = $query->rowCount();
    if ($count>0){
      return true;
    }
    else{
      return false;
    }
  }

  public static function stage2_get_varspat($language,$var_tree_id,$unpublished){

    $now = DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s');

    $que = db_select('s2.var_values','values');
    $que->fields('values',array('id','valid_from','spatial_layer_id'));
    $que->join('s2.var_names','names','values.var_names_id = names.id');
    $que->join('s2.spatial_layer','layers','values.spatial_layer_id = layers.id');
    $que->fields('names',array('var_tree_id'));
    $que->condition('names.var_tree_id',$var_tree_id);
    if($unpublished!=='true'){
      $que->condition('values.publish_on',array($now),'<');
    }
    // get translations if language is not 'en'
    $que->addField('layers', 'id', 'su_id');
    $que->addField('layers', 'weight', 'su_weight');
	
    if ($language !='en'){
      $que->join('s2.translations','translations','values.spatial_layer_id = translations.orig_id');
      $que->condition('translations.table_name','spatial_layer');
      $que->condition('translations.column_name','name');
      $que->condition('translations.language_id',$language);
      $que->addField('translations', 'translation', 'name');
      $que->addField('translations', 'translation', 'popup_title');
      $que->addField('translations', 'translation', 'legend_title');

    }
    else {
      $que->fields('layers',array('name'));
      $que->fields('names',array('description'));
      $que->fields('names',array('popup_title'));
      $que->fields('names',array('legend_title'));

    }
    $que->orderBy('valid_from','DESC');
    $que->orderBy('su_weight','DESC');
    $query = $que->execute()->fetchAll();

    $result = array();
    foreach ($query as $key => $value) {
      // check if spatial layer exists for the given date
      $exists = db_query("select * from s2.spatial_layer_date where spatial_layer_id = :slid and valid_from <= :valid_from;",
      [ ':slid' => $value->spatial_layer_id,
        ':valid_from' => $value->valid_from
      ]
      )->fetchField();

      if(!$exists){
        continue;
      }
      if (!array_key_exists($value->spatial_layer_id,$result)){
        // $description = "fdsfds";
        $result[$value->spatial_layer_id] = array(
          'name'  => $value->name,
          'su_id'  => $value->su_id,
          'su_weight'  => $value->su_weight,
          'variable_description'  => $language!='en' ? self::get_desctiption_translate($language,$var_tree_id):$value->description,
          'dates' => array(
            array(
              'date' => $value->valid_from,
              'id'=> $value->id
            )
            )
        );
      }
      else{
        $dates = $result[$value->spatial_layer_id]['dates'];
        array_push($dates,
        array(
          'date'=> $value->valid_from,
          'id'=> $value->id
        )
      );
        $result[$value->spatial_layer_id]['dates'] = $dates;
      };

    }

    $popup_title  = $language!='en' ? self::get_tree_title_translate($language,$var_tree_id,'popup_title'):$value->popup_title;
    $legend_title  = $language!='en' ? self::get_tree_title_translate($language,$var_tree_id,'legend_title'):$value->legend_title;

    // SORT BY WEIGHT
    $weight = array();
    $available_su = array(); // prepare array with all spatial units
    foreach ($result as $key => $row)
    {
        $weight[$key] = $row['su_weight'];
        $available_su[] = $row['su_id'];
    }
    array_multisort($weight, SORT_DESC, $result);

    // GET THE PREFERED SU
    $que2 = db_select('s2.spatial_layer','sl');
    $que2->fields('sl',array('id','note_id'));
    $que2->condition('sl.note_id','1');
    $def_su = $que2->execute()->fetch()->id;

    $result1['popup_title']=$popup_title;
    $result1['legend_title']=$legend_title;
    $result1['result']= $result;
    $result1['prefered_su']= in_array ($def_su,$available_su) ? $def_su: false;
    return $result1;
  }


  public static function get_tree_title_translate($language,$var_tree_id,$title){
    $que = db_select('s2.translations','trans');
    $que->addField('trans', 'translation', $title);
    $que->condition('trans.language_id',$language);
    $que->condition('trans.column_name',$title);
    $que->condition('trans.table_name','var_names');
    $que->condition('trans.orig_id',$var_tree_id);
    $translated_description = $que->execute()->fetch()->{$title};

    return $translated_description;
  }
  public static function get_desctiption_translate($language,$var_tree_id){
    $que = db_select('s2.translations','trans');
    $que->addField('trans', 'translation', 'description');
    $que->condition('trans.language_id',$language);
    $que->condition('trans.column_name','description');
    $que->condition('trans.table_name','var_names');
    $que->condition('trans.orig_id',$var_tree_id);
    $translated_description = $que->execute()->fetch()->description;
    // $que->join('s2.translations','translations1','values.spatial_layer_id = translations.orig_id');

    return $translated_description;
  }

  private static function joinDataQuery($query,$valueMD,$fields=[],$innerJoin=false){
    $table_alias=$query->addJoin($innerJoin?'INNER':'LEFT OUTER', "sta.{$valueMD->sta_tname}",'sta',"ge.{$valueMD->ge_cnames['geo_code']}=%alias.$valueMD->sta_georef");

    foreach($fields as $alias=>$field){
      $query->addField($table_alias,$field,$alias);
    }

    return $query;
  }

  private static function getValuesDataSet($var_values_id,$join=true){
    $s1=$s2=$s3=$s4='';

    if ($join) {
      $s1='sld.id as spatial_layer_date_id,
          sld.table_name as table_name,
          sld.col_names as col_names,
          sld.crs_id as srid,';
      $s2='s2.spatial_layer_date sld,';
      $s3='var_values.spatial_layer_id=sld.spatial_layer_id and
                 var_values.valid_from >= sld.valid_from and ';
      $s4='order by sld.valid_from desc ';
    }

    $ds=db_query('select
                 date(var_values.valid_from) as var_values_valid_from,
                 var_values.data as data,
                 var_ds.ispx as ispx,
                 var_ds.georef as georef,
                 var_ds.tname as tname,'.
                 $s1.
                 'var_properties.data as prop

                 from
                 s2.var_ds var_ds,
                 s2.var_values var_values,'.
                 $s2.
                 's2.var_properties var_properties

                 where
                 var_properties.id=var_values.var_properties_id and
                 var_ds.id=var_values.var_ds_id and '.
                 $s3.
                 'var_values.id=:id '.
                 $s4.
                 'limit 1',array(':id'=>$var_values_id))->fetchObject();
    return $ds;
  }

  private static function getValuesDataSetsByDateAndSpatialLayerDateID($var_values_valid_from,$spatial_layer_date_id){
    $ds=db_query('select
                 var_ds.tname as tname,
                 sld.table_name as table_name,
                 sld.id as sld_id,
                 sld.col_names as col_names,
                 sld.crs_id as srid,
                 var_ds.ispx as ispx,
                 var_ds.georef as georef,
                 array_agg(var_names.short_name) as acronym,
                 array_agg(var_values.data) as data

                 from
                 s2.var_ds var_ds,
                 s2.var_values var_values,
                 s2.spatial_layer_date sld,
                 s2.var_names as var_names

                 where
                 var_names.id=var_values.var_names_id and
                 var_ds.id=var_values.var_ds_id and
                 var_values.spatial_layer_id=sld.spatial_layer_id and
                 sld.id=:spatial_layer_date_id and
                 var_values.valid_from = :var_values_valid_from
                 group by var_ds.tname,var_ds.ispx,var_ds.georef,sld.id,sld.table_name,sld.col_names',array(':spatial_layer_date_id'=>$spatial_layer_date_id,'var_values_valid_from'=>$var_values_valid_from))->fetchAll();
    return $ds;
  }

  public static function stage2_get_sldnames($spatial_layer_date_id){
    $result=db_query('select table_name,col_names from s2.spatial_layer_date where id=:id',array(':id'=>$spatial_layer_date_id))->fetchObject();
    $col_names=json_decode($result->col_names,true);
    $cname=$col_names['names_column'];
    $geo_code=$col_names['geo_code'];
    $tname=$result->table_name;
    return db_query("select $geo_code as id,$cname as name,idgid as idgid from ge.$tname order by __gid_")->fetchAll();
  }

  public static function stage2_get_sunotes($var_values_id) {
    $que = db_select('s2.var_values','vv');
		$que->addField('sn', 'id');
		$que->addField('sn', 'sid');
		$que->addField('sn', 'note');
		$que->join('s2.su_notes', 'sn', 'vv.var_properties_id = sn.var_properties_id');
		$que->condition('vv.id',$var_values_id);
		$sunotes = $que->execute()->fetchAll();

    return $sunotes;
  }

  public static function stage2_get_varval($var_values_id,$prop,$lang,$join=true,$innerJoin=false){
    $ds=self::getValuesDataSet($var_values_id,$join);
    $valueMD=StageFormsCommon::getValueMD($ds);

    if ($join){
      $query=db_select("ge.{$valueMD->ge_tname}",'ge');
      $query->addField('sta',$valueMD->sta_value_cname,'value');
      $query->addField('ge',$valueMD->ge_cnames['geo_code'],'geo_code');
      $query->addField('ge','idgid','idgid');
      $query->addField('ge',$valueMD->ge_cnames['names_column'],'geo_name');
      $query=self::joinDataQuery($query,$valueMD,[],$innerJoin);
      $query->orderBy('ge.__gid_','asc'); //this adds ge__gid_ to the 2nd select field
    }
    else{
      $query=db_select("sta.{$valueMD->sta_tname}",'sta');
      $query->fields('sta',[$valueMD->sta_value_cname]);
    }
    
    $values=[];
    $geoCodes=[];
    $geoNames=[];
    $idgids=[];

    if ($join) {
      $result=$query->execute();
      foreach($result as $r){
        $idgids[]=$r->idgid;
        $values[]=$r->value;
        $geoCodes[]=$r->geo_code;
        $geoNames[]=$r->geo_name;
      }
    }
    else{
      $values=$query->execute()->fetchCol();
    }

    $tname=$ds->table_name;

    if (!empty($tname)){
      $result=db_query("select count(1) as cnt,st_asgeojson(ST_Extent(geom)) as extents from ge.\"$tname\"")->fetchAll();
      $extents=$result[0]->extents;
      $cnt=$result[0]->cnt;
    }

    $return=['cnt'=>$cnt,
             'codes'=>$geoCodes,
             'names'=>$geoNames,
             'gids'=>$idgids,
             'data'=>$values,
             'table_name'=>$ds->table_name,
             'extents'=>$extents,
             'spatial_layer_date_id'=>$ds->spatial_layer_date_id];

    if ($prop) {
      $return['prop']=json_decode($ds->prop);
      $return['special_values']=self::get_translated_special_values($var_values_id, $lang);
    }


    /*********table names for all spatial units for the date of variable***************/
    $q=db_select('s2.spatial_layer_date','sld');
    $q->addExpression('distinct on (spatial_layer_id) spatial_layer_id','slid');
    $q->addExpression('borders','borders');
    $q->addExpression('valid_from','valid_from');
    $q->addExpression('sld.table_name', 'table_name');
    
    $q->where("extract (epoch from '{$ds->var_values_valid_from}'-valid_from) >= 0");
    $q->orderBy('slid', 'desc');
    $q->orderBy('valid_from', 'desc');

    $q->join('s2.spatial_layer','sl','spatial_layer_id = sl.id');
    $q->addExpression('sl.weight','weight');
    $q->orderBy('weight', 'desc');

    if ($lang !='en'){
      $q->join('s2.translations','translations','spatial_layer_id = translations.orig_id');
      $q->condition('translations.table_name','spatial_layer');
      $q->condition('translations.column_name','name');
      $q->condition('translations.language_id',$lang);
      $q->addExpression('translations.translation', 'name');
    }
    else {
      $q->addExpression('sl.name','name');
    }

    //print strtr((string) $q, $q->arguments());

    $res=$q->execute();
    $return['borders']=$res->fetchAll();
    /*
    $return['borders']=db_query("select slid,borders,table_name
             from (
              select sld.spatial_layer_id as slid,
                     sld.borders,
                     sld.table_name,
                     var_values.valid_from-sld.valid_from as diff,
                     min(var_values.valid_from-sld.valid_from) over (partition by sld.spatial_layer_id) as min_diff
              from s2.spatial_layer_date sld,
                   s2.var_values var_values
              where var_values.id=? and var_values.valid_from>=sld.valid_from
            ) t
            where diff = min_diff",[$var_values_id])->fetchAll();
    */

    return $return;
  }

  private static function createView($var_values_valid_from,$spatial_layer_date_id,$publishViewToGeoserver=true){
    $ds=self::getValuesDataSetsByDateAndSpatialLayerDateID($var_values_valid_from,$spatial_layer_date_id);
    $dsi=$ds[0];
    $valueMD=StageFormsCommon::getValueMD($dsi);
    $query=db_select("ge.{$valueMD->ge_tname}",'ge');
    $query->fields('ge',[$valueMD->ge_cnames['geo_code']]);
    $query->fields('ge',[$valueMD->ge_cnames['names_column']]);
    $query->addExpression('st_transform(geom,'.$dsi->srid.')','geom');

    $allTableAcronyms=[];
    $view_name='"'.'v'.$dsi->sld_id.'-'.$var_values_valid_from.'"';

    foreach($ds as $dsi){
        $valueMD=StageFormsCommon::getValueMD($dsi);
        $data=array_map(function($a){return json_decode($a,true);},json_decode('['.rtrim(ltrim($dsi->data,'{'),'}').']'));
        $export_cnames=array_map('strtolower',array_map('current',$data));
        $export_aliases=array_map('strtolower',explode(',',rtrim(ltrim($dsi->acronym,'{'),'}')));

        $fields=array_combine($export_aliases,$export_cnames);

        $query=self::joinDataQuery($query,$valueMD,$fields,true);

        $allTableAcronyms=array_merge($allTableAcronyms,$export_aliases);
    }

    db_query("drop view if exists ge.$view_name");
    db_query("create view ge.$view_name as ".(string)$query,(array)$query->arguments());

    if ($publishViewToGeoserver){
      self::publishViewToGeoserver($view_name);
    }

    return [$view_name,$allTableAcronyms];
  }

  private static function publishViewToGeoserver($view_name){
    $service = \Drupal::service('gi_services');
    $conn=db_query("SELECT value from s2.advanced_settings where setting='gsrv'")->fetchField();
    $service->initGeoserverCurlHandler($conn);
    $service->publishGeoserverLayer(trim($view_name,'"'),'stage',StageFormsCommon::getInstanceName());
  }

  private static function exportSHP($view_name){
    $view_name=trim($view_name,'"');
    $conn=db_query("SELECT value from s2.advanced_settings where setting='gsrv'")->fetchField();
    $conn=json_decode($conn);
    $url="{$conn->protocol}://{$conn->hostname}:{$conn->port}/{$conn->path}/stage/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=stage:$view_name&outputFormat=SHAPE-ZIP&format_options=CHARSET:UTF-8";

    header("Pragma: ");
		header("Cache-Control: ");

    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header('Content-Disposition: attachment; filename="'.$view_name.'.zip"');

		readfile($url);
  }

  /**
   * Returns the url to a geoserver served file.
   * @param $format {string} either SHAPE-ZIP or CSV
   * @param $var_values_id {integer} variable id found in the var_values.id column. If null than the following two parameters are to be set ($var_values_valid_from,$spatial_layer_date_id)
   * @param $var_values_valid_from {date} date of variable in the format YYYY-MM-DD (as found in the var_values.valid_from column). If null than the $var_values_id parameters has to be set.
   * @param $spatial_layer_date_id {integer} spatial layer id found in the $spatial_layer_date.id column. If null than the $var_values_id parameters has to be set.
   */

  public static function getFileUrl($format,$var_values_id,$var_values_valid_from,$spatial_layer_date_id){
    
    $format=strtoupper($format);
    if (!in_array($format,['SHAPE-ZIP','CSV','GPKG', 'XLSX'])){
      throw new \Exception(t('Only SHAPE-ZIP, CSV, GPKG and XLSX formats are supported'));
    }

    if (!empty($var_values_id)){
      $ds=self::getValuesDataSet($var_values_id);
      $cnames=json_decode($ds->col_names,true);
      $geo_code=$cnames['geo_code'];
      $names_column=$cnames['names_column'];
      $var_values_valid_from=$ds->var_values_valid_from;
      $spatial_layer_date_id=$ds->spatial_layer_date_id;
    }
    else{
      if (empty($var_values_valid_from) || empty($spatial_layer_date_id)){
        throw new \Exception(t('You must specify either var_values.id or (var_values.valid_from and spatial_layer_date.id)'));
      }
    }

    list($view_name,$allTableAcronyms)=self::createView($var_values_valid_from,$spatial_layer_date_id);

    $propertyName='';
    if (!empty($var_values_id) || $format=='CSV' || $format == 'XLSX'){
      $geom='geom,';
      if ($format=='CSV' || $format=='XLSX') $geom='';

      if (!empty($var_values_id)){
        $variable = \Drupal\stage2_admin\StageDatabase::loadVariables(array("id" => $var_values_id));
        $allTableAcronyms = array($variable[0]->short_name);
      }

      $propertyName=strtolower('&propertyName='.$geom."$geo_code,$names_column,".implode(',',$allTableAcronyms));
    }

    $view_name=trim($view_name,'"');
    $conn=db_query("SELECT value from s2.advanced_settings where setting='gsrv'")->fetchField();
    $conn=json_decode($conn);

    if ($format === 'XLSX') $format = 'excel2007';

	  return [
      "{$conn->protocol}://{$conn->hostname}:{$conn->port}/{$conn->path}/stage/ows?service=WFS&version=1.0.0&request=GetFeature$propertyName&typeName=stage:$view_name&outputFormat=$format&format_options=CHARSET:UTF-8",
      $view_name
    ];
  }

  /*
  *	Function prepares txt for export attachment
  * @param $var_values_id 	{integer}	variable id found in the var_values.id column
  * @param $acronym			{string} 	variable acronym, used for single variable (optional)
  */

  public static function txt2export($var_values_id, $acronym = ""){

    $ds=self::getValuesDataSet($var_values_id);
    list($view_name,$allTableAcronyms)=self::createView($ds->var_values_valid_from,$ds->spatial_layer_date_id);

	// get available languages
    $lang_codes = \Drupal::languageManager()->getLanguages();

    // assign language names
    foreach ($lang_codes as $key => $value){
      $languages[$key] =$value->getName() ;
    }

	// za vsak akronim pridobi prevode
	$translated_paths = array();
	foreach($languages as $key => $lang){
		$translated_paths[$key] = StageFormsCommon::treeStructure($key);
	}

	// get acronym ids
	$oldnames = \Drupal\stage2_admin\StageDatabase::getVariableNames();


	// set array keys to lowercase
	$names = array();
	foreach($oldnames as $key => $value){
		$names[strtoupper($key)] = $value;
	}

	$lang = 'en';
	$special_values = self::get_translated_special_values($var_values_id, $lang);

	$special_values_txt = '';

	if(!empty($special_values)){
		$special_values_txt.= "---------------------- <br>";
		//parse special values
		foreach ($special_values as $key => $value) {
			$special_values_txt .= $value->value.' : '.$value->legend_caption.'<br>';
		}
	}


	// column print text function
	function mb_str_pad($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT, $encoding = NULL)
	{
		$encoding = $encoding === NULL ? mb_internal_encoding() : $encoding;
		$padBefore = $dir === STR_PAD_BOTH || $dir === STR_PAD_LEFT;
		$padAfter = $dir === STR_PAD_BOTH || $dir === STR_PAD_RIGHT;
		$pad_len -= mb_strlen($str, $encoding);
		$targetLen = $padBefore && $padAfter ? $pad_len / 2 : $pad_len;
		$strToRepeatLen = mb_strlen($pad_str, $encoding);
		$repeatTimes = ceil($targetLen / $strToRepeatLen);
		$repeatedString = str_repeat($pad_str, max(0, $repeatTimes)); // safe if used with valid utf-8 strings
		$before = $padBefore ? mb_substr($repeatedString, 0, floor($targetLen), $encoding) : '';
		$after = $padAfter ? mb_substr($repeatedString, 0, ceil($targetLen), $encoding) : '';
		return $before . $str . $after;
	}

	// txt glava
	/*
	header('Content-type: text/plain; charset=utf-8');
	header('Content-disposition: attachment; filename="readme.txt"');
	*/
	if(isset($acronym) && $acronym != ""){
		$allTableAcronyms = array(strtoupper($acronym));
	}


	if(!empty($allTableAcronyms)){
		// header
		$fcontent = mb_str_pad("ACRONYM", 15);
		foreach($languages as $key => $lang){
			$fcontent .= mb_str_pad($lang, 50);
		}

		$fcontent .= "\r\n";
		$fcontent .= mb_str_pad("-",115,"-");
		$fcontent .= "\r\n";

		//html header
		$htmlc = '<tr><td><b>ACRONYM</b></td><td><b>'.$languages['en'].'</b></td><td><b>METADATA</b></td></tr>';

		// acronym loop
		foreach($allTableAcronyms as $acronym){
			$acronym = strtoupper($acronym);
			$acronymId = $names[$acronym]['var_tree_id'];
			$aname=strtolower($names[$acronym]['short_name']);
			$fcontent .= mb_str_pad($aname, 15);
			$htmlc .= "<tr><td>$aname</td><td>{$translated_paths['en'][$acronymId]['path']}</td><td>{$names[$acronym]['description']}{$special_values_txt}</td></tr>";

			foreach($languages as $key => $lang){
				$fcontent .= mb_str_pad($translated_paths[$key][$acronymId]['path'], 50);
			}

			$fcontent .= "\r\n";
		}

		return [$fcontent,$htmlc];

	} else{
		error_log("Error: StageClientSM::txt2export() - given value does not exist.");
	}
  }

  public static function html_table($htmlc){
    return '<!doctype html><html lang="en"><head><meta charset="utf-8"><title></title><style type="text/css">td{border:solid 1px #ededed;}</style></head><body><table><tbody>'.$htmlc.'</tbody></table></body></html>';
  }

  public static function shp2export($var_values_id, $all_variables, $format='SHAPE-ZIP'){
	 $ds=self::getValuesDataSet($var_values_id);

		 // get url for zip

     $fileUrl = null;
     $view_name = null;

		 if(!intval($all_variables)){
			list($fileUrl, $view_name) = self::getFileUrl($format,$var_values_id,null,null);
		 }else{
			list($fileUrl, $view_name) = self::getFileUrl($format,0,$ds->var_values_valid_from,$ds->spatial_layer_date_id);
		 }

	// table (= zip file) name
	$fileName = $ds->table_name;

		// shp
		$fileUrl = str_replace(" ", "%20", $fileUrl);
		
		$ctx = stream_context_create(array('http'=>
    		array(
        		'timeout' => 300,  //300 Seconds is 5 Minutes
    		)
		));
		
    //$shp = file_get_contents($fileUrl);
    $shp = file_get_contents($fileUrl, false, $ctx);
    
    if ($format === 'SHAPE-ZIP') {
      file_put_contents("sites/default/files/$fileName.zip", $shp);
    }
    else {
      $fname = "sites/default/files/$view_name.$format";
      file_put_contents($fname, $shp);
      $zip = new \ZipArchive;
      if ($zip->open("sites/default/files/$fileName.zip", \ZipArchive::CREATE)!==TRUE) {
        throw new \Exception("Cannot create <$fileName.zip>\n");
      }
      $zip->addFile($fname,"$view_name.$format");
      $zip->close();
    }

		// txt
    $acronym="";
    if(!intval($all_variables)){
      
			$variable = \Drupal\stage2_admin\StageDatabase::loadVariables(array("id" => $var_values_id));
			$acronym = $variable[0]->short_name;
		}

		list($text,$htmlc) = self::txt2export($var_values_id,$acronym);
		file_put_contents("sites/default/files/$fileName.txt", $text);
    file_put_contents("sites/default/files/$fileName.html", self::html_table($htmlc));

		// zip
		$zip = new \ZipArchive;
		if ($zip->open("sites/default/files/$fileName.zip") === TRUE) {
			$zip->addFile("sites/default/files/$fileName.txt","info.txt");
      $zip->addFile("sites/default/files/$fileName.html","info.html");
			$zip->deleteName('wfsrequest.txt');
			$zip->close();
		} else{
			echo "no zip ".$fileName;
		}

		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary");
		header("Content-Disposition: attachment; filename=STAGE_data.zip");

		readfile("sites/default/files/$fileName.zip");
		unlink("sites/default/files/$fileName.zip");
		unlink("sites/default/files/$fileName.txt");
}

public static function getViewData($viewName, $columns){
	$select = db_select('ge.'.$viewName, "vn");
	$select->fields("vn", $columns);
	return $select->execute()->fetchAll(PDO::FETCH_NUM);
}

public static function tsv2export($var_values_id, $all_variables, $format=null){
	$columns = ['__gid_'];
	$ds=self::getValuesDataSet($var_values_id);
    $var_values_valid_from=$ds->var_values_valid_from;
    $spatial_layer_date_id=$ds->spatial_layer_date_id;

    list($view_name,$allTableAcronyms)=self::createView($var_values_valid_from,$spatial_layer_date_id,false);

    sort($allTableAcronyms);

		if(!intval($all_variables)){
			$variable = \Drupal\stage2_admin\StageDatabase::loadVariables(array("id" => $var_values_id));
			$allTableAcronyms = array($variable[0]->short_name);
		 }

	// read view
	$cnames=json_decode($ds->col_names,true);
    $geo_code=$cnames['geo_code'];
    $names_column=$cnames['names_column'];
    $allTableAcronyms=implode(',',$allTableAcronyms);
    $viewData = db_query("select $geo_code,$names_column,$allTableAcronyms from ge.$view_name")->fetchAll();

	// prepare data
	$dataPrint = array();
	//--- header
	$header = array();
	foreach($viewData[0] as $key => $value){
		array_push($header, $key);
	}
	array_push($dataPrint, $header);
	//--- data
	foreach($viewData as $value){
		$row = array();
		foreach($value as $val){
			array_push($row, $val);
		}
		array_push($dataPrint, $row);
	}

		$tableName = $ds->table_name;

    $ext = 'tsv';

    if ($format==='xlsx') {
      require_once(__DIR__."/external/xlsxwriter.class.php");
      $writer = new \XLSXWriter();
      $writer->writeSheet($dataPrint);
      $writer->writeToFile("sites/default/files/$tableName.xlsx");
      $ext = 'xlsx';
    }
    else
    {
      // tsv file
      $file = fopen("sites/default/files/$tableName.tsv","w");
      foreach($dataPrint as $row){
        fputcsv($file,$row,"\t");
      }
      fclose($file);
    }

		 // txt file
		 $acronym = (!intval($all_variables))?$variable[0]->short_name:"";
		 list($text,$htmlc) = self::txt2export($var_values_id, $acronym);
		file_put_contents("sites/default/files/$tableName.txt", $text);
    file_put_contents("sites/default/files/$tableName.html", self::html_table($htmlc));

	// return [];
		 // zip file
		 $zip = new \ZipArchive;
		 if ($zip->open("sites/default/files/$tableName.zip",\ZipArchive::CREATE) === TRUE) {
			$zip->addFile("sites/default/files/$tableName.$ext","data.$ext");
			$zip->addFile("sites/default/files/$tableName.txt","info.txt");
      $zip->addFile("sites/default/files/$tableName.html","info.html");
			$zip->close();
		}


		// download in izbris zaÄasnih datotek
		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary");
		header("Content-Disposition: attachment; filename=STAGE_data.zip");
		readfile("sites/default/files/$tableName.zip");
		unlink("sites/default/files/$tableName.zip");
		unlink("sites/default/files/$tableName.txt");
		unlink("sites/default/files/$tableName.$ext");
	}

public static function exportLog($entry = array()){
		$return_value = NULL;
		try {
		  $return_value = db_insert('s2.var_download')
			->fields($entry)
			->execute();
		}
		catch (\Exception $e) {
		  drupal_set_message(t('db_insert failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}
	}

public static function export($var_values_id, $format, $all_variables){
		if($format == "SHAPE-ZIP"){
			self::shp2export($var_values_id, $all_variables);
		}
    elseif ($format == "TSV"){
			self::tsv2export($var_values_id, $all_variables);
		}
    elseif ($format == "gpkg"){
			self::shp2export($var_values_id, $all_variables, $format);
		}
    elseif ($format == "xlsx"){
			self::tsv2export($var_values_id, $all_variables, 'xlsx');
		}
    else {
      echo 'Unsupported format.';
      return;
    }

		// log export
		$logData = array(
						"var_values_id" => $var_values_id,
						"ip" => $_SERVER['REMOTE_ADDR'],
						"time" => date("Y-m-d H:m:s", time()));

		self::exportLog($logData);

	}

public static function layers(){
	$layers = \Drupal\stage2_admin\StageDatabaseSM::stage_get_advanced_settings();

	foreach($layers as $key => $layer){
		if($layer->setting == 'tile_layers'){
			$lkey = $key;
		}
	}

	$enabledLayers = array();
	foreach(json_decode($layers[$lkey]->value) as $layer){

		if($layer->enabled == 1){
			array_push($enabledLayers,$layer);
		}
	};

	return $enabledLayers;
}

  public static function stage2_get_varprop($var_values_id, $lang){
    $que = db_select('s2.var_values','values');
    $que->join('s2.var_properties','var_properties','values.var_properties_id = var_properties.id');
    $que->addField('var_properties', 'data', 'prop');
    $que->condition('values.id',$var_values_id);
    $result['prop'] = json_decode($que->execute()->fetchAll()[0]->prop);
	$result['special_values']=self::get_translated_special_values($var_values_id, $lang);
    // var_dump($result);
    return $result;
  }

  public static function stage2_get_allsettings(){
    // class breaks
    $que = db_select('s2.advanced_settings','settings');
    $que->fields('settings',array('value'));
    $que->condition('settings.setting','class_breaks');
    $class_breaks = json_decode($que->execute()->fetch()->value,true);
    
    //geoserver port
    $conn=db_query("SELECT value from s2.advanced_settings where setting='gsrv'")->fetchField();
    $port=json_decode($conn)->port;

    // classification methods
    $que1 = db_select('s2.advanced_settings','settings');
    $que1->fields('settings',array('value'));
    $que1->condition('settings.setting','classification_methods');
    $class_methods = json_decode($que1->execute()->fetch()->value,true);

    // get color openssl_get_cert_locations
    $colors = array(


      'Pastel2'=> array(
        "rgb(179,226,205)", "rgb(253,205,172)", "rgb(203,213,232)", "rgb(244,202,228)", "rgb(230,245,201)", "rgb(255,242,174)"
      ),
      'Set2'  	=> array(
        "rgb(102,194,165)", "rgb(252,141,98)", "rgb(141,160,203)", "rgb(231,138,195)", "rgb(166,216,84)", "rgb(255,217,47)"
      ),
      'RdYlGn' => array(
        "rgb(215,48,39)", "rgb(252,141,89)", "rgb(254,224,139)", "rgb(217,239,139)", "rgb(145,207,96)", "rgb(26,152,80)"
      ),
      'BrBG'  	=> array(
        "rgb(140,81,10)", "rgb(216,179,101)", "rgb(246,232,195)", "rgb(199,234,229)", "rgb(90,180,172)", "rgb(1,102,94)"
      ),
      'YlOrRd' => array(
        "rgb(255,255,178)", "rgb(254,217,118)", "rgb(254,178,76)", "rgb(253,141,60)", "rgb(240,59,32)", "rgb(189,0,38)"
      ),
      'Greens' => array(
        "rgb(237,248,233)", "rgb(199,233,192)", "rgb(161,217,155)", "rgb(116,196,118)", "rgb(49,163,84)", "rgb(0,109,44)"
      ),
      'Blues'  => array(
        "rgb(239,243,255)", "rgb(198,219,239)", "rgb(158,202,225)", "rgb(107,174,214)", "rgb(49,130,189)", "rgb(8,81,156)"
      ),
      'Purples'  => array(
        "rgb(242,240,247)", "rgb(218,218,235)", "rgb(188,189,220)", "rgb(158,154,200)", "rgb(117,107,177)", "rgb(84,39,143)"
      )
    );


    $result= array(
      'colors'=> $colors,
      'class_breaks' => $class_breaks,
      'classification_methods' => $class_methods,
      'port' => $port
    );
    return $result;
  }

  public static function stage2_get_geolay($var_values_id){

    $que = db_select('s2.var_values','values');
    $que->join('s2.spatial_layer_date','spatial_layer','values.spatial_layer_id = spatial_layer.spatial_layer_id');
    $que->addField('values', 'valid_from', 'values_valid_from');
    $que->addField('spatial_layer', 'valid_from', 'layer_valid_from');
    $que->addField('spatial_layer', 'table_name', 'table_name');
    $que->orderBy('layer_valid_from');
    $que->condition('values.id',$var_values_id);
    $result = $que->execute()->fetchAll();
    $table_name = $result[0]->table_name;

    foreach ($result as $key => $value) {
      $layer_date = strtotime($value->layer_valid_from);
      $variable_date = strtotime($value->values_valid_from);
      if ($layer_date > $variable_date){
        $table_name = $value->table_name;
      }
    }
    $return = array('table_name'=>$table_name);
    return $return;
  }

  public static function stage2_client_get_advanced_settings($setting){

    $que = db_select('s2.advanced_settings','adv');
    $que->fields('adv',array('value'));
    $que->condition('adv.setting',$setting);
    $vobj=$que->execute()->fetch();
    $result = $vobj?json_decode($vobj->value,true):"";
    return $result;

}
public static function stage2_client_get_var_img($var_tree_id){
  $que = db_select('s2.var_names','names');
  $que->addField('names', 'picture', 'picture');
  $que->condition('names.var_tree_id',$var_tree_id);
  $result = $que->execute()->fetch();
  return ($result->picture);
}

	public static function stage2_client_get_varpropdesc($var_values_id,$lang){

		$return = [];
		$que = db_select('s2.var_values','values');
		$que->join('s2.var_properties','var_properties','values.var_properties_id = var_properties.id');
		$que->addField('var_properties', 'data', 'prop');
		$que->addField('var_properties', 'id', 'prop_id');
		$que->condition('values.id',$var_values_id);
		$result = $que->execute()->fetch();
		$prop_id = $result->prop_id;

		$properties = json_decode($result->prop);
		isset($properties->description) ? $description_en = $properties->description : $description_en= false;

		// vrednost za slovenijo;

    $where = "";
    if (\Drupal::currentUser()->isAnonymous()) {
      $where = "published = 1 and";
    }

		$qres = db_query("SELECT vv.id, sl.tsuv, sl.id as slid, sl.name as name
									FROM s2.var_values vv
									JOIN s2.spatial_layer sl on vv.spatial_layer_id = sl.id
									where $where vv.id = :var_values_id order by sl.weight desc;",[':var_values_id' => $var_values_id])->fetch();

		$return['vs_lay'] = $qres->name;

		if ($qres && $qres->tsuv){
			$ds=self::getValuesDataSet($qres->id);
			$valueMD=StageFormsCommon::getValueMD($ds);
			$que = db_select('sta.'.$valueMD->sta_tname,'data');
			$que -> addField('data', $valueMD->sta_value_cname, 'values');
			$re = $que->execute()->fetch();
			if (count($re) == 1){
				$return['vs'] = $re->values;
			}
		}

		if ($lang=='en'){
			$return["desc"] = $description_en;
			return $return;
		}
		else{
			$que = db_select('s2.translations','trans');
			$que->addField('trans', 'translation', 'description');
			$que->condition('trans.language_id',$lang);
			$que->condition('trans.column_name','data');
			$que->condition('trans.table_name','var_properties');
			$que->condition('trans.orig_id',$prop_id);
			$translated_description = $que->execute()->fetch()->description;


			$return["desc"] = $translated_description ? $translated_description:'';

			$que = db_select('s2.translations','trans');
			$que->addField('trans', 'translation', 'description');
			$que->condition('trans.language_id',$lang);
			$que->condition('trans.column_name','name');
			$que->condition('trans.table_name','spatial_layer');
			$que->condition('trans.orig_id',$qres->slid);
			$translated_vs_lay = $que->execute()->fetch()->description;

			$return["vs_lay"] = $translated_vs_lay ? $translated_vs_lay:'';

			return $return;
		}

	  }
  /**
  * The function is used to prepare the data used in the time delineation.
  * It returns the values of published all variables saved in the table s2.var_values that have the same var_names_id and spatial_layer_id
  * @param $var_values_id {integer} the id in the s2.var_values table
  * @param $su_ids {string array} the array is used as a fillter for spatial units geocode e.g.     $su_ids = array('001','056','074');
  */
	public static function varvids($var_values_id,$su_ids=false){

    $return = array();

// SELECT * from table WHERE column SIMILAR TO '(AAA|BBB|CCC)%';
    // Get he data about the first variable to be used in the time delineation
    $ds=self::getValuesDataSet($var_values_id);
    $valueMD=StageFormsCommon::getValueMD($ds);

    $return['properties'] = $ds->prop;
    $geocodes =[];
    $names_col = $valueMD->ge_cnames["names_column"];
    $geo_code = $valueMD->ge_cnames["geo_code"];
    // generate like condition
    $like_condition = "";
    $like="";


    if ($su_ids <> "false"){
      foreach ($su_ids as $key => $su_name) {
        $like_condition.= "'$su_name',";
      }
      $like_condition = rtrim($like_condition, ',');
      $like = "WHERE $names_col LIKE ANY (ARRAY[$like_condition])";
    }


    $lab = db_query("SELECT $names_col as name, $geo_code as geo_code
      FROM ge.$valueMD->ge_tname AS ge $like ORDER BY name LIMIT 50")->fetchall();

    $labels = [];
    $geo_codes = [];
    foreach ($lab as $key => $value) {
      array_push($labels,$value->name);
      array_push($geo_codes,$value->geo_code);
      // $labels[] = $value;
    }
    // var_dump($labels);

    $return['labels'] = $labels;

    // the data on current variable
    $sel_var = db_query("SELECT * FROM s2.var_values AS vv WHERE vv.id = :var_values_id;", [':var_values_id'=>$var_values_id])->fetch();

    // get ids of all variables that meet condition
    $que1 = db_query("SELECT id FROM s2.var_values AS vv
      WHERE vv.published = 1
      AND vv.spatial_layer_id = :slid
      AND vv.var_names_id = :vni
      ORDER BY vv.valid_from;",[':slid'=>$sel_var->spatial_layer_id, ':vni'=>$sel_var->var_names_id])->fetchAll();

    $datasets=[];
    foreach ($que1 as $key => $value) {

      $ds1=self::getValuesDataSet($value->id);
      $valueMD1=StageFormsCommon::getValueMD($ds1);

      $dataset = array();
      $dataset['label'] = date('Y', strtotime($ds1->var_values_valid_from)); // Dataset labels e.g.2001, 2002
      $dataset['backgroundColor'] = self::pastelColors(); // Random background color
      $datasetval = [];
      foreach ($geo_codes as $key => $geo_code) {

          $table_name = $valueMD1->sta_tname;
          $georef = $valueMD1->sta_georef;
          $data_column_name = $valueMD1->sta_value_cname;

          /**
          * Function returns special values for given value id for given language
          * @param  $var_values_id 	Variable value id
          * @param  $lang			Language code
          */
          $var_values_id = $value->id;
          $special_values = self::get_translated_special_values($var_values_id, 'en');
          $all_spv = array();
          foreach ($special_values as $key => $spv) {
            $all_spv[] = $spv->value;
          }
          // var_dump($table_name);
          $que6 = db_select('sta.'.$table_name,'data');
          $que6 -> addField('data', $data_column_name, 'values');
          $que6 ->condition('data.'.$georef,$geo_code);

					if (count($special_values)>0){
						$que6 ->condition('data.'.$data_column_name,$all_spv,'NOT IN');
					}
          $result6 = $que6->execute()->fetch();

          $datasetval[] = $result6->values ? $result6->values:"0";


      }


        $dataset['data'] = $datasetval;

      $datasets[] = $dataset;

    }
    $return['datasets'] = $datasets;


    return ($return);

	}
  // Function is used in varvids. It returns random HEX codes for pastele colors
  function  pastelColors() {
    $r = dechex(round(((float) rand() / (float) getrandmax()) * 127) + 127);
    $g = dechex(round(((float) rand() / (float) getrandmax()) * 127) + 127);
    $b = dechex(round(((float) rand() / (float) getrandmax()) * 127) + 127);

    return "#" . $r . $g . $b;
  }
  public static function publish_var($vid){
    $que = db_update('s2.var_values');
    $que->condition('id',array($vid));
    $que ->fields(array(
      'published'=> 1,
      'modified'=> DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s'),
      'publish_on'=> DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s'),
    ))
    ->execute();
  }
  public static function stage2_is_variable_published_vid($id){

    $now = DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s');
    $que = db_select('s2.var_values','values');
    $que->fields('values',array('id'));
    $que->condition('id',array($id));
    $que->condition('published',array(1));
    $que->condition('publish_on',array($now),'<');
    $query = $que->execute();
    $query->allowRowCount = TRUE;
    $count = $query->rowCount();
    if ($count>0){
      return true;
    }
    else{
      return false;
    }
  }
  public static function update_var_param($var_values_id,$param){
    $result = array();

    $insert = db_insert('s2.var_properties')
    ->fields(array(
      'data'=>   $param,
      'default'=> 0,
    ))
     ->execute();
    $prop_id = StageDatabase::lastInsertedId('id', 's2.var_properties');

    $que = db_update('s2.var_values');
    $que->condition('id',array($var_values_id));
    $que ->fields(array(
      'var_properties_id'=> $prop_id,
      'modified'=> DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s'),
    ))
    ->execute();

    return $result;
  }

   public static function view($var_values_id){
    if (empty($var_values_id)){
      throw new \Exception('var_values_id has to be specified');
    }

    $ds=self::getValuesDataSet($var_values_id);
    $var_values_valid_from=$ds->var_values_valid_from;
    $spatial_layer_date_id=$ds->spatial_layer_date_id;

    list($view_name,$allTableAcronyms)=self::createView($var_values_valid_from,$spatial_layer_date_id,false);
    $cnames=json_decode($ds->col_names,true);
    $geo_code=$cnames['geo_code'];
    $names_column=$cnames['names_column'];
    $allTableAcronyms=implode(',',$allTableAcronyms);
    return db_query("select $geo_code,$names_column,$allTableAcronyms from ge.$view_name")->fetchAll();
  }


  public static function get_child_data($parent_vid,$child_vid,$parent_selected_id){

    $parent_ds=self::getValuesDataSet($parent_vid);
    $clild_ds=self::getValuesDataSet($child_vid);

    $ptn = $parent_ds->table_name;
    $pGeo_code = json_decode($parent_ds->col_names,true)['geo_code'];
    $ctn = $clild_ds->table_name;
    $cGeo_code = json_decode($clild_ds->col_names,true)['geo_code'];


    $intersection = db_query("SELECT distinct child.idgid
    FROM ge.".$ptn." AS parent

    JOIN ge.".$ctn." AS child
      ON  ST_Intersects(parent.geom,child.geom)
    where parent.idgid = '".$parent_selected_id."' and ST_Area(ST_Intersection(child.geom,parent.geom))/ST_Area(child.geom)> 0.9",array())->fetchAll();


    $result = array();
    $keyName = strtolower($cGeo_code);
    foreach($intersection as $key => $value){
      $result[] = intval ($value->idgid);
    }
    return $result;

  }

  /**
  * Function returns geometry table name and geo column names for given variable value id
  * @param  $var_values_id 	Variable value id
  */
  public static function geometryTableName($id){
	  $ds=db_query('SELECT a.table_name, a.col_names
						FROM s2.spatial_layer_date a
						LEFT JOIN s2.spatial_layer b
						LEFT JOIN s2.var_values c
						ON c.spatial_layer_id = b.id
						ON b.id = a.spatial_layer_id
						WHERE c.id =:id AND c.valid_from > a.valid_from
						ORDER BY a.valid_from DESC
						LIMIT 1 ',array(':id'=>$id))->fetchObject();

		return $ds;
  }

  /**
  * Function returns features withih circle buffer
  * @param  $var_values_id 	Variable value id
  * @param  $lat 			Point latitude
  * @param  $lon 			Point longtitude
  * @param  $r		 		Circle radius
  */
	public static function circle_query($var_values_id,$lat,$lon,$r){
    $ds=self::getValuesDataSet($var_values_id);
    $valueMD=StageFormsCommon::getValueMD($ds);

		$tableName = $valueMD->ge_tname;
		// get geo column name
		$colName = $valueMD->ge_cnames["geo_code"];//strtolower(json_decode($var->col_names)->geo_code);



		$ds2=db_query('SELECT '.$colName.'
						FROM ge.'.$tableName.'
						WHERE ST_DWithin(Geography(ST_Transform(geom,4326)), Geography(ST_Point(:lon,:lat)), :r)',
						array(':lat'=>$lat, ':lon'=>$lon, ':r'=>$r))->fetchAll(PDO::FETCH_NUM);

		$return = array();
		foreach($ds2 as $row){

			array_push($return, $row[0]);
		}

		return json_encode($return);
	}

  /**
  * Function returns features withih square
  * @param  $var_values_id 	Variable value id
  * @param  $latNE NorthEast Point latitude
  * @param  $lonNE NorthEast Point longtitude
  * @param  $latSW SouthWest Point latitude
  * @param  $lonSW SouthWest Point longtitude
  */
	public static function square_query($var_values_id,$lonNE,$latNE,$lonSW,$latSW){

    $ds=self::getValuesDataSet($var_values_id);
    $valueMD=StageFormsCommon::getValueMD($ds);
		// do spatial query
		$ds2=db_query('SELECT '.$valueMD->ge_cnames["geo_code"].' FROM ge.'.$valueMD->ge_tname.' WHERE ST_Intersects(Geography(ST_Transform(geom,4326)), Geography(ST_MakeEnvelope(:latSW,:lonSW,:latNE,:lonNE,4326)))',
						array(':latNE'=>$latNE, ':lonNE'=>$lonNE, ':latSW'=>$latSW, ':lonSW'=>$lonSW))->fetchAll(PDO::FETCH_NUM);


		$return = array();
		foreach($ds2 as $row){
			array_push($return, $row[0]);
		}

		return json_encode($return);
	}

	/**
  * Function returns features withih polygon
  * @param  $var_values_id 	Variable value id
  * @param  $polygon		Polygon coordinates
  */
	public static function polygon_query($var_values_id,$poly){

    $ds=self::getValuesDataSet($var_values_id);
    $valueMD=StageFormsCommon::getValueMD($ds);

		$tableName = $valueMD->ge_tname;
		// get geo column name
		$colName = $valueMD->ge_cnames["geo_code"];//strtolower(json_decode($var->col_names)->geo_code);

		 // prepare LINESTRING
		$coordinates = str_replace('+', ' ', $poly);
		$boom = explode(',', $coordinates);
		$coordinates .= ','.$boom[0];

    // do spatial query
		$ds2=db_query("SELECT $colName FROM ge.".$tableName." WHERE ST_Intersects(Geography(ST_Transform(geom,4326)), Geography(ST_MakePolygon(ST_GeomFromText('LINESTRING(".$coordinates.")'))))")->fetchAll(PDO::FETCH_NUM);

		$return = array();
		foreach($ds2 as $row){
			array_push($return, $row[0]);
		}

		return json_encode($return);
	}

	/**
  * Function returns special values for given value id for given language
  * @param  $var_values_id 	Variable value id
  * @param  $lang			Language code
  */
	public static function get_translated_special_values($var_values_id, $lang){
	  // get special values from table by variable_id
	  $que = db_select('s2.var_values','vv');
		$que->addField('sv', 'id');
		$que->addField('sv', 'special_value','value');
		$que->addField('sv', 'legend_caption');
		$que->addField('sv', 'color');
		$que->join('s2.special_values', 'sv', 'vv.var_properties_id = sv.var_properties_id');
		$que->condition('vv.id',$var_values_id);
		$special_values = $que->execute()->fetchAll();





	  // if lang is different as english, get translations
	  if($lang != 'en'){
		  $que = db_select('s2.translations','t');
			$que->addField('t', 'column_name');
			$que->addField('t', 'orig_id');
			$que->addField('t', 'translation');
			$que->join('s2.special_values', 'sv', 't.orig_id = sv.id');
			$que->join('s2.var_values', 'vv', ' sv.var_properties_id = vv.var_properties_id');
			$que->condition('vv.id',$var_values_id);
			$que->condition('t.language_id',$lang);
      $que->condition('t.table_name','special_values');
			$translations = $que->execute()->fetchAllAssoc('orig_id');
	  }

	  // replace special_values with translations if exists
	  foreach($special_values as &$value){
		  $value->legend_caption = (isset($translations[$value->id]))?$translations[$value->id]->translation:$value->legend_caption;
	  }


	  return $special_values;
	}

  /**
   * Function returns data needed to populate delineation statistics chart
   * Support function delineationValues is used to get the actual data based on formulas that are set in the form menu tree editor
   * @param  [array] parsed request
   */
  public static function delineation($request_decode){
    $result = array();
    $data = array();



    foreach ($request_decode as $key => $value) {
      $result['labels'][] = $value['var_name'];

      $data[] = self::delineationValues($value['vid'],$value['date'],$value['selected'],$value['sid']);
      $backgroundColor[] = '#8ABDCC';//self::pastelColors(); // Random background color
    }

    foreach ($data as $key => $value) {
      if ($value[0]=== 'DELINEATION_DISABLED'){
        return array(
          'datasets' => false
        );
      }
    }
    $result['datasets'] = array(
        array(
          'backgroundColor' => $backgroundColor,
          'data' => $data
        )
    );
    return $result;
  }


  public static function delineationValues($var_values_id,$date,$selected,$sid){

    $formula = db_query(
      'SELECT
       vn.delineation
       from s2.var_values vv
       left join s2.var_names vn on vv.var_names_id = vn.id
       where
         vv.id = :var_values_id',[':var_values_id'=>$var_values_id]
      )->fetch();

      $delineation = $formula->delineation;

      if($delineation === 'DELINEATION_DISABLED'){
        return array('DELINEATION_DISABLED');
      }


      $ds=self::getValuesDataSet($var_values_id);
      $valueMD=StageFormsCommon::getValueMD($ds);

      $filter_selected = "'".implode("','",$selected)."'";


			$geoCodeStat = json_decode ($ds->col_names)->geo_code;
      // echo(var_dump($valueMD).'</br>');
      if (strlen(preg_replace('/\s+/', '', $delineation)) == 0){

         $raw_sum = db_query("SELECT SUM(CAST(REGEXP_REPLACE(COALESCE(val.".$valueMD->sta_value_cname.",'0'), '[^0-9]+', '', 'g') AS NUMERIC)) as sum_val FROM ge.".$valueMD->ge_tname." ge
         left join sta.".$valueMD->sta_tname." val on ge.".$geoCodeStat."::text = val.".$valueMD->sta_georef."::text
         WHERE   val.".$valueMD->sta_georef." = ANY(ARRAY[".$filter_selected."])"
         )->fetch()->sum_val;
         if(is_null ($raw_sum)){
           return 0;
         }

        return $raw_sum;
      }
      else {// TODO make formula more secure

        // get acronyms and special area
        preg_match_all('/{(.*?)}/', $delineation, $split_formula);

        $eval_elements = array();

        foreach ($split_formula[1] as $key => $value) {

          if ($value <> 'area'){
            $nid = db_query("SELECT id FROM s2.var_names where short_name = :short_name",[':short_name' => $value])->fetch()->id;

            $var_values_id = db_query("SELECT id FROM s2.var_values where var_names_id = :nid and valid_from >= to_date(:date,'YYYY')
              --AND published = 1
              AND spatial_layer_id = :sid
              ORDER BY valid_from DESC
              LIMIT 1", [':nid'=>$nid, ':date' => $date, ':sid' => $sid])->fetch()->id;

              $ds=self::getValuesDataSet($var_values_id);
              $valueMD=StageFormsCommon::getValueMD($ds);

      if (!$ds){
        return array('DELINEATION_DISABLED');
      }


              $raw_sum = db_query("SELECT sum(val.".$valueMD->sta_value_cname."::numeric) as sum_val
              FROM ge.".$valueMD->ge_tname." ge
              left join sta.".$valueMD->sta_tname." val on ge.".$valueMD->ge_cnames['geo_code']."::text = val.".$valueMD->sta_georef."::text
              WHERE   val.".$valueMD->sta_georef." = ANY(ARRAY[".$filter_selected."])"
              )->fetch()->sum_val;

              $eval_elements[$value] = $raw_sum;
          }
          elseif ($value == 'area') {
            $nid = db_query("SELECT id FROM s2.var_names where short_name = :value",[':value'=>$value])->fetch()->id;
            // var_dump($valueMD->ge_cnames ["geo_code"]);
            // var_dump(("SELECT sum(st_area(geom::geography)) from ge.".$valueMD->ge_tname."  where ".$valueMD->ge_cnames ["geo_code"]." = ANY(ARRAY[".$filter_selected."])"));
            $area = db_query(("SELECT sum(st_area(geom::geography)) from ge.".$valueMD->ge_tname."  where ".$valueMD->ge_cnames ["geo_code"]." = ANY(ARRAY[".$filter_selected."])"))->fetch()->sum;
            $eval_elements['area'] = $area;
          }
        }
        $formula_mod = str_replace(array_keys($eval_elements), $eval_elements, $delineation);

        $formula_mod = preg_replace('/[{}]/', '', $formula_mod);
        if ($formula_mod == ""){
          return 0;
        }

            eval('$result = '.$formula_mod.';');
            return $result;
      }

  }

  /**
   * save the data about embeded chart into the s2.published_charts tabele
   * @param  [json] everything needed to render the embeded chart
   * @return [integer]   the id of data that has been saved
   */
  public static function publish_chart($request_decode){
    $insert = db_insert('s2.published_charts')
    ->fields(array(
      'chart_data'=> $request_decode
    ))
     ->execute();
    $return = array();
    $return['url'] = db_query('SELECT MAX(id) FROM {s2.published_charts}')->fetchField();
    return $return;
  }

  public static function gecd($id){
    $result = array();
    $data = array();
    $labels = array();
    $ds=db_query('SELECT chart_data
						FROM s2.published_charts
            where id = :id', [':id' => $id])->fetchField();

		return $ds;

  }

}
