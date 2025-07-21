<?php

// custom_html_override/src/EventSubscriber/CustomHtmlOverrideSubscriber.php

namespace Drupal\html_page\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Modifies the page content with static HTML.
 */
class HtmlOverrideSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Specify the event to listen to.
    $events[KernelEvents::RESPONSE][] = ['onResponse'];

    return $events;
  }

  /**
   * Event callback to modify the response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   */
  public function onResponse(FilterResponseEvent $event) {
    // Get the response object.
    $response = $event->getResponse();

    // Check if the response corresponds to the page you want to override.
    if ($this->isTargetPage($event)) {
      $route_match = \Drupal::routeMatch();
      $id = $route_match->getParameter('id');
      $service = \Drupal::service('html_page.manager');
      $data = $service->loadData($id);
      $body = [
         '#type' => 'inline_template',
         '#template' => $data["content"]["value"]
      ];
      $body =  \Drupal::service('renderer')->renderRoot($body);
      $response->setContent($body);
    }
  }

  /**
   * Check if the response corresponds to the target page.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The response event.
   *
   * @return bool
   *   TRUE if the response corresponds to the target page, FALSE otherwise.
   */
  protected function isTargetPage(FilterResponseEvent $event) {
    // Add your logic to determine if the response corresponds to the target page.
    // You might check the route, path, or any other criteria.
    // For example, check the route name:
    return $event->getRequest()->get('_route') === 'html_page.view';
  }
}

