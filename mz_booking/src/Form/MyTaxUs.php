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
class MyTaxUs extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'tax_us';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $config_name = '')
    {

     
        $config = $this->config('tax_us.config');
        $tax_us = $config->get('tax_us');
        $form['tax_us'] = [
            '#type' => 'textarea',
            '#title' => t('Tax json'),
            '#default_value' => ($tax_us),
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
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
       $this->configFactory()->getEditable('tax_us.config')
       ->set('tax_us', $form_state->getValue('tax_us'))
       ->save();
    }


}
