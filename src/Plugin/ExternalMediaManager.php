<?php

namespace Drupal\external_media\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * ExternalMediaManager plugin manager.
 *
 * @package external_media
 */
class ExternalMediaManager extends DefaultPluginManager {

  /**
   * Constructs an ExternalMediaManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ExternalMedia', $namespaces, $module_handler, 'Drupal\external_media\Plugin\ExternalMediaInterface', 'Drupal\external_media\Annotation\ExternalMedia');
    $this->alterInfo('external_media_plugin_info');
    $this->setCacheBackend($cache_backend, 'external_media_plugin');
  }

}
