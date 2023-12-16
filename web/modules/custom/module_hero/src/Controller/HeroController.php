<?php

namespace Drupal\module_hero\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * This is our hero controller.
 */
class HeroController extends ControllerBase {

  public function heroList() {

    $heroes = [
      ['name' => 'Hulk'],
      ['name' => 'Thor'],
      ['name' => 'Iron Man'],
      ['name' => 'Luke Cage'],
      ['name' => 'Black Widow'],
      ['name' => 'Daredevil'],
      ['name' => 'Captain America'],
      ['name' => 'Wolverine']
    ];

    return [
      '#theme' => 'hero_list',
      '#items' => $heroes,
      '#title' => $this->t('Our wonderful heroes list'),
    ];
  }

}
