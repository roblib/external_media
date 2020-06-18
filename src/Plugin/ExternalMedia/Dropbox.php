<?php

namespace Drupal\external_media\Plugin\ExternalMedia;

use Drupal\Core\Form\FormStateInterface;
use Drupal\external_media\Plugin\ExternalMediaBase;

/**
 * Dropbox File picker plugin.
 *
 * @ExternalMedia(
 *   id = "dropbox_chooser",
 *   name = @Translation("Dropbox"),
 *   description = @Translation("Dropbox file chooser"),
 *   css_class = "dropbox-chooser",
 *   module = "external_media"
 * )
 */
class Dropbox extends ExternalMediaBase {

  /**
   * {@inheritdoc}
   */
  public function setAttributes($info) {
    $attributes = parent::setAttributes($info);
    $extensions = [];
    if (isset($info['upload_validators']['file_validate_extensions'][0])) {
      foreach (array_filter(explode(' ', $info['upload_validators']['file_validate_extensions'][0])) as $extension) {
        $extensions[] = '.' . $extension;
      }
    }
    $attributes['file-extentions'] = join(',', $extensions);
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return [
      'library' => ['external_media/external_media.' . $this->getPluginId()],
      'drupalSettings' => [
        'dropbox_class' => $this->getClassName(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return [
      'js' => [
        'https://www.dropbox.com/static/api/2/dropins.js' => [
          'type' => 'external',
          'minified' => TRUE,
          'attributes' => [
            'id' => 'dropboxjs',
            'data-app-key' => $this->getSetting('dropbox_chooser'),
          ],
        ],
        'js/dropbox.js' => [],
      ],
      'dependencies' => ['core/drupal', 'core/jquery', 'core/drupalSettings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configForm(array $form, FormStateInterface $form_state) {
    $form[$this->getPluginId()]['dropbox_chooser'] = [
      '#title' => $this->t('Dropbox App Key'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('dropbox_chooser'),
      '#description' => $this->t('Please <a href="https://www.dropbox.com/developers/apps" target="_blank">create a Drop-in app</a> to get the App Key.'),
    ];
    return $form;
  }

}
