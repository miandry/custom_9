<?php

namespace Drupal\vbo_extension;

use Drupal\file\Entity\File;
use Drupal\Core\Field\FieldDefinitionInterface;
/**
 * Class BankService.
 */
class ExportService{
  public function __construct()
  {

  }
  public function getData( $items){
      $results = [];
      $fields = [] ;
     // kint($items);
      foreach( $items['preconfiguration'] as $key => $t ) {
         $key_str = "fieldwsxcde_" ;
         $length = strlen($key_str );
         if (substr($key, 0, $length) === $key_str  && $t == 1){
          $end = strlen($key);
          $field_name = substr($key, $length,$end);
          $fields[] =    $field_name  ;
         }
      } 
   
      foreach( $items['list'] as $value ) {
        $results[] =  $this->format_value($value,$fields) ;      
      }
      return ["header" => $fields , "resultat" => $results];
  }

  function getFieldTypeByName($entity_type, $bundle,$field_name){
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $field_definitions = $entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    $field_definition = $field_definitions[$field_name] ?? NULL;

    if ($field_definition instanceof FieldDefinitionInterface) {
      return $field_definition->getType();
    }
    return false ;
  }
  function array_csv_download( $array, $filename = "export.csv", $delimiter=";" )
  {
      header( 'Content-Type: application/csv' );
      header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
  
      // clean output buffer
      ob_end_clean();
      
      $handle = fopen( 'php://output', 'w' );
  
      // use keys as column titles
      fputcsv( $handle, array_keys( $array['0'] ));
  
      foreach ( $array as $value ) {
          fputcsv( $handle, $value);
      }
      fclose( $handle );
      // flush buffer
      ob_flush();
      
      // use exit to get rid of unexpected output afterward
      exit();
  }
  function format_value($value,$fields){    
    $service_parser = \Drupal::service('entity_parser.manager');
 
    $object = $service_parser->loader_object($value[0],$value[2]);
    $entity_type = $object->getEntityTypeId();
    $bundle = $object->bundle();

    $node = $service_parser->parser($object,$fields);
    foreach($node as $field => $value ) {
        $type = $this->getFieldTypeByName($entity_type, $bundle,$field);
        if(is_array($value)){

            if(isset($value["title"])){
              $node[$field] = $value["title"] ;
            }  
            if(isset($value["name"])){
              $node[$field] = $value["name"] ;
            }  
        }
        if($type == 'created'){
          $node[$field] = date('Y-m-d', floatval($value));
        }
      } 
      return $node ;
  }
}
