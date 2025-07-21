<?php

namespace Drupal\mz_payment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Edit config variable form.
 * Permission : settings payments
 */
class BankSetupForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'bank_setup_form';
    }
    /**
     * Form submission handler for the additional submit button.
     */
    function _reset_submit_handler($form, FormStateInterface $form_state) {
      $config = \Drupal::configFactory()->getEditable('mz_payement.stripe')
            ->delete()
            ->save();
    }
        /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $config_name = '')
    { 

      $config = \Drupal::config('stripe.settings');
      $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
      \Stripe\Stripe::setApiKey($apikeySecret);
      $config = \Drupal::config('mz_payement.stripe');
      $accountId = $config->get('account');
      if($accountId){
        $str = '<div >';
        $str = $str .'<p> Your bank account has been successfully configured, enabling you to receive payments from your customers. You are now ready to accept payments seamlessly. </p>' ;
        $str = $str . '</div>';
        $form['info'] = [
          '#type' => 'markup',
          '#markup' =>  $str ,
          '#weight' => -100,
        ];
        
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Click here Update your bank information')
        ];
          // Add another submit button.
        $form['actions']['submit_reset'] = [
          '#type' => 'submit',
          '#value' => t('Reset Account'),
          '#submit' => ['::_reset_submit_handler'], // Define the submission handler for this button
        ];
      } else{
        $form['mail'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Email setup for payment'),
          '#required' => TRUE
        ];
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Submit to start set up')
        ];
       
      } 
      return $form ;
    }
    // public function selectChanged(array &$form, FormStateInterface $form_state) {
    //     // Perform actions based on the selected value.
    // $selectedValue = $form_state->getValue('select_field');
    // $form['refund_amount']['#value'] = $selectedValue;
    // return $form['refund_amount'];
    // }


    protected function accountCreate( $email){

      $config = \Drupal::config('stripe.settings');
      $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
      \Stripe\Stripe::setApiKey($apikeySecret);
      global $base_url;
      try {
      $subaccount= \Stripe\Account::create([
        'country' => 'US',
        'type' => 'express',
        'email' =>  $email,
        'capabilities' => [
          'card_payments' => ['requested' => true],
          'transfers' => ['requested' => true],
        ]
      ]);
      if($subaccount->id){
        $accountId = $subaccount->id;
        \Drupal::configFactory()->getEditable('mz_payement.stripe')
        ->set('account',  $accountId)
        ->set('status',  'pending')
        ->set('mail',   $email)
        ->save();
        $accountLink = \Stripe\AccountLink::create([
          'account' =>  $accountId ,
          'refresh_url' => $base_url.'/admin/bank/setup',
          'return_url' => $base_url.'/admin/bank/setup',
          'type' => 'account_onboarding'
        ]);
        header("Location: " . $accountLink->url);
        exit();
      }
    }
    catch (\Stripe\Exception\ApiErrorException $e) {
        // Handle errors

        $message =  'Error create account: ' . $e->getMessage();
        \Drupal::logger('mz_payement')->error($message);
    } 
  
    }

    protected function accountReset($accountId){
      $config = \Drupal::config('stripe.settings');
      $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
      \Stripe\Stripe::setApiKey($apikeySecret);
      global $base_url;
      $accountLink = \Stripe\AccountLink::create([
        'account' =>  $accountId ,
        'refresh_url' => $base_url.'/admin/bank/setup',
        'return_url' => $base_url.'/admin/bank/setup',
        'type' => 'account_onboarding'
        ]);
      //   Redirect the user to the generated URL
      header("Location: " . $accountLink->url);
      exit();
    }
    protected function accountExist($accountId){
      $config = \Drupal::config('stripe.settings');
      $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
      \Stripe\Stripe::setApiKey($apikeySecret);
      global $base_url;
      try {
          $account = \Stripe\Account::retrieve($accountId);
          $status  = ($account->requirements->currently_due);
      } catch (\Stripe\Exception\ApiErrorException $e) {
      // Handle API errors
         $message =  'Error retrieving account: ' . $e->getMessage();
         \Drupal::logger('mz_payement')->error($message);
      }
      if( empty($status) ){
        $config = \Drupal::config('mz_payement.stripe');
        $status = $config->get('status');
        $email = $config->get('mail');
        if( $status == "complet"){
        }else{
          \Drupal::configFactory()->getEditable('mz_payement.stripe')
          ->set('status',  'complet')
          ->save();
        }
        $str = '<ul>';
        $str = $str .'<li><b> Stripe account id :</b> '.$accountId.'</li>' ;
        $str = $str .'<li><b> Mail :</b> '.$email.'</li>' ;
        $str = $str . '</ul>';
        return  [
          '#type' => 'markup',
          '#markup' =>  $str ,
          '#weight' => -100,
        ];

      }else{
        $accountLink = \Stripe\AccountLink::create([
         'account' =>  $accountId ,
         'refresh_url' => $base_url.'/admin/bank/setup',
         'return_url' => $base_url.'/admin/bank/setup',
         'type' => 'account_onboarding'
         ]);
       //   Redirect the user to the generated URL
       header("Location: " . $accountLink->url);
       exit();
      }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
      $values = $form_state->getValues();
      if($values['mail']){
        $this->accountCreate($values['mail']);
      }else{
        $config = \Drupal::config('stripe.settings');
        $apikeySecret = $config->get('apikey.' . $config->get('environment') . '.secret');
        \Stripe\Stripe::setApiKey($apikeySecret);
        $config = \Drupal::config('mz_payement.stripe');
        $accountId = $config->get('account');
        $this->accountReset($accountId);
      }





    }

}
