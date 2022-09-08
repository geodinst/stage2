<?php

namespace Drupal\stage2_admin\Classes;

use Drupal\Core\Url;
use Drupal\Core\Link;

use Drupal\stage2_admin\StageDatabaseSM;

class MenuTree{


	/**
	* Function loads menu tree structure from var_tree table
	*
	* src/Form/StageMenuTreeAddForm
	*
	* @param array $condition [var_tree ids as condition]
	* @param boolean $fetchAllAssoc [fetch associative array instead of stdClass]
	*
	*/
	public static function GetMenuTree($condition = [], $fetchAllAssoc = false){

		$query =  db_select('s2.var_tree', 'tree');
		$query -> join('s2.var_names', 'b', 'tree.id = b.var_tree_id');
		$query -> fields('tree')
			   -> fields('b', ['name'])
			   -> addField('b', 'id', 'name_id');
		$query -> addExpression('position::int', 'hej');
		$query -> orderBy('hej', 'ASC');

		$or = db_or();
		foreach($condition as $con){
			$or->condition("tree.id", $con);
		}
		if(!empty($condition)){
			$query->condition($or);
		}

		if(!$fetchAllAssoc){
			return $query->execute()->fetchAll();
		}else{
			return $query->execute()->fetchAllAssoc('id');
		}
	}

	/**
	 * Function prepares the data to be rendered in the Menu tree editor form via jsLibrary jsTree more: https://www.jstree.com/
	 *
	 * Used in:
	 * src/Form/StageMenuTreeForm
	 *
	 */
	public static function GetParsedTreeDataJsTree(){

		$treeData = self::GetMenuTree();
		foreach($treeData as &$data){
			$data->parent = $data->parent_id;
			$data->text = mb_substr($data->name,0,100,'utf-8');
			$data->link = Url::fromUri('internal:/menu_tree/edit/'.$data->id,['absolute' => TRUE])->toString();
			$data->delete = Url::fromRoute('stage2_admin.menuTreeDelete',[],['absolute' => TRUE])
				->setRouteParameters(['id'=>json_encode([$data->id])])->toString();
			if($data->parent == 0){
				$data->parent = "#";
			}
		}
		return $treeData;
	}


	/**
	 * Function deletes selected tree menu item from var_tree table
	 * Used in:
	 * src/Form/StageMenuTreeDeleteForm
	 *
	 * @param  array  $entry [array of menu entry is's]
	 */
	public static function deletById($entry = []) {

		foreach ($entry as $key => $value) {
			$permission_check = StageDatabaseSM::check_user_permissions_for_id('menu tree', $value);
			if ($permission_check !== true) {
				drupal_set_message("Permission error. You have to be a node owner or get your role assigned unrestricted admin permission to delete it.","error");
				continue;
			}
			db_delete('s2.var_tree')
			->condition('id', $value)
			->execute();
			StageDatabaseSM::stageLog('menu tree','Menu node with id: '.$value.' deleted.');
			drupal_set_message(t('Menu node deleted'));
		}
	}
}
