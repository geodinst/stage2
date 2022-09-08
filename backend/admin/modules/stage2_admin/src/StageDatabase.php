<?php

namespace Drupal\stage2_admin;

use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\Core\Database\Query\PagerSelectExtender;
use \PDO;

/**
 * Class stage2_database.
 */
class StageDatabase {

/****
*** 	COORDINATE SYSTEMS
****/

	/**
   * Function deletes selected coordinate systems from datatable "crs"
   */
	public static function deleteCoordinateSystems($entry = array()) {
		foreach($entry as $row){
			db_delete('s2.crs')
			->condition('id', $row)
			->execute();
      db_delete('spatial_ref_sys')
			->condition('srid', $row)
			->execute();
		}
	}

	/**
   * Function updates selected coordinate system from datatable "crs"
   */
	public static function updateCoordinateSystem($entry = array()) {
		try {
		  // db_update()...->execute() returns the number of rows updated.
		  $count = db_update('s2.crs')
			  ->fields($entry)
			  ->condition('id', $entry['id'])
			  ->execute();

      db_update('spatial_ref_sys')
			  ->fields(array('proj4text'=>$entry['proj4text']))
			  ->condition('srid', $entry['id'])
			  ->execute();
		}
		catch (\Exception $e) {
		  drupal_set_message(t('db_update failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}
		return $count;
	}

	/**
   * Function loads available coordinate systems from datatable "crs"
   */
	public static function loadCoordinateSystems($entry = array()) {
	 // Read all fields from the dbtng_example table.
		$select = db_select('s2.crs', 'example');
		$select->fields('example');

		// Add each field and value as a condition to this query.
		foreach ($entry as $field => $value) {
		  $select->condition($field, $value);
		}
		// Return the result in object format.
		return $select->execute()->fetchAll();
	  }

	/**
   * Function saves new coordinate system defined in /Forms/StageCoordinateSystemAddForm.php
   */
	public static function addCoordinateSystem($entry) {
		$return_value = NULL;
    $transaction = db_transaction();
		try {
      $offset=db_query('select max(srid) from spatial_ref_sys')->fetchField();
      $min_offset=915000;
      if ($offset < $min_offset) $offset=$min_offset;
      $offset++;
      $proj4text=$entry['proj4text'];

      db_query("insert into spatial_ref_sys (srid,proj4text) values ($offset,'$proj4text')");
      $entry['id']=$offset;
		  $return_value = db_insert('s2.crs')
			->fields($entry)
			->execute();
		}
		catch (\Exception $e) {
      $transaction->rollback();
		  drupal_set_message(t('db_insert failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}
		return $return_value;
	  }


	/**
   * Function loads available coordinate systems from "spatial_ref_sys" table (postgis extension) - DEPRECATED
   */
  public static function loadCoordinateSystemsDepr($entry = array()) {
    // Read all fields from the dbtng_example table.
    $select = db_select('s2.spatial_ref_sys', 'example');
    $select->fields('example');
	$select->range(0,100);

    // Add each field and value as a condition to this query.
    foreach ($entry as $field => $value) {
      $select->condition($field, $value);
    }
    // Return the result in object format.
    return $select->execute()->fetchAll();
  }

  public static function loadCoordinateSystemsPager($entry = array()) {
	  return $pager_data = db_select('s2.spatial_ref_sys', 'n')
      ->extend(PagerSelectExtender::class)
      ->fields('n')
      ->limit(10)
      ->execute()
      ->fetchAll();
  }

/****
*** 	COORDINATE SPATIAL UNITS
****/

	/**
   * Function loads available coordinate spatial units
   *
   * Used in:
   * src/Form/StageCoordinateSpatialUnitsForm
   * src/From/StageVariablesEditForm
   */
	public static function loadCoordinateSpatialUnits($entry = array()) {
	 // Read all fields from the dbtng_example table.
		$select = db_select('s2.spatial_layer', 'example');
		$select->fields('example');

		// Add each field and value as a condition to this query.
		foreach ($entry as $field => $value) {
		  $select->condition($field, $value);
		}

		$select->orderBy("weight", "DESC");

		// Return the result in object format.
		return $select->execute()->fetchAll();
	  }

	/**
   * Function saves new coordinate spatial unit
   */
	public static function addCoordinateSpatialUnit($entry) {
		$return_value = NULL;
		$transaction = db_transaction();
		try {
		  db_insert('s2.spatial_layer')
			->fields($entry)
			->execute();
			$return_value = db_query("SELECT max(id) from s2.spatial_layer")->fetchField();
		}
		catch (\Exception $e) {
		  $transaction->rollback();
		  drupal_set_message(t('db_insert failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}

		// $transaction goes out of scope here.  Unless it was rolled back, it
  		// gets automatically commited here.

		return $return_value;
	  }

	  /**
   * Function updates coordinate spatial units
   */
	public static function updateCoordinateSpatialUnit($entry = array()) {
		try {
		  // db_update()...->execute() returns the number of rows updated.
		  $count = db_update('s2.spatial_layer')
			  ->fields($entry)
			  ->condition('id', $entry['id'])
			  ->execute();
		}
		catch (\Exception $e) {
		  drupal_set_message(t('db_update failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}
		return $count;
	}

  /**
   * Function deletes selected coordinate spatial unit
   */
	public static function deleteCoordinateSpatialUnit($entry = array()) {
		foreach($entry as $row){
			db_delete('s2.spatial_layer')
			->condition('id', $row)
			->execute();
		}
	}

	/**
   * Function returns spatial layer names as array values
   * First selected field is returned as key of result array
   * It returns only layers with at least one associated variable.
   *
   * Used in:
   * src/Form/stageVariablesForm.php
   *
   */
	public static function loadSpatialLayers($entry = array()) {
		$select = db_select('s2.var_values', 'example');
		$select->join ('s2.var_names', 'names', 'example.var_names_id = names.id');
		$select->join('s2.spatial_layer', 'b', 'example.spatial_layer_id = b.id');
		$select->addField('b','id','id');
    $select  ->orderBy('b.weight', 'DESC');
		$select->addField('b','name','name');

		return $select->execute()->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
	}

	/**
   * Function returns spatial layer
   *
   * Used in:
   * src/Form/StageVariablesEditForm
   *
   */
	public static function loadSpatialLayersCondition($entry = array()) {
		$select = db_select('s2.var_values', 'example');
		$select->join('s2.spatial_layer', 'b', 'example.spatial_layer_id = b.id');
		$select->addField('b','id','id');
    $select->addField('b','name','name');

		foreach ($entry as $field => $value) {
		  $select->condition('example.'.$field, $value);
		}

		return $select->execute()->fetchAll();
	}

	/**
   * Function updates spatial layer for selected variables
   *
   * Used in:
   * src/Form/StageVariablesEditForm
   *
   * @entry		array	spatial_layer id to update
   * @condition	array	Id list of variables
   */
	public static function updateSpatialLayersCondition($entry = array(), $condition = array()) {
		$ids = StageDatabaseSM::get_permitted_ids();
		try {
		  $count = db_update('s2.var_values');
		  $count->fields($entry);

			  $or = db_or();
			  foreach($condition as $con){
				  $or->condition("id", $con);
			  }

		  $count->condition($or);

		  if (count($ids) > 0) {
			$count -> condition('id',$ids,'IN');
		  }

		  $count = $count->execute();
		}
		catch (\Exception $e) {
		  drupal_set_message(t('db_update failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}
		return $count;
	}


/****
*** 	MENU TREE EDITOR
****/

	/**
   * Function loads menu tree structure from var_tree table
   *
   * @deprecated please use GetMenuTree from MenuTree class
   *
   * Used in:
   * src/Form/StageMenuTreeForm
   * src/Form/StageMenuTreeAddForm
   *
   * @condition		array	array of var_tree ids as condition
   */
	public static function loadMenuTree($condition = array(), $fetchAllAssoc = false) {
		$select = db_select('s2.var_tree', 'example');
		$select->join('s2.var_names', 'b', 'example.id = b.var_tree_id');
		$select->fields('example')
				->fields('b', array('name'))
				->addField('b', 'id', 'name_id');
		$select->addExpression('position::int', 'hej');
    $select  ->orderBy('hej', 'ASC');
		$or = db_or();
		foreach($condition as $con){
			$or->condition("example.id", $con);
		}
		if(!empty($condition)){
			$select->condition($or);
		}

		if(!$fetchAllAssoc){
			return $select->execute()->fetchAll();
		}else{
			return $select->execute()->fetchAllAssoc('id');
		}
	}

	/**
   * Function saves new menu item to
   */
	public static function saveMenuTree($entry = array()){
		$return_value = NULL;
		try {
		  $return_value = db_insert('s2.var_tree')
			->fields($entry)
			->execute();
		}
		catch (\Exception $e) {
		  drupal_set_message(t('db_insert failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}
		return $return_value;
	}

	/**
   * Function saves new menu item to var_name
   */
	public static function saveVariableName($entry = array(),$update_positions){
		$return_value = NULL;

		try {
		  $return_value = db_insert('s2.var_names')
			->fields($entry)
			->execute();
		}
		catch (\Exception $e) {
		  drupal_set_message(t('db_insert failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}

		foreach ($update_positions as $key => $value) {
			$que = db_update('s2.var_tree');
			$que->condition('id',array($key));
			$que ->fields(array(
				'position'=> $value,
			))
			->execute();
		}
		return $return_value;
	}

	/**
   * Function saves new menu item to var_name
   */
	public static function getVariableName($entry = array()){
		$select = db_select('s2.var_names', 'example');
		$select->fields('example');

		// Add each field and value as a condition to this query.
		foreach ($entry as $field => $value) {
		  $select->condition($field, $value);
		}

		return $select->execute()->fetchAll();
	}

  /**
   * Function returns spatial table_name from id
   */
	public static function getSpatialTableName($id){
		return db_query('SELECT table_name from s2.spatial_layer_date where id=:id',array(':id'=>$id))->fetchField();
	}

	/**
   * Function renames menu item
   */
	public static function renameMenuTree($entry = array()){
		$return_value = NULL;
		  $count = db_update('s2.var_names');

			  $fields = array();
			  $fields['name'] = $entry['text'];

			  if(isset($entry['acronym'])){
				 $fields['short_name']=$entry['acronym'];
				 $oldAcronym=db_query("SELECT short_name from s2.var_names where var_tree_id=?",[$entry['id']])->fetchField();
			  }

			  if(isset($entry['description'])){
				 $fields['description']=$entry['description'];
			  }
			  if(isset($entry['popup_title'])){
				 $fields['popup_title']=$entry['popup_title'];
			  }
				if(isset($entry['legend_title'])){
				 $fields['legend_title']=$entry['legend_title'];
			  }

			  $count->fields($fields);
			  $count->condition('var_tree_id', $entry['id'])
			  ->execute();

				if(isset($entry['acronym'])){
				 $a=db_update('s2.var_links');
				 $a->fields(['acronym'=>$entry['acronym']]);
				 $a->condition('acronym',$oldAcronym)->execute();
			  }

		return $count;
	}

	/**
	* Get the parent id
	*/
	public static function getParentTreeid ($entry = array()) {
		$que = db_select('s2.var_tree','tree');
		$que-> fields('tree',array('parent_id'));
		$que->condition('id', $entry);
		$result = $que->execute()->fetchAssoc()['parent_id'];
		return $result;
	}
	/**
   * Function deletes selected tree menu item from var_tree table
   */
	public static function deleteTreeItem($entry = array()) {
			db_delete('s2.var_tree')
			->condition('id', $entry)
			->execute();
	}

	/**
   * Function deletes selected tree menu name from var_names table
   */
	public static function deleteTreeName($entry = array()) {

			db_delete('s2.var_names')
			->condition('var_tree_id', $entry)
			->execute();
	}

	/**
   * Function updates menu tree item
   */
	public static function updateTreeItem($entry = array()){
		$return_value = NULL;

		try {
		  $count = db_update('s2.var_tree')
			  ->fields(array(
						'parent_id'=> $entry['parent_id'])
						// ,'position'=> $entry['position'])
						)
			  ->condition('id', $entry['id'])
			  ->execute();
		}
		catch (\Exception $e) {
		  drupal_set_message(t('db_update failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}
		$update_positions = $entry['update_positions'];
		foreach ($update_positions as $key => $value) {

			$que = db_update('s2.var_tree');
			$que->condition('id',array($key));
			$que ->fields(array(
				'position'=> $value,
			))
			->execute();
		}
		return $count;
	}

	/**
   * Function returns all first childrens of given tree ids
   *
   * Used in:
   * src/Form/StageMenuTreeAddForm.php
   *
   * @conditions	array	indexed array with condition values
   */
	public static function getFirstTreeChildrens($condition = array()) {
		try {
		  $select = db_select('s2.var_tree', 'example');
			$select->fields('example');

			  $or = db_or();
			  foreach($condition as $con){
				  $or->condition("parent_id", $con);
			  }
			  $select->condition($or);

			  return $select->execute()->fetchAll();
		}
		catch (\Exception $e) {
		  drupal_set_message(t('db_update failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}
		return $select;
	}

	/**
   * Function returns all childrens of given tree id
   *
   * Used in:
   * src/Form/StageMenuTreeAddForm.php
   *
   * @conditions	integer		var_tree id of parent
   */
	public static function getAllTreeChildrens($id) {

		$childrens = array($id);
		$current = array($id);

		while(!empty($current))
		{
			$results = self::getFirstTreeChildrens($current);
			$current = array();
			foreach($results as $result){
				array_push($childrens, $result->id);
				array_push($current, $result->id);
			}
		}

		return $childrens;
	}

	/**
   * Function returns var_names table by acronym key
   *
   * Used in:
   * stage2_client/src/StageClientSM
   *
   * @lang_code	string	Language code of translation
   */

	public static function getVariableNames(){
		$select = db_select('s2.var_names', 'v');
		$select->fields('v', array('var_tree_id'))
				->fields('v', array('description'))
				->fields('v', array('short_name'));

		return $select->execute()->fetchAllAssoc('short_name', PDO::FETCH_ASSOC);
	}
/****
*** 	VARIABLES
****/

	/**
   * Function loads all variables with their names
   *
   * Used in:
   * stage2_client/src/StageClientSM
   */
	public static function loadVariables($entry = array()) {
		$select = db_select('s2.var_values', 'example');
		$select->join('s2.var_names', 'b', 'example.var_names_id = b.id');
		$select->fields('b', array('id'))
				->fields('b', array('name'))
				->fields('b', array('short_name'));

		$select->addExpression('MAX(published)', 'published_max');
		$select->addExpression('MIN(published)', 'published_min');
		// get one value of variable
		$select->addExpression('MIN(example.id)', 'var_value_id');
		$select->addExpression('MIN(example.spatial_layer_id)', 'spatial_layer_id');

		// Add each field and value as a condition to this query.
		foreach ($entry as $field => $value) {
		  $select->condition('example.'.$field, $value);
		}
		$select->groupBy('b.id');

		return $select->execute()->fetchAll();
	}

	/**
   * Function loads all values of selected variable
   *
   * Used in:
   * src/Form/StageVariablesEditForm
   * src/From/StageMenuTreeAddForm
   *
   * @entry		array	associative array with names of columns as key and values or array with values
   */
	public static function loadVariablesNoNames($entry = array()) {
		$select = db_select('s2.var_values', 'example');
		$select->fields('example', array('id'))
				->fields('example', array('valid_from'))
				->fields('example', array('published'))
				->fields('example', array('var_ds_id'))
				->fields('example', array('var_properties_id'));

		foreach ($entry as $field => $value) {
			if(is_array($value)){
				$or = db_or();
				  foreach($value as $val){
					  $or->condition('example.'.$field, $val);
				  }
				  $select->condition($or);
			}else{
				$select->condition('example.'.$field, $value);
			}
		}

		return $select->execute()->fetchAllAssoc('id');
	}

	/**
   * Function returns closest variable value by valid timestamp
   *
   * Used in:
   * src/From/StageMenuTreeAddForm
   *
   * @variableId		integer		id of variable
   * @timestamp			string		date timestamp - "YYYY-MM-DD" format
   */
	public static function getClosestVariableValue($variableId, $timestamp){
		$select = db_select('s2.var_values', 'example');
		$select->fields('example', array('id'))
				->fields('example', array('var_properties_id'))
				->fields('example', array('valid_from'));

		$or = db_or();
		$or->condition("valid_from", $timestamp,">");
		$or->condition("valid_from", $timestamp,"<");
		$select->condition($or);

		$select->condition("var_names_id", $variableId);

		$select->addExpression("abs(extract(epoch from '".$timestamp."' - valid_from))", "for_order");

		$select->orderBy("for_order", "ASC");

		$select->range(0,1);	// limit 1
		return $select->execute()->fetchAll();
	}

	/**
   * Function returns all variables of given spatial layer
   * Function is updated copy of function "loadVariables()" from "StageDatabaseSM.php"
   *
   * Used in:
   * src/From/StageVariablesForm
   *
   * @entry		array	condition
   */
	public static function  loadVariables2($entry){
     $que = db_select('s2.var_values','var_values');
     $que->range(0, 1000000);
     $que-> fields('var_values',array('spatial_layer_id','published'));
     $que->join ('s2.var_names', 'names', 'var_values.var_names_id = names.id');
     $que->join ('s2.var_tree', 'var_tree', 'var_tree.id = names.var_tree_id');
     $que->join ('s2.spatial_layer', 'spatial_layer', 'var_values.spatial_layer_id = spatial_layer.id');
     $que->addField('names', 'id','id_name');
     $que->addField('names', 'name','names_name');
     $que->addField('names', 'short_name','names_hort_name');
     $que->addField('spatial_layer', 'name','spatial_layer_name');
     $que->addField('spatial_layer', 'id','id_spatial_layer');
     $que->addField('var_values', 'modified','modified');
	 // Add each field and value as a condition to this query.
		foreach ($entry as $field => $value) {
		  $que->condition($field, $value);
		}
     $que  ->orderBy('spatial_layer.weight', 'DESC');
     $que  ->orderBy('var_tree.position', 'ASC');
    //  $que->fields('names', array('id'))
    //      ->fields('names', array('name'));

    return $que->execute()->fetchAll();
   }

   /**
   * Function counts export service downloads (shape, TSV)
   *
   * Used in:
   * src/From/StageVariablesForm
   *
   * @entry		array	condition
   */
	public static function  countDownloads(){
		$select = db_select('s2.var_download', 'vd');
		$select->join ('s2.var_values', 'vv', 'vv.id = vd.var_values_id');
		$select->join ('s2.var_names', 'names', 'vv.var_names_id = names.id');
		$select->join ('s2.spatial_layer', 'spatial_layer', 'vv.spatial_layer_id = spatial_layer.id');
		$select->fields('vv', array('var_names_id'))
				->fields('spatial_layer', array('id'))
				->fields('vd', array('count'));

		$select->addExpression("count(vd.id)", "count");
		$select->groupBy('vv.var_names_id');
		$select->groupBy('spatial_layer.id');
		return $select->execute()->fetchAll();
	}

/****
*** 	VARIABLE
****/


	/**
   * Function updates variable values
   *
   * Used in:
   * src/Form/StageVariablesEditForm
   * src/From/StageMenuTreeAddForm (3x)
   *
   * @entry		array	associative array with names of columns and values to update
   * @condition array	indexed array with ids to update
   */
	public static function updateVariableValues($entry = array(), $condition = array()) {
		try {
		  $count = db_update('s2.var_values')
			  ->fields($entry);

			  $or = db_or();
			  foreach($condition as $con){
				  $or->condition("id", $con);
			  }

			  $count->condition($or)
			  ->execute();
		}
		catch (\Exception $e) {
		  drupal_set_message(t('db_update failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}
		return $count;
	}

/****
*** 	VARIABLE PROPERTIES
****/

	/**
   * Get default variable properties
   *
   * Used in:
   * src/Form/StageDefaultParametersForm
   * src/Form/StageMenuTreeAddForm
   */
	public static function getDefaultVariableProperties(){
		$select = db_select('s2.var_properties', 'example');
		$select->fields('example', array('id'))
				->fields('example', array('data'))
				->condition('default', 1);

		return $select->execute()->fetchAll();
	}

	/**
   * Check for existing JSON properties
   *
   * @entry		associative array		key->value of every condition
   */
	public static function getVariableProperties($entry = array()){
		$select = db_select('s2.var_properties', 'example');
		$select->fields('example', array('id'))
				->fields('example', array('data'));

		foreach ($entry as $field => $value) {
		  $select->condition('example.'.$field, $value);
		}

		return $select->execute()->fetchAll();
	}

	/**
   * Insert variable properties (json)
   */
	public static function saveVariableProperties($entry = array()){
		$return_value = NULL;

		try {
		  $return_value = db_insert('s2.var_properties')
			->fields($entry)
			->execute();
		}
		catch (\Exception $e) {
		  drupal_set_message(t('db_insert failed. Message = %message, query= %query', array(
			'%message' => $e->getMessage(),
			'%query' => $e->query_string,
		  )
		  ), 'error');
		}
		return $return_value;
	}

	/**
   * Saves JSON of variable properties
   *
   * Used in:
   * src/Form/StageVariablesEditRawForm
   */
	public static function updateVariableProperties($entry = "", $var_ds_id = null){

		$id = -1;
		if (!is_null($var_ds_id)) {
			$res = db_query("SELECT var_properties_id from s2.var_values WHERE id=:id",[':id' => $var_ds_id])->fetchField();
			if (!empty($res)) $id = $res;
		}

		// if not exists input new
		if($id == -1)
		{
			self::saveVariableProperties(array("data"=>$entry));
			$id = self::lastInsertedId('id', 's2.var_properties');
		}

		return $id;
	}

	/**
   * Function sets parameter to default
   *
   * Used in:
   * src/Form/StageDefaultParametersForm
   *
   * @id	integer		new default parameter id
   */
	public static function setDefaultProperties($id){
		// unset old default
		$count = db_update('s2.var_properties')
			->fields(array('default'=>0))
			->condition('default', 1)
			->execute();

			// set new default
			$count2 = db_update('s2.var_properties')
			->fields(array('default'=>1))
			->condition('id', $id)
			->execute();

		return $count2;
	}

	public static function saveSuNotes($id, $data) {
		if (empty($data)) {
			db_query("DELETE FROM s2.su_notes WHERE var_properties_id=:id",[':id'=>$id]);
			return;
		}

		$data = json_decode($data);
		
		$before = db_query("SELECT id from s2.su_notes WHERE var_properties_id=:id",[':id'=>$id])->fetchCol();

		$updated = [];

		foreach($data as $row) {
			if (($row->id)[0]==='_') {
				db_query("INSERT INTO s2.su_notes (var_properties_id, sid, note) values(:var_properties_id,:sid,:note)", [':var_properties_id'=>$id,':sid'=>$row->sid,':note'=>$row->note]);
			}
			else {
				$updated[] = $row->id;
				db_query("UPDATE s2.su_notes SET sid=:sid, note=:note WHERE id=:id", [':id'=>$row->id,':sid'=>$row->sid,':note'=>$row->note]);
			}
		}

		$diff = array_values(array_diff($before, $updated));
		
		if (!empty($diff)) {
			db_delete('s2.translations')
				->condition('table_name','su_notes')
				->condition('column_name','note')
				->condition('orig_id',$diff,'IN')
				->execute();

			db_delete('s2.su_notes')
				->condition('id',$diff,'IN')
				->execute();
		}
	}

	/**
   * Function saves special values to database
   *
   * Used in:
   * src/Form/StageVariablesEditRawForm
   *
   * @id	integer		Variable properties id
   * @data	array		Special values data
   */
	public static function insertSpecialValues($id, $data,$inputs){
		//update existing
		$legend_captions = array_values($inputs['lc']);
		$colors = array_values($inputs['col']);
		$values = [];
		$keys = [];
		foreach($data as $key => $value){
			$values[] = $value['value'];
			$keys[] = $key;
		}
		
		$i=0;

		$added = [];
		$updatedKeys = [];
		
		$orig = json_decode($inputs['manual_parameters']['manual_param_input']['pass_special_values']);
		foreach ($keys as $key) {
			if (isset($orig->$key)) {
				$q=db_query("UPDATE s2.special_values SET special_value = :special_value,
													legend_caption = :lc, 
													color = :color 
							WHERE id = :key",[':special_value' =>$values[$i], 
											':lc' => $legend_captions[$i], 
											':color' => $colors[$i], 
											':key' => $key]);
				$updatedKeys[]=$key;
			}
			else {
				$added[] = [
					'var_properties_id' => $id,
					'special_value' => $values[$i],
					'legend_caption' => $legend_captions[$i],
					'color' => $colors[$i]
				];
			}
			$i++;
		}

		$before = db_query("SELECT id from s2.special_values WHERE var_properties_id=:id", [':id' => $id])->fetchCol();

		if (!empty($updatedKeys)) {
			db_delete('s2.special_values')
				->condition('var_properties_id', $id)
				->condition('id',$updatedKeys, 'NOT IN')
				->execute();
		}

		if (empty($keys)) {
			$diff = [];
			foreach ($orig as $key=>$value) {
				$diff[]=$key;
			}

			if (!empty($diff)) {
				db_delete('s2.special_values')
				->condition('var_properties_id', $id)
				->condition('id',$diff, 'IN')
				->execute();
			}
		}

		$after = db_query("SELECT id from s2.special_values WHERE var_properties_id=:id", [':id' => $id])->fetchCol();

		$diff = array_values(array_diff($before, $after));
		
		if (!empty($diff)) {
			db_delete('s2.translations')
				->condition('table_name','special_values')
				->condition('column_name','legend caption')
				->condition('orig_id',$diff,'IN')
				->execute();
		}

		foreach($added as $addition) {
			db_insert('s2.special_values')->fields($addition)->execute();
		}
	}

	/**
   * Function reads special values for given properties id
   *
   * Used in:
   * src/Form/StageVariablesEditRawForm
   *
   * @id	integer		Variable properties id
   */

	public static function getSpecialValues($id){
		$select = db_select('s2.special_values', 'sv');
		$select->fields('sv', array('id'))
			   ->fields('sv', array('special_value'))
			   ->fields('sv', array('legend_caption'))
			   ->fields('sv', array('color'));

		$select->condition('sv.var_properties_id', $id);

		return $select->execute()->fetchAll();
	}

/****
*** 	VARIABLE WARNINGS AND CONTROLS
****/

	/**
   * Returns values for same acronym with same spatial_layer on same date
   *
   * Used in:
   * src/Form/StageBatchImportForm
   *
   * @id	integer		spatial_layer_id - Query for selected spatial layer
   */

	public static function getDuplicateValuesOnDate($id){
		return db_query("SELECT n1.valid_from, n1.var_names_id, names.short_name
						FROM s2.var_values n1
						INNER JOIN s2.var_values n2
							JOIN s2.var_names names
							ON n2.var_names_id = names.id
						ON n2.valid_from=n1.valid_from
						WHERE n1.var_names_id = n2.var_names_id AND n1.spatial_layer_id = {$id}
						GROUP BY n1.valid_from, n1.var_names_id, names.id
						HAVING (COUNT(*) > 1)")->fetchAll();
	}

	/**
   * Returns values for same acronym with same spatial_layer on same year
   *
   * Used in:
   * src/Form/StageBatchImportForm
   *
   * @id	integer		spatial_layer_id - Query for selected spatial layer
   */

	public static function getDuplicateValuesOnYear($id){
		return db_query("SELECT date_part('year',n1.valid_from), n1.var_names_id, names.short_name
						FROM s2.var_values n1
						INNER JOIN s2.var_values n2
							JOIN s2.var_names names
							ON n2.var_names_id = names.id
						ON date_part('year', n2.valid_from)=date_part('year',n1.valid_from)
						WHERE n1.var_names_id = n2.var_names_id AND n1.spatial_layer_id = {$id}
						GROUP BY date_part('year',n1.valid_from), n1.var_names_id, names.id
						HAVING (COUNT(*) > 1)")->fetchAll();
	}

/****
*** 	ADVANCED SETTINGS
****/

	/**
   * Function reads advanced settings
   *
   * Used in:
   * src/Form/StageDefaultParametersForm
   *
   * @id	integer		new default parameter id
   */

	public static function getAdvancedSettings($entry = array()){
		$select = db_select('s2.advanced_settings', 'example');
		$select->fields('example', array('value'));

		foreach ($entry as $field => $value) {
		  $select->condition('example.'.$field, $value);
		}

		return $select->execute()->fetchAll();
	}

/****
*** 	TRANSLATIONS
****/

	/**
   * Function returns menu tree translations for given language code
   *
   * Used in:
   * stage2_admin/src/StageFormsCommon.php
   *
   * @lang_code	string	Language code of translation
   */

	public static function getTreeTranslation($lang_code){
		$select = db_select('s2.translations', 't');
		$select->fields('t', array('orig_id'))
			   ->fields('t', array('translation'));

		$select->condition('t.language_id',$lang_code);
		$select->condition('t.table_name','var_names');
		$select->condition('t.column_name','name');

		return $select->execute()->fetchAllAssoc('orig_id', PDO::FETCH_ASSOC);
	}

/****
*** 	OTHER
****/

	/**
   * Function returns last inserted id
   */
	public static function lastInsertedId($column, $table){
		return db_query('SELECT MAX('.$column.') FROM {'.$table.'}')->fetchField();
	}
}
