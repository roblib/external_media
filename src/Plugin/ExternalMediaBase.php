<?php

namespace Drupal\external_media\Plugin;

use Drupal\Core\Url;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\external_media\ExternalMedia;

/**
 * ExternalMedia plugin base class.
 *
 * @package external_media
 */
class ExternalMediaBase extends PluginBase implements ExternalMediaInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  protected $settings = [];
  protected $field_info = [];

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var \Drupal\external_media\ExternalMedia
   */
  protected $externalMedia;

  /**
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state, ExternalMedia $external_media, PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
    $this->settings = $state->get('external_media.info', []);
    $this->externalMedia = $external_media;
    $this->sessionManager = $session_manager;
    if (\Drupal::currentUser()->isAnonymous() && !$this->sessionManager->isStarted()) {
      $this->sessionManager->start();
    }
    $this->tempStoreFactory = $temp_store_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('state'),
      $container->get('external_media'),
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getClassName() {
    return $this->pluginDefinition['css_class'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon() {
    if ($module = $this->getModule()) {
      $module_path = $this->moduleHandler->getModule($module)->getPath();
      return !empty($this->pluginDefinition['icon']) ? base_path() . $module_path . '/img/' . $this->pluginDefinition['icon'] : '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getModule() {
    return !empty($this->pluginDefinition['module']) ? $this->pluginDefinition['module'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getWebsite() {
    return !empty($this->pluginDefinition['website']) ? $this->pluginDefinition['website'] : '';
  }

  /**
   * Get module path.
   */
  public function getModulePath() {
    if ($module = $this->getModule()) {
      $module_path = $this->moduleHandler->getModule($module)->getPath();
      return base_path() . $module_path;
    }
  }

  /**
   * Set plugin settings.
   */
  public function setSettings($settings) {
    $this->settings[$this->getPluginId()] = $settings;
    $this->state->set('external_media.info', $this->settings);
    Cache::invalidateTags(['emw:' . $this->getPluginId()]);
    return $this;
  }

  /**
   * Get plugin settings.
   */
  public function getSettings() {
    return $this->settings[$this->getPluginId()];
  }

  /**
   * Get a plugin setting.
   */
  public function getSetting($key) {
    return !empty($this->settings[$this->getPluginId()][$key]) ? $this->settings[$this->getPluginId()][$key] : NULL;
  }

  /**
   * Set a plugin setting.
   */
  public function setSetting($key, $val) {
    $this->settings[$this->getPluginId()][$key] = $val;
    $this->state->set('external_media.info', $this->settings);
    Cache::invalidateTags(['emw:' . $this->getPluginId()]);
    return $this;
  }

  /**
   * Get a plugin storage value.
   */
  public function getStorage($key) {
    return $this->tempStoreFactory->get('external_media_' . $this->getPluginId())->get($key);
  }

  /**
   * Set a plugin storage value.
   */
  public function setStorage($key, $val) {
    $this->tempStoreFactory->get('external_media_' . $this->getPluginId())->set($key, $val);
    Cache::invalidateTags(['emw:' . $this->getPluginId()]);
    return $this;
  }

  /**
   * Get field info.
   */
  protected function getFieldInfo($key) {
    return !empty($this->field_info[$key]) ? $this->field_info[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function renderInline() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function renderPopupContents() {
    return [
      '#theme' => 'external_media_popup',
      '#items' => [],
      '#plugin_id' => $this->getPluginId(),
      '#profile_name' => '',
      '#data' => [],
      '#attached' => $this->getAttachments(),
      '#cache' => [
        'contexts' => ['url.path', 'url.query_args'],
        'tags' => ['emw:' . $this->getPluginId()]
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function classExists() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function configForm(array $form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigForm(array &$form, FormStateInterface $form_state) {
    $this->setSetting($this->getPluginId(), $form_state->getValue($this->getPluginId()));
  }

  /**
   * {@inheritdoc}
   */
  public function setAttributes($info) {
    $extensions = [];
    if (isset($info['upload_validators']['file_validate_extensions'][0])) {
      foreach (array_filter(explode(' ', $info['upload_validators']['file_validate_extensions'][0])) as $extension) {
        $extensions[] = $extension;
      }
    }
    return [
      'plugin' => $this->getPluginId(),
      'cardinality' => $info['cardinality'],
      'description' => is_string($info['description']) ? strip_tags($info['description']) : '',
      'max-filesize' => !empty($info['upload_validators']['file_validate_size'][0]) ? $info['upload_validators']['file_validate_size'][0] : '',
      'file-extentions' => join(',', $extensions),
      'multiselect' => !empty($info['multiselect']) ? $info['multiselect'] : '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile($url, $destination) {
    return system_retrieve_file($url, $destination, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirectCallback() {
  }

  /**
   * Get redirect URL.
   */
  public function getRedirectUrl() {
    return Url::fromRoute('external_media.redirect_callback', [
      'external_media' => $this->getPluginId()
    ], ['absolute' => TRUE]);
  }

  /**
   * Build data for reset resrouce used in the viewer.
   */
  public function getRestResponse() {
    return [];
  }

}
