<?php

namespace Drupal\mz_message\Plugin\views\field;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to the node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("mz_message_text")
 */
class MzMessageText extends FieldPluginBase {

  /**
   * Stores the result of node_view_multiple for all rows to reuse it later.
   *
   * @var array
   */
  protected $build;

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $token_service = \Drupal::token();
    $reference =  $values->_entity->reference->entity ;
    $value = $values->_entity->getText();
    $token_service = \Drupal::token();
    $value_new  =  $token_service->replace( $value[0] ,  ['node'=>$reference]);
    return [
      '#markup' =>  $value_new  
    ];
    // $renderer = \Drupal::service('renderer');
    // $html = $renderer->render($result);
    // return   $html ;
  }

}
