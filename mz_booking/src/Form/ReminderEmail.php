<?php

namespace Drupal\mz_booking\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Edit config variable form.
 */
class ReminderEmail extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'reminder';
    }
    public function buildForm(array $form, FormStateInterface $form_state, $config_name = ''){
        $config = $this->config('reminder.config');
        $reminder_body = $config->get('reminder_body');
         $reminder_subject = $config->get('reminder_subject');
        $reminder_set = $config->get('reminder_set');
        $form['reminder_subject'] = [
            '#type' => 'textfield',
            '#title' => t('Email Subject'),
            '#default_value' => ($reminder_subject),
        ];
        $form['reminder_body'] = [
            '#type' => 'textarea',
            '#title' => t('Email Content'),
            '#default_value' => ($reminder_body),
        ];
        $form['reminder_set'] = [
            '#type' => 'radios',
            '#options' => [
              "0" => $this->t('Disable remind Email'),
              "2" => $this->t('Remind Owner Booking 2 days before'),
              "3" => $this->t('Remind Owner Booking 3 days before')
            ],
            '#default_value' => ($reminder_set),
        ];
        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
        ];
        return $form;
    }
    /**
     * {@inheritdoc}
     */
    // public function buildForm(array $form, FormStateInterface $form_state, $config_name = '')
    // {

    //    // Create a vertical tabs container.
    // $form['tabs'] = [
    //     '#type' => 'vertical_tabs'
    //   ];
  
    //   // First tab.
    //   $form['tab_one'] = [
    //     '#type' => 'details',
    //     '#title' => $this->t('Reminder Automatic'),
    //     '#group' => 'tabs',
    //     '#open' => TRUE,
    //   ];
  
   
  
    //   // Second tab.
    //   $form['tab_two'] = [
    //     '#type' => 'details',
    //     '#title' => $this->t('Reminder Manual'),
    //     '#group' => 'tabs',
    //     '#open' => FALSE,
    //   ];
  
    //   $form['tab_two']['booking_id'] = [
    //     '#type' => 'textfield',
    //     '#title' => $this->t('Booking ID'),
    //     '#description' => $this->t('If selected cool message will be used for drupal messages.'),
    //     '#required' => FALSE,
    //   ];
  
  
    //     $config = $this->config('reminder.config');
    //     $reminder_body = $config->get('reminder_body');
    //     $reminder_subject = $config->get('reminder_subject');
    //     $reminder_set = $config->get('reminder_set');
    //     $form['tab_one']['reminder_subject'] = [
    //         '#type' => 'textfield',
    //         '#title' => t('Email Subject'),
    //         '#default_value' => ($reminder_subject),
    //     ];
    //     $form['tab_one']['reminder_body'] = [
    //         '#type' => 'textarea',
    //         '#title' => t('Email Content'),
    //         '#default_value' => ($reminder_body),
    //     ];
    //     $form['tab_one']['reminder_set'] = [
    //         '#type' => 'radios',
    //         '#options' => [
    //           "0" => $this->t('Disable remind Email'),
    //           "2" => $this->t('Remind Owner Booking 2 days before'),
    //           "3" => $this->t('Remind Owner Booking 3 days before')
    //         ],
    //         '#default_value' => ($reminder_set),
    //     ];
    //     $form['actions'] = ['#type' => 'actions'];
    //     $form['actions']['submit_secondary'] = [
    //         '#type' => 'submit',
    //         '#value' => $this->t('Send Email'),
    //         '#submit' => ['::secondarySubmitForm'],
    //       ];
    //     $form['actions']['submit'] = [
    //         '#type' => 'submit',
    //         '#value' => $this->t('Save'),
    //     ];
        
    //     return $form;
    // }


  /**
   * Secondary submit handler.
   */
//   public function secondarySubmitForm(array &$form, FormStateInterface $form_state) {
//     $book_id = $form_state->getValue('booking_id');
//     if(is_numeric( $book_id)){
//         $entity = \Drupal::entityTypeManager()->getStorage('node')->load($book_id);  
//         $remind =   $this->config('reminder.config');
//         $body = $remind->get('reminder_body');
//         $subject = $remind->get('reminder_subject');
//         $service_booking = \Drupal::service('mz_booking.manager');
//         $service_booking->formatEmailRemind($entity,$body,$subject);
//     }

//   }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

       $this->configFactory()->getEditable('reminder.config')
       ->set('reminder_subject', $form_state->getValue('reminder_subject'))
       ->set('reminder_body', $form_state->getValue('reminder_body'))
       ->set('reminder_set', $form_state->getValue('reminder_set'))
       ->save();
    }
}
