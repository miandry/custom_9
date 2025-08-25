<?php

namespace Drupal\mz_payment;



use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class DefaultService.
 */
class PaymentService {
  private $stripe ;
  private $currency ;
  /**
   * Constructs a new DefaultService object.
   */
  public function __construct() {
    $config = \Drupal::config('stripe.settings');
    $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
    $this->stripe = new \Stripe\StripeClient($apikeySecret);

  }
  public function format_price($price){
    return floatval($price)*100;
  }
  function isComplete($id){
    $query = \Drupal::entityTypeManager()->getStorage("node")->getQuery();
    $query->condition("type", 'mz_payment');
    $query->condition('mz_payment_item', $id);
    $t = $query->execute();
    if(empty($t)){
        return null ;
    }else{
        return end($t);
    }
  }
  
  public function  productsFormatter($carts){
    $results = [];
 
    foreach ($carts as $cart){
      $item = [];
      $item['quantity'] = $cart['quantity'] ;
      $item['price_data'] = [
        'currency' => $this->currency,
        'unit_amount' => $this->format_price($cart['price']) ,
        'product_data' => [
          'name' => $cart['title']
        ],
      ];
      $results[] = $item ;
    }
    return $results;
  }

  function unSubscription($subscriptionId){
    $config = \Drupal::config('stripe.settings');
    $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
    \Stripe\Stripe::setApiKey($apikeySecret);
      try {
          // Cancel the subscription immediately
          $subscription = \Stripe\Subscription::retrieve($subscriptionId);
          $subscription->cancel(); 
          return $subscription->status ;   
      } catch (\Stripe\Exception\ApiErrorException $e) {
        \Drupal::logger("mz_payment")->error( "Error: " . $e->getMessage());

      }
    return false ;  
  }
  function createPrice(){
        $config = \Drupal::config('stripe.settings');
        $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
        \Stripe\Stripe::setApiKey($apikeySecret);

        try {
            // Create a product
            $product = \Stripe\Product::create([
                'name' => 'Pro Plan',
                'type' => 'service',
            ]);

            echo 'Product created successfully: ' . $product->id . PHP_EOL;

            // Create a price for the product
            $price = \Stripe\Price::create([
                'unit_amount' => 2000, // Amount in cents (2000 cents = $20)
                'currency' => 'usd',
                'recurring' => ['interval' => 'month'],
                'product' => $product->id,
            ]);

            echo 'Price created successfully: ' . $price->id . PHP_EOL;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            echo 'Error: ' . $e->getMessage();
        }
  }
  function dateNextPaymentSubscription($subscriptionId){
    $config = \Drupal::config('stripe.settings');
    $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
    \Stripe\Stripe::setApiKey($apikeySecret);
    try {
        // Retrieve subscription details
        $subscription = \Stripe\Subscription::retrieve($subscriptionId);

        // Get the current period end (Unix timestamp)
        $nextPaymentTimestamp = $subscription->current_period_end;

        // Convert to a readable date format
        $nextPaymentDate = date('Y-m-d H:i:s', $nextPaymentTimestamp);
        return $nextPaymentDate;
    } catch (\Stripe\Exception\ApiErrorException $e) {
      \Drupal::logger("mz_payment")->error("Error: " . $e->getMessage());
    }

  }
  function getAllPaymentOffSubscription($subscriptionId , $limit = 100){
    $config = \Drupal::config('stripe.settings');
    $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
    \Stripe\Stripe::setApiKey($apikeySecret);
    try {
           // Fetch all invoices for this subscription
            $invoices = \Stripe\Invoice::all([
                'subscription' => $subscriptionId,
                'limit' => $limit,
            ]);
            $results = [];
            foreach ($invoices->data as $invoice) {
              $item = [];
              $item['id'] =  $invoice->id ;
              $item['amount_paid'] = $invoice->amount_paid / 100 ;
              $item['currency'] = strtoupper($invoice->currency);
              $item['status']  = $invoice->status ;
              $item['hosted_invoice_url'] = $invoice->hosted_invoice_url ;
              $item['customer_email'] = $invoice->customer_email ;
              $item['customer_name'] =  $invoice->customer_name ;
              $item['created'] =  $invoice->created ;
              $results[] = $item;
            }
            return $results ;   
      } catch (\Stripe\Exception\ApiErrorException $e) {
        \Drupal::logger("mz_payment")->error( "Error: " . $e->getMessage());
      }
    return false ;  

  }
  // price_id = price_1PXojACtQh0YqFFskXi8xMNy
 // $service = \Drupal::service('mz_payment.manager');
 // $service->subscriptionPayment(470,'price_1PXojACtQh0YqFFskXi8xMNy',1);
  function subscriptionPayment($connectedAccountId,$cart,$booking_id){
    $config = \Drupal::config('stripe.settings');
    $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
    \Stripe\Stripe::setApiKey($apikeySecret);
    $config = \Drupal::config('mz_payement.stripe');
    $connectedAccountId = $config->get('account');
    $host = \Drupal::request()->getSchemeAndHttpHost();
    if(!isset($cart['interval'])){
      $cart['interval'] = "month";
    }
    try {
        $url_success =  $host."/stripe/success?site_id=". $booking_id."&" ;
        $url_cancel =   $host."/stripe/failed?site_id=". $booking_id."&" ;
        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'automatic_tax' => ['enabled' => true],       
            'line_items' => [
                  [
                  'price_data' => [
                      'currency' => 'usd',
                      'product_data' => [
                          'name' => $cart['title'],
                      ],
                      'unit_amount' => floatval($cart['price'])*100, // Amount in cents
                      'recurring' => [
                        'interval' =>  $cart['interval'],
                      ],
                    ],
                  'quantity' => 1,
                  ],
            ],
            'mode' => 'subscription',
            'success_url' =>  $url_success .'?action=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' =>  $url_cancel .'?action=cancel&session_id={CHECKOUT_SESSION_ID}'
        ]);
        $url = $checkout_session->url; 
        $response = new RedirectResponse($url, 302);
        $response->send();
        return;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo 'Error creating Checkout Session: ' . $e->getMessage();
    }
  }
  function checkoutModePayment($connectedAccountId,$cart,$booking_id){
    try {
      $config = \Drupal::config('stripe.settings');
      $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
      \Stripe\Stripe::setApiKey($apikeySecret);
      $host = \Drupal::request()->getSchemeAndHttpHost();
      $url_success =  $host."/stripe/success?booking_site=". $booking_id."&" ;
      $url_cancel =   $host."/stripe/failed?booking_site=". $booking_id."&" ;
      // Quick test of subscription creation
      $session =  $this->stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => [
          [
              'price_data' => [
                  'currency' => 'usd',
                  'product_data' => [
                      'name' => $cart['title'],
                  ],
                  'unit_amount' => floatval($cart['price'])*100, // Amount in cents
                ],
              'quantity' => 1,
          ],
         ],
      'automatic_tax' => ['enabled' => true],
        'mode' => 'payment',
        'success_url' =>   $url_success .'?action=success&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $url_cancel.'?action=cancel&session_id={CHECKOUT_SESSION_ID}',
        'payment_intent_data' => [
          'capture_method' => 'manual',
          'transfer_data' => [
            'destination' => $connectedAccountId ,
          ]
        ],
    

      ]);
      $url  = $session->url ;

      $response = new RedirectResponse($url, 302);
      $response->send();
      return;
    } catch(\Stripe\Exception\CardException $e) {
      // Since it's a decline, \Stripe\Exception\CardException will be caught
      \Drupal::logger("mz_payment")->error( 'Status is:' . $e->getHttpStatus());
      \Drupal::logger("mz_payment")->error('Type is:' . $e->getError()->type);
      \Drupal::logger("mz_payment")->error('Code is:' . $e->getError()->code);
      // param is '' in this case
      \Drupal::logger("mz_payment")->error('Param is:' . $e->getError()->param);
      \Drupal::logger("mz_payment")->error('Message is:' . $e->getError()->message);

    } catch (\Stripe\Exception\RateLimitException $e) {
      // Too many requests made to the API too quickly
    } catch (\Stripe\Exception\InvalidRequestException $e) {
      // Invalid parameters were supplied to Stripe's API
    } catch (\Stripe\Exception\AuthenticationException $e) {
      // Authentication with Stripe's API failed
      // (maybe you changed API keys recently)
    } catch (\Stripe\Exception\ApiConnectionException $e) {
      // Network communication with Stripe failed
    } catch (\Stripe\Exception\ApiErrorException $e) {
      // Display a very generic error to the user, and maybe send
      // yourself an email
    } catch (Exception $e) {
      // Something else happened, completely unrelated to Stripe
    }
  }
  function paymenSingleStayDirect(){
    $service_helper = \Drupal::service('drupal.helper');
    $params_site = $service_helper->helper->get_parameter();
    $price = $params_site['price'] ;

    $temp_store_factory = \Drupal::service('session_based_temp_store');
    $uid = \Drupal::currentUser()->id();// User ID
    $temp_store = $temp_store_factory->get($uid.'_order_booking', 106400); 
    $temp_store->deleteAll();
    $temp_store->set('data',$params_site);
    
    $cart['price'] =  $price ;
    $cart['quantity'] = 1 ;
    $cart['interval'] =  $params_site['interval'] ;
    $cart['title'] =  'Order website from staydirect' ;
    $id = $params_site["site_id_ready"];
    // $service_booking = \Drupal::service('mz_booking.manager');
    // $id = $service_booking->bookingProcessStayDirect($params_site );
   // if($id){   
        //$connectedAccountId = 'acct_1Oo6PT2SzvmOUTDI';
        $config = \Drupal::config('mz_payement.stripe');
        $connectedAccountId = $config->get('account');
        if($connectedAccountId == null || $connectedAccountId == ''){
          $service_helper->helper->redirectTo('/account-not-ready');
        }
       $this->subscriptionPayment($connectedAccountId,$cart,$id);
   // } 
    return false;
  }
  public function getSubscriptionIdFromSession($sessionId){
    $config = \Drupal::config('stripe.settings');
    $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
    \Stripe\Stripe::setApiKey($apikeySecret);

    try {
    
        // Retrieve the Checkout Session
        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        // Get the subscription ID from the session
        $subscriptionId = $session->subscription;

        return  $subscriptionId;

    } catch (\Stripe\Exception\ApiErrorException $e) {
        $messsage =  'Error retrieving Checkout Session: ' . $e->getMessage();
            // param is '' in this case
        \Drupal::logger("mz_payment")->error($messsage);
    }
    return false ;
  }
   // new version
  public function paymenValidateCheckout($payment_intent){
      $config = \Drupal::config('stripe.settings');
      $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
      \Stripe\Stripe::setApiKey($apikeySecret);
      $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent);
      $result = $paymentIntent ;
      if ($paymentIntent->status === 'requires_capture') {
          $result = $paymentIntent->capture();
      }
      $result['status'] = $result->status;
      $result['amount_received'] = $result->amount_received;
      $result['amount_capturable'] = $result->amount_capturable;
      $result['amount'] = $result->amount;
      return $result ;
  }
  /**
  *  $cart =  ['price'=>1,'quantity'=>1,'title'=>'product']
   */
  public function paymenSingle($cart){
    $service_helper = \Drupal::service('drupal.helper');
    //$connectedAccountId = 'acct_1Oo6PT2SzvmOUTDI';
 
    $booking_new = 2 ;
    $config = \Drupal::config('mz_payement.stripe');
    $connectedAccountId = $config->get('account');
    if($connectedAccountId == null || $connectedAccountId == ''){
      $service_helper->helper->redirectTo('/account-not-ready');
    }
    try {
      $config = \Drupal::config('stripe.settings');
      $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
      \Stripe\Stripe::setApiKey($apikeySecret);
      $host = \Drupal::request()->getSchemeAndHttpHost();
      $url_success =  $host."/stripe/success";
      $url_cancel =   $host."/stripe/failed";
      // Quick test of subscription creation
      $session =  $this->stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => [
          [
              'price_data' => [
                  'currency' => 'usd',
                  'product_data' => [
                      'name' => $cart['title'],
                  ],
                  'unit_amount' => floatval($cart['price'])*100, // Amount in cents
                ],
              'quantity' => 1,
          ],
         
      ],
      'automatic_tax' => ['enabled' => true],
        'mode' => 'payment',
        'success_url' =>   $url_success .'?booking_new='. $booking_new .'&action=success&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $url_cancel.'?booking_new='. $booking_new .'&action=cancel&session_id={CHECKOUT_SESSION_ID}',
        'payment_intent_data' => [
          'capture_method' => 'manual',
          'transfer_data' => [
            'destination' => $connectedAccountId ,
          ]
        ],
    

      ]);
      $url  = $session->url ;
 
      $response = new RedirectResponse($url, 302);
      $response->send();
      return;
    } catch(\Stripe\Exception\CardException $e) {
      // Since it's a decline, \Stripe\Exception\CardException will be caught
      \Drupal::logger("mz_payment")->error( 'Status is:' . $e->getHttpStatus());
      \Drupal::logger("mz_payment")->error('Type is:' . $e->getError()->type);
      \Drupal::logger("mz_payment")->error('Code is:' . $e->getError()->code);
      // param is '' in this case
      \Drupal::logger("mz_payment")->error('Param is:' . $e->getError()->param);
      \Drupal::logger("mz_payment")->error('Message is:' . $e->getError()->message);

    } catch (\Stripe\Exception\RateLimitException $e) {
      // Too many requests made to the API too quickly
    } catch (\Stripe\Exception\InvalidRequestException $e) {
      // Invalid parameters were supplied to Stripe's API
    } catch (\Stripe\Exception\AuthenticationException $e) {
      // Authentication with Stripe's API failed
      // (maybe you changed API keys recently)
    } catch (\Stripe\Exception\ApiConnectionException $e) {
      // Network communication with Stripe failed
    } catch (\Stripe\Exception\ApiErrorException $e) {
      // Display a very generic error to the user, and maybe send
      // yourself an email
    } catch (Exception $e) {
      // Something else happened, completely unrelated to Stripe
    }
    
    return false;
  }
  public function paymentHold($params){
    try {
      //Use Stripe's library to make requests...
      $description = $params['description'] ?? 'Hold payment';
      $charge = $this->stripe->charges->create([
             'amount'=>$this->format_price($params['amount']),
             'currency'=> $this->currency,
             'source'=> $params['stripeToken'],
              'description'=>  $description,
               'capture' => false
        ]);
      return $charge->id;
    } catch(\Stripe\Exception\CardException $e) {
      // Since it's a decline, \Stripe\Exception\CardException will be caught
      \Drupal::logger("mz_payment")->error( 'Status is:' . $e->getHttpStatus());
      \Drupal::logger("mz_payment")->error('Type is:' . $e->getError()->type);
      \Drupal::logger("mz_payment")->error('Code is:' . $e->getError()->code);
      // param is '' in this case
      \Drupal::logger("mz_payment")->error('Param is:' . $e->getError()->param);
      \Drupal::logger("mz_payment")->error('Message is:' . $e->getError()->message);
      
    } catch (\Stripe\Exception\RateLimitException $e) {
      // Too many requests made to the API too quickly
    } catch (\Stripe\Exception\InvalidRequestException $e) {
      // Invalid parameters were supplied to Stripe's API
    } catch (\Stripe\Exception\AuthenticationException $e) {
      // Authentication with Stripe's API failed
      // (maybe you changed API keys recently)
    } catch (\Stripe\Exception\ApiConnectionException $e) {
      // Network communication with Stripe failed
    } catch (\Stripe\Exception\ApiErrorException $e) {
      // Display a very generic error to the user, and maybe send
      // yourself an email
    } catch (Exception $e) {
      // Something else happened, completely unrelated to Stripe
    }
    return false;
  }
  public function paymentValidate($id){
    try {
      $chg = $this->stripe->charges->capture($id);
      return ($chg->paid);
    } catch(\Stripe\Exception\CardException $e) {
      // Since it's a decline, \Stripe\Exception\CardException will be caught
      \Drupal::logger("mz_payment")->error( 'Status is:' . $e->getHttpStatus());
      \Drupal::logger("mz_payment")->error('Type is:' . $e->getError()->type);
      \Drupal::logger("mz_payment")->error('Code is:' . $e->getError()->code);
      // param is '' in this case
      \Drupal::logger("mz_payment")->error('Param is:' . $e->getError()->param);
      \Drupal::logger("mz_payment")->error('Message is:' . $e->getError()->message);

    } catch (\Stripe\Exception\RateLimitException $e) {
      // Too many requests made to the API too quickly
    } catch (\Stripe\Exception\InvalidRequestException $e) {
      // Invalid parameters were supplied to Stripe's API
    } catch (\Stripe\Exception\AuthenticationException $e) {
      // Authentication with Stripe's API failed
      // (maybe you changed API keys recently)
    } catch (\Stripe\Exception\ApiConnectionException $e) {
      // Network communication with Stripe failed
    } catch (\Stripe\Exception\ApiErrorException $e) {
      // Display a very generic error to the user, and maybe send
      // yourself an email
    } catch (Exception $e) {
      // Something else happened, completely unrelated to Stripe
    }
    return false;
  }
  /** return status = 'succeeded' */
  public function paymentCancel($charge_id){
    try {
      $charge = $this->stripe->charges->retrieve($charge_id);
      $refund = $charge->refunds->create(array('amount' =>  $charge->amount));
      if($refund->status == 'succeded'){
        \Drupal::logger("mz_payment")->warning('stripe payement cancel');
      }
      return ($refund->status);
    } catch(\Stripe\Exception\CardException $e) {
      // Since it's a decline, \Stripe\Exception\CardException will be caught
      // echo 'Status is:' . $e->getHttpStatus() . '\n';
      // echo 'Type is:' . $e->getError()->type . '\n';
      // echo 'Code is:' . $e->getError()->code . '\n';
      // // param is '' in this case
      // echo 'Param is:' . $e->getError()->param . '\n';
       $message =  'Message is:' . $e->getError()->message;
       \Drupal::logger("mz_payment")->warning($message);
    } catch (\Stripe\Exception\RateLimitException $e) {
      // Too many requests made to the API too quickly
      $message =  'Message is:' . $e->getError()->message;
      \Drupal::logger("mz_payment")->warning($message);
    } catch (\Stripe\Exception\InvalidRequestException $e) {
      // Invalid parameters were supplied to Stripe's API
      $message =  'Message is:' . $e->getError()->message;
      \Drupal::logger("mz_payment")->warning($message);
    } catch (\Stripe\Exception\AuthenticationException $e) {
      // Authentication with Stripe's API failed
      // (maybe you changed API keys recently)
      $message =  'Message is:' . $e->getError()->message;
      \Drupal::logger("mz_payment")->warning($message);
    } catch (\Stripe\Exception\ApiConnectionException $e) {
      // Network communication with Stripe failed
      $message =  'Message is:' . $e->getError()->message;
      \Drupal::logger("mz_payment")->warning($message);
    } catch (\Stripe\Exception\ApiErrorException $e) {
      // Display a very generic error to the user, and maybe send
      // yourself an email
      $message =  'Message is:' . $e->getError()->message;
      \Drupal::logger("mz_payment")->warning($message);
    } catch (Exception $e) {
      // Something else happened, completely unrelated to Stripe
      $message =  'Message is:' . $e->getError()->message;
      \Drupal::logger("mz_payment")->warning($message);
    }
    return false ;
  }
  public function checkIfRefunded($payment_intent_id){
    try {
      // Retrieve the PaymentIntent
      $config = \Drupal::config('stripe.settings');
      $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
      \Stripe\Stripe::setApiKey($apikeySecret);
      $paymentIntent = \Stripe\PaymentIntent::retrieve('pi_3OzKBLCtQh0YqFFs1wxeMcOk'); 
       $refunds = \Stripe\Refund::all(['payment_intent' =>  $paymentIntent->id]);
         return ($refunds->count);   
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Handle error
            \Drupal::logger("mz_payment")->error( 'Error: ' . $e->getMessage());
            return false;
        }
        return false;
  }
  public function tokenCustomBooking($booking,$txt){
     if(!is_object($booking)){
      return $txt ;
     }
   // $txt  = str_replace('[price_refunded]', ,  $txt );
   // $txt  = str_replace('[booking:mz_payment_refund]', $txt );
    $array = \Drupal::service('entity_parser.manager')->node_parser($booking);
    $type =  $array['type'];
    foreach($array as $key => $content){
      if(is_string($content)){
        $pattern = "[". $type.":". $key ."]" ;
        $txt  = str_replace($pattern,$content,$txt );
      }
      if(is_numeric($content)){
        $pattern = "[". $type.":". $key ."]" ;
        $txt  = str_replace($pattern,$content,$txt );
      }
    }
    $txt  = str_replace('[booking:field_client:name]', $array['field_client']['name'],$txt );

        // Check for the date_range field.
    if ($booking->hasField('field_dates') && !$booking->get('field_dates')->isEmpty()) {
        $start_date = $booking->field_dates->value;
        $end_date = $booking->field_dates->end_value;
        $replacement = $start_date . ' to ' . $end_date;
        $txt  = str_replace('[booking:field_dates]',  $replacement ,$txt );
    }
    if ($booking->hasField('refund_reference') && !$booking->get('refund_reference')->isEmpty()) {
      $ref = $booking->refund_reference->entity;
      $reason = $ref->field_reason->value;
      $txt  = str_replace('[booking:refund_reference:field_reason]',    $reason  ,$txt );
    }

    return $txt ;
  }
  public function refundPaymentSendEmail($entity){
    $token_service = \Drupal::token();
    $mail_config = \Drupal::config('user.mail');
    $message = $mail_config->get('email_refund.body');
    $message = $this->tokenCustomBooking($entity,$message);
    $message  = $token_service->replace( $message ,  ['node'=>$entity]);  
    $subject = $mail_config->get('email_refund.subject');
    $subject = $this->tokenCustomBooking($entity,$subject);
    $subject  = $token_service->replace($subject ,  ['node'=>$entity]);
  
    $uid = $entity->getOwnerId();
    $item_user = \Drupal\user\Entity\User::load($uid);
    $to  = $item_user->getEmail();
    \Drupal::service('mz_message.default')->send_mail_simple($message,$to,$subject);
  }

  public function unsubscribeSendEmail($entity){
    $token_service = \Drupal::token();
    $mail_config = \Drupal::config('user.mail');
    $message = $mail_config->get('email_unsubscribe.body');
    $message = $this->tokenCustomBooking($entity,$message);
    $message  = $token_service->replace( $message ,  ['node'=>$entity]);  
    $subject = $mail_config->get('email_unsubscribe.subject');
    $subject = $this->tokenCustomBooking($entity,$subject);
    $subject  = $token_service->replace($subject ,  ['node'=>$entity]);
    $uid = $entity->getOwnerId();
    $item_user = \Drupal\user\Entity\User::load($uid);
    $to  = $item_user->getEmail();
    \Drupal::service('mz_message.default')->send_mail_simple($message,$to,$subject);
  }

  // new version 
  public function paymentRefundCheckout($payment_intent_id,$amount=0){
      $status = false ;
      try {
        $config = \Drupal::config('stripe.settings');
        $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
        \Stripe\Stripe::setApiKey($apikeySecret);

        $amount = floatval($amount)*100 ;
        // Create a refund
        $refund = \Stripe\Refund::create([
            'payment_intent' => $payment_intent_id, // PaymentIntent ID
            'amount' => $amount, // Amount to refund in cents
        ]);
        return $refund->id ;
            // Output refund ID
        $message =  'Refund successful. Refund ID: ' . $refund->id;         
        \Drupal::logger("mz_payment")->warning($message);
      } catch (\Stripe\Exception\ApiErrorException $e) {
        \Drupal::logger("mz_payment")->error('Error :' . $e->getMessage());
      }
      return $status; 
  }
  /** return status = 'succeeded' */
  public function paymentRefund($charge_id,$amount=0){

    try {
      $charge = $this->stripe->charges->retrieve($charge_id);
      $refund = $charge->refunds->create(array('amount' =>  $this->format_price($amount)));
      if($refund->status == 'succeded'){
        \Drupal::logger("mz_payment")->warning('stripe payement refunded');
      }
      return ($refund->status);
    } catch(\Stripe\Exception\CardException $e) {
      // Since it's a decline, \Stripe\Exception\CardException will be caught
      // echo 'Status is:' . $e->getHttpStatus() . '\n';
      // echo 'Type is:' . $e->getError()->type . '\n';
      // echo 'Code is:' . $e->getError()->code . '\n';
      // // param is '' in this case
      // echo 'Param is:' . $e->getError()->param . '\n';
       $message =  'Message is:' . $e->getError()->message;
       \Drupal::logger("mz_payment")->warning($message);
    } catch (\Stripe\Exception\RateLimitException $e) {
      // Too many requests made to the API too quickly
      $message =  'Message is:' . $e->getError()->message;
      \Drupal::logger("mz_payment")->warning($message);
    } catch (\Stripe\Exception\InvalidRequestException $e) {
      // Invalid parameters were supplied to Stripe's API
      $message =  'Message is:' . $e->getError()->message;
      \Drupal::logger("mz_payment")->warning($message);
    } catch (\Stripe\Exception\AuthenticationException $e) {
      // Authentication with Stripe's API failed
      // (maybe you changed API keys recently)
      $message =  'Message is:' . $e->getError()->message;
      \Drupal::logger("mz_payment")->warning($message);
    } catch (\Stripe\Exception\ApiConnectionException $e) {
      // Network communication with Stripe failed
      $message =  'Message is:' . $e->getError()->message;
      \Drupal::logger("mz_payment")->warning($message);
    } catch (\Stripe\Exception\ApiErrorException $e) {
      // Display a very generic error to the user, and maybe send
      // yourself an email
      $message =  'Message is:' . $e->getError()->message;
      \Drupal::logger("mz_payment")->warning($message);
    } catch (Exception $e) {
      // Something else happened, completely unrelated to Stripe
      $message =  'Message is:' . $e->getError()->message;
      \Drupal::logger("mz_payment")->warning($message);
    }
  }
  public function getApiKey(){
    $config = \Drupal::config('stripe.settings');
    return $config->get('apikey.' . $config->get('environment') . '.public');
  }

  public function getListsPayment(){
    $config = \Drupal::config('stripe.settings');
    $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
    \Stripe\Stripe::setApiKey($apikeySecret);
  
  $connectedAccountId = 'acct_1OmgIOCc6iIbTA19';
  try {
     $payments = \Stripe\PaymentIntent::all(
          [
              'limit' => 10,  // Number of payment intents to retrieve
              // Add more parameters as needed
          ],
          ['stripe_account' => $connectedAccountId] // Specify the connected account
      );
      foreach ($payments as $payment) {
          // Access payment details using $payment object
          echo "Payment ID: " . $payment->id . "\n";
          echo "Amount: " . $payment->amount_received . "\n";
          // Add more details as needed
          echo "------------------------\n";
      }
  } catch (\Stripe\Exception\ApiErrorException $e) {
      // Handle API error
      echo 'Error: ' . $e->getMessage();
  }
  }
  public function getRequest($param = null)
  {
    $method = \Drupal::request()->getMethod();
    if ($param == null) {
      if ($method == "GET") {
        return \Drupal::request()->query->all();
      } elseif ($method == "POST") {
        return \Drupal::request()->request->all();
      } else {
        return null;
      }
    } else {
      if ($method == "GET") {
        return \Drupal::request()->query->get($param);
      } elseif ($method == "POST") {
        return \Drupal::request()->request->get($param);
      } else {
        return null;
      }
    }
  }

  public function getPayementByBooking($id){
    $array = ['type' => 'mz_payment','mz_payment_item' => $id];
    $nodes = \Drupal::entityTypeManager()->getStorage('node')
    ->loadByProperties($array);
    return  end($nodes);
  }
// cs_test_a1sP9z6FXGwDZ0JDfwnGEqeNqnx9J4HYo1nSR4d9cPFoh1VpDviaEnhkPo

public function getSesssion($sessionId){
  $config = \Drupal::config('stripe.settings');
  $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
  \Stripe\Stripe::setApiKey($apikeySecret);
  $pay = [];
  try {
    // Replace with the actual Checkout Session ID

    $session = \Stripe\Checkout\Session::retrieve($sessionId);

    // Access session properties

    $customerId = $session->customer;
    // ... Access other properties as needed
    ///kint($session);
    $pay['total'] = ($session->amount_total);
    $pay['subtotal'] = ($session->amount_subtotal);
    $pay['status'] = ($session->payment_status);
    $pay['created'] = ($session->created);
    $pay['currency'] = ($session->currency);
    $pay['sessionId'] =    $sessionId;
    $pay['payment_intent'] = ($session->payment_intent);
    $pay['payment_method_types'] =  ($session->payment_method_types);
    $pay['payment_intent_id'] =  ($session->payment_intent);
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Handle errors
    $pay = false ;
    \Drupal::logger("mz_payment")->error('Error: ' . $e->getMessage());
}
return $pay ;


}
public function verificationSetup($stripeAccountId){
  $config = \Drupal::config('stripe.settings');
  $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
  \Stripe\Stripe::setApiKey($apikeySecret);

  $stripeAccountObj = \Stripe\Account::retrieve($stripeAccountId);
  if (count($stripeAccountObj->requirements->currently_due) == 0) {
    die('You are all setup!');
   } else {
    # Other wise load element one.
    # Following the same structure as above:
    $neededCode = $stripeAccountObj->requirements->currently_due;
    kint($neededCode);
    die();
   }
}
public function updateSubAccount($stripeAccountId,$data){
  $config = \Drupal::config('stripe.settings');
  $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
  \Stripe\Stripe::setApiKey($apikeySecret);
  //$stripeAccountObj = \Stripe\Account::retrieve($stripeAccountId);
  // $stripeAccountObj->tos_acceptance->date = time();
  // $stripeAccountObj->tos_acceptance->ip = $_SERVER['REMOTE_ADDR'];
  // return $stripeAccountObj->save();
  try {
    $account = \Stripe\Account::update(
        $stripeAccountId ,
        [
          'tos_acceptance' => [
            'date' => time(),
            'ip' => '8.8.8.8'    //$_SERVER['REMOTE_ADDR']
          ]
        ]
    );
    $account->save();

    // Handle success
    echo 'Account updated successfully';
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Handle errors
    echo 'Error updating account: ' . $e->getMessage();
}


}
public function createNewSubAccount($data){

  $config = \Drupal::config('stripe.settings');
  $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
  \Stripe\Stripe::setApiKey($apikeySecret);
  // $subaccount = \Stripe\Account::create([
  //   'type' => 'standard', // or 'express' based on your needs
  //   'country' => 'US', // replace with the appropriate country code
  //   'email' =>  $email , // replace with the email address
  // ]);  
  $subaccount = \Stripe\Account::create([
    'type' => 'standard',
    'country' => 'US',
    'email' => $email ,
    'business_type' => 'individual',
    'individual' => [
        'phone' => '+1(615)573-0077'  ,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com'
        ],
        'company' => [
          'name' => 'Example Company',
          'address' => [
              'line1' => '123 Main St',
              'city' => 'City',
              'state' => 'CA',
              'postal_code' => '10001'
          ],
          'tax_id' => '123-45-6789',
      ]
]);


// Retrieve the ID of the newly created subaccount   
  return $subaccount->id ;

  }
  public function createNewBankToken(){

  }
  public function getCustomerInfo($customerId){
    try {

      $config = \Drupal::config('stripe.settings');
      $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
      \Stripe\Stripe::setApiKey($apikeySecret);

      $customer = \Stripe\Customer::retrieve($customerId);
  
      // Access customer properties
      $name = $customer->name;
      $email = $customer->email;
      $address = $customer->address;
      // ... Access other properties as needed
  
      // Output customer information (for demonstration purposes)
      echo 'Name: ' . $name . '<br>';
      echo 'Email: ' . $email . '<br>';
      echo 'Address: ' . print_r($address, true) . '<br>';
      // ... Output other information as needed
  }  catch (\Stripe\Exception\ApiErrorException $e) {
      // Handle errors
        echo 'Error: ' . $e->getMessage();
     }
  }


  function getConnectURLAUtho(){
    $config = \Drupal::config('stripe.settings');
    $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
    \Stripe\Stripe::setApiKey($apikeySecret);

    try {

  
      // Generate the Connect OAuth authorization URL
      $url = \Stripe\OAuth::authorizeUrl([
          'client_id' => 'ca_PTFtWpFI05QtXTkAEFSvCE7BayOH9FB2',
          'scope' => 'read_write',
          'response_type' => 'code',
          'redirect_uri' => 'https://eroso.mizara.mg',
          'state' => "acct_1OkS1KEI3AMwEFOo",
      ]);
  
      // Redirect the user to the authorization URL
      header('Location: ' . $url);
      exit;
  } catch (\Stripe\Exception\ApiErrorException $e) {
      // Handle errors
      echo 'Error: ' . $e->getMessage();
  }
  }

  function addRedirectURL(){
    $config = \Drupal::config('stripe.settings');
    $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
    \Stripe\Stripe::setApiKey($apikeySecret);

    try {
      $accountId = 'connected-account-id'; // Replace with the actual connected account ID
  
      // Retrieve the existing settings of the connected account
      $account = \Stripe\Account::retrieve($accountId);
  
      // Add the new redirect URI to the account settings
      $account->redirect["return_url"] = "https://your-new-redirect-uri.com";
  
      // Save the updated settings
      $account->save();
  
      echo 'Redirect URL added successfully!';
  } catch (\Stripe\Exception\ApiErrorException $e) {
      // Handle errors
      echo 'Error: ' . $e->getMessage();
  }
  }
  


}
