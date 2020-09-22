<?php

namespace Drupal\stage2_admin\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Select;

/**
 * Select element with select 2 library attached
 *
 * @FormElement("select2")
 */
class Select2 extends Select {

    public function getInfo() {
        $info = parent::getInfo();
        $info['#attached']['library'][] = 'stage2_admin/select2';
        return $info;
    }

    public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
        $element = parent::processSelect($element, $form_state, $complete_form);
        $element['#attributes']['class'][] = 'stage2_admin-select2';
        $element['#attached']['drupalSettings']['stage2_admin'][$element['#id']] = [
            'select2' => (isset($element['#select2'])) ? $element['#select2'] : ['dropdownAutoWidth' => true]
        ];
        return $element;
    }

}
