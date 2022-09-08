<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\stage2_admin\StageDatabase;
use Drupal\stage2_admin\StageDatabaseSM;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Database\Database;
use Drupal\stage2_admin\StageFormsCommon;

class StageGeospatialLayerEditForm extends FormBase{

  protected $allUploadedFiles=array();
  protected $root;
  protected $service;
  protected $tname;
  protected $fileControlPassed=false;
  protected $headers;
  private $importShpCurrentFile;

  function __construct(){
    $this->service = \Drupal::service('gi_services');
    $conn=db_query("SELECT value from s2.advanced_settings where setting='gsrv'")->fetchField();
    $this->service->initGeoserverCurlHandler($conn);
  }
	/**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'stage_geospatial_layer_edit_SM_form';
  }
  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state,  $pass = false) {

		// //******************* Load initial data *******************

		// Determine if form is in editing mode
		$form_state->setValue('edit_mode', false);
		// load passed arguments to the form state if in edit mode
		if ($pass){
			$form_state->setValue('stage2_admin_geo_layers_pass', $pass);
			$form_state->setValue('edit_mode', true);

		}

		//******************* Base settings fieldset *******************
    // set containers
		$form['left_container'] = array(
			'#type' => 'container',
			'#attributes' => array('class' => array('element-column-left')),
		);
		$form['right_container'] = array(
			'#type' => 'container',
			'#prefix' => '<div id="image_container">',
			'#suffix' => '</div>',
			'#attributes' => array('class' => array('element-column-right'))
		);
		$form['calear_left_container'] = array(
				'#type'=> 'container',
				'#attributes' => array('class' => array('element-clear-fix'))
		);

		$available_su = StageDatabaseSM::stage2GetAvailableSpatialUnits(); // load available spatial units
  	$form['left_container']['spatial_layer_id'] = array(
  		'#type' => 'select',
			'#required' => TRUE,
      '#empty_value' => '--',
		  '#title' => $this->t('Spatial unit'),
      '#description' => t('Code list can be modified under Settings > Spatial units.'),
		  '#options' => $available_su,
      '#disabled' => $form_state->getValue('edit_mode'),
			'#default_value' => $form_state->getValue('edit_mode') ? $form_state->getValue('stage2_admin_geo_layers_pass')['spatial_layer_id'] : null,
      '#attributes' => array('class' => array('stage_select_box'))
  		);

		$available_cs = StageDatabaseSM::stage2GetAvailableGeoLayers(array('epsg_srid','epsg_srid')); // load available coordinate systems
    $default_cs=3912; // hardcoded Slovenian coordinate system that is alwas available
    $google_cs=4326; // hardcoded Google wgs coordinate system that is alwas available
    $available_cs[$default_cs]=$default_cs;
    $available_cs[$google_cs]=$google_cs;

  	$form['left_container']['crs_id'] = array(
  			'#type' => 'select',
				'#required' => TRUE,
  		  '#title' => $this->t('Coordinate system'),
        '#description' => t('Projection setting of the Shape file. </br>E.g. 3912 - D-48 (MGI / Slovene national grid).'),
        '#attributes' => array('class' => array('stage_select_box')),
			  '#options' => $available_cs,
        '#disabled' => $form_state->getValue('edit_mode'),
				'#default_value' => $form_state->getValue('edit_mode') ? $form_state->getValue('stage2_admin_geo_layers_pass')['crs_id'] : $default_cs
  		 );

		 /*
  	$form['left_container']['valid_from'] = array(
  	  '#type' => 'datetime',
			'#required' => TRUE,
        '#title' => t('Start Date'),
        '#description' => t('Effective Date of the data.'),
				'#default_value' => $form_state->getValue('edit_mode') ?
						DrupalDateTime::createFromFormat('Y-m-d H:i:s', $form_state->getValue('stage2_admin_geo_layers_pass')['valid_from'])
						 : DrupalDateTime::createFromTimestamp(strtotime('today midnight')),
  	);
	*/

	$form['left_container']['valid_from'] = array(
                  '#type' => 'textfield',
				  '#required' => TRUE,
                  '#size' => 10,
				  '#title' => t('Start Date'),
					'#description' => t('Effective Date of the data.'),
                  '#attributes' => array('class'=>array('date_var'),'title'=>t('The format of a date is YYYY-MM-DD.')),
				  '#default_value' => $form_state->getValue('edit_mode') ?
						date('Y-m-d',strtotime($form_state->getValue('stage2_admin_geo_layers_pass')['valid_from']))
						 : date('Y-m-d',strtotime('today midnight')),
                );

  	$form['left_container']['borders'] = array(
  	  '#type' => 'checkbox',
  	  '#title' => $this->t('Show geometry borders'),
			'#default_value' => $form_state->getValue('edit_mode') ? $form_state->getValue('stage2_admin_geo_layers_pass')['borders'] : 0
  	);

    $userRoles = \Drupal::currentUser()->getRoles();

    if ($form_state->getValue('edit_mode')){
      $tname=StageDatabase::getSpatialTableName($form_state->getValue('stage2_admin_geo_layers_pass')['id']);
      $this->tname=$tname;
    }
    else{
      $tname=$this->tname=uniqid('t'.StageFormsCommon::getInstanceName());
    }

    $form['left_container']['geoserver'] = array(
  	  '#type' => 'checkbox',
      '#access' => in_array("administrator", $userRoles),
  	  '#title' =>  $tname.' '.$this->t('is published on geoserver'),
			'#default_value' => $form_state->getValue('edit_mode') ? $this->checkGeoserver($tname) : 0
  	);

  	$form['right_container']['description'] = array(
  	  '#type' => 'textarea',
  	  '#title' => $this->t('Description'),
      '#description' => t('Description of spatial data layer is intended for internal guidance. Description is not visible in the client.'),
			'#default_value' => $form_state->getValue('edit_mode') ? $form_state->getValue('stage2_admin_geo_layers_pass')['description'] : false
  	);

  	$form['right_container']['edit_id'] = array(
  	  '#type' => 'textfield',
      '#access' => $form_state->getValue('edit_mode') ? TRUE : FALSE,
      '#size' => 6,
      '#attributes' => array('disabled' => 'disabled',
                              'id' => 'edit_id_id',),
			'#default_value' => $form_state->getValue('edit_mode') ? $form_state->getValue('stage2_admin_geo_layers_pass')['id'] : false
  	);
		$header = array(
			'short_name' =>array('data' =>t('Acronym')),
			'valid_from' => array('data' =>t('Variable valid from')),
			'publish_on' => array('data' =>t('Publish on')),
		);
    $options = array();
    if ($form_state->getValue('edit_mode') ){
      $valid_from = DrupalDateTime::createFromFormat('Y-m-d H:i:s', $form_state->getValue('stage2_admin_geo_layers_pass')['valid_from']);
      $spatial_layer_id = $form_state->getValue('stage2_admin_geo_layers_pass')['spatial_layer_id'];
      $options = StageDatabaseSM::stage_get_variables_by_layer($spatial_layer_id,$valid_from,$header);
    }

    $rows = array();
    foreach ($options as $key => $value) {
      $rows[$value->id_name] = array(
        'short_name' => $value->short_name,
        'valid_from' => explode(' ',$value->valid_from)[0],
        'publish_on' => explode(' ',$value->publish_on)[0],
      );
    }

		$form['right_container']['linked_layers'] = array(
			'#access' => $form_state->getValue('edit_mode') ? TRUE : FALSE,
			'#type' => 'table',
			'#header' =>$header,
			'#rows' => $rows,
			'#empty' => $this->t('No linked variables available.'),
		);
		$form['right_container']['pager'] = array('#type' => 'pager','#element'=>1);

    $col_names=$form_state->getValue('stage2_admin_geo_layers_pass')['col_names'];
		$form['right_container']['geo_code_display'] = array(
			'#access' => $form_state->getValue('edit_mode') ? TRUE : FALSE,
			'#type' => 'textfield',
			'#size' => 10,
			'#value' => isset($col_names)?json_decode($col_names)->geo_code:'',
			'#title' => t('GEO reference column'),
			'#description' => t('The column selected during the import.'),
			'#disabled' => true
		);

		$form['right_container']['names_column_display'] = array(
			'#access' => $form_state->getValue('edit_mode') ? TRUE : FALSE,
			'#size' => 10,
			'#type' => 'textfield',
			'#value' =>isset($col_names)?json_decode($col_names)->names_column:'',
			'#title' => t('Names column'),
			'#description' => t('The column selected during the import.'),
			'#disabled' => true

		);
		//******************* Files fieldset ***********************
		//********* Section is disabled in the edditing mode *******
		$form['files'] = array(
      '#type' => 'fieldset',
			'#attributes' => array('class' => array('element-clear-fix')),
			'#access' => $form_state->getValue('edit_mode') ? FALSE : TRUE,
  		'#open' => TRUE,
  	);

    $form['files']['left_container'] = array(
      '#type' => 'container',
			'#prefix' => '<div id="files_left_container">',
			'#suffix' => '</div>',
			'#attributes' => array('class' => array('element-column-left'))
		);

		$form['files']['right_container'] = array(
			'#type' => 'container',
			'#prefix' => '<div id="column_settings">',
			'#suffix' => '</div>',
			'#attributes' => array('class' => array('element-column-right'))
		);

    $form['files']['clear_left_2'] = array(
				'#type'=> 'container',
				'#attributes' => array('class' => array('element-clear-fix'))
		);

    $form['files']['left_container']['test_markup_upload'] = array(
      '#markup'=> t('<b>Upload compressed shapefile</b></br>
                    <ul>
                      <li>accepted file extension (<b>.zip</b>)</li>
                      <li>accepted file format SHAPE-ZIP (ZIP archive containing at least mandatory shapefile components)</li>
                      <li>only <b>one shapefile per ZIP archive</b> is supported</li>
                      <li>max file size <b>2GB</b></li>
                      <li>the encoding of DBF files must be <b>UTF-8</b></li>
                      <li>the only geometric data type allowed for a SHP file is <b>POLYGON</b></li>
                      </ul>')
    );

    $form['files']['left_container']['test_upload'] = array(
      '#type' =>'html_tag',
      '#tag' => 'input',
      '#prefix' => '<span class="btn btn-success fileinput-button"><span>'.t('Select files...').'</span>',
      '#suffix' => '</span">',
      '#attributes' => array(
        'id' => 'fileupload',
        'type' => 'file',
        'name' => 'files[]',
        'multiple' => '',
      )
    );

    $form['files']['left_container']['test_markup_progress'] = array(
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

   $form['files']['right_container']['uploaded_files_list']= array(
      '#type' => 'table',
      '#prefix' => '<div id="uploaded_files_list_table">',
			'#suffix' => '</div>',
      '#attributes' => array(
        'id' => 'uploaded_files_list'
      )
    );

		// button is rendered if some files are already on the server
  	$form['files']['right_container']['get_headers'] = array(
  			'#type' => 'submit',
  			'#value' => t('Get headers'),
				'#limit_validation_errors' => array(),
				'#submit' => array('::stage2GetShpHeaders'),
        '#attributes' => array(
          'id' => 'get_headers_btn',
        ),
				'#ajax' => array(
					'callback' => '::stage_ii_set_existing_files_ajax_callback',
					'wrapper' =>  'files_container',
					'progress' => array(
						'type' => 'bar',
						'message' => 'Uploading file, please wait ...',
					),
					'event' => 'click'
				)
			);
		// geo_code predstavlja ime stolpca v katerem se nahajajo referenÄni id geometrije
		// uporabniku ponudimo imena vseh stolpcev, ki se nahajajo v shp datoteki
		$form['files']['right_container']['select_columns_container'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="select_columns_container">',
      '#suffix' => '</div>',
      '#access' => $this->fileControlPassed && count($this->allUploadedFiles)>0
    );

    $form['files']['right_container']['select_columns_container']['left_container'] = array(
      '#type' => 'container',
			'#prefix' => '<div id="files_left_container">',
			'#suffix' => '</div>',
			'#attributes' => array('class' => array('element-column-left'))
		);

		$form['files']['right_container']['select_columns_container']['right_container'] = array(
			'#type' => 'container',
			'#prefix' => '<div id="column_settings">',
			'#suffix' => '</div>',
			'#attributes' => array('class' => array('element-column-left'))
		);

    $form['files']['right_container']['select_columns_container']['clear_left_2'] = array(
				'#type'=> 'container',
				'#attributes' => array('class' => array('element-clear-fix'))
		);

    $values=$form_state->getValues();

		$form['files']['right_container']['select_columns_container']['left_container']['geo_code'] = array(
				'#type' => 'select',
				'#empty_value' => '--',
        '#default_value'=>isset($values['geo_code'])?$values['geo_code']:null,
				'#required' => TRUE,
				"#options" => isset($this->headers) ? $this->headers  : array(),
			  '#title' => t('GEO reference column'),
        '#description' => t('- it has to be unique,<br>- leading and trailing spaces will be trimmed'),
        '#attributes' => array('class' => array('stage_select_box')),
        '#validated' => TRUE
			 );

		$form['files']['right_container']['select_columns_container']['right_container']['names_column'] = array(
			'#type' => 'select',
			'#empty_value' => '--',
      '#default_value'=>isset($values['names_column'])?$values['names_column']:null,
			'#required' => TRUE,
			"#options" => isset($this->headers) ? $this->headers  : array(),
			'#title' => t('Names column'),
      '#attributes' => array('class' => array('stage_select_box')),
      '#validated' => TRUE
			 );


		$form['save'] = array(
			'#type' => 'submit',
			'#value' => t('Save'),
      '#attributes' => array(
        'id' => 'save_shp_btn',
      ),
      '#access' => ($this->fileControlPassed && count($this->allUploadedFiles)>0) || $pass
		  );

			$form['cancel'] = array(
				'#type' => 'link',
				'#title' => 'Cancel',
				'#attributes' => array(
					'class' => array('button'),
				),
				'#url' => Url::fromRoute('stage2_admin.geospatialLayers'),
			);
      $base_url = base_path();
		// Attach js
		$form['#attached']['library'][] = 'stage2_admin/StageGeospatialLayerEditForm';
		$form['#attached']['drupalSettings']['stage2_admin']['form_name'] = 'StageGeospatialLayerEditForm';
    $form['#attached']['drupalSettings']['stage2_admin']['StageGeospatialLayerEditForm']['$base_url'] = $base_url;
    $form['#attached']['drupalSettings']['gIjQueryFileUpload']=['maxChunkSize'=>2000000000, // 2GB
                                                                'acceptFileTypes'=>'zip'
                                                                ];
    if ($pass){
      $form['#attached']['library'][]='stage2_admin/leaflet';
      $form['table_data'] = array(
		  '#type' => 'details',
		  '#title' => t('Geospatial layer table data'),
		  '#open' => false,
		);

      $table_header = array(
        array('data' => $this->t('GEO reference ID')),
        array('data' => $this->t('Spatial feature name'))
      );

      $query=db_select("ge.{$tname}",'ge')
          ->extend('Drupal\Core\Database\Query\PagerSelectExtender')->element(0)->limit(15);
      $query->fields('ge',array_map('strtolower',[json_decode($col_names)->geo_code,json_decode($col_names)->names_column]));
      $result=$query->execute();

      $form['table_data']['table'] = array(
        '#theme' => 'table',
        '#header' =>$table_header,
        '#rows' => $result->fetchAll(\PDO::FETCH_ASSOC)
      );
      $form['table_data']['pager'] = array('#type' => 'pager','#element'=>0);

      $form['wms']=array(
        '#type' => 'container',
        '#prefix' => '<hr><div>',
        '#suffix' => '</div>',
        '#attributes' => array('id' => array('wms-map'))
      );
      
      //geoserver port
      $conn=db_query("SELECT value from s2.advanced_settings where setting='gsrv'")->fetchField();
      $port=json_decode($conn)->port;
      
      $form['#attached']['drupalSettings']['wms']=['tname'=>$tname,
                                                   'port' => $port,
                                                   'extent'=>db_query("select st_asgeojson(ST_FlipCoordinates(ST_Extent(geom))) from ge.\"$tname\"")->fetchField()
                                                   ];
    }

	$form['#attached']['library'][] = 'stage2_admin/datepicker';

	  return $form;
  }

  private static function removedFileStartMessage($fname){
    return t('File').': <b>'.$fname.'</b> '.t('was removed from the uploaded files list').': <br>- ';
  }

	//************* VALIDATE FUNCTIONS ***********

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  private function importShp($attr,$srid){
    $tname=$this->tname;
    $attr=array_unique($attr);

    $service = \Drupal::service('gi_services');
    $dropTableIfExists=true;
    foreach($this->allUploadedFiles as $fname){
      $this->importShpCurrentFile=$fname;
      $service->importShp($this->root.$fname,$tname,$attr,'ge',$dropTableIfExists,false,true,$srid);
      $dropTableIfExists=false;
    }
    return $tname;
  }

  private function checkGeoserver($tname){
    return $this->service->checkGeoserverLayer($tname);
  }

  private function publishToGeoserver($tname){
    if ($this->checkGeoserver($tname)===true) return;
    $schema = Database::getConnection()->schema();

		if ($schema->tableExists('ge.').$tname){

			if (!$schema->fieldExists('ge.'.$tname, 'idgid')){
        $schema->addField('ge.'.$tname, 'idgid', array('type' => 'int'));
      }

			db_update('ge.'.$tname)
        ->expression('idgid','__gid_')
        ->execute();

      $instanceName=StageFormsCommon::getInstanceName();
      $this->service->publishGeoserverLayer($tname,'stage',$instanceName);
      $this->service->putGeoserverLayerProperties($tname);
    }
  }

  private function unpublishGeoserverLayer($tname){
    $this->service->unpublishGeoserverLayer($tname);
  }

	//************* SUBMIT FUNCTIONS ***********

	// final submit
	public function submitForm(array &$form, FormStateInterface $form_state) {

		$values = $form_state->getValues();
		$neki = $form_state->getTriggeringElement();
		$trigger_btn_id = $neki["#parents"][0];


    $id = $values['edit_id'];

    if (!empty($id)) {

      $ids = StageDatabaseSM::get_allowed_geospatial_layer_ids("additional_data->>'id'", 'geospatial_layers', false);

      if (count($ids) > 0) {
        if (array_search($id,$ids)===FALSE) {
          drupal_set_message('You can not edit layers uploaded by other users. For the unrestricted access permission contact the system administrator.', 'warning');
          $form_state->setRebuild();
          return;
        }
      }
    }

		$entry = array();

    $entry['borders'] = $values['borders'];
		$entry['modified'] = DrupalDateTime::createFromTimestamp(time())->format('Y-m-d H:i:s');
    $entry['valid_from'] = date("Y-m-d H:i:s",strtotime($values['valid_from']));
    $entry['description'] = $values['description'];
    try{
      if($id && $id <>'-1'){
        $entry['col_names'] = json_encode (array('geo_code'=>$values['geo_code_display'],'names_column'=>$values['names_column_display']));
      }
      else{
        $entry['col_names'] = json_encode (array('geo_code'=>$values['geo_code'],'names_column'=>$values['names_column']));
        $entry['table_name'] = $this->importShp(array($values['geo_code'],$values['names_column']),$values['crs_id']);
        $entry['spatial_layer_id'] = $values['spatial_layer_id'];
        $entry['crs_id'] = $values['crs_id'];

        $cname=$values['geo_code'];
        $repeatedCount = db_query("select count(*) from ge.\"{$entry['table_name']}\" group by $cname having count($cname)>1")->fetchField();
        if ($repeatedCount > 0) {
          drupal_set_message(t('The geo reference ID column ('.$cname.') values are not unique.'),'error');
          $form_state->setRebuild();
          return;
        }
      }
    }
    catch(\Exception $e){
      drupal_set_message(StageFormsCommon::mu(t('The import was aborted!').' '.self::removedFileStartMessage($this->importShpCurrentFile).$e->getMessage()),'error');
      db_query("drop table if exists ge.\"{$this->tname}\"");
      $this->allUploadedFiles = StageFormsCommon::removeFileNamesFromList($form_state,$this->allUploadedFiles,[$this->importShpCurrentFile]);
      $this->stage2GetShpHeaders($form, $form_state);
	  StageFormsCommon::deleteUploadedFile($this->importShpCurrentFile);
      return;
    }

		$return = StageDatabaseSM::stage2SaveGeoLayer($entry,$id);

    if(!($id && $id <>'-1')){
      try{
        db_query("SELECT UpdateGeometrySRID('ge','".$entry['table_name']."','geom',4326);");
        $srid=$entry['crs_id'];
        db_query("UPDATE ge.\"".$entry['table_name']."\" set geom=st_transform(st_setsrid(geom,$srid),4326)");
      }
      catch(\Exception $e){
        drupal_set_message('The layer has been successfully imported, however, there was an error while transforming the geometry coordinates.','warning');
      }
    }

    $userRoles = \Drupal::currentUser()->getRoles();
    if (in_array("administrator", $userRoles)){ //admin lahko upravlja s slojem na geoserverju
      if ($values['geoserver']==1)
        $this->publishToGeoserver($this->tname);
      else
        $this->unpublishGeoserverLayer($this->tname);
    }
    else {
      $this->publishToGeoserver($this->tname);
    }

    $url = Url::fromUri('internal:/geospatial_layers');

    $logid = null;
    if(!($id && $id <>'-1')){
      $logid = $return;
      $c=db_query("select count(*) from ge.\"{$entry['table_name']}\"")->fetchField();
      drupal_set_message(StageFormsCommon::mu($c.' '.t('feature(s) were successfully imported.').' <a href="'.$url->toString().'/geospatial_layer/'.$return.'">'.t('Click here to see or edit the imported layer details.').'</a>'));
    }

	StageFormsCommon::deleteUploadedFile($this->importShpCurrentFile);

    // Log changes
		StageDatabaseSM::stageLog('geospatial_layers',$return,is_null($logid)?null:"{\"id\":\"$logid\"}");
		$form_state->setRedirectUrl($url);

	}

	// Costum submit read headers of all shp files
	public function stage2GetShpHeaders(array &$form, FormStateInterface $form_state) {
    $user_input=$form_state->getUserInput();
		$this->allUploadedFiles = $all_uploaded_files = json_decode($user_input['uploaded_files_names'],true);
		// array to store headers
		$headers = array();
		// get headers of all files available to be imported HEADERS IN ALL OF THE FILES HAVE TO BE THE SAME !!!!!!!!!!!!!! laÅ¾je je, Äe reÄemo, da vzamemo array_intersect
    $service = \Drupal::service('gi_services');
    $this->root=$root=\Drupal::service('file_system')->realpath(file_default_scheme() . "://").'/temp_shp_uploads/';
    $errors=[];
    foreach ($all_uploaded_files as $key => $value) {
      $public_addres_zip_file = $this->root.$value;
      try{
        $fnames=$service->getShapeFileNames($public_addres_zip_file);
        $shpType=$service->getShpType($public_addres_zip_file,$fnames);
        if (strpos($shpType,'Polyg')===false){
          throw new \Exception(t('the shape data type').' ('.$shpType.') '.t('is not allowed'));
        }
        $headers[]=array_column($service->getShpHeader($public_addres_zip_file,$fnames),'name');
      }
      catch(\Exception $e){
        $errors[$value]=self::removedFileStartMessage($value).$e->getMessage();
      }
    }

    if (count($errors)>0){
      drupal_set_message(StageFormsCommon::mu(implode('<br><br>',$errors)),'error');
      $this->allUploadedFiles = $all_uploaded_files = StageFormsCommon::removeFileNamesFromList($form_state,$all_uploaded_files,array_keys($errors));
    }

    if (count($headers)>1){
      $headers=call_user_func_array('array_intersect',$headers);
    }
    else if (count($headers)===1){
      $headers=$headers[0];
    }

    $this->headers=$headers=array_combine($headers,$headers); //ime stolpca je tudi key

    $form_state->setValue('has_files','true');

		$form_state->setValue('btn_access_get_headers',false);
		$form_state->setValue('container_select_headers',true);
		$form_state->setRebuild();
    $this->fileControlPassed=true;
	}

	// Ajax callback to populate available column names in layer_settings fieldset
	public function stage_ii_set_existing_files_ajax_callback(array &$form, FormStateInterface $form_state){
		return $form['files']['files_container'];
	}
}
