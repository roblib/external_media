<?php

namespace Drupal\external_media\Plugin\ExternalMedia;

use Drupal\Core\Form\FormStateInterface;
use Drupal\external_media\Plugin\ExternalMediaBase;

/**
 * Box File picker plugin.
 *
 * @ExternalMedia(
 *   id = "box_picker",
 *   name = @Translation("Box"),
 *   description = @Translation("Box File picker"),
 *   css_class = "box-picker",
 *   module = "external_media"
 * )
 */
class Box extends ExternalMediaBase {

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return [
      'library' => ['external_media/external_media.' . $this->getPluginId()],
      'drupalSettings' => [
        'box_client_id' => $this->getSetting('box_picker'),
        'box_class' => $this->getClassName(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return [
      'js' => [
        'https://app.box.com/js/static/select.js' => ['type' => 'external'],
        'js/box.js' => [],
      ],
      'dependencies' => ['core/drupal', 'core/jquery', 'core/drupalSettings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configForm(array $form, FormStateInterface $form_state) {
    $form[$this->getPluginId()]['box_picker'] = [
      '#title' => $this->t('Box Client ID'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('box_picker'),
      '#description' => $this->t('Please <a href="https://app.box.com/developers/services" target="_blank">create a Box Application</a> to get the Client ID.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile($url, $destination) {
    list($file_url, $orignal_name) = explode('@@@', $url);
    return [
      'source' => $file_url,
      'destination' => $destination . '/' . $orignal_name,
    ];
  }

}
