<?php

namespace Drupal\mz_base;

/**
 * Class DefaultService.
 */
class DefaultService {

  /**
   * Constructs a new DefaultService object.
   */
  public function __construct() {

  }
  public function diff($aArray1, $aArray2) {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->diff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }
        return $aReturn;
    }
  public function sendMail($subject,$mail,$message){
      $to = \Drupal::config('system.site')->get('mail');
      $message .= "<p>".$message."</p>";
      $header = "From:".$mail." \r\n";
//    if($values['copy'] && $values['copy']==1){
//        $header .= "Cc:".$values['mail']." \r\n";
//    }
      $header .= "MIME-Version: 1.0\r\n";
      $header .= "Content-type: text/html\r\n";
      $retval = mail ($to,$subject,$message,$header);
  }
}
