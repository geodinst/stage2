<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;

class StageCoordinateSpatialUnitsDeleteForm extends ConfirmFormBase{

	protected $id;
	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_coordinate_spatial_units_delete_form';
  }

  public function getQuestion() {
    return t('Do you really want to delete spatial unit '.$this->id .' ?');
  }

  public function getCancelUrl() {
      return new Url('stage2_admin.realContent');
  }

  public function getDescription() {
    return t('This option cannot be undone!');
  }

  public function getConfirmText() {
    return t('OK');
  }

  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
	$this->id = $id;
	return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

	//delete
  $entry = json_decode($this->id);

  $ids = StageDatabaseSM::get_allowed_geospatial_layer_ids("additional_data->>'id'", $action='spatial units');

  if (count($ids) > 0) {
    $unpermitted = array_diff($entry, $ids);
    $entry = array_diff($entry, $unpermitted);
  }
  
	StageDatabase::deleteCoordinateSpatialUnit($entry);
	StageDatabaseSM::stageLog('spatial units','Spatial unit deleted with id: '.json_encode($entry));

	$url = \Drupal\Core\Url::fromRoute('stage2_admin.realContent');
    $form_state->setRedirectUrl($url);
  }
}
