<?php

namespace Drupal\mz_payment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Edit config variable form.
 */
class SettingsPayment extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'settings_payment_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $config_name = '')
    {
        $config = $this->config('mz_payment.config');
        $form['price'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Price per mounth of the booking system'),
          '#default_value' => $config->get('price') ?? ''
        ];
        $form['price_year'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Price per year of the booking system'),
            '#default_value' => $config->get('price_year') ?? ''
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
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
//      $carid = $form_state->getValue('carid');
//      $carid = $this->getIdCar($carid);
//      $car = \Drupal::entityTypeManager()->getStorage('node')->load($carid);
//      if (!is_object($car)) {
//        $form_state->setError($form['carid'], $this->t('Car id is not exist'));
//      }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

      $this->configFactory()->getEditable('mz_payment.config')
        ->set('price', $form_state->getValue('price'))
        ->set('price_year', $form_state->getValue('price_year'))
        ->save();
    }



}
