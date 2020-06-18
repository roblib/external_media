<?php

namespace Drupal\external_media\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * ExternalMediaController controller class.
 */
class ExternalMediaController extends ControllerBase {

  /**
   * Redirect Callback page title.
   */
  public function getTitle($external_media) {
    return $external_media->getName();
  }

  /**
   * Redirect Callback.
   */
  public function redirectCallback($external_media) {
    if ($contents = $external_media->renderPopupContents()) {
      return $contents;
    }
    else {
      return [
        '#markup' => 'External Media Callback',
        '#attached' => $external_media->getAttachments(),
        '#cache' => [
          'contexts' => ['url.path', 'url.query_args'],
        ],
      ];
    }
  }

  /**
   * Generate permissions dynamically.
   */
  public function permissions() {
    $permissions = [];
    $external_media = \Drupal::service('plugin.manager.external_media');
    foreach ($external_media->getDefinitions() as $plugin) {
      $plugin = $external_media->createInstance($plugin['id']);
      if ($plugin->classExists()) {
        $permissions['upload from ' . $plugin->getPluginId()] = [
          'title' => $this->t('Use %name', ['%name' => $plugin->getName()]),
          'description' => $this->t('Upload files from %name.', ['%name' => $plugin->getName()]),
        ];
      }
    }
    return $permissions;
  }

}
