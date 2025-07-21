<?php

namespace Drupal\mz_payment;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;

class BookingPaymentService extends  PaymentService {
    public function __construct() {
        parent::__construct();
    }

    public function saveBookingPayement( $charge_id, $params ) {
        $fields[ 'title' ] = $charge_id;
        if ( !isset( $params[ 'booking_id' ] ) ) {
            \Drupal::logger( 'mz_payment' )->error( 'Booking Id not exist in payment' );
            return;
        }
        $fields[ 'nid' ] = $params[ 'booking_id' ] ;
        $fields[ 'moderation_state' ] = 'draft';
        $booking_payed = \Drupal::service( 'crud' )->save( 'node', 'booking', $fields );

        $payements[ 'title' ] = $charge_id ;
        $payements[ 'mz_payment_amount' ] = $params[ 'amount' ] ;
        $payements[ 'mz_payment_item' ] = $params[ 'booking_id' ] ;
        $payements[ 'mz_payment_status' ] = $params[ 'in_validation' ] ;
        $payements[ 'status' ] = 0 ;
        \Drupal::service( 'crud' )->save( 'node', 'mz_payment', $payements );
        $service = \Drupal::service( 'drupal.helper' );
        $service->helper->storage_delete( 'booking_info' );
        $service->helper->storage_delete( 'booking_info_process' );
        $url  = '/user/bookings?new='.$booking_payed->id();
        $response = new RedirectResponse( $url, 302 );
        $response->send();
        return;
    }

    public function txtEmailNewWebsite( $name ) {
        $account = $this->findUserByUser( $name );
        if ( !is_object( $account ) ) {
            \Drupal::messenger()->addError( t( 'The user %email does not exist.', [ '%email' => $name ] ) );
            return false ;
        }
        $config = \Drupal::config( 'user.mail' );
        $body = $config->get( 'email_new_website.body' );
        $subject = $config->get( 'email_new_website.subject' );
        $timestamp = \Drupal::time()->getRequestTime();
        $url = user_pass_reset_url( $account, $timestamp );
        $body = str_replace( '[user:one-time-login-url]', $url, $body );

        $token_service = \Drupal::token();
        // Prepare the replacements for the tokens.
        $data = [ 'user' => $account ];
        $options = [ 'clear' => TRUE ];
        // Replace the tokens in the email template.
        $email_body = $token_service->replace( $body, $data, $options );
        $email_subject = $token_service->replace( $subject, $data, $options );
        return  [ 'subject' =>   $email_subject, 'body' => $email_body ]  ;
    }
    // in client web site inside

    public function staydirectPaymentInit( $params ) {
        global $site_variables;
        \Drupal::configFactory()->getEditable( 'mz_payement.status' )
        ->set( 'status', 'start_payment' )
        ->set( 'site_variables', $site_variables )
        ->save();
        $enable = 1;
 
        
        // delete demo user un template
        $service = \Drupal::service('mz_staydirect.manage');
        $service->deleteDemoUser();
        $service->setPermissionOfSite();
                   
        \Drupal::state()->set('system.maintenance_mode', $enable );
        drupal_flush_all_caches();
        $path = $params[ 'parent' ].'/pay?site_id='.$params[ 'site_id' ];
        $response = new RedirectResponse( $path, 302 );
        $response->send();
        return;

    }
    // in client web site inside

    public function staydirectAfterPayment( $params ) {
        \Drupal::configFactory()->getEditable( 'mz_payement.status' )
        ->set( 'status', 'in_process' )
        ->save();
        drupal_flush_all_caches();
        $string_para = '';
        $url = $params[ 'parent' ];
        unset( $params[ 'parent' ] );
        foreach ( $params as $key => $p ) {
            $string_para =  $string_para.$key.'='.$p.'&' ;
        }
        $path = $url.'/stripe/success?'. $string_para;
        $response = new RedirectResponse( $path, 302 );
        $response->send();
        return;

    }

    function findUserByUser( $name ) {
        // Query for the user by email.
        $user_query = \Drupal::entityQuery( 'user' )
        ->condition( 'name', $name )
        ->range( 0, 1 );
        // Limit to 1 result

        // Execute the query and get the user IDs.
        $uids = $user_query->execute();

        // If a user is found, load and return the user entity.
        if ( !empty( $uids ) ) {
            $uid = reset( $uids );
            // Get the first ( and only ) UID.
            return User::load( $uid );
            // Load and return the user entity.
        }

        // Return FALSE if no user was found.
        return FALSE;
    }

    // in client web site inside

    public function staydirectAfterPaymentConfirmed( $params ) {
        global $site_variables;
        global $base_url ;
        if ( isset( $site_variables[ 'email' ] ) ) {
            $status = \Drupal::config( 'mz_payement.status')->get( 'status' );
            if ( $status != 'confirmed' ) {
                $service_helper = \Drupal::service( 'drupal.helper' );

                \Drupal::configFactory()->getEditable( 'mz_payement.status' )
                ->set( 'status', 'confirmed' )
                ->save();

                \Drupal::state()->set( 'system.maintenance_mode', 0 );
              
                $to = $site_variables[ 'email' ];
                $username = $site_variables[ 'username' ];
                $site_label = $site_variables['site_label'];
                // Set new values for multiple site configurations.
                $config = \Drupal::configFactory()->getEditable('system.site');
                $config->set('name', $site_label)                // Set the site name.
                       ->set('mail', $to )                   // Set the site email address.
                       ->save(); 
   
                $txt = $this->txtEmailNewWebsite( $username );
                $subject = $txt[ 'subject' ];
                $body =  $txt[ 'body' ];
                // 'Your payment has been confirmed. Please proceed to log in to your temporary account . <br/> <strong> For security , Please Update your password </strong><br/> username: admin <br/> Temporary password : '.$password ."<br/> <a href='".$base_url."/user/login?destination=/admin/bank/setup'>Click to Login My Site web </a><br/> Thanks to choose your platorm";
                \Drupal::service( 'mz_email.default' )->sendMail( $to, $subject, $body );

            }
        }

        \Drupal::configFactory()->getEditable( 'mz_payement.stripe' )
        ->delete()
        ->save();

        \Drupal::state()->set( 'system.maintenance_mode', 0 );
        drupal_flush_all_caches();
        $path = $params[ 'parent' ].'?status=ok';
        $response = new RedirectResponse( $path, 302 );
        $response->send();
        return;

    }

    function executeRefundBooking(object $booking_object, $amount,$reason = ''){
         
        $service = \Drupal::service('mz_payment.manager');
        $pay = $booking_object->field_payment_intent_id->value ;
        if($amount > 0 ){
          $status = $service->paymentRefundCheckout($pay,$amount);
        }else{
          $status = true ;
        }
        if($status){
          $booking_object->moderation_state->value = "published" ;
          if($booking_object->mz_payment_refund->value){
            $amount =  floatval($booking_object->mz_payment_refund->value) + $amount ;
          }
          if($booking_object->refund_reference){
            $fields['nid'] =  $booking_object->refund_reference->target_id  ;
          }
          $fields['title'] =   $status ;
          $fields['field_item'] = $booking_object->id() ;
          $fields['mz_payment_refund'] = $amount ;
          $fields['field_reason'] =    $reason ;
          $ref =  \Drupal::service('crud')->save('node', 'refund', $fields);

          if(is_object($ref)){
            $booking_object->mz_payment_refund->value = $amount ;  
            $booking_object->refund_reference->target_id = $ref->id() ;   
            return $booking_object->save();
          }
          return false ;
        }else{
          return false ;
        }
    }
}

