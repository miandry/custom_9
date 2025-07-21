<?php

namespace Drupal\mz_mobile_page\EventSubscriber;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\node\NodeInterface;
use Drupal\Component\Serialization\Json;

/**
 * Event subscriber to modify JSON response for a specific node.
 */
class MobilePageEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['modifyJsonResponse'];
    return $events;
  }

  /**
   * Modifies JSON response for a specific node.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function modifyJsonResponse(ResponseEvent $event) {
    $response = $event->getResponse();
    $request = $event->getRequest();

    // Check if the current route matches the desired route.
    if ($request->attributes->get('_route') === 'entity.node.canonical') {
      // Get the node entity from the request attributes.
      $node = $request->attributes->get('node');

      // Check if the node is of the desired type (e.g., article).
      if ($node instanceof NodeInterface && $node->getType() === 'page_mobile') {
        $service  = \Drupal::service('mz_mobile_page.render');
        $data = Json::decode($node->content_json->value);   
        $data = $service->executeJSON($node, $data);     
        // Modify the data as needed.
     
        // Create a new JsonResponse object with the modified data.
        $jsonResponse = new JsonResponse($data);

        // Set the new JsonResponse as the response for the event.
        $event->setResponse($jsonResponse);
      }
    }
  }
}
