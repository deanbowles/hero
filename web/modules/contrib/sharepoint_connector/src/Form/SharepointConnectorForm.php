<?php

namespace Drupal\sharepoint_connector\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for the Sharepoint Connector.
 */
class SharepointConnectorForm extends ConfigFormBase {

  const FORM_ID = 'sharepoint_connector_settings_form';

  /**
   * Config Factory object via Dependency Injection.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
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

    // Create a field group.
    $form['connection_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Connection'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $connection_defaults = $sharepoint_config->get('connection_fieldset', '');

    // Connection Type.
    $type_default = (isset($connection_defaults[$this->getFormId() . '_connection_type']) && !empty($connection_defaults[$this->getFormId() . '_connection_type'])) ? $connection_defaults[$this->getFormId() . '_connection_type'] : '';
    $form['connection_fieldset'][$this->getFormId() . '_connection_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Connection Type'),
      '#options' => [
        'client_tenant_id' => $this->t('Client/Tenant ID'),
      ],
      '#description' => $this->t('Select the type of connection you would like to use to connect to Sharepoint.'),
      '#default_value' => $type_default,
    ];

    $disabled_default = (isset($connection_defaults[$this->getFormId() . '_disable_api']) && !empty($connection_defaults[$this->getFormId() . '_disable_api'])) ? $connection_defaults[$this->getFormId() . '_disable_api'] : '';
    $form['connection_fieldset'][$this->getFormId() . '_disable_api'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable all calls to Sharepoint'),
      '#description' => $this->t('Killswitch - disable Sharepoint API connections entirely.'),
      '#default_value' => $disabled_default,
      '#prefix' => '<span style="line-height: 5px;">&nbsp;</span>',
    ];

    $log_default = (isset($connection_defaults[$this->getFormId() . '_log_requests']) && !empty($connection_defaults[$this->getFormId() . '_log_requests'])) ? $connection_defaults[$this->getFormId() . '_log_requests'] : '';
    $form['connection_fieldset'][$this->getFormId() . '_log_requests'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log requests'),
      '#description' => $this->t('Log all Sharepoint requests and responses.'),
      '#default_value' => $log_default,
    ];

    // Create a field group.
    $form['keys_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Keys'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $key_defaults = $sharepoint_config->get('keys_fieldset', '');

    // Client ID.
    $client_default = (isset($key_defaults[$this->getFormId() . '_client_id']) && !empty($key_defaults[$this->getFormId() . '_client_id'])) ? $key_defaults[$this->getFormId() . '_client_id'] : '';
    $form['keys_fieldset'][$this->getFormId() . '_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#description' => $this->t('Enter your Microsoft Graph API Client ID.'),
      '#default_value' => $client_default,
    ];

    if ($sharepoint_config->get('client_id')) {
      $form['keys_fieldset'][$this->getFormId() . '_client_id']['#default_value'] = '';
      $form['keys_fieldset'][$this->getFormId() . '_client_id']['#disabled'] = TRUE;
      $form['keys_fieldset'][$this->getFormId() . '_client_id']['#description'] = $form['keys_fieldset'][$this->getFormId() . '_client_id']['#description'] . ' (Overriden by settings.php)';
    }

    // Client Secret.
    $secret_default = (isset($key_defaults[$this->getFormId() . '_client_secret']) && !empty($key_defaults[$this->getFormId() . '_client_secret'])) ? $key_defaults[$this->getFormId() . '_client_secret'] : '';
    $form['keys_fieldset'][$this->getFormId() . '_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#description' => $this->t('Enter your Microsoft Graph API Client Secret (Insecure Method)'),
      '#default_value' => $secret_default,
    ];

    if ($sharepoint_config->get('client_secret')) {
      $form['keys_fieldset'][$this->getFormId() . '_client_secret']['#default_value'] = '';
      $form['keys_fieldset'][$this->getFormId() . '_client_secret']['#disabled'] = TRUE;
      $form['keys_fieldset'][$this->getFormId() . '_client_secret']['#description'] = $form['keys_fieldset'][$this->getFormId() . '_client_secret']['#description'] . ' (Overriden by settings.php)';
    }

    // Tenant ID.
    $tenant_default = (isset($key_defaults[$this->getFormId() . '_tenant_id']) && !empty($key_defaults[$this->getFormId() . '_tenant_id'])) ? $key_defaults[$this->getFormId() . '_tenant_id'] : '';
    $form['keys_fieldset'][$this->getFormId() . '_tenant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tenant ID'),
      '#description' => $this->t('Enter your Microsoft Graph API Tenant ID.'),
      '#default_value' => $tenant_default,
    ];

    if ($sharepoint_config->get('tenant_id')) {
      $form['keys_fieldset'][$this->getFormId() . '_tenant_id']['#default_value'] = '';
      $form['keys_fieldset'][$this->getFormId() . '_tenant_id']['#disabled'] = TRUE;
      $form['keys_fieldset'][$this->getFormId() . '_tenant_id']['#description'] = $form['keys_fieldset'][$this->getFormId() . '_tenant_id']['#description'] . ' (Overridden by settings.php)';
    }

    // Create a field group.
    $form['host_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Host'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    // Host / Base URL.
    $host_default = $sharepoint_config->get('host_fieldset', '');
    $host_default = (isset($host_default[$this->getFormId() . '_host']) && !empty($host_default[$this->getFormId() . '_host'])) ? $host_default[$this->getFormId() . '_host'] : '';
    $form['host_fieldset'][$this->getFormId() . '_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host URL'),
      '#description' => $this->t('Enter the host url of your Sharepoint list.'),
      '#default_value' => $host_default,
      '#maxlength' => 255,
    ];

    if ($sharepoint_config->get('base_url')) {
      $form['host_fieldset'][$this->getFormId() . '_host']['#default_value'] = '';
      $form['host_fieldset'][$this->getFormId() . '_host']['#disabled'] = TRUE;
      $form['host_fieldset'][$this->getFormId() . '_host']['#description'] = $form['host_fieldset'][$this->getFormId() . '_host']['#description'] . ' (Overriden by settings.php)';
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('sharepoint_connector.settings')
      ->set('connection_fieldset', $form_state->getValue('connection_fieldset'))
      ->set('keys_fieldset', $form_state->getValue('keys_fieldset'))
      ->set('host_fieldset', $form_state->getValue('host_fieldset'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
