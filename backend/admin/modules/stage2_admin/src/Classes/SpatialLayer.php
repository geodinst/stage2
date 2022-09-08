<?php

namespace Drupal\stage2_admin\Classes;

use Drupal\Core\Url;
use Drupal\Core\Link;


class SpatialLayer{

	/**
	 * [__construct description]
	 * @param boolean $id [Spatial Layer id. If false class is treated as array of all spatial layers]
	 */
	public function __construct($id = false) {
        // allocate your stuff
    }


	/**
	* Get all available spatial layers to populate selectbox
	* @param  string $default the text to be displayed as default
	* @return array [keyed array to populate select box]
	* @return [type]          [description]
	*/
	public static function getAllLayersSelect($default){
		$que = db_select('s2.spatial_layer', 'layer');
		$que -> fields('layer', array('id','name'));
    	$que  ->orderBy('layer.weight', 'DESC');
		$query = $que->execute();
		$return = $query->fetchAllKeyed();

		if($default){
			$return[0] = $default;
		}
		return $return;
	}

	public static function countLayers(){
		$que = db_select('s2.spatial_layer', 'layer');
	    $que->fields('layer',array('id'));
		$query = $que->execute();

		$query->allowRowCount = TRUE;
	    $count = $query->rowCount();
		if($count == 0){
			return false;
		}
		return $count;
	}

}
