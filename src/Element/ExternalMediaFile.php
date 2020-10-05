<?php

namespace Drupal\external_media\Element;

use Drupal\file\Element\ManagedFile;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;

/**
 * Provides an AJAX/progress aware widget for uploading and saving a file.
 *
 * @FormElement("external_media")
 */
class ExternalMediaFile extends ManagedFile {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#attached']['library'] = ['file/drupal.file', 'external_media/external_media.core'];
    $info['#theme'] = 'external_media_element';
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $force_default = FALSE;
    $parents_prefix = implode('_', $element['#parents']);
    $user_input = $form_state->getUserInput();
    $external_urls = !empty($user_input['external_urls'][$parents_prefix]) ? $user_input['external_urls'][$parents_prefix] : NULL;

    if (!empty($external_urls)) {
      // Ensure the destination is still valid.
      $destination = $element['#upload_location'];
      $destination_scheme = \Drupal::service('stream_wrapper_manager')->getScheme($destination);
      \Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
      $fids = [];
      if (strstr($external_urls, '::::')) {
        $file_urls_raw = explode('|', $external_urls);
        foreach ($file_urls_raw as $file_url_item) {
          list($plugin_id, $remote_file) = explode('::::', $file_url_item);
          $plugin = \Drupal::service('plugin.manager.external_media')->createInstance($plugin_id);
          $file = $plugin->getFile($remote_file, $destination);
          $file->save();
          if ($file) {
            $fids[] = $file->id();
          }
        }
      }

      // If there is no input or if the default value was requested above, use the
      // default value.
      if ($input === FALSE || $force_default) {
        if ($element['#extended']) {
          $default_fids = isset($element['#default_value']['fids']) ? $element['#default_value']['fids'] : [];
          $return = isset($element['#default_value']) ? $element['#default_value'] : ['fids' => []];
        }
        else {
          $default_fids = isset($element['#default_value']) ? $element['#default_value'] : [];
          $return = ['fids' => []];
        }

        // Confirm that the file exists when used as a default value.
        if (!empty($default_fids)) {
          $fids = [];
          foreach ($default_fids as $fid) {
            if ($file = File::load($fid)) {
              $fids[] = $file->id();
            }
          }
        }
      }
    }
    else {
      // Pass regular file upload routine to the parent element.
      return parent::valueCallback($element, $input, $form_state);
    }

    $return['fids'] = $fids;
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public static function processManagedFile(&$element, FormStateInterface $form_state, &$complete_form) {

    // This is used sometimes so let's implode it just once.
    $parents_prefix = implode('_', $element['#parents']);

    $fids = isset($element['#value']['fids']) ? $element['#value']['fids'] : [];

    // Set some default element properties.
    $element['#progress_indicator'] = empty($element['#progress_indicator']) ? 'none' : $element['#progress_indicator'];
    $element['#files'] = !empty($fids) ? File::loadMultiple($fids) : [];
    $element['#tree'] = TRUE;

    // Generate a unique wrapper HTML ID.
    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');

    $ajax_settings = [
      'callback' => [get_called_class(), 'uploadAjaxCallback'],
      'options' => [
        'query' => [
          'element_parents' => implode('/', $element['#array_parents']),
        ],
      ],
      'wrapper' => $ajax_wrapper_id,
      'effect' => 'fade',
      'progress' => [
        'type' => $element['#progress_indicator'],
        'message' => $element['#progress_message'],
      ],
    ];

    // Set up the buttons first since we need to check if they were clicked.
    $element['upload_button'] = [
      '#name' => $parents_prefix . '_upload_button',
      '#type' => 'submit',
      '#value' => t('Upload'),
      '#attributes' => ['class' => ['js-hide']],
      '#validate' => [],
      '#submit' => ['file_managed_file_submit'],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
      '#weight' => -5,
    ];

    // Force the progress indicator for the remove button to be either 'none' or
    // 'throbber', even if the upload button is using something else.
    $ajax_settings['progress']['type'] = ($element['#progress_indicator'] == 'none') ? 'none' : 'throbber';
    $ajax_settings['progress']['message'] = NULL;
    $ajax_settings['effect'] = 'none';
    $element['remove_button'] = [
      '#name' => $parents_prefix . '_remove_button',
      '#type' => 'submit',
      '#value' => $element['#multiple'] ? t('Remove selected') : t('Remove'),
      '#validate' => [],
      '#submit' => ['file_managed_file_submit'],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
      '#weight' => 1,
    ];

    $element['fids'] = [
      '#type' => 'hidden',
      '#value' => $fids,
    ];

    // Add progress bar support to the upload if possible.
    if ($element['#progress_indicator'] == 'bar' && $implementation = file_progress_implementation()) {
      $upload_progress_key = mt_rand();

      if ($implementation == 'uploadprogress') {
        $element['UPLOAD_IDENTIFIER'] = [
          '#type' => 'hidden',
          '#value' => $upload_progress_key,
          '#attributes' => ['class' => ['file-progress']],
          // Uploadprogress extension requires this field to be at the top of
          // the form.
          '#weight' => -20,
        ];
      }

      // Add the upload progress callback.
      $element['upload_button']['#ajax']['progress']['url'] = Url::fromRoute('file.ajax_progress', ['key' => $upload_progress_key]);

      // Set a custom submit event so we can modify the upload progress
      // identifier element before the form gets submitted.
      $element['upload_button']['#ajax']['event'] = 'fileUpload';
    }

    // Use a manually generated ID for the file upload field so the desired
    // field label can be associated with it below. Use the same method for
    // setting the ID that the form API autogenerator does.
    // @see \Drupal\Core\Form\FormBuilder::doBuildForm()
    $id = Html::getUniqueId('edit-' . implode('-', array_merge($element['#parents'], ['upload'])));

    // The file upload field itself.
    $element['upload'] = [
      '#name' => 'files[' . $parents_prefix . ']',
      '#type' => 'file',
      '#title' => t('Choose a file'),
      '#title_display' => 'invisible',
      '#id' => $id,
      '#size' => $element['#size'],
      '#multiple' => $element['#multiple'],
      '#theme_wrappers' => [],
      '#weight' => -10,
      '#error_no_message' => TRUE,
    ];
    if (!empty($element['#accept'])) {
      $element['upload']['#attributes'] = ['accept' => $element['#accept']];
    }

    // Indicate that $element['#title'] should be used as the HTML label for the
    // file upload field.
    $element['#label_for'] = $element['upload']['#id'];

    if (!empty($fids) && $element['#files']) {
      foreach ($element['#files'] as $delta => $file) {
        $file_link = [
          '#theme' => 'file_link',
          '#file' => $file,
        ];
        if ($element['#multiple']) {
          $element['file_' . $delta]['selected'] = [
            '#type' => 'checkbox',
            '#title' => \Drupal::service('renderer')->renderPlain($file_link),
          ];
        }
        else {
          $element['file_' . $delta]['filename'] = $file_link + ['#weight' => -10];
        }
        if ($file->isTemporary() && \Drupal::currentUser()->isAnonymous()) {
          $element['file_' . $delta]['fid_token'] = [
            '#type' => 'hidden',
            '#value' => Crypt::hmacBase64('file-' . $delta, \Drupal::service('private_key')->get() . Settings::getHashSalt()),
          ];
        }
      }
    }

    // Add the extension list to the page as JavaScript settings.
    if (isset($element['#upload_validators']['file_validate_extensions'][0])) {
      $extension_list = implode(',', array_filter(explode(' ', $element['#upload_validators']['file_validate_extensions'][0])));
      $element['upload']['#attached']['drupalSettings']['file']['elements']['#' . $id] = $extension_list;
    }

    // Prefix and suffix used for Ajax replacement.
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    // External Media Widget
    $cardinality = !empty($element['#cardinality']) ? $element['#cardinality'] : 1;
    $description = !empty($element['#description']) ? $element['#description'] : '';
    $upload_validators = !empty($element['#upload_validators']) ? $element['#upload_validators'] : [];
    $multiselect = ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) ? 1 : 0;

    // Sometimes size limits are not set. We would just use system upload limit.
    if (empty(['#upload_validators']['file_validate_size'][0])) {
      $upload_validators['file_validate_size'][] =  self::fileUploadMaxSize();
    }

    $info = [
      'cardinality' => $cardinality,
      'description' => $description,
      'upload_validators' => $upload_validators,
      'multiselect' => $multiselect,
    ];

    $visible_widgets = !empty($element['#visible_widgets']) ? $element['#visible_widgets'] : [];
    $browser_only = (isset($element['#visible_widgets']) && $element['#visible_widgets'] === FALSE);
    
    $render_inlines = [];
    $items = [];
    foreach (\Drupal::service('plugin.manager.external_media')->getDefinitions() as $plugin) {
      $external_media = \Drupal::service('plugin.manager.external_media')->createInstance($plugin['id']);
      $has_access = \Drupal::currentUser()->hasPermission('upload from ' . $external_media->getPluginId());
      if ($has_access && $external_media->getSetting('enabled') && $external_media->classExists()) {
        $item = [
          '#theme' => 'external_media',
          '#label' => $external_media->getName(),
          '#class' => $external_media->getClassName(),
          '#attributes' => $external_media->setAttributes($info),
          '#attached' => $external_media->getAttachments(),
        ];
        if ($template = $external_media->renderInline()) {
          $render_inlines[] = $template;
        }
        if (!empty($visible_widgets)) {
          if (in_array($external_media->getPluginId(), $visible_widgets)) {
            $items[] = $item;
          }
        }
        else {
          $items[] = $item;
        }
      }
    }
    $widgets_list = [
      '#theme' => 'external_media',
      '#label' => t('Choose file...'),
      '#class' => 'browse',
      '#attributes' => [],
    ];
    $inline_templates_rendered = \Drupal::service('renderer')->render($render_inlines);
    $buttons_rendered = \Drupal::service('renderer')->render($items);
    $widgets_list_rendered = \Drupal::service('renderer')->render($widgets_list);
    $default_file = !empty($parents_prefix) ? $form_state->getValue($parents_prefix) : '';
    $buttons = empty($fids) ? $widgets_list_rendered . $buttons_rendered . $inline_templates_rendered : '';
    if ($browser_only) {
      $buttons = $widgets_list_rendered;
    }

    $element['external_urls'] = [
      '#name' => 'external_urls[' . $parents_prefix . ']',
      '#type' => 'textfield',
      '#weight' => -20,
      '#prefix' => '<div class="external-media-widget-wrapper">' . $buttons,
      '#suffix' => '</div>',
      '#attributes' => ['class' => ['external-media-widget-urls']],
    ];

    return $element;
  }

  /**
   * Get default max upload size.
   * @see https://stackoverflow.com/a/25370978/258899
   */
  protected static function fileUploadMaxSize() {
    static $max_size = -1;
    if ($max_size < 0) {
      // Start with post_max_size.
      $post_max_size = self::parse_size(ini_get('post_max_size'));
      if ($post_max_size > 0) {
        $max_size = $post_max_size;
      }

      // If upload_max_size is less, then reduce. Except if upload_max_size is
      // zero, which indicates no limit.
      $upload_max = self::parse_size(ini_get('upload_max_filesize'));
      if ($upload_max > 0 && $upload_max < $max_size) {
        $max_size = $upload_max;
      }
    }
    return $max_size;
  }

  /**
   * Parse size value.
   * @see https://stackoverflow.com/a/25370978/258899
   */
  protected static function parse_size($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
    $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
    if ($unit) {
      // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
      return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    else {
      return round($size);
    }
  }

  /**
   * Get file information and its contents to upload.
   */
  public static function getFileInfo($path) {
    $file = pathinfo($path);

    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    $mimetype = @finfo_file($finfo, $path);
    $contents = file_get_contents($path);

    $info = [
      'filename'  => $file['basename'],
      'extension' => $file['extension'],
      'mimetype'  => $mimetype,
      'filesize'  => strlen($contents),
    ];
    return (object) $info;
  }

}
