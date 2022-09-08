<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabaseSM;

class StageLandingPageForm extends FormBase
{

/**
* Implements \Drupal\Core\Form\FormInterface::getFormID().
*/
    public function getFormID()
    {
        return 'stage_landing_page_form';
    }
    /**
    * Implements \Drupal\Core\Form\FormInterface::buildForm().
    */
    public function buildForm(array $form, FormStateInterface $form_state)
    {

      // get landing page json from advanced settings
      $landing_pageDB=db_query("SELECT value from s2.advanced_settings where setting='landing_page'")->fetchField();

      // Get all languages available in the drupal translate module
      $language_all = \Drupal::languageManager()->getLanguages();
      // Languages that are registered in the drupal administrative pages
      $available_languages = array_keys($language_all);
      $form_state->set('available_languages', $available_languages);
      foreach ($available_languages as $key => $language) {


        $form[$language] = array(
          '#type' => 'text_format',
          '#title' => t('Landing page: ').$language,
          '#default_value' => isset (json_decode($landing_pageDB, true)[$language]) ? json_decode($landing_pageDB, true)[$language][0]: '',
          '#name' => 'd'.$language,
          '#format'=> 'full_html',
        );
      }

        $form['save'] = array(
         '#type' => 'submit',
         '#value' => t('Save')
        );


        // $form['#attached']['library'][] = 'stage2_admin/StageCoordinateSystemForm';
        return $form;
    }

    public function validateForm(array &$form, FormStateInterface  $form_state)
    {
        // Validate the form values. { "base": "\/var\/www\/html\/stage2client\/" }
    }
    public function submitForm(array &$form, FormStateInterface  $form_state)
    {
        $values = $form_state->getValues();
        $available_languages = $form_state->get('available_languages');
        $landing_pageDB = array();
        foreach ($available_languages as $key => $language) {
          $landing_pageDB[$language] = array($values[$language]['value']);
        }


        StageDatabaseSM::stage_update_advanced_setting('landing_page','landing_page',json_encode($landing_pageDB),'landing_page');

    }
}
