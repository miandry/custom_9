<?php

namespace Drupal\mz_message\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Plugin implementation of the message_email_queue queueworker.
 *
 * @QueueWorker (
 *   id = "message_email_queue",
 *   title = @Translation("Message Send email Notification."),
 *   cron = {"time" = 30}
 * )
 */
class MessageEmailQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
      \Drupal::service('mz_message.default')->send_mail_queue($data);
  }

}
