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
class SharepointConnectorFormWebforms extends ConfigFormBase {

  const FORM_ID = 'sharepoint_connector_settings_webforms_form';

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
    $webforms = $this->getWebforms();

    $form['typetable'] = [
      '#type' => 'table',
      '#header' => [$this->t('Webform'), $this->t('Operations')],
      '#empty' => $this->t('There are no items yet.'),
    ];

    foreach ($webforms as $webform => $webform_label) {

      $form['typetable'][$webform]['type_label'] = [
        '#plain_text' => $webform_label,
      ];

      // Operations (dropbutton) column.
      $form['typetable'][$webform]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];

      $webform_host = $sharepoint_config->get(SharepointConnectorFormWebformFields::FORM_ID . '_webform_' . $webform . '_host', '');
      $webform_host = is_array($webform_host) && isset($webform_host['url']) ? $webform_host['url'] : '';
      $webform_fields = $sharepoint_config->get(SharepointConnectorFormWebformFields::FORM_ID . '_webform_' . $webform . '_enabled_fields', '');
      $webform_fields = is_array($webform_fields) && isset($webform_fields['fields']) ? array_filter($webform_fields['fields']) : '';

      if (!empty($webform_host) || !empty($webform_fields)) {
        $form['typetable'][$webform]['operations']['#links']['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('sharepoint_connector.manage_webforms_edit',
            ['id' => $webform_label, 'machine' => $webform]),
        ];
        $form['typetable'][$webform]['operations']['#links']['delete'] = [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('sharepoint_connector.manage_webforms_delete',
            ['id' => $webform_label]),
        ];
      }
      else {
        $form['typetable'][$webform]['operations']['#links']['add'] = [
          'title' => $this->t('Add'),
          'url' => Url::fromRoute('sharepoint_connector.manage_webforms_edit',
            ['id' => $webform_label, 'machine' => $webform]),
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * Helper function to get a list of webforms.
   *
   * @return array
   *   An array of webform labels keyed by their machine names.
   */
  protected function getWebforms() {
    $webforms = $this->entityTypeManager->getStorage('webform')->loadMultiple();
    $options = [];
    foreach ($webforms as $webform) {
      $options[$webform->id()] = $webform->label();
    }
    return $options;
  }

}
