<?php
namespace Drupal\stage2_admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\stage2_admin\Classes\MenuTree;
use Drupal\stage2_admin\StageFormsCommon;


class StageMenuTreeForm extends FormBase{

    public function getFormID() {
        return 'stage_menu_tree_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        //populate default value of jstree form database
        $treeData = MenuTree::GetParsedTreeDataJsTree();

        // set containers
        $form['left_container'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => [
                    'element-column-left',
                ],
            ],
        ];

        $form['right_container'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => [
                    'element-column-right',
                ],
            ],
        ];

        $form['calear_left_container'] = [
            '#type'=> 'container',
            '#attributes' => [
                'class' => [
                    'element-clear-fix',
                ],
            ],
        ];

        // if empty data add link to add first field
        if(empty($treeData)){

            $form['empty'] = [
                '#markup' => "<p>Menu empty. Add first menu item <a href='#' id='link4empty'> here</a></p><br>",
                '#attached' => [
                'library' => 'stage2_admin/jsMenuTreeForm']
            ];

        }else{

			$form['left_container']['actions'] = [
				'#prefix' => '<div class="action_links-stage">',
				'#suffix' => '</div>',
			];

            $user = \Drupal::currentUser();
            if ($user->hasPermission('stage2_admin content_administrator')) {
                $form['left_container']['actions'] ['clear_cache'] = [
                    '#type' => 'submit',
                    '#value' => t('Clear cache'),
                    '#submit' => [
                        '::clearCache'
                    ],
                    '#attributes' => [
                        'class' => ['button-blue',
                    ],
                    ],
                ];
            }
			
            $form['left_container']['jstree'] = [
                '#type' => 'jstree_element',
                '#title' => $this->t('Struktura menija'),
                '#js_data' => $treeData,
            ];
        }

        $form['selected_id'] = [
            '#type' => 'hidden',
            '#attributes' => [
                'id' => 'jstree_value',
            ],
        ];


        $form['right_container']['note'] = [
            '#type' => 'fieldset',
            '#title' => t('Note'),
        ];

        $form['right_container']['note']['description'] = [
            '#markup' => t('Menu tree editor is used to manage general time and space independent settings of the variables. </br></br>
                            Available commands:
                        <ul>
                            <li>Create a new node [right mouse click -> Create]</li>
                            <li>Rename a node [right mouse click -> Rename]</li>
                            <li>Delete a node [right mouse click -> Delete]</li>
                            <li>Set settings of a single node [right mouse click -> Set variable settings]</li>
                            <li>Move a node [lef mouse click -> drag and drop]</li>
                        </ul></br>
                        *Set variable settings option is used to manage single node settings. e.g. Color palette, Number of decimals, Classification method'),
            '#attributes' => [
                'id' => 'description',
            ],
        ];

        return $form;
    }


    public function validateForm(array &$form, FormStateInterface $form_state) {
    }
    public function submitForm(array &$form, FormStateInterface $form_state) {
    }

    public function clearCache(array &$form, FormStateInterface $form_state) {
        $m = new \Memcached();
        $m->addServer('stage2-memcached', 11211);
        $languages=\Drupal::languageManager()->getLanguages();
        $instanceName=StageFormsCommon::getInstanceName();
        foreach($languages as $code=>$lang){
            $key='s2tree'.$instanceName.$code;
            drupal_set_message("Cache cleared for language '$code'");
            $m->delete($key);  #key name: s2-tree
        }
    }
}
