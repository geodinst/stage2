<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabaseSM;

class StageGeospatialLayersDeleteForm extends ConfirmFormBase{

	protected $id;
	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_geospatial_layers_delete_form';
  }

  public function getQuestion() {
    return t('Do you really want to delete geospatial layers?');
  }

  public function getCancelUrl() {
      return new Url('stage2_admin.geospatialLayers');
  }

  public function getDescription() {
    json_decode($this->id, true)['dependent'] ?drupal_set_message(t('Some layers may have related valieables.'),'warning'):false;
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

		//json to array
		$array = json_decode($this->id, true)['table'];
		// selected elements
		$selected = array_filter($array);
		$delete_count = StageDatabaseSM::stage2DeleteGeoLayer($selected);
		drupal_set_message(t($delete_count.' geospatial layer(s) deleted.'));

		// // Log that something has been changed in the DB TODO more detailed description
		StageDatabaseSM::stageLog('geospatial layer', $delete_count.' geospatial layer(s) deleted.');

		$url = Url::fromUri('internal:/geospatial_layers');
		$form_state->setRedirectUrl($url);

  }
}
