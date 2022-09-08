<?php
require_once("px_metadata.php");
require_once("PxDataIterator.php");
require_once("px_dimensions.php");

class PxParser
{
	private $pxa=array();
  private $encoding='windows-1250';
	
	public function __construct($url,$parseData=TRUE)
	{
		$stream=fopen($url,"r");
		while(!feof($stream))
		{
			$line=iconv($this->encoding,'utf-8',trim(fgets($stream)));
			if ($parseData===FALSE && (substr($line,0,4)=="DATA"))
			{
				$ei=strpos($line,"=",4);
				if ($ei!==FALSE && trim(substr($line,0,$ei))=="DATA") break;				
			}
			$linea=array($line);
			while(substr(end($linea),-1)!==";" && !feof($stream)) $linea[]=iconv($this->encoding,'utf-8',trim(fgets($stream)));
			$this->processLines($linea,$this->pxa);
		}
		fclose($stream);
	}
	
	public function metadata()
	{
		$metadata=new px_metadata();
		return $metadata->metadata2array($this->pxa);
	}
	
	public function variables()
	{
		$dimensions=new px_dimensions();
		return $dimensions->dimensionValues2array($this->pxa);
	}
	
	public function data()
	{
		$iterator=new PxDataIterator($this->pxa);
		return $iterator->rawData();
	}
	
	public function data_query_all()
	{
		$dimensions=new px_dimensions();
		$da=$dimensions->dimensions2array($this->pxa);
		$dva=$dimensions->dimensionValues2array($this->pxa);
		return new PxDataIterator($this->pxa,$da,$dva);
    
	}
	
	public function data_query($cond)
	{
		$dimensions=new px_dimensions();
		$da=$dimensions->dimensions2array($this->pxa);
		$dva=$dimensions->dimensionValues2array($this->pxa);
		return new PxDataIterator($this->pxa,$da,$dva,$cond);
	}
	
	/*private*/
	
	private function processLines($linea,&$a)
	{
		$arr=explode("=",$linea[0],2);
		if (count($arr)==2)
		{
			$linea[0]=$arr[1];
			$a[$arr[0]]=$linea;
		}
	}
}