<?php

namespace Drupal\mz_booking\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Edit config variable form.
 */
class UploadPrice extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'upload_price_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $config_name = '')
    {
        $nid = \Drupal::request()->query->get('nid');
        if( $nid ){
             $car = \Drupal::service('entity_parser.manager')->node_parser($nid);
             $file_id = ($car['price_lists']['document']['target_id']);
             $service_booking = \Drupal::service('mz_booking.manager');
             $service_booking->setPriceByExcel( $file_id ,$car['nid']);
             $form['loading']  = [
                '#type' => 'markup',
                '#markup' => 'loading ...',
              ];
        }else{
     
        $form['upload'] = [
            '#type' => 'managed_file',
            '#required' => true,
            '#title' => $this->t('Upload Excel master price for one year'),
            '#upload_location' => 'public://upload',
            '#description' => $this->t('File format allowed : xlsx,xls,csv'),
            '#upload_validators' => [
                'file_validate_extensions' => ['xlsx','xls','csv'],
            ],
        ];
        $form['carid'] = [
            '#type' => 'textfield',
            '#title' => t('Title car '),
            '#autocomplete_route_name' => 'mz_booking.car',
            '#autocomplete_route_parameters' => array('count' => 10),
            '#maxlength' => 264,
            '#size' => 64
          ];
        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
        ];
        }
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
      $carid = $form_state->getValue('carid');
      $carid = $this->getIdCar($carid);
      $car = \Drupal::entityTypeManager()->getStorage('node')->load($carid);
      if (!is_object($car)) {
        $form_state->setError($form['carid'], $this->t('Car id is not exist'));
      }
    }
    public function getIdCar($carid){
        $t = explode('(',$carid);
        $t = explode(')',$t[sizeof($t)-1]);
        return $t[0];
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        $helper = \Drupal::service('config_export_import.manager');
        $form_file = $form_state->getValue('upload', 0);
        $carid = $form_state->getValue('carid');
        $carid = $this->getIdCar($carid);
        $path = $form_state->getValue('path');
        if (isset($form_file[0]) && !empty($form_file[0])) {
            $file = File::load($form_file[0]);
            // $file->setPermanent();
            //  $file->save();
            $uri = $file->getFileUri();
            $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($uri);
            $file_path = $stream_wrapper_manager->realpath();
            if (file_exists($file_path)) {
                $phpexcel = \Drupal::service('phpexcel');
                $result = $phpexcel->import($file_path);

                $price = 0;
                $element = [];
                /// get end date
                foreach ($result[0] as $key => $item) {
                    if ($key > 0 && $price != $item['Price_Day']) {
                        $p = $result[0][$key - 1];
                        $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($p['Date']);
                        $element[] = [
                            "date_end" => $date,
                            "value" => $p['Price_Day'],
                            "key_end" => $key - 1,
                        ];
                    }
                    $price = $item['Price_Day'];
                }

                /// get start date
                foreach ($element as $key => $item) {
                    $p = $result[0][$item['key_end'] + 1];
                    $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($p['Date']);
                    $element[$key + 1]["date_start"] = $date;
                    $element[$key + 1]["key_start"] = $item['key_end'] + 1;
                }
                //get peripherique
                foreach ($element as $key => $item) {
                    if (!isset($item["key_start"])) {
                        $p = $result[0][0];
                        $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($p['Date']);
                        $element[$key]["date_start"] = $date;
                        $element[$key]["value"] = $p['Price_Day'];
                    }
                    if (!isset($item["key_end"])) {
                        $p = $result[0][sizeof($result[0]) - 1];
                        $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($p['Date']);
                        $element[$key]["date_end"] = $date;
                        $element[$key]["value"] = $p['Price_Day'];

                    }
                }
                $fields['nid'] = $carid;
                foreach ($element as $key => $item) {
                    $start = date('Y-m-d', $item['date_start']);
                    $end = date('Y-m-d', $item['date_end']);
                    $price = $this->notableDays($start, $end);
                    if(!$price){
                        $price = "Normal";
                    }
                    $fields['field_price'][] = [
                        'field_price_value' => $item['value'],
                        'field_price' =>  $price,
                        'field_date' => [
                            'value' =>  $start,
                            'end_value' =>$end ,
                        ],
                    ];
                }
                $booking_new = \Drupal::service('crud')->save('node', 'car', $fields);
                if (is_object($booking_new)) {
                    \Drupal::messenger()->addMessage(t('Master price uploaded successfully  <a href="@link">Edit this car </a> ', ['@link' => '/node/'.$carid.'/edit']));
                } else {
                    \Drupal::messenger()->addMessage(t('Failed to upload Master price <a href="@link">Edit this car </a>', ['@link' => '/node/'.$carid.'/edit']), 'warning');
            
               }

            }
        }
    }

    public function notableDays($start,$end){
      //  $start = "2023-01-04";
      //  $end = "2023-01-13";
     //   $date = new DrupalDateTime($mydate);
      //  $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
      //  $formatted = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
      //  kint($mydate);
        $ids = \Drupal::entityQuery('taxonomy_term')
        ->condition('field_dates.value',$start, '<=')   
        ->condition('field_dates.end_value',$end, '>') 
        ->range(0,1)
        ->condition('vid', 'notable_dates')
        ->execute();
        if(!empty($ids)){
            foreach($ids as $i){
                return \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($i)->label();
            }
        }
        return false ;
        
    }

}
