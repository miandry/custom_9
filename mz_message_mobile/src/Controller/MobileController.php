<?php

namespace Drupal\mz_message_mobile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
/**
 * Class ApiController.
 */
class MobileController extends ControllerBase {

  /**
   * Paragraph_delete.
   *
   * @return string
   *   Return Hello string.
   */
  public function verify_code($name){
    $item = [];
    return new JsonResponse($item);

  }

  public function validate_code($name){
    $item = [];
    return new JsonResponse($item);
  }

    /**
   * Page.
   *
   * @return string
   *   Return Hello string.
   */
  public function page_validation() {

    return [
      '#theme' => 'page_validation',
      '#cache' => ['max-age' => 0],
      "#items" =>  null
    ];
  }

}
