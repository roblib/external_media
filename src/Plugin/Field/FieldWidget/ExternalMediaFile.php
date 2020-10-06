<?php

namespace Drupal\external_media\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\file\Entity\File;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\external_media\Element\ExternalMediaFile as ExternalMediaFileElement;

/**
 * Plugin implementation of the 'external_media_file_widget' widget.
 *
 * @FieldWidget(
 *   id = "external_media_file_widget",
 *   label = @Translation("External Media"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class ExternalMediaFile extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'visible_widgets' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $options = self::getPluginList();
    if (!empty($options)) {
      $form['visible_widgets'] = [
        '#title' => $this->t('Set visibile widgets'),
        '#type' => 'checkboxes',
        '#options' => $options,
        '#default_value' => $this->getSetting('visible_widgets'),
        '#description' => $this->t('If none selected all options will be available. This option overrides permissions set for user roles.'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Progress indicator: @progress_indicator', ['@progress_indicator' => $this->getSetting('progress_indicator')]);
    $visible_widgets = $this->getSetting('visible_widgets');
    if (empty(array_filter($visible_widgets))) {
      $summary[] = t('External Media: Default');
    }
    else {
      $widgets_list = [];
      $options = self::getPluginList();
      foreach (array_filter($visible_widgets) as $plugin_id) {
        $widgets_list[] = $options[$plugin_id];
      }
      $summary[] = t('External Media: %name', ['%name' => join(', ', $widgets_list)]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();

    // The field settings include defaults for the field type. However, this
    // widget is a base class for other widgets (e.g., ImageWidget) that may act
    // on field types without these expected settings.
    $field_settings += [
      'display_default' => NULL,
      'display_field' => NULL,
      'description_field' => NULL,
    ];

    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $defaults = [
      'fids' => [],
      'display' => (bool) $field_settings['display_default'],
      'description' => '',
    ];

    // Essentially we use the external_media type, extended with some
    // enhancements.
    $element_info = $this->elementInfo->getInfo('external_media');
    $element += [
      '#type' => 'external_media',
      '#upload_location' => $items[$delta]->getUploadLocation(),
      '#upload_validators' => $items[$delta]->getUploadValidators(),
      '#value_callback' => [get_class($this), 'value'],
      '#process' => array_merge($element_info['#process'], [[get_class($this), 'process']]),
      '#progress_indicator' => $this->getSetting('progress_indicator'),
      // Allows this field to return an array instead of a single value.
      '#extended' => TRUE,
      // Add properties needed by value() and process() methods.
      '#field_name' => $this->fieldDefinition->getName(),
      '#entity_type' => $items->getEntity()->getEntityTypeId(),
      '#display_field' => (bool) $field_settings['display_field'],
      '#display_default' => $field_settings['display_default'],
      '#description_field' => $field_settings['description_field'],
      '#cardinality' => $cardinality,
      '#visible_widgets' => ($visible_widgets = $this->getSetting('visible_widgets')) ? array_keys(array_filter($visible_widgets)) : FALSE,
    ];

    $element['#weight'] = $delta;

    // Field stores FID value in a single mode, so we need to transform it for
    // form element to recognize it correctly.
    if (!isset($items[$delta]->fids) && isset($items[$delta]->target_id)) {
      $items[$delta]->fids = [$items[$delta]->target_id];
    }
    $element['#default_value'] = $items[$delta]->getValue() + $defaults;

    $default_fids = $element['#extended'] ? $element['#default_value']['fids'] : $element['#default_value'];
    if (empty($default_fids)) {
      $file_upload_help = [
        '#theme' => 'file_upload_help',
        '#description' => $element['#description'],
        '#upload_validators' => $element['#upload_validators'],
        '#cardinality' => $cardinality,
      ];
      $element['#description'] = \Drupal::service('renderer')->renderPlain($file_upload_help);
      $element['#multiple'] = $cardinality != 1 ? TRUE : FALSE;
      if ($cardinality != 1 && $cardinality != -1) {
        $element['#element_validate'] = [[get_class($this), 'validateMultipleCount']];
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Since file upload widget now supports uploads of more than one file at a
    // time it always returns an array of fids. We have to translate this to a
    // single fid, as field expects single value.
    $new_values = [];
    foreach ($values as &$value) {
      if (!empty($value['fids'])) {
        foreach ($value['fids'] as $fid) {
          $new_value = $value;
          $new_value['target_id'] = $fid;
          unset($new_value['fids']);
          $new_values[] = $new_value;
        }
      }
    }

    return $new_values;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    parent::extractFormValues($items, $form, $form_state);

    // Update reference to 'items' stored during upload to take into account
    // changes to values like 'alt' etc.
    // @see \Drupal\file\Plugin\Field\FieldWidget\FileWidget::submit()
    $field_name = $this->fieldDefinition->getName();
    $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    $field_state['items'] = $items->getValue();
    static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
  }

  /**
   * Form API callback. Retrieves the value for the file_generic field element.
   *
   * This method is assigned as a #value_callback in formElement() method.
   */
  public static function value($element, $input, FormStateInterface $form_state) {
    if ($input) {
      // Checkboxes lose their value when empty.
      // If the display field is present make sure its unchecked value is saved.
      if (empty($input['display'])) {
        $input['display'] = $element['#display_field'] ? 0 : 1;
      }
    }

    // We depend on the managed file element to handle uploads.
    $return = ExternalMediaFileElement::valueCallback($element, $input, $form_state);

    // Ensure that all the required properties are returned even if empty.
    $return += [
      'fids' => [],
      'display' => 1,
      'description' => '',
    ];

    return $return;
  }

  /**
   * Get a list of all plugins.
   */
  public static function getPluginList() {
    $options = [];
    foreach (\Drupal::service('plugin.manager.external_media')->getDefinitions() as $plugin) {
      $external_media = \Drupal::service('plugin.manager.external_media')->createInstance($plugin['id']);
      if (\Drupal::currentUser()->hasPermission('upload from ' . $external_media->getPluginId()) && $external_media->getSetting('enabled')) {
        $options[$external_media->getPluginId()] = $external_media->getName();
      }
    }
    return $options;
  }

}
