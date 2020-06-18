<?php

namespace Drupal\external_media\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;
use Drupal\external_media\Plugin\ExternalMediaManager;

/**
 * Resolves "external_media" type parameters in routes.
 */
final class ExternalMediaConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\external_media\Plugin\ExternalMediaManager
   */
  protected $externalMediaManager;

  /**
   * ExternalMediaConverter constructor.
   *
   * @param \Drupal\external_media\Plugin\ExternalMediaManager $external_media_manager
   */
  public function __construct(ExternalMediaManager $external_media_manager) {
    $this->externalMediaManager = $external_media_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $not_found = new NotFoundHttpException();
    try {
      $plugin = $this->externalMediaManager->createInstance(str_replace('-', '_', $value));
      if ($plugin->classExists()) {
        return $plugin;
      }
      else {
        throw $not_found;
      }
    }
    catch (PluginNotFoundException $e) {
      throw $not_found;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'external_media');
  }

}
