<?php

namespace Drupal\mz_payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Crypt;
/**
 * Class ApiController.
 */
class PaymentController extends ControllerBase {
  public function unSubscription($id) {
    $site = \Drupal::entityTypeManager()->getStorage('node')->load($id);
    $message = 'Failed to unSubscribe';
    $status = false ;
    if(is_object($site)){
      $current_user = \Drupal::currentUser();
      $current_user_id =  $current_user->id();
      $node_author_id = $site->getOwnerId();
      $roles = $current_user->getRoles();
      if($current_user_id === $node_author_id || 
        in_array('admin',  $roles) || 
        in_array('webmaster',  $roles)){
        $subscriptionId = $params_site['subscriptionId'];
        $service = \Drupal::service('mz_payment.manager');
        $result = $service->unSubscription($subscriptionId);
        $message = 'You have sucessfully unSubscribe !!';
        $status = true ;
      }else{
        $message = 'You dont have permission  to unSubscribe !! , please the website admin';
        $status = false ;
      }
  
    }
    

    $api_response = [
      'status' => $status,
      'message' =>  $message
    ];

    // Return the API response as JSON.
    return new JsonResponse($api_response);
  }
  public function setup_bank_page(){
    $params = [];
    return [
      '#theme' => 'setup_bank_page',
      '#cache' => ['max-age' => 0],
      "#items" => $params
   ];
  }
  public function setup_bank_process(){
    $username = 'admin';
    $user = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $username]);
    if (!empty($user)) {
        $user = reset($user);
        $link = user_pass_reset_url($user);
        $response = new RedirectResponse( $link);
        $response->send();
        return ;

    }else{
    }
  }
   // /pay?site_id=122 from this
  //themes/custom/staydirect/templates/templating/block--staydirect-bloc-payement-full.html.twig 
  public function checkoutstaydirect(){
    $service_helper = \Drupal::service('drupal.helper');
    $params_site = $service_helper->helper->get_parameter();

    // form get submit
    if(isset($params_site['site_id_ready'])){
        $mysite = \Drupal::entityTypeManager()->getStorage('node')->load($params_site["site_id_ready"]);  
        $mysite->set('status',0);
        $mysite->save();

        $service = \Drupal::service('mz_payment.manager');
        $service->paymenSingleStayDirect();
    }
    $path_root = '/stripe/failed';
    $path = $path_root.'?action=failed';
    $response = new RedirectResponse($path, 302);
    $response->send();
    return;
  }
  // checkout template
  public function checkout() {
    $service_helper = \Drupal::service('drupal.helper');
    $params_site = $service_helper->helper->get_parameter();

    $temp_store_factory = \Drupal::service('session_based_temp_store');
    $uid = \Drupal::currentUser()->id();// User ID
    $temp_store = $temp_store_factory->get($uid.'_order_booking', 106400); 
    $params = $temp_store->get('data');
    $params_new = array_merge($params,$params_site);

    $temp_store_new = $temp_store_factory->get($uid.'_order_booking', 106400); 
    $temp_store_new->deleteAll();
    $temp_store_new->set('data',$params_new);
    if($params_new){
      // $parser =  \Drupal::service('entity_parser.manager') ;
      // $booking = $parser->node_parser($params["booking_new"]);
        $price = $params_new['total'];
        $config = \Drupal::config('system.site');
        $site_name = $config->get('name');
        $cart['price'] =  $price ;
        $cart['quantity'] =  1 ;
        $cart['title'] =  'Booking order from '.$site_name ;
        $service = \Drupal::service('mz_payment.manager');
        $service->paymenSingle($cart);
    }
    $uid = \Drupal::currentUser()->id();
    $path_root = '/user'.'/'.$uid;
    $path = $path_root.'?action=failed';
    $response = new RedirectResponse($path, 302);
    $response->send();
    return;
  }
  public function webhook(Request $request) {
    $message  = $request->request;
    \Drupal::logger("mz_payment")->error(json_encode($message));
    $json = $request;
    return new JsonResponse($json);
  }
  public function success() {
    $service_helper = \Drupal::service('drupal.helper');
    $params = $service_helper->helper->get_parameter();
    $path = "/page-error" ;
    /// template site

     if(isset($params['action'] ) && $params['session_id'] && $params['action'] == 'success'){

      
      $service_booking = \Drupal::service('mz_booking.manager');

      $status = $service_booking->bookingProcessTempalteFinaliser();
      if (\Drupal::currentUser()->isAnonymous()) {
          $service_booking = \Drupal::service('mz_booking.manager');
          return [
            '#theme' => 'success',
            '#cache' => ['max-age' => 0],
            "#items" => $params
          ];
      } else {      
          $uid = \Drupal::currentUser()->id();
          $path_root = '/user'.'/'.$uid;
          $path = $path_root.'?action=failed && session_id = '.$params['session_id'];
      }
      if( $status == 2){
        $path =  $path_root.'?action='. $params['action'] .'&session_id='. $params['session_id'];
      }

     }


     // staydirect 
     if(isset($params["site_id"]) && isset($params['session_id']) && $params['?action'] == 'success'){
       /// after payement stripe got to template to change status
       if(!isset($params["pay_action"])){
          $service_booking = \Drupal::service('mz_booking.manager');
          $booking_id = $service_booking->bookingProcessStayDirectFinaliser();
          $parser =  \Drupal::service('entity_parser.manager') ;
          $site = \Drupal::entityTypeManager()->getStorage('node')->load($params["site_id"]);
          $url = $site->field_st_domain_name->value ;
          $service_helper = \Drupal::service('drupal.helper');
          $parser =  \Drupal::service('entity_parser.manager') ;
          $string_para = "";
          foreach($params as $key => $p){
            $string_para =  $string_para."&".$key."=".$p ;
          }
          global $base_url ; 
          $string_para = $string_para."&parent=".$base_url  ;
          $path =    $url."/parent-action?booking_site=".$booking_id."&pay_action=submit_payed&status=in_process&".$string_para;
          $response = new RedirectResponse($path, 302);
          $response->send();
          return;
        }else{
          return [
            '#theme' => 'success_staydirect',
            '#cache' => ['max-age' => 0],
            "#items" => $params
         ];
        }
     }
     $response = new RedirectResponse($path, 302);
     $response->send();
      return;
 

  }
  public function failed_template() {

    $service_helper = \Drupal::service('drupal.helper');
    $params = $service_helper->helper->get_parameter();
    $config = \Drupal::config('stripe.settings');
    $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
    \Stripe\Stripe::setApiKey($apikeySecret);
    $session_id =  $params["session_id"];
    $session = \Stripe\Checkout\Session::retrieve($session_id);
  // The session contains a PaymentIntent if mode=payment
  if($session->status = "open" && $session->payment_status =="unpaid"){
    //if($params["site_id"])
    $temp_store_factory = \Drupal::service('session_based_temp_store');
    $uid = \Drupal::currentUser()->id();// User ID
    $temp_store = $temp_store_factory->get($uid.'_order_booking', 106400); 
    $params_config = $temp_store->get('data');
    unset($params_config["termService"]);
    $path_root = '/booking-process?'.http_build_query($params_config);
    $path = $path_root.'&action=return';
  }else{
    $uid = \Drupal::currentUser()->id();
    $path_root = '/user'.'/'.$uid;
    $path = $path_root.'?action=failed';    
  }
  $response = new RedirectResponse($path, 302);
  $response->send();
  return;
     
}
  public function failed() {

    $service_helper = \Drupal::service('drupal.helper');
    $params = $service_helper->helper->get_parameter();
 
    $config = \Drupal::config('stripe.settings');
    $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
    \Stripe\Stripe::setApiKey($apikeySecret);
  $session_id =  $params["session_id"];
  $session = \Stripe\Checkout\Session::retrieve($session_id);
  // The session contains a PaymentIntent if mode=payment
  if($session->status = "open" && $session->payment_status =="unpaid"){
   // if($params["site_id"])
    $path_root = '/pay?site_id='.$params["site_id"];
    $path = $path_root.'&action=return';

  }else{
    $uid = \Drupal::currentUser()->id();
    $path_root = '/user'.'/'.$uid;
    $path = $path_root.'?action=failed';    
  }
  $response = new RedirectResponse($path, 302);
  $response->send();
  return;
     
}
  public function parent_action(){
      drupal_flush_all_caches();
      return [
        '#theme' => 'parent_action',
        '#cache' => ['max-age' => 0],
        "#items" => []
     ];
  }
}