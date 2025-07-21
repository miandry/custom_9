<?php


namespace Drupal\html_page\Form;


use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Config\FileStorage;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Edit config variable form.
 */
class HtmlPageInsert extends FormBase {

  /**
   * {@inheritdoc}
   */
  private $conf = '' ;
  public function getFormId() {
    return 'html_page_insert_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name machine'),
      '#required' => TRUE,
    );
    $form['alias'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URl alias'),
      '#required' => TRUE,
    );
    $form['content'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('HTML New Page'),
      '#rows' => 24,
      '#required' => TRUE,
      '#format' => 'code_html',
      '#description' => $this->t('create format : code_html ')
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->buildCancelLinkUrl(),
    );
    

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $alias = $form_state->getValue('alias');
    $path_alias_repository = \Drupal::service('path_alias.repository');
    if ($path_alias_repository->lookupByAlias($alias, 'en')) {
      $form_state->setErrorByName('html_page', $this->t('Alias Exit already '));
    }
    $id = $form_state->getValue('id');
    $service = \Drupal::service('html_page.manager');
    if (!$service->isDrupalMachineName($id)) {
      $form_state->setErrorByName('html_page', $this->t('Special character can not be id'));
    }
    $config = \Drupal::config('html_page.list') ;
    $data = $config->get();
    foreach($data as $key => $item){
       if($key==$id){
          $form_state->setErrorByName('html_page', $this->t('ID Exit already '));
       }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $service = \Drupal::service('html_page.manager');
    $service->saveData( $values );



  }

  /**
   * Builds the cancel link url for the form.
   *
   * @return Url
   *   Cancel url
   */
  private function buildCancelLinkUrl() {
    $query = $this->getRequest()->query;

    if ($query->has('destination')) {
      $options = UrlHelper::parse($query->get('destination'));
      $url = Url::fromUri('internal:/' . $options['path'], $options);
    }
    else {
      $url = Url::fromRoute('html_page.pages');
    }

    return $url;
  }

}
