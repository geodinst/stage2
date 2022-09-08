<?php
//	echo file_get_contents('http://localhost:8081/geoserver/stage/wms?'.$_SERVER['QUERY_STRING']);

 $m = new Memcached();
 $m->addServer('stage2-memcached', 11211);
 $key=$_GET['layers'].$_GET['bbox'].$_GET['styles'].$_GET['env'];
 $img=$m->get($key);

 if ($img===FALSE)
 {
 	$m->set($key,"");
 	$img=file_get_contents('http://localhost:8081/geoserver/stage/wms?'.$_SERVER['QUERY_STRING']);
 	$m->set($key,$img);
 }
 else
 {
 	while(empty($img)) $img=$m->get($key);
 }

 echo $img;
