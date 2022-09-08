<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_admin\StageFormsCommon;
use Drupal\Core\Database\Database;

use Drupal\stage2_admin\BackgroundProcess;

class StageFlushConfirmForm extends ConfirmFormBase{

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_variable_delete_form';
  }

  public function getQuestion() {
    return t('Do you really want to flush test environemnt?');
  }

  public function getCancelUrl() {
		  $url = Url::fromUri('internal:/clientset');
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
  public function buildForm(array $form, FormStateInterface $form_state) {
	return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $pid=db_query("SELECT value from s2.advanced_settings where setting='cloning'")->fetchField();
    BackgroundProcess::kill_process($pid);
    
    $path=__DIR__."/../../install_scripts";
    
    $conn=Database::getConnectionInfo('default');
    $mainDatabase=$conn['default']['database'];
    $pwd=$conn['default']['password'];
    $tmpfname = tempnam("/tmp", $instanceName);
    
    $bp=new BackgroundProcess("$path/flush.sh $mainDatabase $pwd $tmpfname $path");
    $pid=$bp->pid();
    
    if (BackgroundProcess::is_process_running($pid)) {
      drupal_set_message("Database cloning to test environment has started ...");
    }
    else {
      drupal_set_message('Something went wrong when trying to initiate cloning ...','error');
    }
    
    db_query("UPDATE s2.advanced_settings SET value=? where setting='cloning'",[$pid]);
        
    $url = Url::fromUri('internal:/clientset');
    $form_state->setRedirectUrl($url);
  }
}
