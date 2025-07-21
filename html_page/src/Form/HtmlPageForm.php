<?php

namespace Drupal\html_page\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContentExportSettingForm.
 */
class HtmlPageForm extends FormBase
{

//  /**
//   * {@inheritdoc}
//   */

  public function getFormId() {
    return 'html_page_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $parameter = NULL) {
    $form['parameter'] = [
      '#markup' => $this->t('Parameter value: @parameter', ['@parameter' => $parameter]),
    ];

    // Add other form elements here.

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle form submission.
  }
    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {

    }

}
