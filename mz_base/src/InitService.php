<?php

namespace Drupal\mz_base;

/**
 * Class InitService.
 */
class InitService {

  /**
   * Constructs a new InitService object.
   */
  public function __construct() {

  }
  public function buildLayoutCategory(){
      $is_enabled = \Drupal::moduleHandler()->moduleExists('layout_builder_browser');
      if($is_enabled){
          $layout_service = \Drupal::entityTypeManager()->getStorage('layout_builder_browser_blockcat') ;
          $values = [
              ['id' => 'showcase','label' => 'Showcase'],
              ['id' => 'hero','label' => 'Hero'],
              ['id' => 'tab','label' => 'Tab nagivation'],
              ['id' => 'carousel','label' => 'Carousel'],
              ['id' => 'gallery','label' => 'Gallery Image'],
              ['id' => 'action','label' => 'Action'],
              ['id' => 'copy','label' => 'Copy'],
              ['id' => 'header','label' => 'Header'],
              ['id' => 'footer','label' => 'footer'],
              ['id' => 'service','label' => 'Service widget'],
              ['id' => 'video','label' => 'Video'],
              ['id' => 'eduction','label' => 'Eduction'],
              ['id' => 'Shop','label' => 'Shop'],
              ['id' => 'list','label' => 'List'],
          ];
          $list = array_keys($layout_service->loadMultiple());
          foreach ($values as $item){
              if(!in_array($item['id'],$list)){
                  $category_new = $layout_service->create($item);
                  $category_new->save();
              }
          }
      }
  }
  public function init(){
      /** Build layout category */
      $this->buildLayoutCategory();
  }
}
