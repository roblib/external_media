<?php

namespace Drupal\external_media\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines ExternalMedia annotation object.
 *
 * Plugin Namespace: Plugin\external_media\Plugin\ExternalMedia
 *
 * @see \Drupal\external_media\Plugin\ExternalMediaManager
 * @see plugin_api
 *
 * @Annotation
 */
class ExternalMedia extends Plugin {

  /**
   * Plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Plugin name.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * Plugin description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The module which this plugin is assigned to.
   *
   * @var string
   */
  protected $module;

  /**
   * Plugin CSS class name.
   *
   * @var string
   */
  public $css_class;

  /**
   * Plugin icon file name.
   *
   * @var string
   */
  public $icon;

  /**
   * Plugin service website URL.
   *
   * @var string
   */
  public $website;

}
