<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\Core\Datetime\DrupalDateTime;

class StageClientSettingsApearanceForm extends FormBase
{

    /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
    public function getFormID()
    {
        return 'stage_client_settingsa_apearance_form';
    }
    /**
     * Implements \Drupal\Core\Form\FormInterface::buildForm().
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
      $form['color'] = array(
      '#type' => 'color',
              '#title' => t('Lead color'),
              '#default_value' => '#25a2d1',
            );

        $form['upload'] = array(
        '#type' => 'managed_file',
        '#multiple'=> false,
        '#status'=> FILE_STATUS_PERMANENT,
        '#title' => t('Choose a logo'),
        '#description' => t('Allowed extensions: png., max height 50px, max width 200px'),
        '#upload_location' => 'public://',
        '#upload_validators' => [
            'file_validate_is_image'      => array(),
            'file_validate_extensions'    => array('png'),
            'file_validate_size'          => array(500000)
        ]
    );

    $client_path = StageDatabase::getAdvancedSettings(array("setting"=>"client_path"));
    if (json_decode($client_path[0]->value, true)["base"]=="") {
      drupal_set_message('Client path cannot be an empty string', 'warning');
    }
    else{
      $locale_path = json_decode($client_path[0]->value, true)["base"].'locale.json';
      $this->locale_path = $locale_path;
      $content = file_get_contents($locale_path);
      $this->content = json_decode($content,true);
    }

    $form['tr'] = array(
      '#type' => 'range',
      '#title' => t('Map initial transparency'),
      '#default_value' => json_decode($content)->tr,
    );
    $form['popup'] = array(
      '#type' => 'details',
      '#open' => false,
      '#title' => $this
      ->t('Popup settings'),
    );
    $form['popup']['leaflet-popup-tip-background'] = array(
      '#type' => 'range',
      '#title' => t('leaflet-popup-tip-background'),
      '#default_value' => 70,
    );
    $form['popup']['leaflet-popup-tip-shadow'] = array(
      '#type' => 'range',
      '#title' => t('leaflet-popup-tip-box-shadow'),
      '#default_value' => 20,
    );
    $form['popup']['leaflet-popup-tip-color'] = array(
      '#type' => 'color',
      '#title' => t('leaflet-popup-tip-color'),
      '#default_value' => '#333',
    );
    $form['popup']['leaflet-popup-tip-disable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide popup'),
    );
    $form['save_client'] = array(
      '#type' => 'submit',
      '#name' => 'save_client',
      '#value' => t('Save client appearance settings'),
    );
        return $form;
    }


    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $client_path = StageDatabase::getAdvancedSettings(array("setting"=>"client_path"));

        if (empty($client_path)) {
            drupal_set_message('Please set client path in the advanced settings', 'warning');
            return;
        }
        if (json_decode($client_path[0]->value, true)["base"]=="") {
            drupal_set_message('Client path cannot be an empty string', 'warning');
            return;
        }
        $input = &$form_state->getUserInput();
        $fid = $input['upload']['fids'];
        $file = file_load($fid);
        $css_path = json_decode($client_path[0]->value, true)["base"]."/css/";
        $uri = isset ($file->uri)?$file->uri:false;
        if ($uri) {
            $db = \Drupal::database();
            $data = $db->select('file_managed', 'fe')
        ->fields('fe')
        ->range(0, 1)
        ->condition('fe.fid', $fid, '=')
        ->execute();

            $value = $data->fetchAssoc();
            $filename = $value['uri'];

            if (!rename("sites/default/files/".basename($filename), $css_path."logo.png")) {
              drupal_set_message('File could not be moved', 'error');
            };
        } else {
            drupal_set_message('Please upload logo', 'warning');
        }
        $col = $input['color'];
        $background = floatval($input['leaflet-popup-tip-background'])/100;
        $shadow = floatval($input['leaflet-popup-tip-shadow'])/100;
        $color = $input['leaflet-popup-tip-color'];
        $disable = $input['leaflet-popup-tip-disable'];
        $d = $d1 = '';
        if ($disable){
          $d = 'visibility: hidden';
          $d1 = '.leaflet-container a.leaflet-popup-close-button{visibility: hidden}';
        }

        $override_css =
        '.style_button,.style_buttonBack:hover{border-color:'.$col.'}.leaflet-bar,.sidebar-tabs>li.active,.sidebar-tabs>ul>li.active{background-color:'.$col.'}.noUi-connect{background:0 0}.style_button{color:'.$col.'!important}.style_button:hover,.style_buttonBack{background-color:'.$col.';color:#fff!important}.style_buttonBack:hover{background-color:'.$col.'60;color:'.$col.'!important}.tab-title,.tab-title #selected-variable a{color:'.$col.'}.statistics_container_controles button:hover{background-color:#eee;border:.1em solid}.ui-state-active,input[type=checkbox].switch_1:checked{background:'.$col.'}.anim-current-date,.animationBTN:hover,.animationLegendBTN:hover{background-color:'.$col.'}.sidebar-left .sidebar-content{border-left:2px solid '.$col.'}.tab-title{border-bottom:2px solid '.$col.'}.foc{background-color:'.$col.'60}.leaflet-popup-content-wrapper,.leaflet-popup-tip{background:rgba(255,255,255,'.$background.');color:'.$color.';box-shadow:0 3px 14px rgba(0,0,0,'.$shadow.')}.sidebar-header {background: url(logo.png);background-repeat: no-repeat;background-color: '.$col.';text-align: center;}.sidebar-close{background-image:  none;}'.$d1.'';

        file_put_contents("sites/default/files/override.css", $override_css);
        if (!rename("sites/default/files/override.css", $css_path."override.css")) {
          drupal_set_message('File could not be moved', 'error');
        };
        $lj = $this->content;
        $lj['tr'] = $input['tr'];
        $jpth = json_decode($client_path[0]->value, true)["base"]."locale.json";

        file_put_contents($jpth, json_encode($lj));
    }
    public function save_appearance(array &$form, FormStateInterface $form_state)
    {
    }
}
