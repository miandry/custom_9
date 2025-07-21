<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 6/7/18
 * Time: 5:51 PM
 */

namespace Drupal\mz_message;

//require composer require phpmailer/phpmailer
class DrupalMailManager
{

    public function __construct()
    {
    }
    public function sendMail($sentTo , $subject , $body ){
        if(!$this->verificationMail($sentTo)){
            \Drupal::logger('mz_message')->error("E-mail is not valid ".$sentTo);
            return false ;
        }
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $mail = new \PHPMailer\PHPMailer\PHPMailer();       
            // Set mailer to use SMTP
            $mail->isSMTP();
            
            // SMTP settings for Gmail
          
           // $mail->Host = 'smtp.gmail.com';
            //$mail->Username = 'boutamiandrilala@gmail.com';
            //$mail->Password = 'Bota@#009856';
            
            $mail->Host = 'smtp.titan.email';
            $mail->Username = 'staydirect@staydirecttech.com'; // Your Gmail address
            $mail->Password = 'StayDirect@#009856'; // Your Gmail password

            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            // Sender and recipient details
            $mail->setFrom('staydirect@staydirecttech.com', 'StayDirect Technology');
            $mail->addAddress('staydirect@staydirecttech.com', 'StayDirect Support');
            if(is_string($sentTo)){
                $mail->addAddress($sentTo, 'Recipient');
            }
            if(is_array($sentTo)){
                foreach($sentTo as $to){
                    $mail->addAddress($to, 'Recipient');
                }
            }
            // Email subject and body
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            // Send the email
            if(!$mail->send()) {
                $message = 'Message could not be sent.';
                \Drupal::logger('mz_message')->error( $message);
                $message =  'Mailer Error: ' . $mail->ErrorInfo;
                \Drupal::logger('mz_message')->error( $message);
            } else {
                \Drupal::logger('mz_message')->info('Message has been sent');
                \Drupal::messenger()->addMessage('Email Message has been sent');  
            } 
 
        } else {
            // PHPMailer library is not available
            // Handle the situation accordingly
            $message =  'PHPMailer library is not available.';
            \Drupal::logger('mz_message')->error($message);
        }
    }
    function verificationMail($email){
        list($user, $domain) = explode('@', $email);
        if (!checkdnsrr($domain, 'MX')) {
            \Drupal::logger('mz_message')->error("Email Domain does not have an MX record.");
            return false ;
        }
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            \Drupal::logger('mz_message')->error("E-mail is not valid");
            return false ;
        
        }
        return true ;

    }
}