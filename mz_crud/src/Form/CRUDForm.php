<?php

namespace Drupal\mz_crud\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 */
class CRUDForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mz_crud.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mz_crud_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['default_hook_alias'] = [
      '#type' => 'textarea',
      '#title' => $this->t('IMPORT JSON ENTITY'),
      '#description' => $this->t('<strong>This setion is still in developpement process </strong>'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // $this->config('entity_parser.config')
    //      ->set('default_hook_alias', $form_state->getValue('default_hook_alias'))
    //      ->save();
  }

}
