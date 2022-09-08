<?php	
	$layers=json_decode(base64_decode($argv[1]));
	$path=$argv[2];
	$port=$argv[3];
	
	@mkdir($path);
	
	foreach($layers as $layer){
		$extents=json_decode($layer->extents);
		$tname=$layer->tname;
		for ($z=7;$z<17;$z++){
			tile($path,$layer->tname, $extents, $z);
		}
	}
	
	function tile($path,$tname,$extents,$zoom) {
		global $port;
		$x1=$extents->coordinates[0][0][0];
		$x2=$extents->coordinates[0][2][0];
		$y1=$extents->coordinates[0][0][1];
		$y2=$extents->coordinates[0][2][1];
		
		$mms1=metersToPixels($x1,$y1,$zoom);
		$mms2=metersToPixels($x2,$y2,$zoom);
		
		$tms1=pixelsToTile($mms1[0],$mms1[1]);
		$tms2=pixelsToTile($mms2[0],$mms2[1]);
		
		foreach (['stage_color','line'] as $style){
			$transparent='false';
			if ($style=='line') {
				$transparent='true';
			}
			@mkdir("$path/{$tname}_{$style}");
			@mkdir("$path/{$tname}_{$style}/$zoom");
			for ($x=$tms1[0];$x<=$tms2[0];$x++) {
				$p="$path/{$tname}_{$style}/$zoom/$x";
				@mkdir($p);
				for ($y=$tms1[1];$y<=$tms2[1];$y++) {
					$bbox=implode(',',tileBounds($x,$y,$zoom));
					file_put_contents("$p/$y.png",file_get_contents("http://localhost:$port/geoserver/stage/wms?service=WMS&request=GetMap&layers=stage:$tname&styles=$style&format=image%2Fpng&transparent=$transparent&version=1.1.1&format_options=antialias%3Anone&height=256&width=256&srs=EPSG%3A3857&bbox=$bbox"));
				}
			}
		}
	}
	
	/*
	def MetersToPixels(self, mx, my, zoom):
        "Converts EPSG:900913 to pyramid pixel coordinates in given zoom level"
                
        res = self.Resolution( zoom )
        px = (mx + self.originShift) / res
        py = (my + self.originShift) / res
        return px, py
    
    def PixelsToTile(self, px, py):
        "Returns a tile covering region in given pixel coordinates"

        tx = int( math.ceil( px / float(self.tileSize) ) - 1 )
        ty = int( math.ceil( py / float(self.tileSize) ) - 1 )
        return tx, ty
        
    def TileBounds(self, tx, ty, zoom):
		"Returns bounds of the given tile in EPSG:900913 coordinates"
		
		minx, miny = self.PixelsToMeters( tx*self.tileSize, ty*self.tileSize, zoom )
		maxx, maxy = self.PixelsToMeters( (tx+1)*self.tileSize, (ty+1)*self.tileSize, zoom )
		return ( minx, miny, maxx, maxy )
		
	def PixelsToMeters(self, px, py, zoom):
		"Converts pixel coordinates in given zoom level of pyramid to EPSG:900913"

		res = self.Resolution( zoom )
		mx = px * res - self.originShift
		my = py * res - self.originShift
		return mx, my
    */
	
	function tileBounds($tx, $ty, $zoom, $tileSize=256) {
		list($minx, $miny) = pixelsToMeters($tx * $tileSize, $ty * $tileSize, $zoom );
		list($maxx, $maxy) = pixelsToMeters(($tx+1) * $tileSize, ($ty+1)*$tileSize, $zoom );
		return [$minx, $miny, $maxx, $maxy];
	}
	
	function pixelsToMeters($px,$py,$zoom){
		$originShift = 20037508.342789244;
		$res = 156543.03392804062 / pow(2,$zoom);
		$mx = $px * $res - $originShift;
		$my = $py * $res - $originShift;
		return [$mx, $my];
	}
	
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