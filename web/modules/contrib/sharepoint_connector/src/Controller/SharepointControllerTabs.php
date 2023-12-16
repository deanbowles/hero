<?php

namespace Drupal\sharepoint_connector\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller for rendering forms in different tabs.
 */
class SharepointControllerTabs extends ControllerBase {

  /**
   * Form builder object via Dependency Injection.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Renders the main configuration form for the module.
   *
   * @return array
   *   A render array representing the main configuration form.
   */
  public function page1() {
    return [
      'form' => $this->formBuilder->getForm('Drupal\sharepoint_connector\Form\SharepointConnectorForm'),
    ];
  }

  /**
   * Renders the form for managing content types in the module.
   *
   * @return array
   *   A render array representing the content types configuration form.
   */
  public function page2() {
    return [
      'form' => $this->formBuilder->getForm('Drupal\sharepoint_connector\Form\SharepointConnectorFormContentTypes'),
    ];
  }

  /**
   * Renders the form for managing webforms in the module.
   *
   * @return array
   *   A render array representing the webforms configuration form.
   */
  public function page3() {
    return [
      'form' => $this->formBuilder->getForm('Drupal\sharepoint_connector\Form\SharepointConnectorFormWebforms'),
    ];
  }

}
