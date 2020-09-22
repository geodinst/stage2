<?php

namespace  Drupal\GiServices;

use \ShapeFile\ShapeFile;
use \ShapeFile\ShapeFileException;

class GiServices {
  protected $say_something;
  protected $path;
  protected $geoserverCurlHandler;
  
  public function __construct() {
    $this->say_something = 'Hello World!';
    $this->path = __DIR__.'/..';
 }
 
 public function shp2pg_version(){
    exec('shp2pgsql',$r,$rval);
    $response['return status'] = $rval;
    $response['version'] = $r[0];
    return $response;
 }
 
  public function  sayHello($name = ''){
    if (empty($name)) {
      return $this->say_something;
    }
    else {
      return "Hello " . $name . "!";
    }
  }
  
  public function initGeoserverCurlHandler($conn){
    require_once($this->path.'/src/GeoserverCurlHandler.php');
    if (empty($this->geoserverCurlHandler)){
      $this->geoserverCurlHandler=new \GeoserverCurlHandler($conn);
    }
  }
  
  public function checkGeoserverLayer($lname,$ws='stage'){
    return $this->geoserverCurlHandler->checkGeoserverLayer($lname,$ws);
  }
  
  public function publishGeoserverLayer($tname,$ws='stage',$storeName='stage_postgis'){
    return $this->geoserverCurlHandler->publishGeoserverLayer($tname,$ws,$storeName);
  }
  
  public function unpublishGeoserverLayer($lname,$ws='stage',$storeName='stage_postgis'){
    return $this->geoserverCurlHandler->unpublishGeoserverLayer($lname,$ws,$storeName);
  }
  
  public function putGeoserverLayerProperties($lname,
                                              $ws='stage',
                                              $prop='<layer><defaultStyle><name>stage_color</name></defaultStyle><enabled>true</enabled></layer>'
                                              ){
    return $this->geoserverCurlHandler->putGeoserverLayerProperties($lname,$ws,$prop);
  }
  
  public function downloadFile($url){
    $tfn=$url;
    if (filter_var($url, FILTER_VALIDATE_URL)!==FALSE){
      if (!$this->urlExists($url)) return false;
      $ext=substr($url, -4);
      $tfn = tempnam(sys_get_temp_dir(),'dld').$ext;
      file_put_contents($tfn, fopen($url, 'r'));
    }
    
    if (file_exists($tfn))
      return $tfn;
    else
      return FALSE;
  }
  
  public function urlExists($url){
      if (!extension_loaded('curl')) throw new \Exception("'curl' extension is not loaded.");
      $ch = curl_init($url);    
      curl_setopt($ch, CURLOPT_NOBODY, true);
      curl_exec($ch);
      $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  
      if($code == 200){
         $status = true;
      }else{
        $status = false;
      }
      curl_close($ch);
      return $status;
  }
  
  public function extractZIP($fname,$dfolder){
    if (!extension_loaded('zip')) throw new \Exception("'zip' extension is not loaded.");
    $zip = new \ZipArchive;
    $res=$zip->open($fname);
    if ($res!==TRUE) throw new \Exception(t("Unable to extract ZIP file").' ('.t('error code').': '.$res.').');
    $zip->extractTo($dfolder);
    $zip->close();
  }
  
  public function listZIP($fname){
    if (!extension_loaded('zip')) throw new \Exception("'zip' extension is not loaded.");
    $zip = new \ZipArchive;
    $res=$zip->open($fname);
    if ($res!==TRUE) throw new \Exception(t("Unable to extract ZIP file").' ('.t('error code').': '.$res.').');
    $lf=array();
    for( $i = 0; $i < $zip->numFiles; $i++ ){ 
      $stat = $zip->statIndex($i);
      $lf[]=basename($stat['name']);
    }
    $zip->close();
    return $lf;
  }
  
  public function globInFolder($folder,$wcards){
    $folder=rtrim($folder,'/').'/';
    $a=glob("$folder$wcards"); //glob('my/dir/*.{CSV,csv}', GLOB_BRACE);
    if (count($a)===0) return false;
    return $a;
  }
  
  public function shpFileNames($folder,&$err_msgs=[]){
    $dbf=$this->globInFolder($folder,'*.[dD][bB][fF]');
    $shp=$this->globInFolder($folder,'*.[sS][hH][pP]');
    $shx=$this->globInFolder($folder,'*.[sS][hH][xX]');
    
    if ($dbf && $shp && $shx){
      $pre='there can only be one ';
      $pst=' file in the ZIP archive';
      if (count($dbf)>1) {
        $err_msgs[]=$pre.'DBF'.$pst.' ('.t('found').': '.count($dbf).')';
      }
      
      if (count($shp)>1) {
        $err_msgs[]=$pre.'SHP'.$pst.' ('.t('found').': '.count($shp).')';
      }
      
      if (count($shx)>1) {
        $err_msgs[]=$pre.'SHX'.$pst.' ('.t('found').': '.count($shx).')';
      }
      
      if (count($err_msgs)>0) return FALSE;
      
      $dbf=$dbf[0];$shx=$shx[0];$shp=$shp[0];
      
      $bname=strtolower(pathinfo($dbf, PATHINFO_FILENAME));
      if (strtolower(pathinfo($shp, PATHINFO_FILENAME))!==$bname){
        $err_msgs[]=t('The SHP file name is different than the DBF file name');
      }
      
      if (strtolower(pathinfo($shx, PATHINFO_FILENAME))!==$bname){
        $err_msgs[]=t('The SHX file name is different than the DBF file name');
      }
      
      if (count($err_msgs)>0) return FALSE;
      
      return array('dbf'=>$dbf,
                 'shp'=>$shp,
                 'shx'=>$shx);
    }
    else{
      if (!$dbf){
        $err_msgs[]=t('DBF file is missing');
      }
      
      if (!$shp){
        $err_msgs[]=t('SHP file is missing');
      }
      
      if (!$shx){
        $err_msgs[]=t('SHX file is missing');
      }
      return FALSE;
    }
  }
  
  function db_table_exists($schema,$table) {
    return $schema==='pg_temp'?
    !empty(db_query("SELECT to_regclass('pg_temp.$table')")->fetchField()):
    (bool) (db_query("SELECT COUNT(*) FROM pg_tables WHERE schemaname=:schema AND tablename = :table",array(':schema'=>$schema,':table'=>$table))->fetchField());
  }
  
  public function prepareTable($tname,$attr=array(),$schema='public',$dropIfExists=false,$temp=false,$geom=false){
    $onlyTable=$tname;
    if (!$temp) {
      db_query("CREATE SCHEMA IF NOT EXISTS $schema;");
      $tname="$schema.\"$tname\"";
    }
    
    if ($dropIfExists){
      db_query("DROP TABLE IF EXISTS $tname");
    }
    
    if (!$this->db_table_exists($temp?'pg_temp':$schema,$onlyTable)){
      $cols="";
      foreach($attr as $a){
        $cols.="$a varchar,";
      }
      
      if ($geom){
        $cols.="geom geometry";
      }
      else{
        $cols=trim($cols,',');
      }
      
      if ($temp){
        $temp='TEMP';
      }
      else{
        $temp="";
      }
      
      db_query("CREATE $temp TABLE $tname(
        __gid_ serial primary key,
        $cols)");
    }
    return $tname;
  }
  
  private function insertRow($tname,$attrs,&$values){
    $values=rtrim($values,',');
    db_query("INSERT INTO $tname ($attrs) values $values",[],['allow_delimiter_in_query'=>true]);
  }
  
  public function transformToSRID($tname,$to=4326){
    db_query("UPDATE $tname SET geom=ST_Transform(geom,$to);");
  }
  
  public function importCsv($url,$tname,$attr=array(),$schema='public', $dropTableIfExists=false,$temp=false){
    
    $url=$this->getCsvPath($url);
    
    $cnames = preg_filter('/^/', 'c', array_keys($attr));
    
    $tname=$this->prepareTable($tname,$cnames,$schema,$dropTableIfExists,$temp,false);
    require_once($this->path.'/lib/spreadsheet-reader/SpreadsheetReader.php');
    $reader = new \SpreadsheetReader($url);
    
    $k=1;
    $values="";
    $attrs=trim(implode(',',$cnames));
    
    $reader->rewind();
    $reader->current();
    $reader->next();
    try{
      while ($reader->valid()){
        $line = $reader->current();
        $row="(";
        foreach($attr as $inx=>$a){
          $row.="'".str_replace("'","''",trim($line[$inx]))."',";
        }
        
        $row=rtrim($row,',').')';
        
        $values.=$row.',';
        if ($k % 1000 === 0){
          $this->insertRow($tname,$attrs,$values);
          $values="";
        }
        $k++;
        $reader->next();
      }
  
      if (!empty($values)) {
        $this->insertRow($tname,$attrs,$values);
      }
    }
    catch (\Exception $e) {
      self::handleUtf8Exception($e);
    }
  }
  
  public function debug($data, $label = NULL, $print_r = TRUE) {
  
    // Print $data contents to string.
    $string = \Drupal\Component\Utility\Html::escape($print_r ? print_r($data, TRUE) : var_export($data, TRUE));
  
    // Display values with pre-formatting to increase readability.
    $string = '<pre>' . $string . '</pre>';
    drupal_set_message(\Drupal\Core\Render\Markup::create(trim($label ? "{$label}: {$string}" : $string)),'warning');
  }
  
  public function getPxMetadata($url){
    require_once($this->path.'/lib/dts/PxDts.php');
    $pxdts=new \PxDts();
    return $pxdts->exec('metadata',$url);
  }
  
  public function importPx($url,$tname,$attr=array(),$schema='public', $dropTableIfExists=false,$temp=false){
    $cnames = preg_filter('/^/', 'c', array_keys($attr));
    $cnames[]='pxval';
    $tname=$this->prepareTable($tname,$cnames,$schema,$dropTableIfExists,$temp,false);
    
    require_once($this->path.'/lib/dts/PxDts.php');
    $pxdts=new \PxDts();
    $iterator=$pxdts->exec('data_query_all',$url);
    
    $values="";
    $attrs=trim(implode(',',$cnames));
    
    foreach($iterator as $k=>$cell){
      $row="('".implode("','",$cell)."')";
      $values.=$row.',';
      if ($k % 1000 === 0){
        $this->insertRow($tname,$attrs,$values);
        $values="";
      }
    }
    
    if (!empty($values)) {
      $this->insertRow($tname,$attrs,$values);
    }
  }
  
  public function importShp($url,$tname,$attr=array(),$schema='public', $dropTableIfExists=false,$temp=false,$geom=true,$srid=3912){
    require_once($this->path.'/lib/php-shapefile/src/ShapeFileAutoloader.php');
    \ShapeFile\ShapeFileAutoloader::register();
    
    $fnames=$this->getShpFileNames($url);
    $tname=$this->prepareTable($tname,$attr,$schema,$dropTableIfExists,$temp,$geom);
    
    $k=1;
    $values="";
    if ($geom)
      $attrs=trim(implode(',',$attr).',geom',',');
    else
      $attrs=trim(implode(',',$attr));
    
    try {
      $shp=new ShapeFile($fnames, ShapeFile::FLAG_SUPPRESS_Z | ShapeFile::FLAG_SUPPRESS_M);
      while ($record = $shp->getRecord(ShapeFile::GEOMETRY_WKT)) {
          if ($record['dbf']['_deleted']) continue;
          
          $row="(";
          foreach($attr as $a){
            $str=utf8_decode($record['dbf'][$a]);
            $row.="'".str_replace("'","''",trim($str))."',";
          }
          
          if ($geom){
            $row.="ST_GeomFromText('".$record['shp']."',$srid))";
          }
          else{
            $row=rtrim($row,',').')';
          }
          
          $values.=$row.',';
          if ($k % 1000 === 0){
            $this->insertRow($tname,$attrs,$values);
            $values="";
          }
          $k++;
      }
      
      if (!empty($values)) {
        $this->insertRow($tname,$attrs,$values);
      }
      
      if ($geom){
        db_query("SELECT Populate_Geometry_Columns('$tname'::regclass);");
      }
    
    }
    catch (ShapeFileException $e) {
      throw new \Exception($e->getMessage());
    }
    catch (\Exception $e) {
      self::handleUtf8Exception($e);
    }
    
  }
  
  private static function handleUtf8Exception($e){
    $msg=$e->getMessage();
    if (strpos($msg,'SQLSTATE[22021]')!==false){
      $msg=t('The DBF file contains invalid byte sequence for UTF-8 encoding. Please convert it to UTF-8 and upload the ZIP archive again.');
    }
    throw new \Exception($msg);
  }
  
  private function getShpFileNames($url){
    if (is_dir($url)){
      $fnames=$this->shpFileNames($url,$err_msg);
    }
    else if (filter_var($url, FILTER_VALIDATE_URL)!==FALSE){
      $url=$this->downloadFile($url);
    }
    
    if (file_exists($url) && !is_dir($url)){
      $ext=strtolower(substr($url, -3));
      if ($ext!=='zip'){
        $fnames=$this->shpFileNames(pathinfo($url,PATHINFO_DIRNAME),$err_msg);
      }
      else{
        $tfn = sys_get_temp_dir().'/'.uniqid().'/';
        $this->extractZIP($url,$tfn);
        $fnames=$this->shpFileNames($tfn,$err_msg);
      }
    }
    
    if (empty($fnames)) throw new \Exception(implode('<br>- ',$err_msg));
    
    return $fnames;
  }
  
  public function getShapeFileNames($url){
    return $this->getShpFileNames($url);
  }
  
  public function getShpType($url,$fnames=null){
    require_once($this->path.'/lib/php-shapefile/src/ShapeFileAutoloader.php');
    \ShapeFile\ShapeFileAutoloader::register();
    
    if (is_null($fnames)) $fnames=$this->getShpFileNames($url);
    
    $shp=new ShapeFile($fnames);
    return $shp->getShapeType(ShapeFile::FORMAT_STR);
  }
  
  public function getShpHeader($url,$fnames=null){
    require_once($this->path.'/lib/php-shapefile/src/ShapeFileAutoloader.php');
    \ShapeFile\ShapeFileAutoloader::register();
    
    if (is_null($fnames)) $fnames=$this->getShpFileNames($url);
    
    $shp=new ShapeFile($fnames);
    return $shp->getDBFFields();
  }
  
  private function getCsvPath($url){
    if (filter_var($url, FILTER_VALIDATE_URL)!==FALSE){
      $url=$this->downloadFile($url);
    }
    
    if (!file_exists($url)) throw new \Exception('CSV file access error.');
    return $url;
  }
  
  public function getCsvHeader($url){
    $url=$this->getCsvPath($url);
    require_once($this->path.'/lib/spreadsheet-reader/SpreadsheetReader.php');
    $reader = new \SpreadsheetReader($url);
    foreach ($reader as $row)
    {
      return $row;
    }
    return array();
  }
  
  public function getPxHeader($px){
    require_once($this->path.'/lib/dts/PxDts.php');
    $pxdts=new \PxDts();
    return $pxdts->exec('variables',$px);
    //url=http://pxweb.stat.si/pxweb/Database/Dem_soc/05_prebivalstvo/10_stevilo_preb/10_05C20_prebivalstvo_stat_regije/05C2002S.px&format=json
  }
  
  public function pxdts($request,$url,$cond=null){
    require_once($this->path.'/lib/dts/PxDts.php');
    $pxdts=new \PxDts();
    return $pxdts->exec($request,$url,$cond);
  }
  
  //http://stackoverflow.com/questions/6311779/finding-cartesian-product-with-php-associative-arrays
  public function cartesian($input,$codes=false) {
      // filter out empty values
      $input = array_filter($input);
  
      $result = array(array());
  
      foreach ($input as $key => $values) {
          $append = array();
  
          foreach($result as $product) {
              foreach($values as $code=>$item) {
                if ($codes) {
                  $item=array($code=>$item);
                }
                  $product[$key] = $item;
                  $append[] = $product;
              }
          }
  
          $result = $append;
      }
  
      return $result;
  }
}