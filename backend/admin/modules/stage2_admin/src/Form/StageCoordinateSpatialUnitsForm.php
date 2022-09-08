<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageFormsCommon;
use Drupal\stage2_admin\BackgroundProcess;

class StageCoordinateSpatialUnitsForm extends FormBase{

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_coordinate_spatial_units_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $users = StageDatabase::loadCoordinateSpatialUnits();

	  $header = array(
		'first_name' => t('Name'),
		'prefered' => t('Prefered'),
		'weight' => t('Weight'),
    'cache' => t('Caching'),
	  );

	  $options = array();

	  foreach ($users as $user) {
      $cache=json_decode($user->cache);
      $options[$user->id] = array(
        'first_name' => Link::fromTextAndUrl($user->name, Url::fromUri('internal:/stage_settings/spatial_units/edit/'.$user->id)),
        'prefered' => ($user->note_id && $user->note_id=='1') ? $user->note_id : '',
        'weight' => $user->weight,
        'cache' => BackgroundProcess::is_process_running($cache->pid)?'in progress':'idle'
      );
	  }

    $instanceName=StageFormsCommon::getInstanceName();
    
    if ($instanceName != 'stage2_test'){
      $form['coordinate_spatial_units']['add'] = array(
        '#type' => 'submit',
        '#value' => t('Add spatial unit'),
      );
      $form['coordinate_spatial_units']['delete'] = array(
        '#type' => 'submit',
        '#value' => t('Delete selected'),
      );
      
    $user = \Drupal::currentUser();
    if ($user->hasPermission('stage2_admin content_administrator')) {

      $form['do_cache'] = [
        '#type' => 'submit',
        '#value' => t('Precache selected'),
      ];
      
      $form['clear_cache'] = [
        '#type' => 'submit',
        '#value' => t('Clear selected layer(s) cache'),
      ];
    }
  }
    
	  $form['coordinate_spatial_units']['table'] = array(
		'#type' => 'tableselect',
		'#header' => $header,
		'#options' => $options,
		'#js_select' => false,
		'#empty' => t('No spatial units found'),
	  );

	  $form['coordinate_spatial_units']['pager'] = array(
		  '#type' => 'pager',
		  '#weight' => 10,
		);
    $form['coordinate_spatial_units']['table_note'] = array(
      '#type' => 'fieldset',
      '#title' => t('Note'),
    );
    $form['coordinate_spatial_units']['table_note']['table_note'] = array(
      '#markup' => t('The table shows the hierarchically arranged list of codes of spatial units. Prefered column indicates the spatial unit, which is shown as the first, as long as there are values for the selected variable.')
    );


	  return $form;
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate the form values.
  }
  
  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do something useful.
	$bla = $form_state->getTriggeringElement();
	$id = $bla["#parents"][0];

	if($id == "add"){

		$url = \Drupal\Core\Url::fromRoute('stage2_admin.coordinateSpatialUnitsEdit')
				->setRouteParameters(array('id'=>-1));
		$form_state->setRedirectUrl($url);

	}elseif($id == "delete"){

		$bla = $form_state->getValues();

		$idsToDelete = array();
		foreach($bla['table'] as $key => $value)
		{
			if($value != 0){
				array_push($idsToDelete, $key);
			}
		}

		$url = \Drupal\Core\Url::fromRoute('stage2_admin.coordinateSpatialUnitsDelete')
			  ->setRouteParameters(array('id'=>json_encode($idsToDelete)));

		$form_state->setRedirectUrl($url);
	}
  else if ($id == "do_cache"){
      //\Drupal::service('file_system')->realpath(file_default_scheme() . "://");
      //\Drupal::service('file_system')->realpath("private://");
			$fsv= $form_state->getValues();
			// get id's of the selected elements
			$selected = array_filter($fsv['table']);
      
      $layers=db_query("SELECT * from s2.spatial_layer where id IN (:ids[])",[':ids[]'=>$selected])->fetchAll();
      
      $path=\Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/proxy_cache';
      
      foreach($layers as $layer){
        $pid=json_decode($layer->cache)->pid;
        BackgroundProcess::kill_process($pid);
        $tnames=db_query("SELECT table_name from s2.spatial_layer_date where spatial_layer_id=?",[$layer->id])->fetchCol();
        $params=[];
        foreach($tnames as $tname){
          $params[]=['tname'=>$tname, 'extents'=>db_query("SELECT st_asgeojson(ST_Transform(st_setsrid(ST_Extent(geom),4326),900913)) as extents from ge.$tname")->fetchField()];
        }
        
        $params=base64_encode(json_encode($params));
        $conn=db_query("SELECT value from s2.advanced_settings where setting='gsrv'")->fetchField();
        $port=json_decode($conn)->port;
        $bp=new BackgroundProcess("php ".__DIR__."/../_create_cache.php $params $path $port");
        drupal_set_message("Caching process was initiated ...");
        $pid=$bp->pid();
        db_query("UPDATE s2.spatial_layer SET cache=? where id=?",["{\"pid\":$pid}",$layer->id]);
      }
		}
		else if ($id == "clear_cache"){
			$fsv= $form_state->getValues();
			// get id's of the selected elements
			$selected = array_filter($fsv['table']);
      
      $layers=db_query("SELECT * from s2.spatial_layer where id IN (:ids[])",[':ids[]'=>$selected])->fetchAll();
      
      $path=\Drupal::service('file_system')->realpath(file_default_scheme() . "://");
      
      $keys=self::getMemcachedKeys();
      
      $m = new \Memcached();
      $m->addServer('stage2-memcached', 11211);
      
      foreach($layers as $layer){
        $pid=json_decode($layer->cache)->pid;
        BackgroundProcess::kill_process($pid);
        $tnames=db_query("SELECT table_name from s2.spatial_layer_date where spatial_layer_id=?",[$layer->id])->fetchCol();
        foreach($tnames as $tname){
          foreach($keys as $key) {
            if (strpos($key,$tname)!==false){
              $m->delete($key);
              drupal_set_message("MEMCACHED CLEARED");
            }
          }
          
          foreach(['stage_color','line'] as $style){
            system("rm -R $path/proxy_cache/{$tname}_{$style}");
          }
        }
      }
		}

	return;
  }
  
  public static function getMemcachedKeys($host = 'stage2-memcached', $port = 11211)
  {
  
      $mem = @fsockopen($host, $port);
      if ($mem === FALSE) return -1;
  
      // retrieve distinct slab
      $r = @fwrite($mem, 'stats items' . chr(10));
      if ($r === FALSE) return -2;
  
      $slab = array();
      while (($l = @fgets($mem, 1024)) !== FALSE) {
          // sortie ?
          $l = trim($l);
          if ($l == 'END') break;
  
          $m = array();
          // <STAT items:22:evicted_nonzero 0>
          $r = preg_match('/^STAT\sitems\:(\d+)\:/', $l, $m);
          if ($r != 1) return -3;
          $a_slab = $m[1];
  
          if (!array_key_exists($a_slab, $slab)) $slab[$a_slab] = array();
      }
  
      // recuperer les items
      reset($slab);
      foreach ($slab AS $a_slab_key => &$a_slab) {
          $r = @fwrite($mem, 'stats cachedump ' . $a_slab_key . ' 100' . chr(10));
          if ($r === FALSE) return -4;
  
          while (($l = @fgets($mem, 1024)) !== FALSE) {
              // sortie ?
              $l = trim($l);
              if ($l == 'END') break;
  
              $m = array();
              // ITEM 42 [118 b; 1354717302 s]
              $r = preg_match('/^ITEM\s([^\s]+)\s/', $l, $m);
              if ($r != 1) return -5;
              $a_key = $m[1];
  
              $a_slab[] = $a_key;
          }
      }
  
      // close
      @fclose($mem);
      unset($mem);
  
      // transform it;
      $keys = array();
      reset($slab);
      foreach ($slab AS &$a_slab) {
          reset($a_slab);
          foreach ($a_slab AS &$a_key) $keys[] = $a_key;
      }
      unset($slab);
  
      return $keys;
  }
}
