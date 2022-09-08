<?php

namespace Drupal\stage2_client\Controller;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\stage2_client\StageClientSM;
use Drupal\Core\Url;

/**
 * Controller routines for GiServices routes.
 */
class Stage2ClientController extends ControllerBase {

  private $response = array(); // response array
  private $lang_codes = array(); // array of languages avaulable in the REGIONAL AND LANGUAGE SETTINGS
  private $lang_names = array(); // keyed array of language names


  /**
  * Function returns available languages
  * if language code e.g. 'en' is defined as an imput parameter function returns client labels
  * @param  Request  $request the selected language e.g. 'en'
  */
  public function languages(Request $request ) {
    // get available languages
    $lang_codes = \Drupal::languageManager()->getLanguages();
    // assign language names
    foreach ($lang_codes as $key => $value){
      $lang_names[$key] =$value->getName() ;
    }
    $response = $lang_names;
    return new JsonResponse($response);
  }

  /**
  * Function returns translations and the respective language code
  * if language code e.g. 'en' is not defined default language code is used. if default
  * language code is empty, then the 'en' code (english) is used
  * @param  Request  $request the selected language e.g. 'en'
  */
  public function translations(Request $request) {
    // check if language parametr is set in the get request
    $lang=$request->query->get('lang');

    if (empty($lang)){ //if not get it from the advanced settings
      $lang=StageClientSM::stage2_client_get_advanced_settings('default_lc')['lc'];
    }

    if (empty($lang)) $lang='en';

    $response = array('translations'=>StageClientSM::stage2_get_labels($lang),
                      'lc'=>$lang);

    return new JsonResponse($response);
  }

  public static function getInstanceName(){
    return "stage2";
    $rootFolder=strrchr(DRUPAL_ROOT,'/');
    $instancePath=str_replace($rootFolder,'',DRUPAL_ROOT);
    return trim(strrchr($instancePath,'/'),'/');
  }

  /**
  * Function returns menu tree structure in the selected language
  * @param  Request  $request the selected language e.g. 'en'
  */
  public function tree(Request $request){
    $m = new \Memcached();
    $m->addServer('stage2-memcached', 11211);
    $lang=$request->query->get('lang');
    $key='s2tree'.self::getInstanceName().$lang;
    $response=$m->get($key);

    if ($response===FALSE){
      $response = StageClientSM::stage2_get_tree($lang,($request->query->get('unpublished') === 'true'));
      $m->set($key,json_encode($response));
      return new JsonResponse($response);
    }
    else{
      $response = new Response($response);
      $response->headers->set('Content-Type', 'application/json');
      return $response;
    }
  }

  /**
  * Function returns Spatial units and dates for which specific variable is defined
  * @param  Request  $request ['lang'] the selected language e.g. 'en'
  * @param  Request  $request ['var_tree_id'] the id frtom the table var_tree
  */
  public function varspat(Request $request){
    $lang = $request->query->get('lang');
    $var_tree_id = $request->query->get('var_tree_id');
    $unpublished = $request->query->get('unpublished');

    if (\Drupal::currentUser()->isAnonymous() && $unpublished == 'true') {
      return new JsonResponse(['error' => 'Please Sign in to your Stage administrative account']);
    }

    $response = StageClientSM::stage2_get_varspat($lang,$var_tree_id,$unpublished);
    return new JsonResponse($response);
  }

  /**
  * Function returns values based on the variable id
  * @param  Request  $request ['var_values_id'] the id from the table var values table
  * @param  Request  $request ['prop '] if set to 1 parameters are also returned variable parameters
  *        if not defined only values are returned
  */
  public function varval(Request $request){
    $prop = false;
    $var_values_id = $request->query->get('var_values_id');
    $lang = empty($request->query->get('lang'))?'en':$request->query->get('lang');
    $key='varval'.$var_values_id.$lang;
    
    $jsonResponse = new JsonResponse();
    
    $m = new \Memcached();
    $m->addServer('stage2-memcached', 11211);
    $cached=$m->get($key);
    if ($cached!==FALSE){
      $m->delete($key);
      $jsonResponse->setJSON($cached);
      return $jsonResponse;
    }
    
    if ($request->query->get('prop') == '1'){
      $prop = true;
    }
    $response = StageClientSM::stage2_get_varval($var_values_id,$prop,$lang,true,true);

    if (!empty($response['prop']) && !empty($response['special_values'])) {
			$response['prop']->special_values=$response['special_values'];
    }

    unset($response['special_values']);

    if (isset($response['codes']) && count($response['codes']) > 0) {
      if (is_numeric($response['codes'][0])) {
        $keyedCodes = [];
        foreach($response['codes'] as $i=>$code) {
          $keyedCodes[$code] = $i;
        }
        $response['codes'] = $keyedCodes;
        $response['keyedCodes'] = true;
      }
    }

    if ((int)($request->query->get('limit'))===1){
      $c=count($response['data']);
      if (count($response['data'])>=100000) {
        $jsonResponse->setData(['limit'=>true,'c'=>$c]);
        $m->set($key, json_encode($response,JSON_NUMERIC_CHECK));
        return $jsonResponse;
      }
    }

    $jsonResponse->setEncodingOptions(JSON_NUMERIC_CHECK);
    $jsonResponse->setData($response);

    return $jsonResponse;
  }

  /**
  * Function returns spatial_layer_date entity names ordered by gid based on the spatial_layer_date.id
  * @param  Request  $request ['spatial_layer_date_id'] the id from the table spatial_layer_date
  */
  public function sldnames(Request $request){
    $response = StageClientSM::stage2_get_sldnames($request->query->get('spatial_layer_date_id'));
    return new JsonResponse($response);
  }

  /**
  * @param  Request  $request ['var_values_id'] the id frtom the table var_tree
  */
  public function varprop(Request $request){

    $var_values_id = $request->query->get('var_values_id');
	$lang = empty($request->query->get('lang'))?'en':$request->query->get('lang');
    $response = StageClientSM::stage2_get_varprop($var_values_id,$lang);
    return new JsonResponse($response);
  }
  /**
  * Function variable settings parameters from based on the var_values_id
  * @param  Request  $request ['var_values_id'] the id frtom the table var_tree
  */
  public function allsetings(Request $request){

    $response = StageClientSM::stage2_get_allsettings();
    return new JsonResponse($response);
  }

  /**
  * Function returns table name based on the id from the var_values table
  * @param  Request  $request ['var_values_id'] the id from the var_values table
  */
  public function geolay(Request $request){

    $var_values_id = $request->query->get('var_values_id');
    $response = StageClientSM::stage2_get_geolay($var_values_id);
    return new JsonResponse($response);
  }
  /**
  * Function returns the values from the advanced settings table
  * @param  Request  $request ['setting'] manually defined id from the table s2.advanced_settings
  */
  public function client_get_advanced_settings(Request $request){

    $setting = $request->query->get('setting');
    $response = StageClientSM::stage2_client_get_advanced_settings($setting);
    return new JsonResponse($response);
  }

  /**
   * Returns the url to a geoserver served file.
   */

  public function getFileUrl(Request $request){
    $format = $request->query->get('format');
    $var_values_id = $request->query->get('var_values_id');
    $var_values_valid_from = $request->query->get('var_values_valid_from');
    $spatial_layer_date_id = $request->query->get('spatial_layer_date_id');
    $response = StageClientSM::getFileUrl($format,$var_values_id,$var_values_valid_from,$spatial_layer_date_id);
    echo $response;
    return new Response();
  }

  /**
  * Function returns the picture from the var names table
  * @param  Request  $request ['var_tree_id']
  */
  public function var_img(Request $request){
        $var_tree_id = $request->query->get('var_tree_id');
        $response = StageClientSM::stage2_client_get_var_img($var_tree_id);
        return new Response($response);
  }

  /**
  * Function returns the description that is dependent on the language, spatial unit and time
  * @param  Request  $request ['var_values_id']
  * @param  Request  $request ['lang']
  */
  public function varpropdesc(Request $request){
        $var_values_id = $request->query->get('var_values_id');
        $lang = $request->query->get('lang');
        $response = StageClientSM::stage2_client_get_varpropdesc($var_values_id,$lang);
        return new JsonResponse($response);
  }

  /**
  * @param  Request  $request ['var_values_id'] (required)
  * @param  Request  $request ['format'] tsv (tab separated values) or shp (tsv default)
  * @param  Request  $request ['all_variables'] 0 - one variable, 1 - all connected variables (0 default)
  */
  public function export(Request $request){
	$var_values_id = $request->query->get('var_values_id');
	$format = (null !== $request->query->get('format'))?$request->query->get('format'):"tsv";
	$all_variables = (null !== $request->query->get('all_variables'))?$request->query->get('all_variables'):"0";

	$response = StageClientSM::export($var_values_id, $format, $all_variables);
    return new JsonResponse($response);
  }

  /**
	* Function returns enabled layers specified in admin
	*/
	public function layers(Request $request){
		$response = StageClientSM::layers();
		return new JsonResponse($response);
	}

  /**
  * Function returns the view
  * @param  Request  $request ['var_values_id'] (required)
  * @param  Request  $request ['all_variables'] 0 - one variable, 1 - all connected variables (0 default)
  */
  public function view(Request $request){
    $var_values_id = $request->query->get('var_values_id');
    $all_variables = (null !== $request->query->get('all_variables'))?$request->query->get('all_variables'):"0";
    $response = StageClientSM::view($var_values_id, $all_variables);
    return new JsonResponse($response);
  }

  /**
  * Function returns the data to populete the time delineation chart. Based on the  (id == vid - in the embeded map link)
  * all of the data of all time periods with same spatial unit and variable name id is returned
  * @param  Request  $request ['var_values_id'] all the values are exported if empty
  */
  public function varvids(Request $request){
    $var_values_id = $request->query->get('var_values_id');
    $su_ids = $request->query->get('su_ids');
    $response = StageClientSM::varvids($var_values_id,$su_ids);
    return new JsonResponse($response);
  }
  public function publish_var(Request $request){
    $var_values_id = $request->query->get('var_values_id');
    $response = StageClientSM::publish_var($var_values_id);
    return new JsonResponse($response);
  }

  public function ispublished(Request $request){
    $var_values_id = $request->query->get('var_values_id');
    $response = StageClientSM::stage2_is_variable_published_vid($var_values_id);
    return new JsonResponse($response);
  }

  public function update_var_param(Request $request){
    $var_values_id = $request->query->get('var_values_id');
    $param = $request->query->get('param');
    $paramObj=json_decode($param);
    if (isset($paramObj->manual_classification) && isset($paramObj->manual_classification->manual_breaks)){
      $a=$paramObj->manual_classification->manual_breaks;
      $modified=false;
      if (empty($a[0])) {$a[0]='-Infinity';$modified=true;}
      if (empty($a[count($a)-1])) {$a[count($a)-1]='Infinity';$modified=true;}
      if ($modified){
        $paramObj->manual_classification->manual_breaks=str_replace(['"-Infinity"','"Infinity"'],['-Infinity','Infinity'], json_encode($a));
        $param=json_encode($paramObj);
      }
    }

    $response = StageClientSM::update_var_param($var_values_id,$param);
    return new JsonResponse($response);
  }

  /**
  * Function deletes given file from upload folder
  * @param  Request  $request ['file_name'] (required)
  */
  public function delete_file(Request $request){
    $file_name = $request->request->get('file_name');
	// delete file from folder
	$realpath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
	$path = $realpath.'/temp_shp_uploads/'.$file_name;
	unlink($path);
	$response = $path;
    return new JsonResponse($response);
  }

  // Get the data to be ploted in the chart when add child unit option is clicked
  public function get_child_data(Request $request){

    $parent_vid = $request->query->get('parent_vid');
    $child_vid = $request->query->get('child_vid');
    $parent_selected_id = $request->query->get('parent_selected_id');

    $response = StageClientSM::get_child_data($parent_vid,$child_vid,$parent_selected_id);
    return new JsonResponse($response);
  }

/**
  * Function returnes features withih circle buffer
  * @param  Request  $request['var_values_id'] (required) Variable value id
  * @param  Request  $request['lat'] (required) Point latitude
  * @param  Request  $request['lon'] (required) Point longtitude
  * @param  Request  $request['r'] (required) Circle radius
  */
public function circle_query(Request $request){

    $var_values_id = $request->query->get('var_values_id');
    $lat = $request->query->get('lat');
	$lon = $request->query->get('lon');
    $r = $request->query->get('r');

    $response = StageClientSM::circle_query($var_values_id, $lat, $lon, $r);
    return new JsonResponse($response);
  }

/**
  * Function returnes features withih square
  * @param  Request  $request['var_values_id'] (required) Variable value id
  * @param  Request  $request['latNE'] (required) NorthEast Point latitude
  * @param  Request  $request['lonNE'] (required) NorthEast Point longtitude
  * @param  Request  $request['latSW'] (required) SouthWest Point latitude
  * @param  Request  $request['lonSW'] (required) SouthWest Point longtitude
  */
public function square_query(Request $request){

    $var_values_id = $request->query->get('var_values_id');
    $latNE = $request->query->get('latNE');
	$lonNE = $request->query->get('lonNE');
	$latSW = $request->query->get('latSW');
	$lonSW = $request->query->get('lonSW');

    $response = StageClientSM::square_query($var_values_id, $latNE, $lonNE, $latSW, $lonSW);
    return new JsonResponse($response);
  }

/**
  * Function returnes features withih polygon
  * @param  Request  $request['var_values_id'] (required) Variable value id
  * @param  Request  $request['poly'] (required) Multiple pairs of coordinates - coordinates separated with comma, coordinate pairs separated with plus
  */
public function polygon_query(Request $request){

    $var_values_id = $request->query->get('var_values_id');
    $poly = $request->query->get('poly');

    $response = StageClientSM::polygon_query($var_values_id, $poly);
    return new JsonResponse($response);
  }

/**
 * The function is used in tge delineation tab
 * @param  Request $request - json with all data available in all delineation accordions
 * @return [type]           [description]
 */
public function delineation(Request $request){

  // var_dump($request_decode);
  $request_decode =  json_decode($request->request->get('dca'),true);
  $response = StageClientSM::delineation($request_decode);

  return new JsonResponse($response);
}

public function publish_chart(Request $request){

  $request_decode =  $request->request->get('cd');
  $response = StageClientSM::publish_chart($request_decode);
  return new JsonResponse($response);
}

public function gecd(Request $request){
  $cid = $request->query->get('cid');
  $response = StageClientSM::gecd($cid);
  return new JsonResponse($response);
}

/**
 * Custom function for updates, it can only be run by admin - in this way a little bit easier to handle and correct than the default Drupal mechanism for updates
 */
public function update(Request $request){
  function insertTranslations($required_client_labels){
    foreach ($required_client_labels as $key => $entry) {
        try{
            $return_value = db_insert('s2.var_labels')
            ->fields($entry)
            ->execute();
        }
        catch(Exception $e){
          ;
        }
    }
  }
  
    $service = \Drupal::service('gi_services');
    $conn=db_query("SELECT value from s2.advanced_settings where setting='gsrv'")->fetchField();
    $service->initGeoserverCurlHandler($conn);

    $rows = db_query("SELECT table_name from s2.spatial_layer_date")->fetchAll();

    foreach($rows as $row) {
      $tname = $row->table_name;
      echo "$tname\n";
      $res = $service->unpublishGeoserverLayer($tname,'stage','stage2');
      $res = $service->publishGeoserverLayer($tname,'stage','stage2');
      $res = $service->putGeoserverLayerProperties($tname);
    }
    
    $required_client_labels=[];
    $required_client_labels[] = array('id_cli'=> '1000','label'=>'Download GeoPackage (all variables)','language'=>'en','description'=>'dsc');
    $required_client_labels[] = array('id_cli'=> '1000','label'=>'Prenesi GeoPackage (vse spremenljivke)','language'=>'sl','description'=>'dsc');
    $required_client_labels[] = array('id_cli'=> '1001','label'=>'Download GeoPackage','language'=>'en','description'=>'dsc');
    $required_client_labels[] = array('id_cli'=> '1001','label'=>'Prenesi GeoPackage','language'=>'sl','description'=>'dsc');

    $required_client_labels[] = array('id_cli'=> '1002','label'=>'Download XLSX (all variables)','language'=>'en','description'=>'dsc');
    $required_client_labels[] = array('id_cli'=> '1002','label'=>'Prenesi XLSX (vse spremenljivke)','language'=>'sl','description'=>'dsc');
    $required_client_labels[] = array('id_cli'=> '1003','label'=>'Download XLSX','language'=>'en','description'=>'dsc');
    $required_client_labels[] = array('id_cli'=> '1003','label'=>'Prenesi XLSX','language'=>'sl','description'=>'dsc');

    insertTranslations($required_client_labels);

    db_query("UPDATE s2.special_values SET legend_caption='not shown' WHERE legend_caption='ni prikazano';");
    db_query("UPDATE s2.special_values SET legend_caption='special value' WHERE legend_caption='posebna vrednost';");
    db_query("UPDATE s2.special_values SET legend_caption='special value' WHERE legend_caption='posebna vrenost';");
    db_query("UPDATE s2.special_values SET legend_caption='special value that works' WHERE legend_caption='posebna vrednost, ki dela';");

    $required_client_labels=[];
    $required_client_labels[] = array('id_cli'=> '1004','label'=>'Spatial units borders','language'=>'en','description'=>'dsc');
    $required_client_labels[] = array('id_cli'=> '1004','label'=>'Meje prostorskih enot','language'=>'sl','description'=>'dsc');
    insertTranslations($required_client_labels);

    db_query("DROP TABLE IF EXISTS s2.su_notes");
    db_query("CREATE TABLE s2.su_notes(
      id serial primary key,
      var_properties_id integer,
      sid varchar,
      note varchar
    )");

    $rows = db_query("SELECT * from s2.var_ds where ispx=true")->fetchAll();

    foreach($rows as $row) {
      $dsname = json_decode($row->dsname);

      $exploded = explode('/', trim($dsname->url));

      $px = $exploded[count($exploded)-1];

      $dsname->url = "https://pxweb.stat.si/SiStatData/Resources/Px/Databases/Data/".$px;
      
      $dsname = json_encode($dsname);

      db_query("UPDATE s2.var_ds SET dsname=:dsname WHERE id=:id", [':dsname'=>$dsname,':id'=>$row->id]);
    }

  return new JsonResponse([]);
}
}
