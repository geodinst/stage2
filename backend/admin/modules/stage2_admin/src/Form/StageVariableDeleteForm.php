<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;

class StageVariableDeleteForm extends ConfirmFormBase{

	protected $id;
	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_variable_delete_form';
  }

  public function getQuestion() {
    return t('Do you really want to delete variables?');
  }

  public function getCancelUrl() {
		  $url = Url::fromUri('internal:/variables');
      return $url;
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
	StageDatabaseSM::deleteVariables($entry);
	StageDatabaseSM::stageLog('variables ','Variables deleted with id: '.$this->id);

  $url = Url::fromUri('internal:/variables');
  $form_state->setRedirectUrl($url);
  }
}
