<?php

class GeoserverCurlHandler {
  protected $ch=false;
  protected $service;
  protected $passwordStr;
  
  /**
   *@param $conn connection properties, json
    {
      "port": 8081,
      "hostname": "localhost",
      "protocol": "http",
      "path": "geoserver",
      "username": "admin",
      "password": "..."
    }
  */
  public function __construct($conn) {
    $p=json_decode($conn);
    $this->service = rtrim("{$p->protocol}://{$p->hostname}:{$p->port}/$p->path",'/').'/rest/';
    $this->passwordStr = "{$p->username}:{$p->password}";
    $this->ch = curl_init();
  }
  
  private function reinit(){
    curl_reset($this->ch);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1); //disable printout
    curl_setopt($this->ch, CURLOPT_USERPWD, $this->passwordStr);
  }
 
  public function checkGeoserverLayer($lname,$ws){
    $this->reinit();
    $request="layers/$ws:$lname.xml";
    return $this->exec($request)===true?true:false;
  }
  
  public function publishGeoserverLayer($tname,$ws,$storeName){
    $this->reinit();
    curl_setopt($this->ch, CURLOPT_POST, 1);
    curl_setopt($this->ch, CURLOPT_HTTPHEADER,array("Content-type: application/xml"));
    
    $xmlStr = "<featureType><name>$tname</name></featureType>";
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $xmlStr);
    
    $request="workspaces/$ws/datastores/$storeName/featuretypes";
    return $this->exec($request);
  }
  
  public function unpublishGeoserverLayer($lname,$ws,$storeName){
    $this->reinit();
    curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    
    $request="layers/$ws:$lname.xml";
    $this->exec($request);
    
    $request="workspaces/$ws/datastores/$storeName/featuretypes/$lname";
    return $this->exec($request);
  }
  
  public function putGeoserverLayerProperties($lname,$ws,$prop){
    $this->reinit();
    $request="layers/$ws:$lname";
    curl_setopt($this->ch, CURLOPT_HTTPHEADER,array("Content-type: application/xml"));
    curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $prop);
    return $this->exec($request);
  }
  
  private function exec($request){
    $url = $this->service . $request;
    
    curl_setopt($this->ch, CURLOPT_URL, $url);
    
    $buffer = curl_exec($this->ch); // Execute the curl request
    $info = curl_getinfo($this->ch);
    $http_code=$info['http_code'];
    if ($http_code != 201 && $http_code != 200) {
      $info['result']=$buffer;
      return $info;
    } else {
      return true;
    }
  }
  
  function __destruct() {
       curl_close($this->ch); // free resources if curl handle will not be reused
  }
}