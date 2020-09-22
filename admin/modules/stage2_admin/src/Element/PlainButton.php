<?php

namespace Drupal\stage2_admin\Element;

use Drupal\Core\Render\Element\Button;

/**
 * Select element with select 2 library attached
 *
 * @FormElement("plainbutton")
 */
class PlainButton extends Button {

    public function getInfo() {
        $info = parent::getInfo();
        return $info;
    }

    public static function preRenderButton($element) {
        $element = parent::preRenderButton($element);
        $element['#attributes']['type'] = 'button';
        return $element;
    }

}
