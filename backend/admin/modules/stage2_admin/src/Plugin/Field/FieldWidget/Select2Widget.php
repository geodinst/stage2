<?php

namespace Drupal\d8_form_elements\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;

/**
 * Plugin implementation of the 'select2' widget
 *
 * @FieldWidget(
 *   id = "select2_widget",
 *   module = "d8_form_elements",
 *   label = @Translation("Select2"),
 *   multiple_values = true,
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string",
 *     "code_list_reference"
 *   }
 * )
 */
class Select2Widget extends OptionsSelectWidget {

    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        $element = parent::formElement($items, $delta, $element, $form, $form_state);
        $element['#type'] = 'select2';
        return $element;
    }

}
