<?php

namespace Drupal\stage2_admin\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;

class StagePostRedirectController{

	function init(){
		if(isset($_POST['type']) || isset($_GET['type']))
		{
			if($_POST['type'] == 'saveTree' || $_GET['type'] == 'saveTree')
			{
				$proto = !empty($_POST) ? $_POST : $_GET;

				$input['parent_id'] = $proto['parent_id'];
				$update_positions = $proto['update_positions'];

				$output = StageDatabase::saveMenuTree($input);
				$output3 = StageDatabase::lastInsertedId('id', 's2.var_tree');

				$added_item = end($update_positions);
				array_pop($update_positions);
				$update_positions[$output3] = intval($added_item);
				$input2['var_tree_id'] = $output3;
				$input2['name'] = $proto['text'];
				$input2['description'] = '';
				$input2['short_name'] = 'auto_'.$output3;

				$output2 = StageDatabase::saveVariableName($input2,$update_positions);

				StageDatabaseSM::stageLog('menu tree','New tree item inserted with id: '.$output3,"{\"id\":$output3}");

				return self::output($output3);

			}elseif($_POST['type'] == 'updateNameTree'){
				$permission_check = StageDatabaseSM::check_user_permissions_for_id('menu tree', $_POST['id']);
				if ($permission_check !== true) return self::permission_error_output("Permission error. You have to be a node owner or get your role assigned unrestricted admin permission.");

				$input['id'] = $_POST['id'];
				$input['text'] = $_POST['text'];

				$output = StageDatabase::renameMenuTree($input);

				StageDatabaseSM::stageLog('menu tree','Tree menu item updated with id: '.$_POST['id']);

				return self::output($output);

			}elseif($_POST['type'] == 'deleteTreeItem'){
				$permission_check = StageDatabaseSM::check_user_permissions_for_id('menu tree', $_POST['id']);
				if ($permission_check !== true) return self::permission_error_output("Permission error. You have to be a node owner or get your role assigned unrestricted admin permission.");
				$output = false;
				$parent_id =  StageDatabase::getParentTreeid($_POST['id']);
				if ($parent_id <>0){
					$output = StageDatabase::deleteTreeItem($_POST['id']);
					$output1 = StageDatabase::deleteTreeName($_POST['id']);
					StageDatabaseSM::stageLog('menu tree','Tree menu item deleted with id: '.$_POST['id']);
				}
				return self::output($output);
			}elseif($_POST['type'] == 'moveTreeItem'){
				$permission_check = StageDatabaseSM::check_user_permissions_for_id('menu tree', $_POST['id']);
				if ($permission_check !== true) return self::permission_error_output("Permission error. You have to be a node owner or get your role assigned unrestricted admin permission.");
				$input['id'] = $_POST['id'];
				$input['parent_id'] = $_POST['parent'];
				// $input['position'] = $_POST['position'];
				$input['update_positions'] = $_POST['update_positions'];

				$output = StageDatabase::updateTreeItem($input);

				StageDatabaseSM::stageLog('menu tree','Tree menu item moved with id: '.$_POST['id']);

				return self::output($output);
			}
		}
	}

	function output($output,$code=200){
		return new JsonResponse($output, $code, ['Content-Type'=> 'application/json']);
	}

	function permission_error_output($msg) {
		drupal_set_message($msg,'error');
		return self::output("Permission error.", 401);
	}
}
