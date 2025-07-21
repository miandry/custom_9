<?php

namespace Drupal\mz_message_mobile;
use Twilio\Rest\Client;
/**
 * Class MessageService.
 */
class MessageMobile {

  /**
   * Constructs a new MessageService object.
   */
  public function __construct() {

  }

  // composer require 'drupal/twilio:^3.0@alpha'
  public function sendSms($phone_number){
    return true ;
  }
}