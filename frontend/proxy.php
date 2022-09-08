<?php
//	echo file_get_contents('http://localhost:8081/geoserver/stage/wms?'.$_SERVER['QUERY_STRING']);

 $m = new Memcached();
 $m->addServer('stage2-memcached', 11211);
 
 $port=$_GET['_port'];
 
 $style=$_GET['styles'];
 $key=$_GET['layers'].$_GET['bbox'].$style.$_GET['env'];
 $img=$m->get($key);

 if ($img===FALSE)
 {
  $layer=str_replace('stage:','',$_GET['layers']);
  $cwd=getcwd();
  $cacheFolder=$cwd.'/admin/sites/default/files/proxy_cache/'.$layer.'_'.$style;
  
  if (file_exists($cacheFolder)){
   $bbox=explode(',',$_GET['bbox']);
   $tileSizeMeters=$bbox[2]-$bbox[0];
   $zoom=round(log(20037508.342789244/($tileSizeMeters),2))+1; //determine zoom
   if (file_exists("$cacheFolder/$zoom")){
    $a=metersToPixels($bbox[0],$bbox[1],$zoom);
    list($x,$y)=pixelsToTile($a[0],$a[1]);
    $cachedFileName="$cacheFolder/$zoom/$x/$y.png";
    
    if (file_exists($cachedFileName)) {
     $img=file_get_contents($cachedFileName);
    }
    else{
     $img='';
    }
   }
  }
  
  if ($img===FALSE) {
   $img=file_get_contents('http://stage2-geoserver:'.$port.'/geoserver/stage/wms?'.$_SERVER['QUERY_STRING']);
  }
  
 	$m->set($key,$img);
  
 }
 
 echo $img;
 
 function metersToPixels($mx,$my,$zoom)  {
		/*
		 self.initialResolution = 2 * math.pi * 6378137 / self.tileSize
        # 156543.03392804062 for tileSize 256 pixels
        self.originShift = 2 * math.pi * 6378137 / 2.0
        # 20037508.342789244
		 */
		$res = 156543.03392804062 / pow(2,$zoom);
		$originShift = 20037508.342789244;
		
		$px = ($mx + $originShift) / $res;
        $py = ($my + $originShift) / $res;
		
		return [$px, $py];
	  }
	  
	  function pixelsToTile($px,$py, $tileSize=256){
		$tx = intval( ceil( $px / floatval($tileSize) ) - 1 );
        $ty = intval( ceil( $py / floatval($tileSize) ) - 1 );
		return [$tx,$ty];
	  }
