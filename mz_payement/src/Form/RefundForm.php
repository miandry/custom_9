<?php

namespace Drupal\mz_payment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Edit config variable form.
 */
class RefundForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'refund_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $config_name = '')
    { 
      $id = \Drupal::request()->query->get('nid');
      $str = '';
      if($id){
        $parser =  \Drupal::service('entity_parser.manager') ;
        $booking_object = \Drupal::entityTypeManager()->getStorage('node')->load($id);
        $booking = $parser->node_parser($booking_object);
        $str = $str.'<ul>';
        // $str = $str .'<li><b>Charge Stripe id : </b>'. $booking['title'].'</li>' ;
        if(isset($booking['field_item']['title'])){
          $str = $str .'<li><b>Item : </b>'. $booking['field_item']['title'].'</li>' ;
        }
        if(isset($booking['field_price_default'])){
        $str = $str .'<li><b>Booking amount : </b> $ '. $booking['field_price_default'].' </li>' ;
        }
        if(isset($booking['mz_payment_refund'])){
          $str = $str .'<li><b>Refunded Booking  </b> $ '. $booking['mz_payment_refund'].' </li>' ;
        }
        $str = $str . '</ul>';
        $form['booking_id'] = array(
          '#type' => 'hidden',
          '#value' => $id , 
        );
        $form['refund_amount'] = [
          '#suffix' => '</div>',
          '#prefix' => '<div id="ajax-result-wrapper">',
          '#type' => 'textfield',
          '#title' => t('Refund amount ( $ )'),
          '#required' => true ,
          '#description' => $this->t('Enter the amount you wish to refund. If you do not want to issue a refund, please enter 0.'),
          '#size' => '30',
        ];
      }else{
        $form['booking_id'] = [
          '#type' => 'textfield',
          '#title' => t('ID Booking'),
          '#required' => true ,
          '#size' => '15',
        ];
        $form['refund_amount'] = [
          '#suffix' => '</div>',
          '#prefix' => '<div id="ajax-result-wrapper">',
          '#type' => 'textfield',
          '#title' => t('Refund amount ( $ )'),
          '#required' => true ,
          '#description' => $this->t('Entre the amount you want to add in existing amount'),
          '#size' => '30',
        ];
      }  

  
        $form['information'] = [
          '#type' => 'markup',
          '#markup' =>  $str ,
          '#weight' => -100,
        ];
        $form['reason'] = [
          '#type' => 'textfield',
          '#title' => t('Reason')
        ];
        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => t('Submit'),
          '#attributes' => array('onclick' => 'if(!confirm("Are you sure to refund this amount?")){return false;}')
        );

        return $form;
    }
 

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
     $amount= $form_state->getValue('refund_amount');
     $id= $form_state->getValue('booking_id');
     $booking_object = \Drupal::entityTypeManager()->getStorage('node')->load($id);
     if(!is_object($booking_object)){
       $form_state->setError($form['booking_id'], $this->t('Booking Id not exist'));
     }
     if(is_object($booking_object)){
      $bundle = $booking_object->bundle();
      if( $bundle !='booking'){
       $form_state->setError($form['booking_id'], $this->t('Booking Id not exist'));
      }
      $field_price_default = $booking_object->field_price_default->value ;
      if (!is_numeric($amount)) {
        $form_state->setError($form['refund_amount'], $this->t('Amount must be numeric'));
      }
      if(floatval($field_price_default) < floatval($amount)){
        $form_state->setError($form['refund_amount'],$this->t('Refund Amount is limited to $%price', ['%price' => $field_price_default]));
      }
    }

    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
      $values = $form_state->getValues();
      $amount = $values['refund_amount'];
      $id = $values['booking_id'];
      $reason = $values['reason'];
      $booking_object = \Drupal::entityTypeManager()->getStorage('node')->load($id);
      $uid = $booking_object->getOwnerId();
      $user = \Drupal\user\Entity\User::load($uid);
      if (!is_null($user)) {
          $username = $user->getDisplayName() ;
      }
      $service_booking = \Drupal::service('mz_payment.booking');
      $status = $service_booking->executeRefundBooking($booking_object,$amount,$reason);
      if($status){
        \Drupal::messenger()->addMessage($this->t('Booking refund process of %client was successfully', array('%client' =>  $username )));
        $service = \Drupal::service('mz_payment.manager');
        $service->refundPaymentSendEmail($booking_object);
      }else{
        \Drupal::messenger()->addMessage('Failed to refund booking , please contact your webmaster', 'error');
      }
      $url = Url::fromUri('internal:/admin/my-refunds' );
      $form_state->setRedirectUrl($url);


    }

}
