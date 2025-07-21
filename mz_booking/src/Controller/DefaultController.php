<?php

namespace Drupal\mz_booking\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
/**
 * Class ApiController.
 */
class DefaultController extends ControllerBase
{
  public function order()
  {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('You need to setup your bank account')
    ];
  }
    public function car(Request $request, $count)
    {
        $results = [];

        // Get the typed string from the URL, if it exists.
        if ($input = $request->query->get('q')) {
            $typed_string = Tags::explode($input);
            $typed_string = mb_strtolower(array_pop($typed_string));
            // @todo: Apply logic for generating results based on typed_string and other
            // arguments passed.
        

            $orGroup = \Drupal::entityQuery('node')->orConditionGroup()
            ->condition('title', '%' . $typed_string . '%', 'like')   
            ->condition('title', $typed_string . '%', 'like')    
            ->condition('title',  '%'. $typed_string , 'like') ;
        
            $ids = \Drupal::entityQuery('node')
            ->range(0,$count)
            ->condition($orGroup)
            ->condition('type', 'car')
            ->execute();
    
            $results = [];

            foreach ($ids as $key => $item) {
                $item_array = \Drupal::service('entity_parser.manager')->node_parser($item);
                $markeup = '<div class="user-client">';
                $markeup .= '<b>Nom : </b>' . $item_array['title'] . '<br/>';
                $markeup .= '</div>';
                $results[] = [
                    "value" => $item_array['title'] . '(' . $item . ')',
                    "label" => $item_array['title'],
                    "markup" => $markeup,
                ];
            }
    
        }
      
        return new JsonResponse($results);
    }
    public function saveBooking(){
        $service = \Drupal::service('drupal.helper');
        $service_booking = \Drupal::service('mz_booking.manager');
        $params = $service->helper->get_parameter();    
        $random = time() . rand(10*45, 100*98);
        $fields['title'] = $random ;
        $fields['field_price_default'] = $params['total'] ;
        $fields['field_item'] = $params['id'] ;
        $uid = \Drupal::currentUser()->id();
        $fields['field_client'] =   $uid;
       // $fields['field_payement_method'] = $params['payment_method'] ;
        $date = explode(' - ',$params['booking-range-date']) ;
        $fields['field_dates'][] = [
          'value' =>  $date[0],
          'end_value' =>  $date[1]
        ];
        $fields['body'] = $this->insertAddonsToBooking($params)['table'];
 
        $booking_new = \Drupal::service('crud')->save('node', 'booking', $fields);
        
        //$service_helper->helper->redirectTo('/confirmation');
    }
    protected function insertAddonsToBooking( $params){
        $service_booking = \Drupal::service('mz_booking.manager');
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
           && isset($params[$key."_value"]) 
           && $key == 'nid'
           ) {
            
                $name = str_replace("_", " ", $params[$key]);
                $price = $params[$key."_value"];
                $price_result =    $service_booking->getPrice($price);
                $type = $params_old[$params[$key].'__type'];
                $price_amount = $price_result[1];
                $subtotal  =  $subtotal  +  floatval($price_amount) ;
                $qty = 1 ;
                if($type == 'addon_with_quantity'){
                  $price_unit =    $service_booking->getPriceUnitAddonWithQuantity($name) ;
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

        $table .= '<tr>';
        $table .= '<td colspan="2" > <b>Sub Total</b></td>';
        $table .= '<td colspan="2">' .        $subtotal  . ' $ </td>';
        $table .= '</tr>';
    
        $table .= '<tr>';
        $table .= '<td colspan="2" > <b>Total</b></td>';
        $table .= '<td colspan="2">' .   $params['total'] . ' $ </td>';
        $table .= '</tr>';
        $table .= '</table>';
        return ['table' => $table , 'array'=> $array_addons] ;
      }

}
