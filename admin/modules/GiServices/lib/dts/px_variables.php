<?php
class px_variables
{
	private $prescaleFactors;
	private $variableValues;
	private $variableCounts;
	private $searchIndices;
	private $currentIndex=-1;
	
	public function __construct($variableValues)
	{
		$this->variableValues=$variableValues;
		$this->prescaleFactors=array();
		$this->variableCounts=array();
		$this->init();
	}
	
	public function variableValuesCount()
	{
		return count($this->variableValues);
	}
	
	private function init()
	{
		foreach ($this->variableValues as $key=>$values) $this->variableCounts[]=count($values);
		
		$n=1;$a=array();
		for ($i=count($this->variableCounts)-1;$i>=0;$i--)
		{
			$a[]=$n; $n=$n*$this->variableCounts[$i];
		}
		
		$this->prescaleFactors=array_reverse($a);
	}
	
	public function conditions($cond)
	{
		$searchKeys=array_keys($cond);
		$this->searchIndices=array();
		$k=0;
		foreach ($this->variableValues as $key=>$values)
		{
			if (in_array($key,$searchKeys)) $this->searchIndices[$k]=array_search($cond[$key],$values);
			$k++;
		}
	}
	
	public function getCount($i)
	{
		return $this->variableCounts[$i];
	}
	
	public function getPrescaleFactor($i)
	{
		return $this->prescaleFactors[$i];
	}
	
	public function getValues($k)
	{
		$a=array();
		$i=0;
		foreach ($this->variableValues as $key=>$values)
		{
			$a[$key]=$values[((int)($k/$this->prescaleFactors[$i]))%$this->variableCounts[$i]];
			$i++;
		}
		return $a;
	}
	
	public function getValueIndexes($k=null)
	{
		$a=array();
		$i=0;
		if (is_null($k)) $k=$this->currentIndex;
		foreach ($this->variableValues as $values)
		{
			$a[]=((int)($k/$this->prescaleFactors[$i]))%$this->variableCounts[$i];
			$i++;
		}
		return $a;
	}
	
	public function getVariableValuesKeys()
	{
		return array_keys($this->variableValues);
	}
	
	public function cellIndex2ValueIndex($i)
	{
		$k=$this->currentIndex;
		return ((int)($k/$this->prescaleFactors[$i]))%$this->variableCounts[$i];	
	}
	
	public function variablesFound($k)
	{
		$this->currentIndex=$k;
		foreach ($this->searchIndices as $keyIndex=>$valueIndex)
		{
			$vic=((int)($k/$this->prescaleFactors[$keyIndex]))%$this->variableCounts[$keyIndex];
			if ($vic!=$valueIndex) return false;
		}
		return true;
	}
}