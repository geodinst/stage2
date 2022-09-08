<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\stage2_admin\StageStatDataImporter;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\stage2_admin\Form\StageVariablesEditRawForm;
use Drupal\stage2_admin\StageFormsCommon;

class StageBatchImportForm extends FormBase{
  protected $headers=array();
  protected $dataImporter;
	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'StageBatchImportForm';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state,  $pass = false) {

      //******************* Files fieldset ***********************
      //********* Section is disabled in the edditing mode *******
      $form['files'] = array(
        '#type' => 'fieldset',
        '#attributes' => array('class' => array('element-clear-fix')),
        '#open' => TRUE,
      );

      $form['files']['files_container'] = array(
        '#type' => 'container',
        '#prefix' => '<div id="files_container">',
        '#suffix' => '</div>'
      );

      $form['files']['files_container']['left_container'] = array(
        '#type' => 'container',
        '#prefix' => '<div id="files_left_container">',
        '#suffix' => '</div>',
        '#attributes' => array('class' => array('element-column-left'))
      );

      $form['files']['files_container']['right_container'] = array(
        '#type' => 'container',
        '#prefix' => '<div id="column_settings">',
        '#suffix' => '</div>',
        '#attributes' => array('class' => array('element-column-right'))
      );

      $form['files']['files_container']['calear_left_2'] = array(
          '#type'=> 'container',
          '#attributes' => array('class' => array('element-clear-fix'))
      );
      $form['files']['files_container']['left_container']['test_markup_upload'] = array(
        '#markup'=> t('<b>Upload CSV or ZIP compressed ESRI Shapefile</b><br>Allowed file extensions (.zip, .csv) max file size 2GB. All files should have the same extension. Mixed file type upload is not supported.<br>
                      <b>CSV file format:</b> Accepted CSV file delimiters are semicolon, tab or comma. All the non numerical values and the headers in a CSV file are to be enclosed in the double quotes (").<br><br>'),
      );

    $form['files']['files_container']['left_container']['test_upload'] = array(
      '#type' =>'html_tag',
      '#tag' => 'input',
      '#prefix' => '<span class="btn btn-success fileinput-button"><span>Select files...</span>',
      '#suffix' => '</span">',
      '#attributes' => array(
        'id' => 'fileupload',
        'type' => 'file',
        'name' => 'files[]',
        'multiple' => '',
      )
    );

    $form['files']['files_container']['left_container']['test_markup_progress'] = array(
      '#markup' =>'
      <div id="progress" class="progress">
          <div class="progress-bar progress-bar-success"></div>
      </div>
      <div id="files" class="files"></div>
      <div id="file_upload_errors" class="errors"></div>'
    );

	$form['files']['right_container']['uploaded_files_names']= array(
			'#type' => 'hidden',
      '#attributes' => array(
        'id' => 'uploaded_files_names',
      ),
	);
    $form['files']['files_container']['right_container']['uploaded_files_list']= array(
      '#type' =>'html_tag',
      '#tag' => 'pre',
      '#attributes' => array(
        'id' => 'uploaded_files_list'
      )
    );
    $form['files']['files_container']['right_container']['get_headers'] = array(
        '#type' => 'submit',
        '#name' => 'get_headers',
        '#value' => t('Get headers'),
        //'#limit_validation_errors' => array(),
        '#submit' => array('::stage2uploadFileSubmit'),
        '#validate' => array('::validateUploadedFiles'),
        '#attributes' => array(
          'id' => 'get_headers_btn',
        ),
    		'#ajax' => array(
    			'callback' => '::stage_ii_import_filter_ajax_callback',
    			'wrapper' =>  'data_container',
    			'progress' => array(
    				'type' => 'bar',
    				'message' => 'Processing file, please wait ...',
    			),
    			'event' => 'click'
    		)
      );

    /**
    * General inport settings
    */
    $form['general'] = array(
      '#type' => 'fieldset',
      '#prefix' => '<div id="general_container">',
      '#suffix' => '</div>',
      '#access' => isset($form_state->getValues()['display_table']) && $form_state->getValues()['display_table'] ? $form_state->getValues()['display_table'] : false
    );

    StageFormsCommon::selectSpatialUnit($form);

		$form['general']['geo_column_id_select'] = array(
    	'#type' => 'select',
      '#empty_value' => '--',
      '#required' => TRUE,
      '#description' => t('Select the column which will be used for joining the imported variables with the selected spatial unit.'),
    	'#title' => t('GEO reference column'),
			'#name' => 'geo_column_id_select',
    	'#options' => isset($form_state->getValues()['import_data_headers']) && $form_state->getValues()['import_data_headers'] ? $form_state->getValues()['import_data_headers'] : array()
    );


    /**
    * This section is used to set date and time for all rows and to auto match acronyms with headers
    */
    $form['data_container'] = array(
  		'#type' => 'fieldset',
  		'#prefix' => '<div id="data_container">',
  		'#suffix' => '</div>',
      '#access' => isset($form_state->getValues()['display_table']) && $form_state->getValues()['display_table'] ? $form_state->getValues()['display_table'] : false
  	);

    /**
    * The table input section. The data that is to be imported is filtered in this section
    */
  	$form['data_container']['import_filter'] = array(
  		'#type' => 'container',
  		'#prefix' => '<div id="import_filter">',
  		'#suffix' => '</div>',
  	);

	$form['data_container']['import_filter']['variable_date_input'] = array(
		  '#type' => 'textfield',
				  '#required' => TRUE,
                  '#size' => 10,
                  '#attributes' => array(
										'id' => 'date_for_all',
										'class'=>array('date_var'),
										'title'=>t('The format of a date is YYYY-MM-DD.'),
										'style' => "float:left;margin-right:10px;margin-left:10px;margin-top:-1px"),
				  '#default_value' => date('Y-m-d',strtotime('today midnight')),
		);

    $form['data_container']['import_filter']['variable_date_btn'] = [
        '#type' => 'plainbutton',
        '#value' => t('Set the date for all variables'),
        '#attributes' => [
            'id' => 'variable_date',
        ]
    ];

	 $header = array(
     'checked'=>array('#type' => 'checkbox'),
		 'header_id' => $this->t('Column header'),
     'variable_name' => $this->t('Variable name'),
		 'acronym' => $this->t('Acronym'),
		 'date' => $this->t('Date')
	 );

   $form['data_container']['import_filter']['import_filter_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#empty' => t('NA')
    );

    if (isset($form_state->getValues()['import_data'])){
      array_shift($header);
      StageFormsCommon::populateCheckBoxTable($form['data_container']['import_filter']['import_filter_table'],
                                      array_keys($header),
                                      $form_state->getValues()['import_data']);
    }

	 //***** PARAMETERS SECTION *****
   StageFormsCommon::parametersSection($form,$form_state);

	 //************** SUBMIT SECTION **************
    $form['save'] = array(
      '#type' => 'submit',
      '#name' => 'save_variable',
      '#value' => t('Save'),
      '#access' => isset($form_state->getValues()['display_table']) && $form_state->getValues()['display_table'] ? $form_state->getValues()['display_table'] : false
      );

      $form['cancel'] = array(
				'#type' => 'link',
				'#title' => 'Cancel',
				'#attributes' => array(
					'class' => array('button'),
				),
				'#url' => Url::fromRoute('stage2_admin.variables'),
			);

    $base_url = base_path();
    $form['#attached']['library'][] = 'stage2_admin/batch_import';
    $form['#attached']['library'][] = 'stage2_admin/StageFormsCommon';
	$form['#attached']['drupalSettings']['stage2_admin']['form_name'] = 'batch_import';
    $form['#attached']['drupalSettings']['stage2_admin']['batch_import']['$base_url'] = $base_url;
	$form['#attached']['drupalSettings']['gIjQueryFileUpload']=['maxChunkSize'=>2000000000, // 2GB
                                                                'acceptFileTypes'=>'zip|csv'
                                                                ];
    return $form;
  }

	//*******Ajax return functions *************
	public function stage_ii_import_filter_ajax_callback(array &$form, FormStateInterface $form_state){
		return $form['import_filter'];
	}

  private static function validateDate($date)
  {
      $d = \DateTime::createFromFormat('Y-m-d', $date);
      return $d && $d->format('Y-m-d') === $date;
  }

	// ********** Validation functions **************

	public function validateForm(array &$form, FormStateInterface $form_state) {
    $el=$form_state->getTriggeringElement();
    if ($el['#name']==='save_variable'){
      StageFormsCommon::validateImportFilterTable($form,$form_state);
      //if (count($form_state->getErrors())>0) return;

      //validate date
      $fsv= $form_state->getValues();
      $variables = $fsv['import_filter_table'];

      foreach ($variables as $key=>$value){
        if ($value['checked']==1){
          if (!self::validateDate($value['date'])){
            $form_state->setError($form['data_container']['import_filter']['import_filter_table'][$key]['date'],
                                  t('The date should be in the format YYYY-MM-DD.'));
          }
        }
      }

      //if (count($form_state->getErrors())>0) return;

      //validate non unique rows
      $spatial_unit_column_id = $fsv['geo_column_id_select']+1;
      $spatial_unit_column_name = $variables[$spatial_unit_column_id]['header_id'];
      $all_uploaded_files = json_decode($form_state->getUserInput()['uploaded_files_names'],true);
      $selectedAttrs=$this->getSelectedAttrs($variables);
      $selectedAttrs[$spatial_unit_column_id-1]=$spatial_unit_column_name;

      $this->dataImporter=new StageStatDataImporter($all_uploaded_files,$selectedAttrs);
      $err=$this->dataImporter->getFileImportError();
      if ($err!==false){
        $form_state->setErrorByName('check_files',StageFormsCommon::mu($err[0].': '.$err[1].' <a href="#" data-fname="'.$err[0].'" class="remove-offending-file">'.t('Remove offending file from the list.').'</a>'));
        return;
      }

      $rows=$this->dataImporter->getNonUniqueRows($spatial_unit_column_name,$spatial_unit_column_id-1);
      if (count($rows)>0){
        $form_state->setErrorByName('non_unique_rows',StageFormsCommon::mu(t('The geo reference column values are to be unique across all the uploaded files. The following values occur more than once: '.
                                                                             implode(', ',$rows)).'. <a href="#" data-fname="" class="remove-offending-file">'.t('Reset upload form.').'</a>'));
      }
    }
  }

  protected function getSelectedAttrs($variables){
    $a=array();
    foreach ($variables as $key => $value) {
          if ($value['checked']!=1) continue;
          $a[$key-1]=$value['header_id'];
    }
    return $a;
  }

//************* Submit functions ****************
  public function submitForm(array &$form, FormStateInterface $form_state) {
				$fsv= $form_state->getValues();
        $variables = $fsv['import_filter_table'];
        //$input = $form_state->getUserInput();

        // $propId=StageFormsCommon::getPropId($form,$form_state,$fsv);

				$save_data = array();

				$var_values_add = array();
				$spatial_unit_column_id = $fsv['geo_column_id_select']+1;
				$spatial_unit_column_name = $variables[$spatial_unit_column_id]['header_id'];

        $var_ds_id=$this->dataImporter->saveDatasourceData($spatial_unit_column_name,$spatial_unit_column_id-1,json_encode($this->allUploadedFiles));

        $spatial_layer_id=$fsv['spatial_layer_id'];

        $isCsv=$this->dataImporter->isCsv();
        $tree_menu=StageFormsCommon::treeStructure();
				foreach ($variables as $key => $value) {
          if ($value['checked']!=1) continue;
					$variable = array();
					$variable['published']= 0;
          if ($isCsv) $value['header_id']='c'.($key-1);
          $variable['data']=json_encode([$value['header_id'],$key-1]);
					$variable['var_ds_id'] = $var_ds_id;
          $variable['spatial_layer_id'] = $spatial_layer_id;
					$variable['var_properties_id'] = StageFormsCommon::getPropId($form,$form_state,$fsv, $value['date'],$spatial_layer_id,$value['variable_name']);
					$variable['var_names_id'] = $value['variable_name'];
					$variable['valid_from'] = $value['date'];
					$var_values_add[] =  StageDatabaseSM::stageCreateVariable($variable,$tree_menu);

				}

		// delete uploaded files after import
		foreach($this->allUploadedFiles as $file){
			StageFormsCommon::deleteUploadedFile($file);
		}

        StageFormsCommon::dsReport($var_ds_id,count ($var_values_add),$this->dataImporter->getDataCount());
				// Log that something has been changed in the DB
        StageDatabaseSM::stageLog('batch import',count ($var_values_add).' variable(s) created.', json_encode(['ids'=>$var_values_add,
          'spatial_layer_id'=>$spatial_layer_id
        ]));

				$url = Url::fromUri('internal:/variables');
				$form_state->setRedirectUrl($url);

		/**
		* WARNINGS FOR DUPLICATED INPUTS
		*/
		$sli= $fsv['spatial_layer_id'];
		$layerName = $form['general']['spatial_layer_id']['#options'][$sli];

		//duplicate acronym selection
		$acronyms = array();
		  foreach ($variables as $key=>$value){
			  if($value['acronym'] > -1){
				  if(!in_array($value['acronym'], $acronyms)){
					  array_push($acronyms, $value['acronym']);
				  }else{
					  $message = t('Acronym "@acronym" has been selected on multiple rows!',
						array('@acronym' => $form['data_container']['import_filter']['import_filter_table'][1]['acronym']['#options'][$value['acronym']]));
						drupal_set_message($message, 'warning');
				  }
			  }
		  }

		// // duplicated on date check
		// $result = StageDatabase::getDuplicateValuesOnDate($sli);
    //
		// foreach($result as $value){
		// 	$message = t('There are multiple inputs saved for acronym "@acronym" on same date (@date) and same geospatial layer (@layer)!',
		// 			array('@acronym' => $value->short_name, '@date' => $value->valid_from, '@layer' => $layerName));
		// 	drupal_set_message($message, 'warning');
		// }
    //
		// // duplicated on year check
		// $resultY = StageDatabase::getDuplicateValuesOnYear($sli);
    //
		// foreach($resultY as $value){
		// 	$message = t('There are multiple inputs saved for acronym "@acronym" on same year (@date) and same geospatial layer (@layer)!',
		// 			array('@acronym' => $value->short_name, '@date' => $value->date_part, '@layer' => $layerName));
		// 	drupal_set_message($message, 'warning');
		// }
	}

  public function validateUploadedFiles(array &$form, FormStateInterface $form_state){
    $form_state->clearErrors();

    $this->allUploadedFiles = $all_uploaded_files = json_decode($form_state->getUserInput()['uploaded_files_names'],true);
    // array to store headers
    $headers = array();

    $extension = strtolower(pathinfo($all_uploaded_files[0])['extension']);

    $service = \Drupal::service('gi_services');
    $this->root=$root=\Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/temp_shp_uploads/';
    foreach ($all_uploaded_files as $key => $value) {
      // check if all extension are the same
      $ext = strtolower(pathinfo($value)['extension']);
      if ($ext == $extension){
        try{
          if ($ext==='csv'){
            $public_addres_csv_file = $this->root.$value;
            $headers[]=$service->getCsvHeader($public_addres_csv_file);
          }
          else if ($ext==='zip'){
            $public_addres_zip_file = $this->root.$value;
            $headers[]=array_column($service->getShpHeader($public_addres_zip_file),'name');
          }
        }
        catch(\Exception $e){
          $form_state->setErrorByName('check_files',StageFormsCommon::mu($value.': '.$e->getMessage().' <a href="#" data-fname="'.$value.'" class="remove-offending-file">'.t('Remove offending file from the list.').'</a>'));
          return;
        }
      }
      else{
        drupal_set_message(t('File extensions are not the same. Only files that match the extension of the first uploaded file were read.'),'warning');
      }
    }

    if (count($headers)>1){
      $headers=call_user_func_array('array_intersect',$headers);
      $form_state->setValue('display_table',true);
    }
    else if (count($headers)===1){
      $headers=$headers[0];
    }

    if (count($headers)<2){
      $form_state->setErrorByName('headers',t('The uploaded files do not contain adequate headers (at least two different headers are required).'));
    }
    $this->headers=$headers;
  }

	// Costum submit function used to get headers
	public function stage2uploadFileSubmit(array &$form, FormStateInterface $form_state) {

			$available_variables = StageDatabaseSM::stage2_GetAvailableVariables();
			$name = array();
			$short_name = array();
			$name[-1] = "-Select -";
			$short_name[-1] = "-Select-";
			foreach ($available_variables as $key => $value){
				$short_name[$value->id] = $value->short_name;
				$tmp_name = str_repeat ('>', StageDatabaseSM::stage2_get_variable_parents($value->var_tree_id,true)-2).' '.$value->name;
        $name[$value->id] = strlen($tmp_name) > 18 ? substr($tmp_name,0,18)." ..." : $tmp_name;
			}

      $date = DrupalDateTime::createFromTimestamp(time());
      $datetime = $date->format('Y-m-d');

      $import_data = array();
			foreach ($this->headers as $key => $value) {
				$key = $key+1; // key is increased because of the way Drupal filters selected rows in the tableselect
				$import_data[$key] = array(
					'id'=>$key,
          'header_id' =>
          array(
            'data' => array(
              '#type' => 'textfield',
              '#value' => $value,
              '#size' => 20,
              '#attributes' => array(
								'readonly' => TRUE,
                'class'=>array('header_name_var')
							),
            )),
            'variable_name' =>
            array(
              'data' => array(
                '#type' => 'select',
                '#options' => $name,
                '#attributes' => array('class'=>array('select_var')),
              )),
            'acronym' =>
            array(
              'data' => array(
                '#type' => 'select',
                '#options' => $short_name,
                '#attributes' => array('class'=>array('select_acr')),
              )),
              'date' =>
              array(
                'data' => array(
                  '#type' => 'textfield',
                  '#size' => 20,
                  '#default_value' => $datetime,
                  '#attributes' => array('class'=>array('date_var'),'title'=>t('The format of a date is YYYY-MM-DD.')),
                )),
				     );
			}

      $form_state->setValue('display_table',true);
      $form_state->setValue('import_data',$import_data);
      $form_state->setValue('import_data_headers',$this->headers);

			$form_state->setRebuild();
		}
	}
