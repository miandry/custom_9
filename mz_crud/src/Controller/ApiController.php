<?php

namespace Drupal\mz_crud\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\drupal_helper\DrupalHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

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
  public function save() {
    $json = [];
    $method = \Drupal::request()->getMethod();
    $id = null;
    if ($method == "POST") {
        $content = \Drupal::request()->getContent();
 
        if (!empty($content)) {
            $content = json_decode($content, TRUE);        
            $service =  \Drupal::service('api.crud');
            $token = $content["token"];
            $is_valid =  true ; //$service->isTokenValid($content["author"], $token)    ; 
            if( $is_valid){
                $entity_type = $content["entity_type"];
                $bundle = $content["bundle"];
                unset($content["bundle"]);
                unset($content["entity_type"]);
                unset($content["token"]);
                $elemt = \Drupal::service('crud')->save($entity_type, $bundle, $content);
                if(is_object($elemt)){
                    $id  = $elemt->id();
                }
            }else{
                $message = "Author token is not valid";
            }

        }else{
           // $message = "Data not found";
        }
    }else{
        $message = "No POST request";
    }
    $json = ($id)? ['item'=> $id,'status'=>true]:['message'=> $message,'status'=>'error'] ;
    return new JsonResponse($json);
  }
  public function register()
  {
      $service =  \Drupal::service('api.crud');
      $method = \Drupal::request()->getMethod();
      $json['status'] = false;

      if ($method == "POST") {
          $content = \Drupal::request()->getContent();

          if (!empty($content)) {
              $data = json_decode($content, TRUE);
              if ($data['name'] && $data['pass']) {

                  $status = $service->isUserNameExist($data['name']);

                  if ($status) {
                      $json['name'] = $data['name'];
                      $json['error'] = 'Username exist deja';
                      $json['status'] = false;
                  } else {
                      $json['name'] = $data['name'];
                      $user = User::create();
                      $user->setPassword($data['pass']);
                      $user->enforceIsNew();
                      $user->setEmail("email@yahoo.fr");
                      $user->setUsername($data['name']); //This username must be unique and accept only a-Z,0-9, - _ @ .
                      //   $user->addRole('authenticated'); //E.g: authenticated
                      $json['status'] = $user->save();
                      $json['token'] = $service->generateToken($user);
                      $json['id'] = $user->id();

                  }
              }
          }
      }
      return new JsonResponse($json);
  }
  public function login()
  {

      $method = \Drupal::request()->getMethod();
      $json['status'] = false;
      if ($method == "POST") {
          $content = \Drupal::request()->getContent();
          if (!empty($content)) {
              $data = json_decode($content, TRUE);
              $json['name'] = $data['name'];
              $user = user_load_by_name($data['name']);
              if (is_object($user)) {
                  $hashed_password = $user->getPassword();
                  $password_hasher = \Drupal::service('password');
                  $password = $data['password'];
                  $json['mail'] = $user->getEmail();
                  $json['token'] = \Drupal\Component\Utility\Crypt::hashBase64($hashed_password);
                  $json['status'] = ($password_hasher->check($password, $hashed_password));
                  $json['roles'] = $user->getRoles();
                  $json['id'] = $user->id();
              }
          }
      }

      return new JsonResponse($json);
  }
  public function user_edit()
  {
     $service =  \Drupal::service('api.crud');
      $method = \Drupal::request()->getMethod();

      if ($method == "POST") {
          $content = \Drupal::request()->getContent();
          if (!empty($content)) {
              $data = json_decode($content, TRUE);
              $json = ($data);
              $json['status'] = false;
              if ($data['name'] && $data['phone']) {
                  $status =  $service->isUserNameExist($data['name']);
                  if ($status) {
                      $field_adress = [
                          'field_adress' => $data['adress']['province'] . " - " . $data['adress']['city'] . " - " . $data['adress']['location'],
                          'field_email' => $data['mail'],
                          'field_phone' => $data['phone']
                      ];
                      $adress = \Drupal::service('crud')->save('paragraph', 'adress', $field_adress);
                      $user = user_load_by_name($data['name']);
                     // var_dump($user->id());
                      $user->setEmail($data['mail']);
                    //  $user->set("field_phone", $data['phone']);
                      $user->set("field_adresse", $adress);
                     /// $user->setUsername($data['name']); //This username must be unique and accept only a-Z,0-9, - _ @ .
                      //   $user->addRole('authenticated'); //E.g: authenticated
                      $json['status'] = $user->save();
                      $json['id'] = $user->id();

                  }
              }
          }
       }
      return new JsonResponse($json);
  }



}
