<?php

namespace Drupal\entity_parser\TwigExtension;

use Drupal\Core\Render\Renderer;
use Drupal\entity_parser\EntityParser;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
/**
 * Class DefaultTwigExtension.
 */

class DefaultTwigExtension extends AbstractExtension {

        
   /**
    * {@inheritdoc}
    */
    public function getTokenParsers() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getNodeVisitors() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getFilters() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getTests() {
      return [];
    }
     /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('entity_parser_load', ['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'entity_parser_load_twig']),
            new TwigFunction('node_parser', ['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'node_parser_twig']),

            new TwigFunction('block_parser', ['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'block_parser_twig']),

            new TwigFunction('user_parser', ['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'user_parser_twig']),
            new TwigFunction('taxonomy_term_parser', ['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'taxonomy_term_parser_twig']),
            new TwigFunction('group_parser', ['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'group_parser_twig']),
            new TwigFunction('group_content_parser', ['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'group_content_parser_twig']),


            new TwigFunction('paragraph_parser', ['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'paragraph_parser_twig']),

            new TwigFunction('media_parser', ['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'media_parser_twig']),
            new TwigFunction('profile_parser', ['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'profile_parser_twig']),
            new TwigFunction('node_parser_by_properties', ['Drupal\entity_parser\TwigExtension\DefaultTwigExtension', 'node_parser_by_properties_twig']),


        ];
    }
    public static function node_parser_by_properties_twig($conditions) {
        $nodes = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->loadByProperties($conditions);
        $nodeStorage = \Drupal::service('entity_type.manager')->getStorage('node');
        foreach($nodes as $key => $node){
          $nodeStorage->resetCache([$key]);
        }
        return ($nodes);
      }
    
    public static function profile_parser_twig($term,$fields = [],$option = [] ){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->profile_parser($term,$fields,$option);
    }
    public static function media_parser_twig($term,$fields = [],$option = [] ){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->media_parser($term,$fields,$option);
    }
    public static function paragraph_parser_twig($term,$fields = [],$option = [] ){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->paragraph_parser($term,$fields,$option);
    }

    public static function taxonomy_term_parser_twig($term,$fields = [],$option = [] ){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->taxonomy_term_parser($term,$fields,$option);
    }
    public static function group_parser_twig($entity,$fields = [],$option = []){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->group_parser($entity,$fields,$option);
    }
    public static function group_content_parser_twig($entity,$fields = [],$option = []){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->group_content_parser($entity,$fields,$option);
    }
    public static function block_parser_twig($block,$fields = [],$option = [] ){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
        $parser = new $option['#entity_parser_extend']();
        }
        if(!isset($block['#block_content'])){
           return null;
        }
        return $parser->block_parser($block['#block_content'],$fields,$option);
    }
    public static function node_parser_twig($node,$fields = [],$option = [] ){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->node_parser($node,$fields,$option);
    }
  public static function user_parser_twig($user,$fields = [],$option = [] ){
    $parser = new EntityParser();
    if(isset($option['#entity_parser_extend'])){
      $parser = new $option['#entity_parser_extend']();
    }
    return $parser->user_parser($user,$fields,$option);
  }
    public static function entity_parser_load_twig($entity,$fields = [],$option = [] ){
        $parser = new EntityParser();
        if(isset($option['#entity_parser_extend'])){
            $parser = new $option['#entity_parser_extend']();
        }
        return $parser->entity_parser_load($entity,$fields,$option);
    }

   /**
    * {@inheritdoc}
    */
    public function getOperators() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getName() {
      return 'entity_parser.twig.extension';
    }

}
