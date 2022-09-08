<?php
  namespace Drupal\stage2_admin;
  class StageStatDataImporter {
    private $tname;
    private $filteredTname;
    private $csv=false;
    private $pxIndices;
    private $cond;
    private $ispx=false;
    private $schema='sta';
    private $committed=false;
    private $error=[];
    
    function __destruct(){
      if (!$this->committed){
        db_query("drop table if exists {$this->schema}.{$this->tname}");
      }
    }
    
    function __construct($urls,$attrs=array(),$destination="public://temp_shp_uploads") {
      $this->error['file_import']=false;
      $tname=$this->tname=uniqid('s_');
      if (!is_array($urls)) $urls=array($urls);
      
      $service = \Drupal::service('gi_services');
      
      $root=\Drupal::service('file_system')->realpath($destination).'/';
      try{
        foreach($urls as $url) {
          $ext=strtolower(substr($url, -4));
          if ($ext==='.csv'){
            $this->csv=true;
            $service->importCsv($root.$url,$tname,$attrs,$this->schema,false,false);  
          }
          else if ($ext==='.zip'){
            $service->importShp($root.$url,$tname,$attrs,$this->schema,false,false,false);  
          }
          else if (substr($ext,-3)==='.px'){
            $this->ispx=true;
            $service->importPx($url,$tname,$attrs,$this->schema,false,true);  //save the whole PX to temporary data table
          }
        }
      }
      catch(\Exception $e){
        $this->error['file_import']=[$url,$e->getMessage()];
      }
    }
    
    public function getFileImportError(){
      return $this->error['file_import'];
    }
    
    public function isCsv(){
      return $this->csv;
    }
    
    public function getNonUniqueRows($cname,$cinx){
      if ($this->csv) $cname='c'.$cinx;
      $rows = db_query("select $cname from {$this->schema}.{$this->tname} group by $cname having count($cname)>1")->fetchAll();
      $rows = array_column((array) $rows,$cname);
      return $rows;
    }
    
    public function saveDatasourceData($cname,$cinx,$dsname,$tname=null){
      if ($this->csv) $cname='c'.$cinx;
      $this->committed=true;
      if (is_null($tname)) $tname=$this->tname;
      return db_query("insert into s2.var_ds (georef,ispx,dsname,tname) values (:cname,:ispx,:dsname,:tname) returning id",array_combine([':cname',':ispx',':dsname',':tname'],[$cname,$this->ispx,$dsname,$tname]))->fetchField();
    }
    
    public function getData($cname,$cinx){
      if ($this->csv) $cname='c'.$cinx;
      return db_query("SELECT array_agg($cname order by __gid_) from {$this->schema}.{$this->tname}")->fetchField();
    }
    
    public function getDataCount($tname=null){
      if ($this->ispx){
        return db_query("SELECT count(*) from {$this->schema}.{$tname}")->fetchField();
      }
      else{
        return db_query("SELECT count(*) from {$this->schema}.{$this->tname}")->fetchField();
      }
    }
    
    public function setPxCondition($headers,$pg_geocode,$pg_date){
      unset($headers[$pg_geocode]);
      unset($headers[$pg_date]);
      
      $pxIndices=array_keys($headers);
      $pxIndices[]=$pg_date;
      
      $cond=[];
      foreach($pxIndices as $key){
        $cond[]="c$key=:c$key";
      }
      $cond=implode(' AND ',$cond);
      
      $pxIndices=array_map(function($value){return ":c$value";},$pxIndices);
      
      if (count($pxIndices)>0) $cond=" where $cond";
      
      $this->pxIndices=$pxIndices;
      $this->cond=$cond;
    }
    
    public function prepareFilteredPxDataTable($attr,$condValues,$dcode,$cname){
      $this->filteredTname=$tname=uniqid('s_');
      
      $service = \Drupal::service('gi_services');
      $service->prepareTable($tname,$attr,$this->schema,false,false,false);
      
      db_query("INSERT into {$this->schema}.$tname ($cname)
               SELECT $cname from {$this->tname}{$this->cond}",$this->getPxDataQuery($condValues,$dcode,$cname));
               
      return $tname;
    }
    
    public function getPxDataQuery($condValues,$dcode,$cname='pxval'){
      $condValues[]=$dcode;
      return array_combine($this->pxIndices,$condValues);
    }
    
    public function getPxData($condValues,$dcode,$cname='pxval'){
      return db_query("SELECT array_agg($cname order by __gid_) from {$this->schema}.{$this->tname}{$this->cond}",$this->getPxDataQuery($condValues,$dcode,$cname))->fetchField();
    }
    
    public function getPxDataColumn($condValues,$dcode,$cname='pxval'){
      return db_query("SELECT $cname from {$this->tname}{$this->cond} order by __gid_",$this->getPxDataQuery($condValues,$dcode,$cname))->fetchCol();
    }
    
    public function updatePxDataTable($tname2,$cname2,$condValues,$dcode,$gcode,$cname='pxval'){
      db_query("UPDATE {$this->schema}.$tname2 t2
               SET $cname2=t1.val1
               FROM (SELECT $gcode,$cname as val1 from {$this->tname}{$this->cond}) t1
               WHERE  t2.$gcode = t1.$gcode",$this->getPxDataQuery($condValues,$dcode,$cname));
    }
  }