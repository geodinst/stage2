<?php
require_once("PxParser.php");
class PxDts{
	private $parameters;

  public function exec($request,$url,$cond=null){
    $func = strtolower(trim($request));
		$this->parameters['url']=$url;
		$this->parameters['cond']=$cond;
		if((int)method_exists($this,$func) > 0){
			return $this->$func();
		}
    return false;
  }
	
	private function metadata(){
		$p=$this->parameters;
		$pxpar=new PxParser($p["url"],false);
		return $pxpar->metadata();
	}
	
	private function variables(){
		$p=$this->parameters;
		$pxpar=new PxParser($p["url"],false);
		return $pxpar->variables();
	}
	
	private function data(){
		$p=$this->parameters;
		$pxpar=new PxParser($p["url"]);
		return $pxpar->data();
	}
	
	private function data_query_all(){
		$p=$this->parameters;
		$pxpar=new PxParser($p["url"]);
		return $pxpar->data_query_all();
	}
	
	private function data_query(){
		$p=$this->parameters;
		$pxpar=new PxParser($p["url"]);
		return $pxpar->data_query($p["cond"]);
	}
	
	private function contentTypeHeader(){
		header("Content-Type: text/plain; charset=windows-1250");
	}
}