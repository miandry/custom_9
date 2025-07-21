<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 23/03/2018
 * Time: 14:49
 */

namespace Drupal\html_page;

use Drupal\path_alias\Entity\PathAlias;

class HtmlPageService 
{




       public function saveData($values){

          $url = "/html_page"."/".$values["id"] ;
          $alias = $values["alias"];

          $result = [
            "id" => $values["id"],
            "content" => $values["content"],
            "alias" => $values["alias"]
          ];
          $status = \Drupal::configFactory()->getEditable('html_page.list')
          ->set($values["id"], $result)
          ->save();
                    $path_alias_repository = \Drupal::service('path_alias.repository');
          if (!$path_alias_repository->lookupByAlias($alias, 'en')) {
            $path_alias = PathAlias::create([
                'path' =>   $url,
                'alias' =>    $alias,
            ]);
            $path_alias->save();
          }

        
          return true ;
       }
       public function loadData($id){

        $config = \Drupal::config('html_page.list') ;
        return $config->get($id);

       }
       function isDrupalMachineName($string) {
        // Check if the string is not empty.
        if (empty($string)) {
          return false;
        }
      
        // Check if the string starts with a letter.
        if (!ctype_alpha($string[0])) {
          return false;
        }
      
        // Check if the string contains only lowercase letters, numbers, and underscores.
        if (!preg_match('/^[a-z0-9_]+$/', $string)) {
          return false;
        }
      
        // Additional checks, if needed.
      
        // If the string passes all checks, it is considered a valid machine name.
        return true;
      }
      
}