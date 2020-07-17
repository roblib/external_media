<?php

namespace Drupal\external_media\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\external_media\Plugin\ExternalMediaManager;
use Drupal\external_media\ExternalMedia;

/**
 * ExternalMediaSettings form controller.
 */
class ExternalMediaSettings extends FormBase {

  /**
   * @var \Drupal\external_media\Plugin\ExternalMediaManager
   */
  protected $externalMediaManager;

  /**
   * @var \Drupal\external_media\ExternalMedia
   */
  protected $externalMedia;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\external_media\Form\ExternalMediaSettings.
   *
   * @param \Drupal\external_media\Plugin\ExternalMediaManager $external_media_manager
   * @param \Drupal\external_media\ExternalMedia $external_media
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(ExternalMediaManager $external_media_manager, ExternalMedia $external_media, ModuleHandlerInterface $module_handler) {
    $this->externalMediaManager = $external_media_manager;
    $this->externalMedia = $external_media;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.external_media'),
      $container->get('external_media'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'external_media_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->moduleHandler->moduleExists('external_media_premium')) {
      $form['premium'] = [
        '#type' => 'inline_template',
        '#template' => '{% if enabled %}<div class="external-media-premium-msg"><strong>New Premium version of the module is available with support of <em>Unsplash, Instagram, Pexel, Pixabay</em> and <em>AWS</em> or any other <em>Remote URL</em>.<br/>Premium version also includes <em>Webform</em> and <em>Media Library</em> support.<a href="https://downloads.minnur.com/drupal/external-media-premium" class="button button--primary" target="_blank">Go Premium</a></strong></div>{% endif %}',
        '#context' => [
          'enabled' => TRUE,
        ],
      ];
    }
    $form['info'] = [
      '#type' => 'inline_template',
      '#template' => '{% if enabled %}<div class="external-media-info-msg">If you have questions or if you do not see services that you need please <a href="https://downloads.minnur.com/contact-developer" target="_blank">contact developer</a>.</div>{% endif %}',
      '#context' => [
        'enabled' => TRUE,
      ],
      '#weight' => 100,
    ];
    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => [
          'external_media/external_media.form',
        ],
      ],
    ];

    foreach ($this->externalMediaManager->getDefinitions() as $plugin) {
      $external_media = $this->externalMediaManager->createInstance($plugin['id']);
      if ($external_media->classExists()) {
        $id = $external_media->getPluginId();
        $form[$id] = [
          '#type' => 'details',
          '#title' => $external_media->getName(),
          '#group' => 'settings',
        ];

        $form[$id][$id . '_enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable plugin'),
          '#default_value' => $external_media->getSetting('enabled'),
          '#description' => $this->t('Disabled plugins are not visible in <em>File</em> and <em>Image</em> fields.'),
        ];

        $form = array_merge($form, $external_media->configForm($form, $form_state));

        if ($external_media->setRedirectCallback()) {
          $form[$id][$id . '_redirect_url'] = [
            '#markup' => '<em>' . $external_media->getRedirectUrl()->toString() . '</em>',
            '#prefix' => '<div><strong>' . $this->t('Redirect URL') . '</strong></div>',
            '#suffix' => '<div class="description">' . $this->t('This is the URL you will need for the redirect URL/OAuth authentication') . '</div>',
          ];
        }
      }
    }
    $form['actions'] = [
      '#weight' => 999,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Settings'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->externalMediaManager->getDefinitions() as $plugin) {
      $external_media = $this->externalMediaManager->createInstance($plugin['id']);
      if ($external_media->classExists()) {
        $external_media->setSetting('enabled', $form_state->getValue($external_media->getPluginId() . '_enabled'));
        $external_media->submitConfigForm($form, $form_state);
      }
    }
    $this->messenger()->addStatus(t('The configuration options have been saved.'));
  }

}
