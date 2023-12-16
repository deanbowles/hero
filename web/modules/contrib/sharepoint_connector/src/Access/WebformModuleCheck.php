<?php

namespace Drupal\sharepoint_connector\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks access to display the Webforms tab based on Webform module existence.
 */
class WebformModuleCheck implements AccessInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the WebformModuleCheck object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Checks access to the Webforms tab.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   The access result.
   */
  public function access(Route $route) {
    return ($this->moduleHandler->moduleExists('webform')) ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
