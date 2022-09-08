<?php
	$x=(int)$_GET['x'];
	$y=(int)$_GET['y'];
	$z=(int)$_GET['z'];
	
	$path=null;
	
	if ($z<=14) {
		$ymax=1 << $z;
		$y=$ymax-$y-1;
		$type='.png';
		$folder='tiles/';
	}
	else{
		$ymax = 1 << $z;
		$y = $ymax - $y -1;
		$shift = $z / 2 | 0;
		$half = pow(2, $shift + 1);
		$digits = 1;
		if ($half > 10) {
			//var digits = ((Math.log(half) / Math.LN10).toFixed(14) | 0) + 1;
			$digits = (number_format(log10($half), 14, '.', '') | 0) + 1;
		}
			
		$halfX = $x / $half | 0;
		$halfY = $y / $half | 0;
		$path='EPSG_900913_'.$z.'/'.str_pad($halfX, $digits,'0',STR_PAD_LEFT) .'_' . str_pad($halfY, $digits,'0',STR_PAD_LEFT).'/'.str_pad($x, 2 * $digits,'0',STR_PAD_LEFT) . '_' . str_pad($y, 2 * $digits,'0',STR_PAD_LEFT);
		$type='.jpeg';
		$folder='dof/';
	}
	
	if ($z>=12 && $z<=14){
		$path="dtk50/$z/$x/$y";
	}
	else if($z>=10 && $z<=11){
		$path="dpk/dpk250/$z/$x/$y";
	}
	else if($z>=8 && $z<=9){
		$path="dpk/dpk500/$z/$x/$y";
	}
	else if($z>=6 && $z<=7){
		$path="dpk/dpk750/$z/$x/$y";
	}
	
	if (!is_null($path)){
		echo file_get_contents('http://localhost/'.$folder.$path.$type);
	}
	
	//echo file_get_contents('http://192.168.159.128/tiles/dpk/dpk1000/9/275/330.png');
/**
{"key":"dpk1000", "zoom_min":6, "zoom_max":10, "function":"

var ymax = 1 << zoom;
var y = ymax - tile.y -1;
return 'tiles/dpk/dpk1000/'+zoom+'/'+tile.x+'/'+y+'.png';", "trans":"DPK 1000" },
{"key":"dpk750", "zoom_min":6, "zoom_max":10, "function":"var ymax = 1 << zoom;var y = ymax - tile.y -1;return 'tiles/dpk/dpk750/'+zoom+'/'+tile.x+'/'+y+'.png';", "trans":"DPK 750" }, {"key":"dpk500", "zoom_min":6, "zoom_max":11, "function":"var ymax = 1 << zoom;var y = ymax - tile.y -1;return 'tiles/dpk/dpk500/'+zoom+'/'+tile.x+'/'+y+'.png';", "trans":"DPK 500" }, {"key":"dpk250", "zoom_min":6, "zoom_max":12, "function":"var ymax = 1 << zoom;var y = ymax - tile.y -1;return 'tiles/dpk/dpk250/'+zoom+'/'+tile.x+'/'+y+'.png';", "trans":"DPK 250" }, {"key":"dtk50", "zoom_min":6, "zoom_max":14, "function":"var ymax = 1 << zoom;var y = ymax - tile.y -1;return 'tiles/dtk50/'+zoom+'/'+tile.x+'/'+y+'.png';", "trans":"DTK 50" }, {"key":"dof", "zoom_min":13, "zoom_max":18, "function":"var ymax = 1 << zoom;var y = ymax - tile.y -1;var shift = zoom / 2 | 0;var half = Math.pow(2, shift + 1);var digits = 1;if (half > 10) {digits = ((Math.log(half) / Math.LN10).toFixed(14) | 0) + 1;};var halfX = tile.x / half | 0;var halfY = y / half | 0;return 'dof/EPSG_900913_'+zoom+'/'+T.zeroPad(halfX, digits) + '_' + T.zeroPad(halfY, digits)+'/'+T.zeroPad(tile.x, 2 * digits) + '_' + T.zeroPad(y, 2 * digits)+'.jpeg';", "trans":"DOF" } ]
**/