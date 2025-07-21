<?php

namespace Drupal\mz_message\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class MessageController.
 */
class MessageController extends ControllerBase {

  /**
   * Insert.
   *
   * @return string
   *   Return Hello string.
   */
  public function insert() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: insert')
    ];
  }

}
