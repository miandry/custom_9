<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 6/7/18
 * Time: 5:51 PM
 */
namespace Drupal\drupal_helper;

use Drupal\views\Views;

class DrupalViewsHelper
{
    function views_result($view_name,$diplay_id,$page = 0,$query = [],$args = []){
        // $view = Views::getView($view_name);
        // $view->storage->load($view_name);
         $view = Views::getView($view_name);
         $view->setDisplay($diplay_id);
         $view->setArguments($args);
         $view->setCurrentPage($page);
         $view->setExposedInput($query);
         //$view->setItemsPerPage($pager_item_size);
         $view->execute();
         $results = [];
         $results['total_rows'] = $view->total_rows ;
         $results['filter'] = $view->exposed_data ;        
         $results['current_page'] = $view->getPager()->current_page ;
         $results['items_per_page'] = $view->getItemsPerPage() ;
         $results['offset'] = $view->getOffset() ;
         foreach($view->result as $row){
            $entity = $row->_entity ;         
            $results['items'][] = [
                "#bundle" =>  $entity->bundle(),
                "label" =>  $entity->label(),
                "id" =>  $entity->id(),
                "#entity_type" =>  $entity->getEntityTypeId(),
                "#entity" => $entity
            ] ;
         }
         return $results;
       }
       
       
     public function render_block($block_id = 'system_breadcrumb_block'){
        $block_manager = \Drupal::service('plugin.manager.block');
        // You can hard code configuration or you load from settings.
        $config = [];
        $plugin_block = $block_manager->createInstance($block_id, $config);
        // Some blocks might implement access check.
        $access_result = $plugin_block->access(\Drupal::currentUser());
        // Return empty render array if user doesn't have access.
        // $access_result can be boolean or an AccessResult class
        if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
        // You might need to add some cache tags/contexts.
        return [];
        }
        $render = $plugin_block->build();
        // Add the cache tags/contexts.
        \Drupal::service('renderer')->addCacheableDependency($render, $plugin_block);
        return $render;
     }
     function render_view_mode($entity_type,$entity , $mode_view){
       return \Drupal::entityTypeManager()->getViewBuilder($entity_type)
         ->view($entity, $mode_view);
     }
     function field_render($field_name,$entity,$entity_type='node'){
         $source = null;
         if(is_object($entity)){
             $source =$entity ;
         }else{
             if(is_numeric($entity)){
                 $source = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity);
             }
         }
         $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
         $output = '';

         if ($source && $source->hasField($field_name) && $source->access('view')) {
             $value = $source->get($field_name);
             $output = $viewBuilder->viewField($value, 'full');
             $output['#cache']['tags'] = $source->getCacheTags();
         }
         return $output ;
     }

     function render_views($view_name,$diplay_id,$argument=[]){
         $view = Views::getView($view_name);
         $view->setDisplay($diplay_id);
         $view->preExecute();
         $view->setArguments($argument);
         $view->execute();
         return $view->buildRenderable($diplay_id,$argument);
     }
     /**
      * @return views object
     **/
     function views_result_by_pager_size($view_name,$diplay_id,$pager_item_size = 200){
       // $view = Views::getView($view_name);
       // $view->storage->load($view_name);
        $view = Views::getView($view_name);
        $view->setDisplay($diplay_id);
        //$view->setItemsPerPage($pager_item_size);
        $view->execute();
        return $view;
      }

     function render_view_exposed_form($view_name){
         $view = Views::getView($view_name);
         $view->initHandlers();
         $form_state = new \Drupal\Core\Form\FormState();
         $form_state->setFormState([
             'view' => $view,
             'display' => $view->display_handler->display,
             'exposed_form_plugin' => $view->display_handler->getPlugin('exposed_form'),
             'method' => 'GET',
             'rerender' => TRUE,
             'no_redirect' => TRUE,
             'always_process' => TRUE, // This is important for handle the form status.
         ]);
         return \Drupal::formBuilder()->buildForm('Drupal\views\Form\ViewsExposedForm', $form_state);
     }
     public function render_block_custom($block_id) {
        $entity_name = 'block_content';
        if(is_numeric($block_id)){
           $block = \Drupal::entityTypeManager()->getStorage($entity_name)->load($block_id);
        }
        if(is_object($block)){
          $render = \Drupal::entityTypeManager()->getViewBuilder($entity_name)->view($block);
          return [
             '#theme' => 'block',
             '#weight' => 999,
             'content' => $render,
             '#cache' => [
               'max-age' => 0,
             ],
           ];
        }
        return '';
    }

}