<?php
    
class Gsrv{
    protected $geoserverCurlHandler;
    public function initGeoserverCurlHandler($conn){
        require_once(__DIR__."/../../GiServices/src/GeoserverCurlHandler.php");
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
}

$pass=$argv[1];

pg_connect("host=localhost port=5432 dbname=stage2_test user=stage2_admin password=$pass");

$result=pg_query("SELECT value from s2.advanced_settings where setting='gsrv'");
$conn=pg_fetch_row($result);

$result=pg_query("SELECT table_name from s2.spatial_layer_date");
$tnames=pg_fetch_all_columns($result);

if (empty($tnames)){
    pg_close();
    return;
}

$a=new Gsrv();
$a->initGeoserverCurlHandler($conn[0]);

foreach($tnames as $tname){
    
}



pg_close();