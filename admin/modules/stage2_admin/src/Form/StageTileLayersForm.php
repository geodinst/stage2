<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\stage2_admin\StageDatabaseSM;

class StageTileLayersForm extends FormBase{

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_tile_layers';
  }

  public static $layers = array();
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	$layers = StageDatabaseSM::stage_get_advanced_settings();

	foreach($layers as $key => $layer){
		if($layer->setting == 'tile_layers'){
			$lkey = $key;
		}
	}

	self::$layers = $layers[$lkey];

	$header = ['enabled' => $this->t('Enabled'),
				'name' => $this->t('Name'),
				'url' => $this->t('Url'),
				'attribution' => $this->t('Attribution')];

	$form['layers'] = array(
	  '#type' => 'table',
	  '#caption' => $this->t('Tile layers'),
	  '#header' => $header,
	);

	$i=0;
	foreach(json_decode($layers[$lkey]->value) as $layer){
		$form['layers'][$i][$layer->name] = array(
			'#type' => 'checkbox',
			'#title' => 'enabled',
			'#title_display' => 'invisible',
			'#default_value' => $layer->enabled,
		);

		$form['layers'][$i]['name'] = array(
			'#markup' => $layer->name,
			'#title' => 'name',
			'#title_display' => 'invisible',
		);

		$form['layers'][$i]['url'] = array(
			'#markup' => $layer->url,
			'#title' => 'url',
			'#title_display' => 'invisible',
		);

		$form['layers'][$i]['attribution'] = array(
			'#markup' => isset($layer->attribution) ? $layer->attribution:'',
			'#title' => 'attribution',
			'#title_display' => 'invisible',
		);

		$i++;
	}

	$form['save'] = array
		  (
			'#type' => 'submit',
			'#value' => t('Save'),
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
	$bla = $form_state->getValues();
	$table = $bla['layers'];
	$layers = json_decode(self::$layers->value);

	foreach($layers as &$layer){
		foreach($table as $key => $checkbox){
			if(isset($checkbox[$layer->name])){
				$layer->enabled = $checkbox[$layer->name];
				continue;
			}
		}
	}

	StageDatabaseSM::stage_update_advanced_setting("Tile layers","tile_layers", json_encode($layers), self::$layers->description);

	return;
  }
}
