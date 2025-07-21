<?php

namespace Drupal\drupal_helper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_helper\DrupalHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
/**
 * Class ApiController.
 */
class ApiController extends ControllerBase {

  /**
   * Paragraph_delete.
   *
   * @return string
   *   Return Hello string.
   */
  public function git_pull($name){
    $config = \Drupal::config('drupal_helper.gitrepo');
    $respo_list = $config->get('list');
    $token =   $respo_list['token'];
    $myrepos = $respo_list['list'];
    if(!isset($myrepos[$name])){
        return new Response('<pre> repos not exist add in /admin/gitrepos </pre>', 200, ['Content-Type' => 'text/html']);
    }
    $current_repo = $myrepos[$name]['repo'];
    $current_path = $myrepos[$name]['path'];
    $path_repo = str_replace('https://miandry@','',$current_repo);
    $username = 'miandry';
 
    // Change to the directory where the repository is located.
    $repoDir = DRUPAL_ROOT.$current_path;
    chdir($repoDir);
    
    // Use the token in place of the password.
    $command = "git pull https://$username:$token@$path_repo 2>&1";
    
    // Execute the command and capture the output.
    $output = shell_exec($command);
    $message = "Git pull executed:\n$output" ;
    \Drupal::logger('drupal_helper')->notice($message);

    // Process the output
    $lines = explode("\n", trim($output));
    $git_results = [];
    foreach ($lines as $line) {
        $git_results[] = $line;
    }
    // Convert the array to JSON with pretty print
    $json_output = json_encode($git_results, JSON_PRETTY_PRINT);

    return new Response('<pre>' . $json_output . '</pre>', 200, ['Content-Type' => 'text/html']);

  }

  public function paragraph_delete($id) {
      $drupal_helper = new DrupalHelper();
      $destination = "/".$drupal_helper->helper->get_parameter('destination');
      $paragraph_list =  \Drupal::entityTypeManager()->getStorage('paragraph')->loadByProperties(array('id' => $id));
      if(!empty($paragraph_list)){
          $para_object = array_values($paragraph_list)[0] ;
          $para_object->delete();
      }
      if($destination){
          return $drupal_helper->helper->redirectTo($destination) ;
      }else{
          return $drupal_helper->helper->redirectTo('/') ;
      }
  }
  public function delete($entity_type,$id) {
        $entity =  \Drupal::entityTypeManager()->getStorage($entity_type)->load($id);
        $json['status']= 0;
        if(is_object($entity)) {
            $entity->delete();
            $json['status']=1;
        }
      return new JsonResponse($json);
  }

}
