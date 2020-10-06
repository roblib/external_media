<?php

namespace Drupal\external_media\Plugin\ExternalMedia;

use Drupal\Core\Form\FormStateInterface;
use Drupal\external_media\Plugin\ExternalMediaBase;

/**
 * OneDrive picker plugin.
 *
 * @ExternalMedia(
 *   id = "onedrive_picker",
 *   name = @Translation("OneDrive"),
 *   description = @Translation("OneDrive file picker"),
 *   css_class = "one-drive-picker",
 *   module = "external_media"
 * )
 */
class OneDrive extends ExternalMediaBase {

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return [
      'library' => ['external_media/external_media.' . $this->getPluginId()],
      'drupalSettings' => [
        'onedrive_client_id' => $this->getSetting('onedrive_picker'),
        'onedrive_class' => $this->getClassName(),
        'onedrive_redirect_url' => $this->getRedirectUrl()->toString() . '?q=' . uniqid(), // ?q= to make sure page doesn't get cached.
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return [
      'js' => [
        'https://js.live.net/v7.2/OneDrive.js' => [
          'type' => 'external',
          'minified' => TRUE,
        ],
        'js/onedrive.js' => [],
      ],
      'dependencies' => ['core/drupal', 'core/jquery', 'core/drupalSettings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configForm(array $form, FormStateInterface $form_state) {
    $form[$this->getPluginId()]['onedrive_picker'] = [
      '#title' => $this->t('Application (client) ID'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('onedrive_picker'),
      '#description' => $this->t('Please <a href="https://apps.dev.microsoft.com/?mkt=en-us" target="_blank">Register your app</a> to get an Application (client) ID, if you haven\'t already done so. '
        . 'Ensure that the web page that is going to reference the SDK is a <em>Redirect URL</em> under <strong>Redirect URIs (Web platform)</strong>. Enable <strong>Implicit grant</strong> and make sure <strong>Access Tokens</strong> and <strong>ID Tokens</strong> checked. Set <strong>Treat application as a public client</strong> to Yes.'
      ),
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

  /**
   * {@inheritdoc}
   */
  public function setRedirectCallback() {
    return $this->getLibraries();
  }

}
