<?php


namespace Drupal\html_page\Form;


use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Edit config variable form.
 */
class HtmlPageEditor extends FormBase {

  /**
   * {@inheritdoc}
   */
  private $conf = '' ;
  public function getFormId() {
    return 'html_page_edit_form';
  }

  /**
   * {@inheritdoc}
   */
   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

$route_match = \Drupal::routeMatch();
$id = $route_match->getParameter('id');
$service = \Drupal::service('html_page.manager');
$data = $service->loadData($id);
    $form['id'] = array(
      '#type' => 'textfield',
      '#attributes' => ['readonly' => 'readonly'],
      '#title' => $this->t('Name machine'),
      '#default_value' => $data["id"],
      '#required' => TRUE
    );
    $form['alias'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URl alias'),
      '#default_value' => $data["alias"],
      '#required' => TRUE
    );

    $form['content'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('HTML New Page'),
      '#default_value' => $data["content"]["value"],
      '#format' => 'code_html',
      '#rows' => 24,
      '#required' => TRUE,
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
    $value = $form_state->getValue('new');
    // try to parse the new provided value

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
