<?php

namespace Drupal\external_media\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for ExternalMedia plugins.
 */
interface ExternalMediaInterface extends PluginInspectionInterface {

  /**
   * Return the name of the ExternalMedia plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Return description for the ExternalMedia plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Return module name that implements ExternalMedia plugin.
   *
   * @return string
   */
  public function getModule();

  /**
   * Get CSS class name.
   */
  public function getClassName();

  /**
   * Get class required by the plugin is avaialble.
   */
  public function classExists();

  /**
   * Get plugin icon name.
   */
  public function getIcon();

  /**
   * Get plugin service website.
   */
  public function getWebsite();

  /**
   * Get plugin attachements.
   */
  public function getAttachments();

  /**
   * Get plugin libraries.
   */
  public function getLibraries();

  /**
   * Render elements in line (could be used to render some containers for pickers).
   */
  public function renderInline();

  /**
   * Render popup contents.
   */
  public function renderPopupContents();

  /**
   * Build plugin config form.
   */
  public function configForm(array $form, FormStateInterface $form_state);

  /**
   * Process plugin config form submit.
   */
  public function submitConfigForm(array &$form, FormStateInterface $form_state);

  /**
   * Set attributes for the plugin upload button.
   */
  public function setAttributes($info);

  /**
   * Parse arguments and download remote file to specified destination.
   */
  public function getFile($url, $destination);

  /**
   * Parse arguments and download remote file to specified destination.
   */
  public function setRedirectCallback();

}
