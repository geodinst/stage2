<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_admin\StageFormsCommon;

class StageVariablesReportINSPIREForm extends FormBase{

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID(){
    return 'stage_variables_report_form';
	}

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $inspire=null) {
    if ($inspire <> -1){
      $gn=json_decode(db_query("SELECT value from s2.advanced_settings where setting='geonetwork'")->fetchField());
      $info= json_decode(unserialize(base64_decode($inspire)),true);
      drupal_set_message(t('INSPIRE metadata info'));
      drupal_set_message(t('Geonetwork uuid:').' '.$info['uuid']);
      $gnid = explode("'",reset($info['metadataInfos'])[0]['message'])[1] ;

      if ($gn->proxy<>''){
        $link = $gn->proxy.'/srv/eng/catalog.search#/metadata/'.$gnid;
        $linkXML = $gn->proxy.'/srv/api/records/'.$gnid.'/formatters/xml';

        $form['link'] =[
          '#markup' => 'Geonetwork link: <a href="'.$link.'">'.$link.'</a><br>'
        ];
        $form['linkxml'] =[
          '#markup' => 'Geonetwork link XML: <a href="'.$linkXML.'">'.$linkXML.'</a>'
        ];
      }else{
        drupal_set_message(t('Geonetwork proxy setting is not defined.'),'warning');

      }
    }
    else{
      drupal_set_message(t('Geonetwork metadata not available'),'warning');
      drupal_set_message(t('Please check geonetwork settings or reimport the data.'),'warning');
    }

	  return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
