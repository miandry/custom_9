<?php

namespace Drupal\vbo_extension\Plugin\Action;


use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\core\Cache\Cache;
use Drupal\views\Views;
/**
 * An example action covering most of the possible options.
 *
 * If type is left empty, action will be selectable for all
 * entity types.
 *
 * @Action(
 *   id = "views_bulk_operations_redirect",
 *   label = @Translation("VBO Redirect"),
 *   type = "",
 *   confirm = FALSE
 * )
 */
class ViewsBulkOperationRedirectAction extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface,PluginFormInterface {


  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /*
     * All config resides in $this->configuration.
     * Passed view rows will be available in $this->context.
     * Data about the view used to select results and optionally
     * the batch context are available in $this->context or externally
     * through the public getContext() method.
     * The entire ViewExecutable object  with selected result
     * rows is available in $this->view or externally through
     * the public getView() method.
     */
    // Do some processing..
    // ...
//    static  $elements = [] ;
//    static $index = 1;
//    $parser = new ExportView();
//    $max = $this->view->total_rows;
//    $entity = $parser->node_parser($entity,[],['#hook_alias'=>'exp_']);
//    $items = [
//      'nid' => $entity['nid'],
//      'title' => $entity['title'],
//      //'image' => $entity['field_image'][0][''],
//      'achat' => $entity['field_autre_prix']['achat'],
//      'vente' => $entity['field_autre_prix']['vente'],
//    ];
//    $elements [] = $items ;

//    if($index==1){
//      //$this->export = new \PHPExcel();
//
//      $this->export->setActiveSheetIndex(0);

  //  if($max == $index ){

  //  }

     ///   $base = new Base();
     //   $revendeur=$this->configuration["field_revendeur"];
     //   $ids  = $base->deformat_auto_completion($revendeur);
       /// $entity->field_client->target_id = $ids[0];
      //  $status = $entity->save();

      //  if($status==2){
         // sprintf('Last  : '. $entity->id() );

     //   $index = $index + 1;
    return sprintf('Success' );
      //  }else{

       //   return sprintf('Modifier Revendeur Annuler');
      //  }
  }

    /**
     * {@inheritdoc}
     */
    public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array {

      $entity = $form_state->getStorage();
      $display = ( $entity['display_id']);
      $view = ($entity['view']);
      $view_name = ($view->id());
      $view_object = Views::getView($view_name);
      $view_object->setDisplay($display);
      $info = ( $view_object->display_handler->default_display->options);
      $fields = [];
      if(isset($info['fields'])){
        $fields = (array_keys($info['fields']));

      }
 

        $form['cache_name'] = [
            '#title' => $this->t('Cache name'),
            '#type' => 'textfield',
            '#description' => ' <?php $items = \Drupal::cache()->get("views_bulk_operations_redirect"); ?>',
            '#default_value' => isset($values['cache_name']) ? $values['cache_name'] : 'views_bulk_operations_redirect',
        ];
        $form['redirect_url'] = [
            '#title' => $this->t('Redirect URL'),
            '#type' => 'textfield',
            '#description' => ' Example : /node/1231 ',
            '#default_value' => isset($values['redirect_url']) ? $values['redirect_url'] : '',
        ];
        foreach( $fields as $field){
          $form["fieldwsxcde_".$field] = array(
            '#type' => 'checkbox',
            '#title' => $field ,
            '#default_value' => 1,
            '#attributes' => array('checked' => 'checked')
            );
        }
        return $form;
    }

  /**
   * Configuration form builder.
   *
   * If this method has implementation, the action is
   * considered to be configurable.
   *
   * @param array $form
   *   Form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The configuration form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
      $form_data = $form_state->get('views_bulk_operations');
      $cache_name = ($form_data['preconfiguration']['cache_name']);
      $temp_store_factory = \Drupal::service('session_based_temp_store');
      $temp_store = $temp_store_factory->get($cache_name, 604800);
      $temp_store->deleteAll();
      // $redirect_url = ($form_data['preconfiguration']['redirect_url']);
      // $temp_store_factory = \Drupal::service('session_based_temp_store');
      // $temp_store = $temp_store_factory->get($cache_name, 604800);
      // global $base_url;
      // $temp_store->set($cache_name,$form_data['list']);
      // var_dump($form_data['list']);die();
      // $path = $base_url .$redirect_url ;
      // $response = new RedirectResponse($path, 302);
      // $response->send();
      return $form ;
  }
  /**
   * Submit handler for the action configuration form.
   *
   * If not implemented, the cleaned form values will be
   * passed direclty to the action $configuration parameter.
   *
   * @param array $form
   *   Form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
      $form_data = $form_state->get('views_bulk_operations');
      $cache_name = ($form_data['preconfiguration']['cache_name']);
      $redirect_url = ($form_data['preconfiguration']['redirect_url']); 
      $temp_store_factory = \Drupal::service('session_based_temp_store');
      $temp_store = $temp_store_factory->get($cache_name, 604800);
      global $base_url;
      $temp_store->set($cache_name,$form_data);
      $path = $base_url .$redirect_url ;
      $response = new RedirectResponse($path, 302);
      $response->send();
      return $form ;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityType() === 'node') {
      $access = $object->access('update', $account, TRUE)
        ->andIf($object->status->access('edit', $account, TRUE));
      return $return_as_object ? $access : $access->isAllowed();
    }
     // kint($object->getEntityType());die();
    // Other entity types may have different
    // access methods and properties.
    return TRUE;
  }

}
