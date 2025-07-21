<?php

namespace Drupal\mz_payment\TwigExtension;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\views\Views;

use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

/**
 * Class DrupalHelperTwigExtension.
 */
class PaymentTwigExtension extends AbstractExtension {

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
          new TwigFunction( 'front_price' ,['Drupal\mz_payment\TwigExtension\PaymentTwigExtension', 'front_price']),
       
          new TwigFunction( 'front_currency' ,['Drupal\mz_payment\TwigExtension\PaymentTwigExtension', 'front_currency']),
          new TwigFunction( 'currency' ,['Drupal\mz_payment\TwigExtension\PaymentTwigExtension', 'currency']),
          new TwigFunction( 'checkout_booking' ,['Drupal\mz_payment\TwigExtension\PaymentTwigExtension', 'checkout_booking'])
    
        ];
    }
    public static function checkout_booking($params){
         
    }
    public static function front_price($price){
      return \Drupal::service('mz_payment.manager')->format_price($price);
    }
  public static function currency(){
    $config = \Drupal::config('mz_payment.config');
    return $config->get('currency');
  }
  public static function front_currency($price = null ){
    $config =\Drupal::config('mz_payment.config');
    $currency =  $config->get('currency');
    if($price == null){
      if( $currency == "USD" || $currency == "usd"){
        return "<span class='currency-label'>$</span>";
      }else{
        return "<span class='currency-label'>".$currency ."</span>"  ;
      }
    }
    $price = number_format($price, 2,'.', ',');
    if( $currency == "USD" || $currency == "usd"){
      return "<span class='currency-label'>$</span>".$price ;
    }else{
      return  "<span class='currency-label'>".$currency ."</span>".$price  ;
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
        return 'mz_payment.twig.extension';
    }

}
