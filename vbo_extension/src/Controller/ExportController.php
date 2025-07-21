<?php

namespace Drupal\vbo_extension\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
/**
 * Class MessageController.
 */
class ExportController extends ControllerBase {

  /**
   * Insert.
   *
   * @return string
   *   Return Hello string.
   */
  public function export() {
    $form  = [];
    $service_helper = \Drupal::service('drupal.helper');
    $items = $service_helper->helper->storage_get('views_bulk_operations_redirect');
    $redirect_url = ( $items['redirect_url']);
    $back_url = $redirect_url->toString();
    $action = \Drupal::request()->query->get('action');

    $service = \Drupal::service('vbo_extension.manager');
    $resultat = $service->getData($items);
  //   kint(  $resultat);
    if($action && $action == 1){
      $date = date('Y-m-d-H:i:s', time());
      $service->array_csv_download( $resultat['resultat'],'export_'.$date.'.csv') ;
    }

    $url = Url::fromRoute('<current>');
    $url_alias = \Drupal::service('path_alias.manager')->getAliasByPath($url->toString());
    $form['button_1'] = [
      '#markup' => "<a class='button  button--primary' href='".$url_alias."?action=1'> Export </a>  <a class='button' href='".$back_url."'> Back </a>",
     ];   
    $form['table'] = array(
      '#type' => 'table',
      '#header' =>    $resultat['header'] ,
      '#rows' => $resultat['resultat'],
     );
     $form['#cache']['max-age'] = 0;
     $form['button'] = [
      '#markup' => "<a class='button  button--primary' href='".$url_alias."?action=1'> Export </a> <a class='button' href='".$back_url."'> Back </a>",
     ];   
     $form['#attached']['library'][] = "vbo_extension/vbo_extension";
     return  $form ;

  }
  

}
