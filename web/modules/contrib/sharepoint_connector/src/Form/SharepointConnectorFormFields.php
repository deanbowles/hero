<?php

namespace Drupal\sharepoint_connector\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Configuration form for the Sharepoint Connector.
 */
class SharepointConnectorFormFields extends ConfigFormBase {

  const FORM_ID = 'sharepoint_connector_settings_form_fields';

  /**
   * Config Factory object via Dependency Injection.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The Entity Field Manager object via Dependency Injection.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Request Stack object via Dependency Injection.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $entity_field_manager, RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('request_stack')
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
    $type_label = $this->requestStack->getCurrentRequest()->query->get('id');
    $type = $this->requestStack->getCurrentRequest()->query->get('machine');

    // Create a field group.
    $form[$this->getFormId() . '_content_type_' . $type . '_host'] = [
      '#type' => 'details',
      '#title' => $this->t('Connection'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $host_default = $sharepoint_config->get($this->getFormId() . '_content_type_' . $type . '_host', '');
    $host_default = (isset($host_default['url']) && !empty($host_default['url'])) ? $host_default['url'] : '';
    $form[$this->getFormId() . '_content_type_' . $type . '_host']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content Type @label Sharepoint Host URL', ['@label' => $type_label]),
      '#description' => $this->t('Enter the host url of your Sharepoint list (leave blank for default)'),
      '#default_value' => $host_default,
      '#maxlength' => 255,
    ];

    $form[$this->getFormId() . '_content_type_' . $type . '_enabled_fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Enabled Fields'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $node_fields = $this->getNodeFields($type);

    $enabled_defaults = $sharepoint_config->get($this->getFormId() . '_content_type_' . $type . '_enabled_fields', '');
    $fields_default = (isset($enabled_defaults['fields']) && !empty($enabled_defaults['fields'])) ? $enabled_defaults['fields'] : [];
    $form[$this->getFormId() . '_content_type_' . $type . '_enabled_fields']['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('@label fields', ['@label' => $type_label]),
      '#options' => $node_fields,
      '#default_value' => $fields_default,
    ];

    foreach ($node_fields as $field_key => $field_label) {
      $field_default = (isset($enabled_defaults[$field_key . '_mapping']) && !empty($enabled_defaults[$field_key . '_mapping'])) ? $enabled_defaults[$field_key . '_mapping'] : '';
      $form[$this->getFormId() . '_content_type_' . $type . '_enabled_fields'][$field_key . '_mapping'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Sharepoint field mapping for Drupal field (@label)', ['@label' => $field_label]),
        '#description' => $this->t('Enter the Sharepoint field that this Drupal field should map to'),
        '#states' => [
          'visible' => [
            ':input[name="' . $this->getFormId() . '_content_type_' . $type . '_enabled_fields[fields][' . $field_key . ']"]' => ['checked' => TRUE],
          ],
        ],
        '#default_value' => $field_default,
        '#maxlength' => 255,
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $type = $this->requestStack->getCurrentRequest()->query->get('machine');

    $this->config('sharepoint_connector.settings')
      ->set($this->getFormId() . '_content_type_' . $type . '_host', $form_state->getValue($this->getFormId() . '_content_type_' . $type . '_host'))
      ->set($this->getFormId() . '_content_type_' . $type . '_enabled_fields', $form_state->getValue($this->getFormId() . '_content_type_' . $type . '_enabled_fields'))
      ->save();

    $saved_fields = $form_state->getValue($this->getFormId() . '_content_type_' . $type . '_enabled_fields');
    if (!empty($saved_fields)) {
      foreach ($saved_fields as $field_key => $label) {
        $this->config('sharepoint_connector.settings')
          ->set($this->getFormId() . '_content_type_' . $type . '_enabled_fields[' . $field_key . '_mapping]', $form_state->getValue($this->getFormId() . '_content_type_' . $type . '_enabled_fields[' . $field_key . '_mapping]'))
          ->save();
      }
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to get a list of fields for content types.
   *
   * @return array
   *   An array of field labels keyed by their machine names.
   */
  protected function getNodeFields($content_type) {
    $fields = [];
    $bundle_fields = $this->entityFieldManager->getFieldDefinitions('node', $content_type);

    $fields[$content_type . ':title'] = 'Title';
    foreach ($bundle_fields as $field_name => $field_definition) {
      if ($field_definition instanceof FieldConfigInterface) {
        $fields[$content_type . ':' . $field_name] = $field_definition->getLabel();
      }
    }
    $fields[$content_type . ':created'] = 'Created';
    $fields[$content_type . ':changed'] = 'Updated';
    return $fields;
  }

}
