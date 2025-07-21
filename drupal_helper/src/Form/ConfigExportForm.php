<?php

namespace Drupal\drupal_helper\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
/**
 * Class ContentExportSettingForm.
 */
class ConfigExportForm extends FormBase {

//  /**
//   * {@inheritdoc}
//   */
  protected function getEditableConfigNames() {
    return [
      'drupal_helper.config_export',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
      $query = $this->getRequest()->query;
      $form_state->setMethod('GET');
      $form['key'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Config search'),
          '#attributes' => ['name' => 'key'],
          '#default_value' => ($query->get('key'))?$query->get('key'):''
      ];
      $form['path'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Config path'),
          '#attributes' => ['name' => 'path'],
          '#default_value' => ($query->get('path'))?$query->get('path'):''
      ];
      $header = [
          'number' =>  t('Number'),
          'config_name' => t('Config name')
      ];
      $output = [];
      $helper = \Drupal::service('drupal.helper')->helper;
      $filter = 'basic' ;
      if($query->get('key')){
          $filter = $query->get('key');
      }
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Search'),

      ];
      $form['actions']['export'] = [
          '#type' => 'submit',
          '#value' => 'Export'
      ];

      $results = $helper->getConfigContains($filter);
      foreach ($results as $key => $result) {
          $output[] = [
              'id' => $key+1 ,
              'config_name' =>  $result
          ];

      }
      $form['table'] = array(
          '#type' => 'table',
          '#weight'=> 999,
          '#header' => $header,
          '#rows' => $output,
          '#empty' => $this->t('No variables found')
      );
      if($query->get('op')
          && $query->get('op')=='Export' && $query->get('path')){
          $path = $query->get('path');
          foreach ($results as $key => $result){
              $helper->exportConfig($result,$path);
          }


      }
    return $form ;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
    public function submitExport(array &$form, FormStateInterface $form_state)
    {

        $values = $form_state->getValues();
        kint($values);
    }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
