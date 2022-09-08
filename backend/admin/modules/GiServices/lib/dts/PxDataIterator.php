<?php
require_once("px_variables.php");

class PxDataIterator implements Iterator {
    private $headings;
    private $stubs;
    private $searchConditions;
    private $linesCount;
    private $lines;
    
    private $ncols;
    private $column=0;
    private $row=0;
    private $stubValues;
    private $headingValues;
    private $linesPosition = 0;

    private $position=0;
    private $currentCellIndex=0;
    private $currentCells=array();
    private $isValid=true;
    
    public function __construct($pxa,$dimensions=null,$dimensionValues=null,$searchConditions=array()) {
        $lines=$pxa['DATA'];
        $lli=count($lines)-1;
        $lines[$lli]=rtrim($lines[$lli],";");
        $this->linesCount=count($lines);
        $this->lines=$lines;
        
        if (is_null($dimensions)) return;
        
        $headings=new px_variables($this->filterHeadingStubValues($dimensions['headings'],$dimensionValues));
        $stubs=new px_variables($this->filterHeadingStubValues($dimensions['stubs'],$dimensionValues));
        
        if (count($searchConditions) > 0){
          $headings->conditions($searchConditions);
          $stubs->conditions($searchConditions);
        }
        
        $this->searchConditions=$searchConditions;
        
        $this->headings=$headings;
        $this->stubs=$stubs;
        $this->rewind();
    }
    
    public function rawData(){
      return trim(implode(" ",$this->lines));
    }

    function rewind() {
        $ncols=1;
        if ($this->headings->variableValuesCount()!=0) $ncols=$this->headings->getPrescaleFactor(0)*$this->headings->getCount(0);
        $this->ncols=$ncols;
        
        $this->column=1;
        $this->row=1;
        $this->stubValues=$this->stubs->getValues(0);
        $this->headingValues=$this->headings->getValues(0);
        $this->linesPosition=0;
        
        $this->position=0;
        $this->currentCellIndex=0;
        $this->currentCells=array();
        $this->isValid=true;
        
        $this->getLineCells();
        $this->cellValue=$this->currentCells[$this->currentCellIndex++];
    }
    
    function current() {
      return array_merge($this->stubValues,$this->headingValues,array($this->cellValue));
    }

    function key() {
        return $this->position;
    }

    function next() {
      for($i=$this->currentCellIndex; $i<count($this->currentCells);++$i){
        $rval=$this->currentAll($i);
        $this->position++;
        $this->currentCellIndex=$i+1;
        return $rval;
      }
      $this->currentCellIndex=0;
      $this->getLineCells();
      $this->next();
    }

    function valid() {
        return $this->isValid;
    }
    
    private function getLineCells(){
      for (; $this->linesPosition<$this->linesCount; ++$this->linesPosition){
        $line=$this->lines[$this->linesPosition];
        if (!empty($line)){
          $this->currentCells=explode (" ",$line);
          $this->linesPosition++;
          return true;
        }
      }
      $this->isValid=false;
      return false;
    }
    
    private function currentAll($i){
      if (($this->column % $this->ncols)==0) {
        $this->stubValues=$this->stubs->getValues($this->row++);
      }
      $this->headingValues=$this->headings->getValues($this->column++);
      $this->cellValue=$this->currentCells[$i];
    }
    
    private function filterHeadingStubValues($a,$dimensionValues)
    {
      $filteredValues=array();
      foreach ($dimensionValues as $key=>$values)
      {
        if (in_array($key,$a)) $filteredValues[$key]=$values["codes"];
      }
      return $filteredValues;
    }
}
