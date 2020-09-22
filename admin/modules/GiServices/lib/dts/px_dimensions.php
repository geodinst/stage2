<?php
class px_dimensions
{
	private $dimensions;
	private $dimensionValues;
	
	public function dimensions2array($pxa)
	{
		$stubs=array();
		if (isset($pxa["STUB"])) $stubs=explode("\",\"",trim(rtrim(implode("",$pxa["STUB"]), ";"),"\""));
		$headings=array();
		if (isset($pxa["HEADING"])) $headings=explode("\",\"",trim(rtrim(implode("",$pxa["HEADING"]), ";"),"\""));
			
		$this->dimensions=array("stubs"=>$stubs, "headings"=>$headings);
		return $this->dimensions;
	}
	
	public function dimensionValues2array($pxa)
	{
		if (!isset($this->dimensions)) $this->dimensions2array($pxa);
		$this->dimensionValues=array();
		$this->_dimensionValues2array($this->dimensions["stubs"],$pxa);
		$this->_dimensionValues2array($this->dimensions["headings"],$pxa);
		return $this->dimensionValues;
	}
	
	private function _dimensionValues2array($a,$pxa)
	{
		foreach($a as $dimension)
		{
			$this->dimensionValues[$dimension]=array();
			$notes=$this->note("NOTE","(\"$dimension\")",$pxa);
			if (count($notes)>0) $this->dimensionValues[$dimension]["dnotes"]=$notes;
			$values=explode("\",\"",trim(rtrim(implode("",$pxa["VALUES(\"$dimension\")"]), ";"),"\""));
			$notes=array();
			foreach ($values as $key=>$value)
			{
				$n=$this->note("VALUENOTE","(\"$dimension\",\"$value\")",$pxa);
				if (count($n)>0) $notes[$key]=$n;
			}
			$this->dimensionValues[$dimension]["values"]=$values;
			if (isset($pxa["CODES(\"$dimension\")"])) $this->dimensionValues[$dimension]["codes"]=explode("\",\"",trim(rtrim(implode("",$pxa["CODES(\"$dimension\")"]), ";"),"\""));
			if (count($notes)>0) $this->dimensionValues[$dimension]["notes"]=$notes;
		}
	}
	
	private function note($vkey,$nkey,$pxa)
	{
		/*
		 *$this->note("NOTE","(\"$dimension\")",$pxa,$this->dimensionValues[$dimension]);
		 *$this->note("VALUENOTE","(\"$dimension\",\"$value\")",$pxa,$a);
		 */
		$a=array();
		$vnkey=$vkey.$nkey;
		if (isset($pxa[$vnkey])) $a["note"]=str_replace("\"\"", "",trim(rtrim(implode("",$pxa[$vnkey]), ";"),"\""));
		
		$vnkey=$vkey."X".$nkey;
		if (isset($pxa[$vnkey])) $a["notex"]=str_replace("\"\"", "",trim(rtrim(implode("",$pxa[$vnkey]), ";"),"\""));
		return $a;
	}
}