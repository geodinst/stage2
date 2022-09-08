<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_admin\Form_support\StageGeospatialLayersFormFunctions;


class StageGeospatialLayersForm extends FormBase{

	public function getFormID() {
		return 'stage_geospatial_layers_form';
	}

	public function buildForm(array $form, FormStateInterface $form_state) {
		// Create tableselect header array
		$header = [
			'name' => ['data' => t('Name')],
			'validity' => ['data' => t('Validity')],
			'status' => ['data' => t('Dependent variables')]
		];

		// Get data to populate te tableselect
		$geo_layers = StageDatabaseSM::stage_get_geo_layers_and_dates();
		$duplicated_layers =  StageDatabaseSM::stage_get_duplicated_layers();

		if ($duplicated_layers){
			$header['warning'] = 'Warning';
			$header['modified'] = 'Modified';
			drupal_set_message(t('There are more spatial layers from the same spatial units with the same effective date.'),'warning');
		}

		//Generate options
		$options = [];
		foreach ($geo_layers as $layer) {
			$options[$layer->id] = [
				'name' => Link::fromTextAndUrl($layer->name, Url::fromUri('internal:/geospatial_layers/geospatial_layer/'.$layer->id))->toString(),
				'validity' => StageGeospatialLayersFormFunctions::stage2_geo_layer_from_to($layer),
				'status' => StageGeospatialLayersFormFunctions::stage2_is_geospatiallayer_dependent_variables_count($layer),
				'published' => $layer->modified,
				'modified' => $layer->modified,
				'warning' => array_key_exists($layer->id, $duplicated_layers) ?
				array(
					'data' => array(
						'#markup' => '<div name = "duplicated_layer" class = "duplicated_layer"> Duplicated layer </div>',
						'#attributes' => array(
							'class'=>array('messages')
						),
					))
					:''
			];
		}

		$form['add'] = [
			'#type' => 'submit',
			'#value' => t('Add layer'),
		];

		$form['delete'] = [
			'#type' => 'submit',
			'#value' => t('Delete selected'),
		];

		$form['publish'] = [
			'#type' => 'submit',
			'#access' => in_array("administrator", \Drupal::currentUser()->getRoles()),
			'#value' => t('Publish selected on geoserver'),
		];

		$form['table'] = [
			'#type' => 'tableselect',
			'#header' => $header,
			'#options' => $options,
			'#js_select' => false,
			'#id' => 'tableselect_id',
			'#empty' => t('No layers found'),
		];

		$form['table_note'] = [
			'#type' => 'fieldset',
			'#title' => t('Note'),
		];

		$form['table_note']['table_note'] = [
			'#markup' => t('Dependent variables column represents the count of variables that may be related to the given geo spatial layer. It is the count of the variables which valid from date is greater than the start date of the validity column.
			Therefore the sum of all dependent variables may be greater than the total of all variables that exist.')
		];

		$form['#attached']['library'][] = 'stage2_admin/StageGeospatialLayersForm';
		return $form;
	}


	public function validateForm(array &$form, FormStateInterface $form_state) {}

	public function submitForm(array &$form, FormStateInterface $form_state) {
		$trigger = $form_state->getTriggeringElement()["#parents"][0];

		if($trigger == "add"){
			$url = Url::fromUri('internal:/geospatial_layers/geospatial_layer/-1');
			$form_state->setRedirectUrl($url);
		}
		else if($trigger == "delete"){
			$fsv= $form_state->getValues();
			// get id's of the selected elements
			$selected = array_filter($fsv['table']);
			$selected = array_filter($selected);
			$has_related = false;
			foreach ($selected as $key => $value) {
				StageGeospatialLayersFormFunctions::stage2_is_geospatiallayer_dependent_variables($value) ?
				$has_related = true: false;
			}
			// Check if something is selected
			$url = \Drupal\Core\Url::fromRoute('stage2_admin.geospatialLayersDelete')
			->setRouteParameters(array('id'=>json_encode(array('table'=>$fsv['table'],'dependent' =>$has_related))));
			!empty($selected) ? $form_state->setRedirectUrl($url) : drupal_set_message(t('Nothing selected'),'warning');
		}
		else if ($trigger == "publish"){
			$fsv= $form_state->getValues();
			// get id's of the selected elements
			$selected = array_filter($fsv['table']);
			$selected = array_filter($selected);
			if (empty($selected)) return;

			$tnames=db_query("SELECT table_name from s2.spatial_layer_date where id IN (:ids[])",[':ids[]'=>$selected])->fetchCol();

			$service = \Drupal::service('gi_services');
			$conn=db_query("SELECT value from s2.advanced_settings where setting='gsrv'")->fetchField();
			$service->initGeoserverCurlHandler($conn);
			foreach($tnames as $tname){
				StageGeospatialLayersFormFunctions::publishToGeoserver($service,$tname);
			}
		}
	}
}
