<?php

namespace Drupal\sharepoint_connector;

use Drupal\sharepoint_connector\Form\sharepointConnectorForm;
use GuzzleHttp\Client;

/**
 * Provides methods for interacting with Sharepoint API.
 */
class SharepointConnector {

  /**
   * GuzzleHTTP Client.
   *
   * @var string
   */
  public $client = '';

  /**
   * Sharepoint Access Token.
   *
   * @var string
   */
  public $sharepointAccessToken = '';

  /**
   * Constructor.
   */
  public function __construct() {
    $sharepoint_config = \Drupal::config('sharepoint_connector.settings');
    $this->client = new Client();

    // Load connection information from settings.php $config if it exists
    // Otherwise load from settings form.
    $this->client_id = $sharepoint_config->get('client_id') ?: $sharepoint_config->get('keys_fieldset', '')[SharepointConnectorForm::FORM_ID . '_client_id'];
    $this->client_secret = $sharepoint_config->get('client_secret') ?: $sharepoint_config->get('keys_fieldset', '')[SharepointConnectorForm::FORM_ID . '_client_secret'];
    $this->tenant_id = $sharepoint_config->get('tenant_id') ?: $sharepoint_config->get('keys_fieldset', '')[SharepointConnectorForm::FORM_ID . '_tenant_id'];
    $this->base_url = $sharepoint_config->get('base_url') ?: $sharepoint_config->get('host_fieldset', '')[SharepointConnectorForm::FORM_ID . '_host'];
  }

  /**
   * Gets mapping of Sharepoint keys, returns formatted array of fields.
   */
  protected function prepareFields($form_content = [], $lookup_mapping = FALSE) {
    $return_data = [];

    // Look up mapping settings for each field to build array.
    if (is_array($form_content) && !empty($form_content)) {
      $sharepoint_config = \Drupal::config('sharepoint_connector.settings');
      $return_data = reset($form_content);
      $sharepoint_map_array = $sharepoint_config->get(key($form_content), '');
      $sharepoint_map_array = is_array($sharepoint_map_array) && !empty($sharepoint_map_array) ? array_filter($sharepoint_map_array) : '';

      if (!empty($sharepoint_map_array)) {
        foreach ($return_data as $key => $value) {
          if (isset($sharepoint_map_array[$key]) && !empty($sharepoint_map_array[$key])) {
            $return_data[$sharepoint_map_array[$key]] = $value;
          }
          unset($return_data[$key]);
        }
      }
    }
    return ['fields' => $return_data];
  }

  /**
   * Create a entry in Sharepoint.
   *
   * All fields sent in the form_content should exist in Sharepoint,
   * otherwise you will get an error from the API.
   *
   * We still need to refine how to error handle this.
   */
  public function postAnEntry($form_content = [], $lookup_mapping = FALSE, $url = '') {

    $sharepoint_config = \Drupal::config('sharepoint_connector.settings');
    $is_logging = $sharepoint_config->get('connection_fieldset', '');
    $is_logging = (isset($is_logging[SharepointConnectorForm::FORM_ID . '_log_requests']) && !empty($is_logging[SharepointConnectorForm::FORM_ID . '_log_requests'])) ? $is_logging[SharepointConnectorForm::FORM_ID . '_log_requests'] : '';

    $form_content = $this->prepareFields($form_content, $lookup_mapping);
    if ($this->authenticateToSharepoint()) {
      try {
        $res = $this->client->request(
          'POST',
          (!empty($url) ? $url : $this->base_url),
          [
            'headers' =>
              [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->sharepointAccessToken,
              ],
            'body' =>
            json_encode($form_content),
          ]
        );
      }
      catch (\Exception $e) {
        if ($is_logging) {
          \Drupal::logger('sharepoint_connector')->error("ERROR from" . __FILE__ . ":" . __LINE__ . " " . $e->getMessage());
        }
      }
      if (isset($res) && $res->getStatusCode() == 201) {
        return TRUE;
      }
      else {
        if ($is_logging) {
          \Drupal::logger('sharepoint_connector')->error("ERROR from SharepointConnector class - Request didn't return a 201.");
        }
      }
    }
    else {
      if ($is_logging) {
        \Drupal::logger('sharepoint_connector')->error("ERROR from SharepointConnector class - Authentication failed.");
      }
    }
    return FALSE;
  }

  /**
   * Authenticate to Sharepoint.
   *
   * This will return us an access token that we need for all subsequent calls.
   */
  protected function authenticateToSharepoint() {
    try {
      $res = $this->client->request(
        'POST',
        'https://login.microsoftonline.com/' . $this->tenant_id . '/oauth2/token?api-version=1.0',
        [
          'form_params' => [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'resource' => 'https://graph.microsoft.com/',
            'grant_type' => 'client_credentials',
          ],
        ]
      );
    }
    catch (\Exception $e) {
      $sharepoint_config = \Drupal::config('sharepoint_connector.settings');
      $is_logging = $sharepoint_config->get('connection_fieldset', '');
      $is_logging = (isset($is_logging[SharepointConnectorForm::FORM_ID . '_log_requests']) && !empty($is_logging[SharepointConnectorForm::FORM_ID . '_log_requests'])) ? $is_logging[SharepointConnectorForm::FORM_ID . '_log_requests'] : '';
      if ($is_logging) {
        \Drupal::logger('sharepoint_connector')->error("ERROR from" . __FILE__ . ":" . __LINE__ . " " . $e->getMessage());
      }
    }

    if (isset($res) && $res->getStatusCode() == 200) {
      $contents = json_decode($res->getBody()->getContents());
      $this->sharepointAccessToken = $contents->access_token;
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
