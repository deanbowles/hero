<?php

namespace Drupal\sharepoint_connector\Controller;

use Drupal\sharepoint_connector\SharepointConnector;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides controller methods for the SharePoint Connector.
 */
class SharepointController {

  /**
   * Posts an entry to SharePoint.
   *
   * The payload for the post operation is derived from the current request.
   * This method will log appropriate messages based on the result of the
   * post operation - whether it succeeds or fails.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A response with a status code of either 201 (Created) if the post
   *   succeeds or 404 (Not Found) if it fails.
   */
  public function postEntry() {
    $payload = $this->buildPayload();

    if (!$payload) {
      $response = new JsonResponse();
      return $response->setStatusCode(JsonResponse::HTTP_NOT_FOUND);
    }

    $sharepoint_config = \Drupal::config('sharepoint_connector.settings');
    $is_logging = $sharepoint_config->get('connection_fieldset', '');
    $is_logging = (isset($is_logging[SharepointConnectorForm::FORM_ID . '_log_requests']) && !empty($is_logging[SharepointConnectorForm::FORM_ID . '_log_requests'])) ? $is_logging[SharepointConnectorForm::FORM_ID . '_log_requests'] : '';

    try {
      $sharepoint = new SharepointConnector();
      $entry = $sharepoint->postAnEntry($payload);

      if ($entry) {
        if ($is_logging) {
          \Drupal::logger('sharepoint_connector')->info("Entry successfully posted to SharePoint.");
        }
        $response = new JsonResponse();
        return $response->setStatusCode(JsonResponse::HTTP_CREATED);
      }
      else {
        if ($is_logging) {
          \Drupal::logger('sharepoint_connector')->warning("Failed to post entry to SharePoint.");
        }
        $response = new JsonResponse();
        return $response->setStatusCode(JsonResponse::HTTP_NOT_FOUND);
      }
    }
    catch (\Exception $e) {
      if ($is_logging) {
        \Drupal::logger('sharepoint_connector')->error("An error occurred while posting to SharePoint: @error", ['@error' => $e->getMessage()]);
      }
    }
  }

  /**
   * Constructs the payload for the SharePoint post operation.
   *
   * Extracts data from the current request and performs any necessary
   * filtering or transformations, like removing certain fields.
   *
   * @return array
   *   An associative array containing the payload data.
   */
  public function buildPayload() {
    $payload = \Drupal::request()->request->all();

    // Filter out the 'metatag' field if it exists.
    if (isset($payload['metatag'])) {
      unset($payload['metatag']);
    }

    return $payload;
  }

}
