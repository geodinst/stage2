<?php

namespace Drupal\stage2_admin\Classes;

use Drupal\Core\Url;
use Drupal\Core\Link;


class Variable{

	/**
	 * [__construct description]
	 * @param boolean $id [Spatial Layer id. If false class is treated as array of all spatial layers]
	 */
	public function __construct($id = false) {
        // allocate your stuff
    }

	/**
	 * Get all available spatial layers to populate selectbox
	 * @return array [keyed array to populate select box]
	 */
	public static function getAllLayersSelect(){
		$que = db_select('s2.spatial_layer', 'layer');
		$que->fields('layer', ['id','name']);
		$query = $que->execute();
		$return = $query->fetchAllKeyed();
		return $return;
	}

	/**
	* Function returns all variables of given spatial layer
	*
	* Used in:
	* src/From/StageVariablesForm
	*
	* @entry		array	condition
	*/
	public static function  getVariables($entry){
		$que = db_select('s2.var_values','var_values');
		$que -> fields('var_values', ['spatial_layer_id','published']);
		$que -> join ('s2.var_names', 'names', 'var_values.var_names_id = names.id');
		$que -> join ('s2.var_tree', 'var_tree', 'var_tree.id = names.var_tree_id');
		$que -> join ('s2.spatial_layer', 'spatial_layer', 'var_values.spatial_layer_id = spatial_layer.id');
		$que -> range(0, 1000000);
		$que -> addField('names', 'id','id_name');
		$que -> addField('names', 'name','names_name');
		$que -> addField('names', 'short_name','names_hort_name');
		$que -> addField('spatial_layer', 'name','spatial_layer_name');
		$que -> addField('spatial_layer', 'id','id_spatial_layer');
		$que -> addField('var_values', 'modified','modified');

		// Add each field and value as a condition to this query.
		foreach ($entry as $field => $value) {
			$que->condition($field, $value);
		}
		
		$que -> orderBy('spatial_layer.weight', 'DESC');
		$que -> orderBy('var_tree.position', 'ASC');

		return $que->execute()->fetchAll();
	}


}
