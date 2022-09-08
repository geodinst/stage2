<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;

class StageCoordinateSystemForm extends FormBase{

	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_coordinate_system_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

	  $crs = StageDatabase::loadCoordinateSystems();
    $crs_data = StageDatabaseSM::stage_get_crs();

	  $header = array(
		'epgs_srid' => t('Name (EPSG SRID)'),
		'description' => t('Description'),
		'type' => t('Type'),
	  );

	  $options = array();

    $options['3912'] = array(
      'epgs_srid' => '3912',
      'description' => 'MGI 1901 / Slovene National Grid',
      'type' => 'protected',
    );
    $options['4326 '] = array(
      'epgs_srid' => '4326',
      'description' => 'SR-ORG:6 / Google Projection',
      'type' => 'protected',
    );

	  foreach ($crs_data as $cr) {
		$options[$cr->epsg_srid] = array(
      'epgs_srid' => Link::fromTextAndUrl($cr->epsg_srid, Url::fromUri('internal:/stage_settings/edit/'.$cr->epsg_srid)),
		  'description' => $cr->proj4text,
		  'type' => $cr->type,
		);
	  }

    $form['add'] = array
    (
      '#type' => 'submit',
      '#value' => t('Add coordinate system'),
    );
    // $form['delete'] = array
    // (
    //   '#type' => 'submit',
    //   '#value' => t('Delete selected'),
    // );
	  $form['table'] = array(
		'#type' => 'table',
		'#header' => $header,
		'#rows' => $options,
		'#js_select' => false,
   	'#id' => 'tableselect_id',
		'#empty' => t('NA'),
	  );

	  $form['pager'] = array(
		  '#type' => 'pager',
		  '#weight' => 10,
		);

    $form['table_note_su'] = array(
      '#type' => 'fieldset',
      '#title' => t('Note'),
    );
    $form['table_note_su']['table_note_su'] = array(
      '#markup' => t('The table shows the list of coordinate systems that can be choosen when uploading Shape files. </br>
                      <b>To delete coordinate system please contact the adminitrator.</b>
                      ')
    );
		$form['#attached']['library'][] = 'stage2_admin/StageCoordinateSystemForm';

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

		$url = \Drupal\Core\Url::fromRoute('stage2_admin.coordinateSystemEdit')
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

		$url = \Drupal\Core\Url::fromRoute('stage2_admin.coordinateSystemDelete')
			  ->setRouteParameters(array('id'=>json_encode($idsToDelete)));

		$form_state->setRedirectUrl($url);
	}

	return;
  }
}
