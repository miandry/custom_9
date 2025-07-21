<?php

namespace Drupal\mz_crud\TwigExtension;


/**
 * Class DefaultTwigExtension.
 */
class DefaultTwigExtension extends \Twig_Extension {

        
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
    public function getFunctions() {
      return [
        new \Twig_SimpleFunction('save',['Drupal\mz_crud\TwigExtension\DefaultTwigExtension', 'twig_save']),
       
      ];
    }
    public static function twig_save($entity_name, $bundle, $fields){
        $object = \Drupal::service('crud')->save($entity_name,$bundle,$fields);    
        if($object){
          return $object->id() ;
        }else{
          return false ;
        }   
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
      return 'mz_crud.twig.extension';
    }
   
}
