<?php

namespace Drupal\drupal_helper\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContentExportSettingForm.
 */
class GitRepoForm extends FormBase {

//  /**
//   * {@inheritdoc}
//   */
  protected function getEditableConfigNames() {
    return [
      'drupal_helper.gitrepo',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_gitrepo_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('drupal_helper.gitrepo');
    $respo_list = $config->get('list');
    $count = 1 ;
    $myresp = [];
    if(   $respo_list){
        $count = $respo_list['count'];
        $myresp = array_values($respo_list['list']);
    }
        // Initialize the links array if not set.
        if (!$form_state->has('links_count')) {
            $form_state->set('links_count',    $count);
          }
      
          $links_count = $form_state->get('links_count');
          $form['token'] = ['#type' => 'password',  '#required' => TRUE,     '#title' => 'Token', '#default_value' => isset($respo_list['token'])? $respo_list['token']:''];
          // Add the link fields.
          $form['links_fieldset'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Git Repositories'),
          ];
      
          for ($i = 0; $i < $links_count; $i++) {
            $form['links_fieldset']['text_' . $i] = [
                '#type' => 'textfield',
                '#title' => 'Machine name',
                '#default_value' =>   ($myresp[$i]['name'])?$myresp[$i]['name']:'',      
                '#required' => TRUE,
                '#prefix' => '<div id="link_element_git">'
              ];
             $form['links_fieldset']['path_' . $i] = [
                '#type' => 'textfield',
                '#title' => 'Path',
                '#default_value' =>   ($myresp[$i]['path'])?$myresp[$i]['path']:'',
                '#required' => TRUE,
                '#description' => '/themes/mytheme',
              ];  
            $form['links_fieldset']['link_' . $i] = [
              '#type' => 'url',
              '#title' => 'Repository link ',
              '#description' => 'https://miandry@bitbucket.org/miandry/greenvanilla.git',
              '#default_value' =>  ($myresp[$i]['repo'])?$myresp[$i]['repo']:'',
              '#required' => TRUE,
              '#suffix' => '</div>'
        
            ];

          }
      
          // Add button to add another link.
          $form['add_link'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add another link'),
            '#submit' => ['::addMoreLink'],
            '#ajax' => [
              'callback' => '::addMoreCallback',
              'wrapper' => 'links-fieldset-wrapper',
            ],
          ];
      
          // Wrap the links fieldset for AJAX.
          $form['links_fieldset']['#prefix'] = '<div id="links-fieldset-wrapper">';
          $form['links_fieldset']['#suffix'] = '</div>';
          $form['count'] = ['#type' => 'hidden', '#value' => $links_count];
          // Add a submit button.
          $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
          ];
      
      

    return $form ;
  }
    /**
   * Submit handler for adding more links.
   */
  public function addMoreLink(array &$form, FormStateInterface $form_state) {
    $links_count = $form_state->get('links_count');
    $form_state->set('links_count', $links_count + 1);
    $form_state->setRebuild();
  }
    /**
   * AJAX callback for adding more links.
   */
  public function addMoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['links_fieldset'];
  }



  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
    public function submitExport(array &$form, FormStateInterface $form_state)
    {

  
    }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $respo_list['list'] = [];
    $respo_list['count'] = $values['count'];
    $respo_list['token'] = $values['token'];
    for ($i = 0; $i < $values['count']; $i++) {
        $respo_list['list'][$values['text_'.$i]] = [
            'name' => $values['text_'.$i],
            'repo' => $values['link_'.$i],
            'path' => $values['path_'.$i]
        ];
    
    }
    $status = \Drupal::configFactory()->getEditable('drupal_helper.gitrepo')
    ->set('list',  $respo_list)
    ->save();
  }

}
