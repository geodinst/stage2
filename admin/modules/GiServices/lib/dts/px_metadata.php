<?php
class px_metadata
{
	private $metadata;
	
	public function metadata2array($pxa)
	{
		$rkeys=array("CHARSET","AXIS-VERSION","LANGUAGE","CREATION-DATE","SUBJECT-AREA",
			 "SUBJECT-CODE","MATRIX","DESCRIPTION","TITLE", "CONTENTS", "UNITS","TIMEVAL","DECIMALS",
			 "LAST-UPDATED","SOURCE","CONTACT","COPYRIGHT","DATABASE","METIS_KONCEPT","NOTE","NOTEX");
		
		$this->metadata=array();
		foreach($pxa as $key=>$value)
			if (in_array($key,$rkeys)) $this->metadata[$key]=str_replace("\"\"", "", trim(rtrim(implode("",$value), ";"),"\""));
		
		return $this->metadata;
	}
}