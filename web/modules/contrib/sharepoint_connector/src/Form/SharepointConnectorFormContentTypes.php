<?php

namespace Drupal\sharepoint_connector\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for the Sharepoint Connector.
 */
class SharepointConnectorFormContentTypes extends ConfigFormBase {

  const FORM_ID = 'sharepoint_connector_settings_types_form';

  /**
   * Config Factory object via Dependency Injection.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The Entity Type Manager object via Dependency Injection.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return self::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sharepoint_connector.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $sharepoint_config = $this->configFactory->get('sharepoint_connector.settings');
    $content_types = $this->getContentTypes();

    $form['typetable'] = [
      '#type' => 'table',
      '#header' => [$this->t('Content Type'), $this->t('Operations')],
      '#empty' => $this->t('There are no items yet.'),
    ];

    foreach ($content_types as $type => $type_label) {

      // Skip webform type since it will be handled in its own form.
      if ($type == 'webform') {
        continue;
      }

      $form['typetable'][$type]['type_label'] = [
        '#plain_text' => $type_label,
      ];

      // Operations (dropbutton) column.
      $form['typetable'][$type]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];

      $content_type_host = $sharepoint_config->get(SharepointConnectorFormFields::FORM_ID . '_content_type_' . $type . '_host', '');
      $content_type_host = is_array($content_type_host) && isset($content_type_host['url']) ? $content_type_host['url'] : '';
      $content_type_fields = $sharepoint_config->get(SharepointConnectorFormFields::FORM_ID . '_content_type_' . $type . '_enabled_fields', '');
      $content_type_fields = is_array($content_type_fields) && isset($content_type_fields['fields']) ? array_filter($content_type_fields['fields']) : '';

      if (!empty($content_type_host) || !empty($content_type_fields)) {
        $form['typetable'][$type]['operations']['#links']['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('sharepoint_connector.manage_edit',
            ['id' => $type_label, 'machine' => $type]),
        ];
        $form['typetable'][$type]['operations']['#links']['delete'] = [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('sharepoint_connector.manage_delete', ['id' => $type_label]),
        ];
      }
      else {
        $form['typetable'][$type]['operations']['#links']['add'] = [
          'title' => $this->t('Add'),
          'url' => Url::fromRoute('sharepoint_connector.manage_edit',
            ['id' => $type_label, 'machine' => $type]),
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('sharepoint_connector.settings')
      ->set($this->getFormId() . '_connection_type', $form_state->getValue($this->getFormId() . '_connection_type'))
      ->set($this->getFormId() . '_client_id', $form_state->getValue($this->getFormId() . '_client_id'))
      ->set($this->getFormId() . '_client_secret', $form_state->getValue($this->getFormId() . '_client_secret'))
      ->set($this->getFormId() . '_tenant_id', $form_state->getValue($this->getFormId() . '_tenant_id'))
      ->set($this->getFormId() . '_host', $form_state->getValue($this->getFormId() . '_host'))
      ->set($this->getFormId() . '_content_types', $form_state->getValue($this->getFormId() . '_content_types'))
      ->save();

    $saved_content_types = $form_state->getValue($this->getFormId() . '_content_types');
    if (!empty($saved_content_types)) {
      foreach ($saved_content_types as $content_type) {
        if (isset($saved_content_types[$content_type])) {
          $this->config('sharepoint_connector.settings')
            ->set($this->getFormId() . '_content_type_' . $content_type . '_host', $form_state->getValue($this->getFormId() . '_content_type_' . $content_type . '_host'))
            ->set($this->getFormId() . '_enabled_fields_' . $content_type, $form_state->getValue($this->getFormId() . '_enabled_fields_' . $content_type))
            ->save();
          $saved_fields = $form_state->getValue($this->getFormId() . '_enabled_fields_' . $content_type);
          foreach ($saved_fields as $field_key => $label) {
            $this->config('sharepoint_connector.settings')
              ->set($this->getFormId() . '_' . $field_key . '_mapping', $form_state->getValue($this->getFormId() . '_' . $field_key . '_mapping'))
              ->save();
          }
        }
      }
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to get a list of content types.
   *
   * @return array
   *   An array of content type labels keyed by their machine names.
   */
  protected function getContentTypes() {
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $options = [];
    foreach ($content_types as $content_type) {
      $options[$content_type->id()] = $content_type->label();
    }
    return $options;
  }

}
