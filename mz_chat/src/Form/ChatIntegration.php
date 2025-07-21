<?php

namespace Drupal\mz_chat\Form;


use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Edit config variable form.
 */
class ChatIntegration extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'chat_create_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $config_name = '')
    {

        $config = $this->config("chat.integration");
        $output = ($config) ? $config->get('source') : '';
        $status = ($config) ? $config->get('status') : 0;
        $form['source'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Script'),
            '#default_value' => $output,
            '#rows' => 24,
            '#required' => FALSE
        );
        $form['status'] = array(
            '#type' => 'checkbox',
            '#title' => t('Enable Chat'),
            '#default_value' =>  $status
        );
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
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->configFactory()->getEditable('chat.integration')
            ->set('source', $form_state->getValue('source'))
            ->set('status', $form_state->getValue('status'))
            ->save();
    }

}
