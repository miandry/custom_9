<?php

namespace Drupal\mz_booking;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \DateInterval;
use Drupal\file\Entity\File;
use GuzzleHttp\Client;
use Drupal\user\Entity\User;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultService.
 */
class BookingService {

  /**
   * Constructs a new DefaultService object.
   */
  public function __construct() {

  }
  function getTaxUsRates() {
      // Set the location for which you want to retrieve tax rates
       
// Replace 'YOUR_VATSTACK_API_ACCESS_KEY' with your actual vatstack API access key
$apiKey = '20c3ac81ddb4068733652169e4472b02';

// Prepare the API endpoint URL
$apiUrl = 'https://api.taxjar.com/v2/summary_rates';

// Initialize cURL session
$ch = curl_init();

// Set the cURL options
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
]);

// Execute the request and get the response
$response = curl_exec($ch);

// Close the cURL session
curl_close($ch);

// Parse the JSON response
$taxRates = json_decode($response, true);

 return $taxRates ;
  }
  public function payment(){
    if ($_POST) {
      Stripe::setApiKey("YOUR-API-KEY");
      $error = '';
      $success = '';
      try {
        if (!isset($_POST['stripeToken']))
          throw new Exception("The Stripe Token was not generated correctly");
        Stripe_Charge::create(array("amount" => 1000,
                                    "currency" => "usd",
                                    "card" => $_POST['stripeToken']));
        $success = 'Your payment was successful.';
      }
      catch (Exception $e) {
        $error = $e->getMessage();
      }
    }
  }

  public function insertAddonsToBooking(){
    $service = \Drupal::service('drupal.helper');
    $params = $service->helper->get_parameter();
    $service = \Drupal::service('drupal.helper');
    $params_old = $service->helper->storage_get('booking_info');
    $price_car = $params["booking-range"] ;
    $date = $params['booking-range-date'];
    $array_addons = [];
   // Generate the table HTML
    $table = '<table >';
    $table .= '<tr>';
    $table .= '<th style="width: 203px;"> Name </th>';
    $table .= '<th  style="width:100px;"> level </th>';
    $table .= '<th style="width: 50px;"> Amount </th>';
    $table .= '<th style="width: 50px;"> Quantity </th>';
    $table .= '</tr>';
    $table .= '<tr>';
    $table .= '<td style="width: 203px;">' .  $date  . '</td>';
    $table .= '<td style="width: 100px;"> </td>';
    $table .= '<td style="width: 50px;">' .  $price_car  . '</td>';
    $table .= '<td style="width: 50px;"> 1 </td>';
    $table .= '</tr>';
    $subtotal =  $price_car  ;
    
    foreach ($params as $key => $p){

      $query = "addon";
      if(substr($key, 0, strlen($query)) === $query
       && isset($params[$key."_value"]) ) {
        
            $name = str_replace("_", " ", $params[$key]);
            $price = $params[$key."_value"];
            $price_result = $this->getPrice($price);
            $type = $params_old[$params[$key].'__type'];
            $price_amount = $price_result[1];
            $subtotal  =  $subtotal  +  floatval($price_amount) ;
            $qty = 1 ;
            if($type == 'addon_with_quantity'){
              $price_unit = $this->getPriceUnitAddonWithQuantity($name) ;
              if( $price_unit){
                $qty =   floatval($price_amount)/floatval( $price_unit );
              }      
            }
            $level = $price_result[0] ?? '';
            $name = str_replace("_", " ", $params[$key]);
            $table .= '<tr>';
            $table .= '<td style="width: 203px;">' . $name . '</td>';
            $table .= '<td style="width: 100px;">' . $level . '</td>';
            $table .= '<td style="width: 50px;">' .  $price_amount . ' $ </td>';
            $table .= '<td style="width: 50px;">' .  $qty . '</td>';
            $table .= '</tr>';
            $array_addons[]  = [
              'field_name' => $name ,
              'field_value' => $price_amount  ,
              'field_level' =>    $level ,
              'field_type' =>  $type,
              'field_quantity' =>  $qty
            ];
      }
    }
    $config = \Drupal::config('mz_payment.config');
    $tax =  ($subtotal* floatval($config->get('tax')) )/100;

    $table .= '<tr>';
    $table .= '<td colspan="2" > <b>Sub Total</b></td>';
    $table .= '<td colspan="2">' .        $subtotal  . ' $ </td>';
    $table .= '</tr>';
    
    $table .= '<tr>';
    $table .= '<td colspan="2" > <b>Tax </b></td>';
    $table .= '<td colspan="2">' .        $tax  . ' $ </td>';
    $table .= '</tr>';

    $table .= '<tr>';
    $table .= '<td colspan="2" > <b>Total</b></td>';
    $table .= '<td colspan="2">' .   $params['total'] . ' $ </td>';
    $table .= '</tr>';
    $table .= '</table>';
    return ['table' => $table , 'array'=> $array_addons] ;
  }
  public function getPrice($price){
    if (strpos($price, "----") !== false) {
        $array_price = explode("----",$price );
        return $array_price;
    } else {
       return ['',$price];
    }
  }
  function asGuestProcess($email){
      // Load the user by email.
      $accounts = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $email]);
      $account = reset($accounts); // Get the first user account found with the given email.

      if ($account instanceof AccountInterface) {
        return  $account->id();
      }
      else {
        $password = '12345'; 
        $service = \Drupal::service('drupal.helper');
        $uid_last = $service->user->getUserLastId();
        $random_username = "GUEST_".$uid_last ;
        $user = User::create();
        $user->setPassword( $password);
        $user->enforceIsNew();
        $user->setEmail($email);
        $user->set('status', 1);
        $user->setUsername($random_username);
        $user->save();
        $uid = $user->id();
        return $uid  ;
      }
  }
  /**
   * Create a new user account and log in programmatically.
   */
  function create_and_login_user($username, $email) {
    // Define user details.

    // Check if the user already exists.
    if (!user_load_by_name($username)) {
    
    }
    else {
      // User already exists.
      // Handle the case where you might want to do something else.
    }
  }


  public function updateAddonsToBookingTemplate($entity,$info_stripe = null ){
    $booking = \Drupal::service('entity_parser.manager')->node_parser($entity);
    $date = $booking["field_dates"][0]["value"]." - ". $booking["field_dates"][0]["end_value"] ;
    $addons = $booking["field_addons"] ;
    $array_addons = [];
    $sum = 0 ;
    foreach ( $addons as $key => $p){
      $price = $p["field_value"];
      $sum =   $sum  + floatval( $price);
    }
    $price_car  = floatval($booking["field_price_default"]) -  $sum ;

   // Generate the table HTML
    $table = '<table >';
    $table .= '<tr>';
    $table .= '<th style="width: 203px;"> Name </th>';
    $table .= '<th style="width: 50px;"> Amount </th>';
    $table .= '</tr>';
       $table .= '<tr>';
       $table .= '<td style="width: 203px;">' .  $date  . '</td>';
       $table .= '<td style="width: 50px;"> $' .  $price_car  . '</td>';
       $table .= '</tr>';
    $subtotal = floatval($price_car); //initial;
    foreach ( $addons as $key => $p){
      $name = $p["field_name"];
      $price_amount = $p["field_value"];
             $table .= '<tr>';
              $table .= '<td style="width: 203px;">' . $name. '</td>';
              $table .= '<td style="width: 50px;">$' .  $price_amount . '  </td>';
              $table .= '</tr>';
              $subtotal =   $subtotal  + floatval( $price_amount);
    }

    $total = $subtotal;
    if($info_stripe &&  $info_stripe['total']){
      $total =  floatval($info_stripe['total'])/100 ;
    }
    $tax =  $total - $subtotal ; 

    $table .= '<tr>';
    $table .= '<td style="text-align: right;" > <b>Sub Total</b></td>';
    $table .= '<td> $' .        $subtotal  . ' </td>';
    $table .= '</tr>';
    
    $table .= '<tr>';
    $table .= '<td style="text-align: right;" > <b>Tax </b></td>';
    $table .= '<td> $' .        $tax  . '  </td>';
    $table .= '</tr>';

    $table .= '<tr>';
    $table .= '<td style="text-align: right;" > <b>Total</b></td>';
    $table .= '<td> $' .   $total . ' </td>';
    $table .= '</tr>';
    $table .= '</table>';
    return ['table' => $table ] ;
  }
  public function insertAddonsToBookingTemplate($booking_info, $info_stripe = null ){

    $price_car = $booking_info["booking-range"] ;
    $date = $booking_info['booking-range-date'];
  
    $array_addons = [];
   // Generate the table HTML
    $table = '<table >';
    $table .= '<tr>';
    $table .= '<th style="width: 203px;"> Name </th>';
    $table .= '<th style="width: 50px;"> Amount </th>';
    $table .= '</tr>';
    if(isset($booking_info['booking-range-date'])){
       $table .= '<tr>';
       $table .= '<td style="width: 203px;">' .  $date  . '</td>';
       $table .= '<td style="width: 50px;"> $' .  $price_car  . '</td>';
       $table .= '</tr>';
    }
    $subtotal = floatval($price_car); //initial;
    foreach ($booking_info as $key => $p){

      $query = "__type";
      $key_addon = str_replace("__type", "",$key);
    
      if(substr($key, -strlen($query)) === $query && isset($booking_info[$key_addon])) {
            $name = str_replace("_", " ", $booking_info[$key]);
            $result_price = $this->getPrice($booking_info[$key_addon]);
            $price_amount =  $result_price[1];
            $level = $result_price[0];
            $type =  $booking_info[$key];
            $subtotal  =  $subtotal  +  floatval($price_amount) ;
            $qty = 1 ;
            $name = str_replace("_", " ",  $key_addon);
            if($type == 'addon_with_quantity'){
              $price_unit = $this->getPriceUnitAddonWithQuantity($name) ;
              if( $price_unit){
                $qty =   floatval($price_amount)/floatval( $price_unit );
              }  
               if($qty == 1){
                $name = $name.'( '.$qty.' piece )';   
               } else{
                $name = $name.'( '.$qty.' pieces )';   
               }
             
            }

            if($type == 'select_addons'){
              $name = $name.'( '.$level.' )';
            }
            if(is_numeric($price_amount) && $price_amount > 0 ){
              $table .= '<tr>';
              $table .= '<td style="width: 203px;">' . $name. '</td>';
              $table .= '<td style="width: 50px;">$' .  $price_amount . '  </td>';
              $table .= '</tr>';
              $array_addons[]  = [
                'field_name' => $name ,
                'field_value' => $price_amount,
                'field_level' =>    $level ,
                'field_type' =>  $type,
                'field_quantity' =>  $qty
              ];
            }
      }
    }
    $total = $subtotal;
    if($info_stripe &&  $info_stripe['total']){
      $total =  floatval($info_stripe['total'])/100 ;
    }
    $tax =  $total - $subtotal ; 

    $table .= '<tr>';
    $table .= '<td style="text-align: right;" > <b>Sub Total</b></td>';
    $table .= '<td> $' .        $subtotal  . ' </td>';
    $table .= '</tr>';
    
    $table .= '<tr>';
    $table .= '<td style="text-align: right;" > <b>Tax </b></td>';
    $table .= '<td> $' .        $tax  . '  </td>';
    $table .= '</tr>';

    $table .= '<tr>';
    $table .= '<td style="text-align: right;" > <b>Total</b></td>';
    $table .= '<td> $' .   $total . ' </td>';
    $table .= '</tr>';
    $table .= '</table>';
    return ['table' => $table , 'array'=> $array_addons] ;
  }
  // after payment stripe
  public function bookingProcessTempalteFinaliser(){
    $service = \Drupal::service('drupal.helper');
    $params = $service->helper->get_parameter();
    $booking_info = $service->helper->storage_get('booking_info');
    $mz_payment_service = \Drupal::service('mz_payment.manager');
    $info_stripe =  $mz_payment_service->getSesssion($params['session_id']);
    $uid = \Drupal::currentUser()->id();// User ID
    $entity = $this->submitBookingProcessTemplate($uid);
    if(is_object( $entity)){
      $entity->title->value = 'bk-'.$entity->id() ;
      $entity->field_status->value = "validation" ;
      $entity->field_session_id->value = $params['session_id'];
      $entity->field_price_with_tax->value = floatval($info_stripe["total"])/100 ;
      $entity->field_payment_intent_id->value = $info_stripe["payment_intent_id"];
      $entity->moderation_state->value = "draft" ;
      $addons_elements = $this->updateAddonsToBookingTemplate($entity,$info_stripe);
      $entity->body->value = $addons_elements['table']; 

      $temp_store_factory = \Drupal::service('session_based_temp_store');
      $uid = \Drupal::currentUser()->id();// User ID
      $temp_store = $temp_store_factory->get($uid.'_order_booking', 106400); 
      $temp_store->deleteAll();

      return $entity->save();
    }
    return false;
  }
  public function bookingProcessTemplate($uid){
      $service = \Drupal::service('drupal.helper');
      $params = $service->helper->get_parameter();
         // Save params to session
      $temp_store_factory = \Drupal::service('session_based_temp_store');
   
      $temp_store = $temp_store_factory->get($uid.'_order_booking', 106400); 
      $temp_store->deleteAll();
      if(isset($params["termService"]) && $params["termService"] == "on" ){
        $paramsAll = $temp_store->set('data',$params);
      }
    // Redirect to /booking-process
      $response = new RedirectResponse('/payment/checkout?booking_new=1'); 
      $response->send();
      // Stop further processing
      exit;
  }

  //// change to after booking is payed
  public function submitBookingProcessTemplate($uid){
    $service = \Drupal::service('drupal.helper');
    $temp_store_factory = \Drupal::service('session_based_temp_store');
    $uid = \Drupal::currentUser()->id();// User ID
    $temp_store = $temp_store_factory->get($uid.'_order_booking', 106400); 
    $params = $temp_store->get('data');
    if(isset($params["termService"]) && $params["termService"] == "on" ){
       $addons = \Drupal::entityTypeManager()
         ->getStorage('taxonomy_term')
         ->loadByProperties(['vid'=>'addons','field_type'=>'article']);
       $random = time() . rand(10*45, 100*98);
       $fields['title'] = $random ;
       $fields['field_price_default'] = $params['total'] ;
       $fields['field_item'] =     $params['nid'] ;
       $date = explode(' - ',$params['booking-range-date']) ;
       $fields['field_dates'][] = [
         'value' =>  $date[0],
         'end_value' =>  $date[1]
       ];
       $addons_elements = $this->insertAddonsToBookingTemplate($params);
       if(!empty($addons_elements['array'])){
        $fields["field_addons"] =  $addons_elements['array'];
       }
       if(isset($params['notes'])){
        $fields["field_notes"] = $params['notes'];
       }
       $fields['field_client'] =  $uid ;
       $fields['field_status'] = "unpaid" ;
     
       $fields['body']  = $addons_elements['table'];


       $booking_new = \Drupal::service('crud')->save('node', 'booking', $fields);

       if(is_object($booking_new)){
         $custom = \Drupal\user\Entity\User::load($uid);
         if(isset($params["field_first_name"])){
            $custom->field_first_name->value = $params["field_first_name"];
         }
         if(isset($params["field_last_name"])){
           $custom->field_last_name->value = $params["field_last_name"];
         }
         $custom->field_phone->value = $params["field_phone"];
         $custom->save();
         return  $booking_new ;
         // $service->helper->set_config($uid, $booking_info);
       }
      
    }
    return false ;
  }
  function calculateEndDate($start_date, $months) {
    // Create a DateTime object from the start date string.
    $start_date_obj = new DrupalDateTime($start_date);
    
    // Clone the start date object to avoid modifying it directly.
    $end_date_obj = clone $start_date_obj;
    
    // Add the given number of months to the start date.
    $end_date_obj->modify('+' . $months . ' months');
    
    // Format the end date as a string.
    $end_date = $end_date_obj->format('Y-m-d');
    
    return $end_date;
}
public function bookingProcessStayDirectFinaliser(){
    
  
        $service = \Drupal::service('drupal.helper');
        $params = $service->helper->get_parameter();
        $temp_store_factory = \Drupal::service('session_based_temp_store');
        $uid = \Drupal::currentUser()->id();// User ID
        $temp_store = $temp_store_factory->get($uid.'_order_booking', 106400); 
        $booking_info = $temp_store->get('data');

        $fields['field_price_default'] = $booking_info['price'] ;
        $fields['field_item'] =     $booking_info['site_id_ready'] ;
    
           if(isset($booking_info['notes'])){
              $fields["notes"] = $booking_info['notes'];
           }
           $fields['field_client'] =  $uid ;
           $fields['interval'] =   $booking_info['interval'];
    
           $mz_payment_service = \Drupal::service('mz_payment.manager');
           $info_stripe = $mz_payment_service->getSesssion($params['session_id']);

           $subscriptionId = $mz_payment_service->getSubscriptionIdFromSession($params['session_id']);
           $fields['field_subscription_id'] =   $subscriptionId ;
           $fields['moderation_state'] = "draft" ;
           $fields['field_price_with_tax'] = floatval($info_stripe["total"])/100 ;            
           $fields['title'] = 'bk-site'.$subscriptionId ;
           $fields['field_session_id'] = $params['session_id'];
           $fields['field_status_booking'] = 'process';
           $booking_new = \Drupal::service('crud')->save('node', 'booking', $fields);
    
           if(is_object($booking_new)){    
            $site = \Drupal::service('entity_parser.manager')->node_parser($booking_info['site_id_ready']);     
             $custom =  $site["uid"]["user"];
             if(isset($booking_info["field_first_name"])){
               $custom->field_first_name->value = $booking_info["field_first_name"];
             }
             if(isset($booking_info["field_last_name"])){
               $custom->field_last_name->value = $booking_info["field_last_name"];
             }
             $custom->field_phone->value = $booking_info["field_phone"];
             $custom->save();
             return $booking_new->id();
           }
           return false;
  }
  // For /pay?site_id=1110 
  public function findAllBookingIdBySiteSetStatus($booking_info){
    $site_id = $booking_info['site_id_ready'] ;
    $query = \Drupal::entityQuery('node')
    ->condition('type', 'booking') 
    ->condition('field_item', $site_id);
    $nids = $query->execute();
    if (!empty($nids)) {
      $bookings = Node::loadMultiple($nids);
      foreach ($bookings as $booking) {
        if ($booking->hasField('field_status_booking')) {
          $booking->set('field_status_booking', 'completed');
          try {
            $booking->save();
          } catch (\Exception $e) {
            \Drupal::messenger()->addError('Failed to update booking ID: ' . $booking->id() . ' Error: ' . $e->getMessage());
          }
        }
      }
    }
    return false ; 
  }
  public function bookingDeleteDraft(){
    $day_ago = strtotime('-2 days');
    $nids = \Drupal::entityQuery('node')
      ->condition('created', $day_ago, '<=') 
      ->condition('type', 'booking')            
      ->condition('status', '0')      
      ->sort('nid','desc')
      ->execute();
      foreach ( $nids as $nid){
        $booking = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        if($booking->field_item && 
           $booking->field_item->entity ){
            $status = ($booking->moderation_state->value);
            if( $status == "init"){
                $site = $booking->field_item->entity ;
                $site->delete();
                $booking->delete();
                sleep(5);
            }
        }
        
   
      }
  
  }


 
  public function getPriceList($node){
    $dates = [];
    if(!is_object($node)){
       return  $dates ;
    }
    if($this->is_field_ready($node,'field_price')){
      $array = \Drupal::service('entity_parser.manager')->node_parser($node);
      if(!empty($array['field_price'])){
        $prices = $array['field_price'];
        $dates_default = $this->getIntervalDayForDefaultPrice($node);
        foreach ($prices as $price){
          $start = $price['field_date'][0]['value'];
          $end = $price['field_date'][0]['end_value'];
          $price = $price['field_price_value'];
          $date_item = $this->date_range($start,$end);
          $date_range[$start]=$price;
          $date_range[$end]=$price;
          foreach ($date_item as $el){
            $date_range[$el]=$price;
          }
          $dates = array_merge($date_range,$dates);
          
        }
      }

    }
    $dates = array_merge( $dates_default , $dates);
    // Custom comparison function to sort the array by keys
      $compareKeys = function ($a, $b) {
        return strtotime($a) - strtotime($b);
      };
      // Sort the array by keys
      uksort($dates, $compareKeys);

      foreach ($dates as $key => $price){
        if($price == null){
          $dates[$key] = $this->getPriceDefault($node);
        }
      }
    return $dates ;
  }
  public function getIntervalDayForDefaultPrice(Node $node){
      $price = $this->getPriceDefault($node) ;
          // Get the current date and time.
      // Get the current date and time.
      $currentDateTime = new DrupalDateTime();

      // Set the start date to be 3 months before today.
      $startDateTime = $currentDateTime->sub(new DateInterval('P6M'));

      // Reset the current date and time to get the original value.
      $currentDateTime = new DrupalDateTime();

      // Set the end date to be 3 months after today.
      $endDateTime = $currentDateTime->add(new DateInterval('P6M'));

      // Adjust the end date by subtracting 1 day to make it inclusive.
      $endDateTime->sub(new DateInterval('P1D'));

      // Output the start and end dates.
      $start = $startDateTime->format('Y-m-d'); 
      $end  = $endDateTime->format('Y-m-d');
      $date_item = $this->date_range($start,$end);
      $date_range[$start]=$price;
      $date_range[$end]=$price;
       foreach ($date_item as $el){
         $date_range[$el]= $price;
       }
       return  $date_range;
  }

  public  function is_field_ready($entity, $field) {
    $bool = FALSE;
    if (is_object($entity) && $entity->hasField($field)) {
      $field_value = $entity->get($field)->getValue();
      if (!empty($field_value)) {
        $bool = TRUE;
      }
    }
    return $bool;
  }
  public function getAddons($node){
    $addons =[];
    if($node == null){
      return   $addons ;
    }
    $bundle =  $node->bundle();
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'addons');
    $query->condition('field_type', $bundle );
    $query->condition('status','1', '=');
    $ids =  $query->execute();


    if(!empty($ids)){
        $tid = end($ids);
        $term_array = \Drupal::service('entity_parser.manager')->taxonomy_term_parser($tid);
        $array =  $term_array['field_addon_item'];
        foreach ($array as $array_item){
            if($array_item['type'] == 'addons'){
              if(isset($array_item["field_force_display"]) && $array_item["field_force_display"] == 1){
                $addons[] =  [
                  "name" => $array_item["field_name"],
                  "price" => $array_item["field_value"],
                  "type" => "addon_additional_price"
                ];
              }else{
                $addons[] =  [
                  "name" => $array_item["field_name"],
                  "price" => $array_item["field_value"],
                  "type" => "addons"
                ];
              }

            }
            if($array_item['type'] == 'addon_with_quantity'){
              $addons[] =  [
                "name" => $array_item["field_name"],
                "price" => $array_item["field_value"],
                "type" => "addon_with_quantity"
              ];
            }
            if($array_item['type'] == 'select_addons'){
              $select = [];
              $addonsElements = $array_item['field_addon_item'];
              foreach ($addonsElements as $add_item){
                $select[] =[
                    "name" => $add_item["field_name"],
                    "price" => $add_item["field_value"]
                  ];

              }
              $addons[] = [
                'name' =>  $array_item['field_name'],
                "type" => "select_addons",
                'list' =>  $select

              ];
            }


      }
      return ($addons);
    }
  }
  public function getPriceDefault($node){
    if(is_object($node)){
     return $node->field_default_price->value;
    }
    return false ;
  }
  public function getBookedDates($node){
    $bookedDates =[];
    if(!is_object($node)){
      return  $bookedDates ;
    }
    $nid =  $node->id();
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'booking');
    $query->condition('field_item', $nid );
    $query->addTag('moderation_state_tag'); //mz_booking_query_moderation_state_tag_alter
    $ids =  $query->execute();
   
    if(!empty($ids)){
      foreach ($ids as $nid){
        $node_array = \Drupal::service('entity_parser.manager')->node_parser($nid);
        $dates = $node_array['field_dates'];
        $start = $dates[0]["value"];
        $bookedDates[$start] = $start ;
        $end = $dates[0]["end_value"];
        $bookedDates = array_merge($this->date_range($start,$end),$bookedDates);
      }
    }
    return array_values($bookedDates);
  }
  function date_range($first, $last, $step = '+1 day', $output_format = 'Y-m-d' ) {
    $dates = array();
    $current = strtotime($first);
    $last = strtotime($last);
    while( $current <= $last ) {
      $dates[date($output_format, $current)] = date($output_format, $current);
      $current = strtotime($step, $current);
    }
    return $dates;
  }
  function getPriceUnitAddonWithQuantity($name){
    $bundle = 'article';
    $query = \Drupal::entityQuery('taxonomy_term')
    ->condition('vid', 'addons');
    $query->condition('field_type', $bundle );
  //  $query->condition('status','1', '=');
    $ids =  $query->execute();
    if(!empty($ids)){
       $tid = end($ids);
       $term_array = \Drupal::service('entity_parser.manager')->taxonomy_term_parser($tid);
       $array =  $term_array['field_addon_item'];
       foreach ($array as $array_item){
          if($array_item['type'] == 'addon_with_quantity' 
          && $array_item['field_name'] == $name){
            return $array_item["field_value"] ?? false ;
          }
       }
       
    }
    return false ;
  }
  public function notableDays($start,$end){
    //  $start = "2023-01-04";
    //  $end = "2023-01-13";
   //   $date = new DrupalDateTime($mydate);
    //  $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
    //  $formatted = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    //  kint($mydate);
      $ids = \Drupal::entityQuery('taxonomy_term')
      ->condition('field_dates.value',$start, '<=')   
      ->condition('field_dates.end_value',$end, '>') 
      ->range(0,1)
      ->condition('vid', 'notable_dates')
      ->execute();
      if(!empty($ids)){
          foreach($ids as $i){
              return \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($i)->label();
          }
      }
      return false ;
      
  }
  public function setPriceByExcel($file_id,$carid){
    $file = File::load($file_id);
    // $file->setPermanent();
    //  $file->save();
    $uri = $file->getFileUri(); 
     $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($uri);
      $file_path = $stream_wrapper_manager->realpath();
      if (file_exists($file_path)) {
          $phpexcel = \Drupal::service('phpexcel');
          $result = $phpexcel->import($file_path);

          $price = 0;
          $element = [];
          /// get end date
          foreach ($result[0] as $key => $item) {
              if ($key > 0 && $price != $item['Price_Day']) {
                  $p = $result[0][$key - 1];
                  $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($p['Date']);
                  $element[] = [
                      "date_end" => $date,
                      "value" => $p['Price_Day'],
                      "key_end" => $key - 1,
                  ];
              }
              $price = $item['Price_Day'];
          }

          /// get start date
          foreach ($element as $key => $item) {
              $p = $result[0][$item['key_end'] + 1];
              $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($p['Date']);
              $element[$key + 1]["date_start"] = $date;
              $element[$key + 1]["key_start"] = $item['key_end'] + 1;
          }
          //get peripherique
          foreach ($element as $key => $item) {
              if (!isset($item["key_start"])) {
                  $p = $result[0][0];
                  $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($p['Date']);
                  $element[$key]["date_start"] = $date;
                  $element[$key]["value"] = $p['Price_Day'];
              }
              if (!isset($item["key_end"])) {
                  $p = $result[0][sizeof($result[0]) - 1];
                  $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($p['Date']);
                  $element[$key]["date_end"] = $date;
                  $element[$key]["value"] = $p['Price_Day'];

              }
          }
          $fields['nid'] = $carid;
          foreach ($element as $key => $item) {
              $start = date('Y-m-d', $item['date_start']);
              $end = date('Y-m-d', $item['date_end']);
              $price = $this->notableDays($start, $end);
              if(!$price){
                  $price = "Normal";
              }
              $fields['field_price'][] = [
                  'field_price_value' => $item['value'],
                  'field_price' =>  $price,
                  'field_date' => [
                      'value' =>  $start,
                      'end_value' =>$end ,
                  ],
              ];
          }
          $fields['update_prices'] = 0 ;
          $booking_new = \Drupal::service('crud')->save('node', 'car', $fields);
          if (is_object($booking_new)) {
            \Drupal::messenger()->addMessage(t('Master price uploaded successfully  <a href="@link">Edit this car </a> ', ['@link' => '/node/'.$carid.'/edit']));
       
          } else {
              \Drupal::messenger()->addMessage(t('Failed to upload Master price <a href="@link">Edit this car </a>', ['@link' => '/node/'.$carid.'/edit']), 'warning');
      
          }
          $destination = '/node/'.$carid.'/edit';
          $response = new RedirectResponse(  $destination, 302);
          $response->send();  

      }
  }
  public function executePricesUpdate($nid){
        $path = '/admin/upload/price?destination=/node'.$nid.'edit&nid='.$nid;
        $response = new RedirectResponse($path, 302);
        $response->send();
        return;
  }
  public  function formatEmailRemind($entity,$body,$subject){
    $service = \Drupal::service('mz_payment.manager');
    $token_service = \Drupal::token();
    $subject = $service->tokenCustomBooking($entity,$subject);
    $subject  = $token_service->replace($subject ,  ['node'=>$entity]);
    $site_name = \Drupal::config('system.site')->get('name');
    $subject   = str_replace('[site:name]',    $site_name  , $subject  );

    $body = $service->tokenCustomBooking($entity,$body);
    $body  = $token_service->replace($body ,  ['node'=>$entity]);
    $site_name = \Drupal::config('system.site')->get('name');
    $body   = str_replace('[site:name]',    $site_name  , $body  );

    $uid = $entity->getOwnerId();
    $item_user = \Drupal\user\Entity\User::load($uid);
    $to  = $item_user->getEmail();
    \Drupal::service('mz_message.default')->send_mail_simple($body,$to,$subject);
  }
  public function reminderEmail(){
            $remind = \Drupal::config('reminder.config');
            $reminder_set = $remind->get('reminder_set');
            $body = $remind->get('reminder_body');
            $subject = $remind->get('reminder_subject');
            if($reminder_set && $reminder_set == 0){
              return false ;
            }
            // Get the current date and time (UTC)
            $current_date = new DrupalDateTime();
            $current_date->setTimezone(new \DateTimeZone('UTC'));

            // Calculate the date 2 days from now
            $two_days_from_now = clone $current_date;
            $two_days_from_now->modify('-'.$reminder_set.' days');

            // Convert the dates to database format
            $two_days_from_now_db = $two_days_from_now->format('Y-m-d');
            // Build the Node Query
            $query = \Drupal::entityQuery('node')
              ->condition('type', 'booking')
              ->condition('field_dates.value', '%'.$two_days_from_now_db.'%', 'LIKE')
              ->execute();
            if (!empty($query)) {
              $nodes = Node::loadMultiple($query);
              foreach ($nodes as $node) {
                    $this->formatEmailRemind($node,$body,$subject);
              }
            } else {
              \Drupal::logger('mz_booking')->notice('No bookings found within the next 2 days.');
            }
  }
  
}
