<?php

namespace Drupal\sharepoint_connector\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Configuration form for the Sharepoint Connector.
 */
class SharepointConnectorFormWebformFields extends ConfigFormBase {

  const FORM_ID = 'sharepoint_connector_settings_form_webform_fields';

  /**
   * Config Factory object via Dependency Injection.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The Request Stack object via Dependency Injection.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
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
    $webform_label = $this->requestStack->getCurrentRequest()->query->get('id');
    $webform_id = $this->requestStack->getCurrentRequest()->query->get('machine');

    // Create a field group.
    $form[$this->getFormId() . '_webform_' . $webform_id . '_host'] = [
      '#type' => 'details',
      '#title' => $this->t('Connection'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $host_default = $sharepoint_config->get($this->getFormId() . '_webform_' . $webform_id . '_host', '');
    $host_default = (isset($host_default['url']) && !empty($host_default['url'])) ? $host_default['url'] : '';
    $form[$this->getFormId() . '_webform_' . $webform_id . '_host']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webform @label Sharepoint Host URL', ['@label' => $webform_label]),
      '#description' => $this->t('Enter the host url of your Sharepoint list (leave blank for default)'),
      '#default_value' => $host_default,
      '#maxlength' => 255,
    ];

    $form[$this->getFormId() . '_webform_' . $webform_id . '_enabled_fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Enabled Fields'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    // Get all fields for this webform.
    $webform_fields = $this->getWebformFields($webform_id);

    $enabled_defaults = $sharepoint_config->get($this->getFormId() . '_webform_' . $webform_id . '_enabled_fields', '');
    $fields_default = (isset($enabled_defaults['fields']) && !empty($enabled_defaults['fields'])) ? $enabled_defaults['fields'] : [];
    $form[$this->getFormId() . '_webform_' . $webform_id . '_enabled_fields']['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('@label fields', ['@label' => $webform_label]),
      '#options' => $webform_fields,
      '#default_value' => $fields_default,
    ];

    foreach ($webform_fields as $field_key => $field_label) {
      $field_default = (isset($enabled_defaults[$field_key . '_mapping']) && !empty($enabled_defaults[$field_key . '_mapping'])) ? $enabled_defaults[$field_key . '_mapping'] : '';
      $form[$this->getFormId() . '_webform_' . $webform_id . '_enabled_fields'][$field_key . '_mapping'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Sharepoint field mapping for Drupal field (@label)', ['@label' => $field_label]),
        '#description' => $this->t('Enter the Sharepoint field that this Drupal field should map to'),
        '#states' => [
          'visible' => [
            ':input[name="' . $this->getFormId() . '_webform_' . $webform_id . '_enabled_fields[fields][' . $field_key . ']"]' => ['checked' => TRUE],
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
    $webform_id = $this->requestStack->getCurrentRequest()->query->get('machine');

    $this->config('sharepoint_connector.settings')
      ->set($this->getFormId() . '_webform_' . $webform_id . '_host', $form_state->getValue($this->getFormId() . '_webform_' . $webform_id . '_host'))
      ->set($this->getFormId() . '_webform_' . $webform_id . '_enabled_fields', $form_state->getValue($this->getFormId() . '_webform_' . $webform_id . '_enabled_fields'))
      ->save();

    $saved_fields = $form_state->getValue($this->getFormId() . '_webform_' . $webform_id . '_enabled_fields');
    if (!empty($saved_fields)) {
      foreach ($saved_fields as $field_key => $label) {
        $this->config('sharepoint_connector.settings')
          ->set($this->getFormId() . '_webform_' . $webform_id . '_enabled_fields[' . $field_key . '_mapping]', $form_state->getValue($this->getFormId() . '_webform_' . $webform_id . '_enabled_fields[' . $field_key . '_mapping]'))
          ->save();
      }
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to get a list of fields for webforms.
   *
   * @return array
   *   An array of field labels keyed by their machine names.
   */
  protected function getWebformFields($webform_key) {
    $webform = Webform::load($webform_key);
    $webform_fields = $webform->getElementsDecoded();
    $fields = [];

    foreach ($webform_fields as $field_name => $field_definition) {
      if (!empty($field_definition) && (isset($field_definition['#title']) && !empty($field_definition['#title'])) && (isset($field_definition['#type']) && $field_definition['#type'] != 'processed_text')) {
        $fields[$webform_key . ':' . $field_name] = $field_definition['#title'];
      }
    }
    $fields[$webform_key . ':created'] = 'Created';
    return $fields;
  }

}
