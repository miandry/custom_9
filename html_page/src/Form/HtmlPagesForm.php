<?php

namespace Drupal\html_page\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\RedirectResponse;
/**
 * Class ContentExportSettingForm.
 */
class HtmlPagesForm extends FormBase
{

//  /**
//   * {@inheritdoc}
//   */


    public function getFormId()
    {
        return 'html_pages_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
{

    $param = \Drupal::request()->query->all();
    if(isset($param["delete"])){
       $config = \Drupal::configFactory()->getEditable('html_page.list');
       $config->clear($param["delete"]);
       $config->save();
    } 


  $config = \Drupal::config('html_page.list') ;
   $data = $config->get();

    $form['actions']['import'] = [
        '#type' => 'submit',
        '#value' => 'Insert new Html Page',
        '#submit' => [[$this, 'insert']],
    ];
    $form['table'] = array(
        '#type' => 'table',
        '#weight' => 999,
        '#header' => [
            'number' => t('Number'),
            'id' => t('Id'),
            'operations' => t('operations')
        ],
        '#empty' => $this->t('No items found')
    );
    $i = 0 ;

    foreach($data as $key => $item){
       
        $i ++ ;
        $form['table'][$key]['number'] = [
            '#plain_text' => $i,
        ];       
        $form['table'][$key]['id'] = [
            '#plain_text' => $item["id"],
        ];
        $operations['edit'] = array(
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('html_page.edit', array('id' => $key))
        );
        $form['table'][$key]['operations'] = [
            '#markup' => $this->getOperationsMarkup($key),
         ];

    }
    return $form;
}
  /**
   * Helper function to get operations markup.
   */
  protected function getOperationsMarkup($key) {
    $edit_url = Url::fromRoute('html_page.edit', ['id' => $key]);
    $delete_url = Url::fromRoute('html_page.pages',['delete' => $key]);
    $view_url = Url::fromRoute('html_page.view',['id' => $key]);


    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $edit_url)->toString();
    $delete_link = Link::fromTextAndUrl($this->t('Delete'), $delete_url)->toString();
    $view_link = Link::fromTextAndUrl($this->t('View'), $view_url)->toString();

    return  $view_link." | ". $edit_link . " | ".  $delete_link  ;
  }
public function insert(array &$form, FormStateInterface $form_state) {
    $path = Url::fromRoute('html_page.page')->toString();
    $response = new RedirectResponse($path, 302);
    $response->send();
    return;
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
        $form_state->disableRedirect();
    }

}
