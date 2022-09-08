<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;

class StageCoordinateSystemDeleteForm extends ConfirmFormBase{

	protected $id;
	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_coordinate_system_delete_form';
  }

  public function getQuestion() {
    return t('Do you really want to delete coordinate systems '.$this->id .' ?');
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
	StageDatabase::deleteCoordinateSystems($entry);
	StageDatabaseSM::stageLog('coordinate systems','Coordinate system deleted with id: '.$this->id);

	$url = \Drupal\Core\Url::fromRoute('stage2_admin.realContent');
    $form_state->setRedirectUrl($url);
  }
}
