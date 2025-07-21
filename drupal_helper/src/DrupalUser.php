<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 11/6/18
 * Time: 4:11 PM
 */

namespace Drupal\drupal_helper;


use Drupal\user\Entity\User;

class DrupalUser {
  public function hasRole($role_name,$uid=null){
    $userCurrent = \Drupal::currentUser();
    if(is_numeric($uid)){
      $userCurrent = \Drupal::entityTypeManager()
        ->getStorage('user')->load($uid);
    }
    $is=false ;
    if (in_array($role_name, $userCurrent->getRoles())) {
      $is = true ;
    }
    return $is;
  }
  public function get_user_by_email($email){
    return \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(array('mail' => $email));
  }
  public  function is_admin($uid=null) {
    return $this->hasRole('administrator',$uid);
  }
  public function hasPermission($permission,$user = null ){
    if($user==null){
    $user = \Drupal::currentUser();
    }
    if(is_object($user)){
      $user_roles = $user->getRoles();
      $roles_permissions = user_role_permissions($user_roles);
      foreach ($roles_permissions as $role_key => $permissions) {
        if($role_key =='administrator'){
          return true ;
        }
        foreach ($permissions as $permission_item) {
          if($permission_item == $permission){
            return true ;
          }
        }
      }
    }
    return false ;
  }
  public function getUserLastId(){
    $last_user_id = false ;
      $query = \Drupal::entityQuery('user')
      ->sort('uid', 'DESC')
      ->range(0, 1); // Get only one result.
      $result = $query->execute();
      if (!empty($result)) {
      $last_user_id = reset($result);
      }
      return    $last_user_id ;
  }
  private function generateRandomUsername($length = 8) {
    // Characters to be used for generating the random username.
    $characters = 'abcdefghijklmnopqrstuvwxyz';
    // Generate a random string of the specified length.
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
      $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    // Add a prefix or suffix to ensure uniqueness.
    $username = 'user_' . $random_string; // Prefix 'user_' can be replaced with anything you prefer.
    return $username;
  }
  public function processRandomUser(){
      // Generate a random username.
      $username = $this->generateRandomUsername();
  
      // Check if the generated username already exists.
      $user_exists = (bool) \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $username]);
  
      // If the username already exists, regenerate until a unique username is found.
      while ($user_exists) {
        $username = $this->generateRandomUsername();
        $user_exists = (bool) \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $username]);
      }
      return $username  ;
  
  }
  public function generate_pass( $length = 8 ) {

    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    return substr(str_shuffle($chars),0,$length);

  }
}