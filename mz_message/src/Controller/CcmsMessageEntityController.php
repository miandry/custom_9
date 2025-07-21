<?php

namespace Drupal\ccms_message\Controller;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\message\Entity\Message;

/**
 * CcmsMessageEntityController.
 */
class CcmsMessageEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a RevisionsController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('date.formatter'),
    $container->get('renderer')
    );
  }

  /**
   * The custom submit_callback for the entity node form.
   */
  public static function createMessage(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    // Get the entity id and create the message with the entity reference.
    $entity = $form_state->getFormObject()->getEntity();

    // Add entity creation/update message info for activities view.
    $message = Message::create(['template' => $values['entity_is_new'] ? 'log_new_content' : 'log_update_content', 'uid' => \Drupal::currentUser()->id()]);
    $message->set('content_reference', $entity);
    $message->save();
  }

}
