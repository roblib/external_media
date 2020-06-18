<?php

namespace Drupal\external_media\Plugin\ExternalMedia;

use Drupal\Core\Form\FormStateInterface;
use Drupal\external_media\Plugin\ExternalMediaBase;

/**
 * Google Drive picker plugin.
 *
 * @ExternalMedia(
 *   id = "google_drive",
 *   name = @Translation("Google Drive"),
 *   description = @Translation("Google Drive file picker"),
 *   css_class = "google-picker",
 *   module = "external_media"
 * )
 */
class GoogleDrive extends ExternalMediaBase {

  /**
   * {@inheritdoc}
   */
  public function setAttributes($info) {
    $attributes = parent::setAttributes($info);
    $extensions = [];
    if (isset($info['upload_validators']['file_validate_extensions'][0])) {
      foreach (array_filter(explode(' ', $info['upload_validators']['file_validate_extensions'][0])) as $extension) {
        $mime = $this->externalMedia->getMimeByExtension($extension);
        if (is_array($mime)) {
          foreach ($mime as $mime_item) {
            $extensions[] = $mime_item;
          }
        }
        else {
          $extensions[] = $mime;
        }
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
        'google_client_id' => $this->getSetting('client_id'),
        'google_app_id' => $this->getSetting('app_id'),
        'google_scope' => explode('\n', $this->getSetting('scope')),
        'google_class' => $this->getClassName(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    return [
      'js' => [
        'js/google.js' => [],
        'https://apis.google.com/js/api.js' => ['type' => 'external'],
      ],
      'dependencies' => ['core/drupal', 'core/jquery', 'core/drupalSettings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configForm(array $form, FormStateInterface $form_state) {
    $form[$this->getPluginId()]['googledrive_client_id'] = [
      '#title' => $this->t('Client ID'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('client_id'),
      '#description' => $this->t('The Client ID obtained from the Google Developers Console. e.g. <em>886162316824-pfrtpjns2mqnek6e35gv321tggtmp8vq.apps.googleusercontent.com</em>'),
    ];
    $form[$this->getPluginId()]['googledrive_app_id'] = [
      '#title' => $this->t('Application ID'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('app_id'),
      '#description' => $this->t('Its the first number in your Client ID. e.g. <em>886162316824</em>'),
    ];
    $form[$this->getPluginId()]['googledrive_scope'] = [
      '#title' => $this->t('Scope'),
      '#type' => 'textarea',
      '#default_value' => !empty($this->getSetting('scope')) ? $this->getSetting('scope') : 'https://www.googleapis.com/auth/drive.file',
      '#description' => $this->t('Scope to use to access user\'s Drive items. Please put each scope in it is own line. <a href="https://developers.google.com/picker/docs/#otherviews" target="_blank">See available scopes</a>.'),
    ];
    $form[$this->getPluginId()]['googledrive_instructions'] = [
      '#type'  => 'fieldset',
      '#title' => t('Configuration instructions'),
    ];
    $form[$this->getPluginId()]['googledrive_instructions']['info'] = [
      '#markup' => '<p>To get started using Google Picker API, you need to first '
      . '<a href="https://console.developers.google.com/flows/enableapi?apiid=picker" target="_blank">'
      . 'create or select a project in the Google Developers Console and enable the API</a>.</p>'
      . '<ul><li>Enable <strong>Google Picker API</strong> <em>(required)</em></li>'
      . '<li>Enable <strong>Drive API</strong> <em>(required)</em></li></ul>'
      . '<p>Read more about <em>Scopes</em> and more details about how to get credentials on the '
      . '<a href="https://developers.google.com/picker/docs/" target="_blank">documentaion page</a>.',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigForm(array &$form, FormStateInterface $form_state) {
    $this
      ->setSetting('developer_key', $form_state->getValue('googledrive_developer_key'))
      ->setSetting('client_id', $form_state->getValue('googledrive_client_id'))
      ->setSetting('app_id', $form_state->getValue('googledrive_app_id'))
      ->setSetting('scope', $form_state->getValue('googledrive_scope'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFile($url, $destination) {
    list($id, $orignal_name, $google_token) = explode('@@@', $url);
    $remote_url = 'https://www.googleapis.com/drive/v2/files/' . $id . '?alt=media';
    $options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $google_token,
      ],
    ];
    $client = \Drupal::httpClient();
    $response = $client->request('GET', $remote_url, $options);
    if ($response->getStatusCode() == 200) {
      return file_save_data($response->getBody()->getContents(), $destination . '/' . $orignal_name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirectCallback() {
    return $this->getLibraries();
  }

}
