<?php

namespace Drupal\drupal_helper\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\FileStorage;
/**
 * Class ConfigImportForm.
 */
class ConfigImportForm extends FormBase {

//  /**
//   * {@inheritdoc}
//   */
  protected function getEditableConfigNames() {
    return [
      'drupal_helper.config_import',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
      $query = $this->getRequest()->query;
      $form_state->setMethod('GET');
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
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => 'Search',

      ];

      if($query->get('op')
          && $query->get('op')=='Search' && $query->get('path')){
          $path = $query->get('path');
          $config_path = DRUPAL_ROOT .$path;
          $config_source  = new FileStorage($config_path);
          $results = $this->readDirectory($config_path,'yml');
          $form['actions']['export'] = [
              '#type' => 'submit',
              '#value' => 'Import'
          ];

          foreach ($results as $key => $result) {
              $config_name = basename($result,'.yml') ;
              $output[] = [
                  'id' => $key+1 ,
                  'config_name' =>  $config_name
              ];
          }

          $form['table'] = array(
              '#type' => 'table',
              '#weight'=> 999,
              '#header' => $header,
              '#rows' => $output,
              '#empty' => $this->t('No variables found')
          );
      }
      if($query->get('op')
          && $query->get('op')=='Import' && $query->get('path')){
          $path = $query->get('path');
          $config_path = DRUPAL_ROOT .$path;
          $results = $this->readDirectory($config_path,'yml');
          foreach ($results as $key => $result){
              $config_name = basename($result,'.yml') ;
              $status = $helper->importConfig($config_name,$path);

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
    }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  public  function readDirectory($directory,$format = 'json')
    {
        $path_file = [];
        if (is_dir($directory)) {
            $it = scandir($directory);
            if (!empty($it)) {
                foreach ($it as $fileinfo) {
                    $element =  $directory . "/" . $fileinfo;
                    if (is_dir($element) && substr($fileinfo, 0, strlen('.')) !== '.') {
                        $childs = $this->readDirectory($element,$format);
                        $path_file = array_merge($childs , $path_file);
                    }else{
                        if ($fileinfo && strpos($fileinfo, '.'.$format) !== FALSE) {
                            if (file_exists($element)) {
                                $path_file[] =  $directory . "/" . $fileinfo;
                            }
                        }
                    }
                }
            }
        }else{
            drupal_set_message(t('No permission to read directory ' . $directory), 'error');
            @chmod($directory  , 0777);
        }
        return $path_file;
    }

}
