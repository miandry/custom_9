<?php

namespace Drupal\mz_mobile_page;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\Core\Field\FieldItemList;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Url;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Abstract class for portal json parser entity like ArticlePortalJson.
 */
class RenderJSONManager  {
    public function __construct()
    {
    }
    public function renderPageJSON($entity){
        $items = [
            "head" => [],
            "body" => []
        ];
       // $render_widget = \Drupal::service('slider_1v1_1');
        $blocks = $entity->blocks->referencedEntities();
        foreach ($blocks as $section){
            $bundle = $section->bundle();
            $widget = $this->applyService($bundle,$section);
            if(!empty($widget)){
                $items['body'][] = $widget ;
            }else{
                $formatter_json = $this->getJSONFormatter($bundle);
                if(!empty($formatter_json)){
                    //    $formatter_array = Json::decode(Html::decodeEntities($formatter_json));
                    $formatter_array = Json::decode($formatter_json);
                    $formatter_array = $this->autoConvertToken($section,$formatter_array);
                    $items['body'][] = reset($formatter_array) ;
                }
            }


        }
        return $items ;
    }
    public function applyService($id , $block){
        $is_exist = \Drupal::hasService($id);
        if($is_exist){
            $render_widget = \Drupal::service($id);
            return $render_widget->process($block);
        }
        return false ;
    }
    public function func_paragraphs($entity,$field_name,$items){
        $item_news =[];
        if($entity->{$field_name}){
            $ref = 'paragraph_type';
            $object_list = method_exists($entity->{$field_name}, 'referencedEntities') ? $entity->{$field_name}->referencedEntities() : [] ;
            if(!empty($object_list)){
                foreach(array_values($object_list) as $key =>  $object){
                    $reference_name = $object->bundle();
                    $temp_array = $this->getChildContentInArray($reference_name,$items,$ref) ;
                    if(!empty($temp_array)){
                        $temp = ($temp_array[0]);
                        unset($temp[$ref]);// because it will take by another widget
                        $temp[$ref] = $reference_name."_".$key;
                        $temp = $this->replaceValueInArray('%index%',$key,$temp);
                        $temp = $this->replaceValueInArray('%'.$reference_name.'%', $temp[$ref],$temp);
                        $temp = $this->replaceKeyInArray('%'.$reference_name.'%', $temp[$ref],$temp);
                        $item_news[] = $this->autoConvertToken($object,$temp,[],['prefix'=>'%','suffix'=>'%']);
                    }

                }
                $displa_status= "display-".$reference_name;
            }else{
                $displa_status= "display-none";
            }
            $items = $this->replaceKeyInArray('@display_'.$reference_name,$displa_status,$items);
        }
        $items = $this->replaceChildParentContentInArray($reference_name,$item_news,$items,$ref);
        return $items;
    }

    public function outputPageJSON($entity){
        $json_page = $this->renderPageJSON($entity);
        $alias =  \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $entity->id());
        $config = \Drupal::config('server_json.settings');
        if($config->get('path_file')){
            $path = trim($config->get('path_file'));
            $path = $path.$alias.'.json';
            $directory = dirname($path);
            $filename = basename($path);
            $json_page = Json::encode($json_page);
            \Drupal::service('filereader')->generateFileForce($directory,$filename,$json_page);
        }else{
            \Drupal::messenger()->addError('Path not find , check /admin/config/deploy');
        }

    }
    public function getJSONFormatter($bundle){

        $results = \Drupal::configFactory()->listAll("storage_config.render.".$bundle);
        if(!empty($results)){
            $config =  \Drupal::config(reset($results));
            return $config->get('content');
        }
        return null ;
    }
    public function convertTokenGlobalArray($block, $fields, $items, $alias = ['prefix' => '@']) {
        $alias += [
            'prefix' => '',
            'suffix' => '',
        ];
        foreach ($fields as $field_name => $type) {
            if (is_array($type)) {
                if (isset($type['type'])) {
                    switch ($type['type']) {
                        case 'function':
                            if (isset($type['hook_alias']) && is_string($type['hook_alias'])) {
                                $items = $this->convertTokenFunction($block, $field_name, $items, $alias, $type['hook_alias']);
                            } else {
                                $items = $this->convertTokenFunction($block, $field_name, $items, $alias);
                            }
                            break;
                        case 'paragraph':
                            if (isset($type['paragraph']) && isset($type['fields'])) {
                                $ref = 'id';
                                if (isset($type['reference'])) {
                                    $ref = $type['reference'];
                                }
                                $alias_array = ['prefix' => '%', 'suffix' => '%'];
                                if (isset($type["alias"])) {
                                    $alias_array = $type["alias"];
                                }
                                $items = $this->convertTokenArrayItem($field_name, $type['paragraph'], $type['fields'], $block, $items, $ref, $alias_array);
                            }
                            break;
                    }
                }
            } else {
                switch ($type) {

                    case 'text':
                        $items = $this->convertTokenTextItem($block, $field_name, $items, $alias);
                        break;

                    case 'function':
                        $items = $this->convertTokenFunction($block, $field_name, $items, $alias);
                        break;
                    case 'image':
                        $items = $this->convertTokenImageItem($block, $field_name, $items, $alias);
                        break;
                    case 'media_image':
                        $items = $this->convertTokenMediaImageItem($block, $field_name, $items, $alias);
                        break;
                }
            }
        }
        return $items;
    }
    public function convertTokenMediaImageItem($entity, $field_name, $items, $alias = ['prefix' => '@']) {
        $block = $entity->{$field_name}->entity ;
        if(is_object($block)){
            $alias += [
                'prefix' => '',
                'suffix' => '',
            ];
            $image_alt = "";
            $image_url = "/";
            if ($block->field_media_image) {

                $image_alt = ($block->field_media_image->alt && $block->field_media_image->alt != '') ? $block->field_media_image->alt : ' ';
                $image_title = ($block->field_media_image->title && $block->field_media_image->title != '') ? $block->field_media_image->title : null;
                if ($image_title == null) {
                    $items = $this->replaceChildContentInArray(trim($alias['prefix'] . $field_name . "_title" . $alias['suffix']), [], $items, 'thumbnail');
                    $items = $this->replaceChildContentInArray(trim($alias['prefix'] . $field_name . "_title" . $alias['suffix']), [], $items, 'desktop');
                }
                $url_img = $this->getImageUrl($block->field_media_image);
                $image_url = ($url_img) ? $url_img : "/";
                $items = $this->replaceValueInArray(trim($alias['prefix'] . $field_name . "_alt" . $alias['suffix']), $image_alt, $items);
                $items = $this->replaceValueInArray(trim($alias['prefix'] . $field_name . "_title" . $alias['suffix']), $image_title, $items);
                $items = $this->replaceValueInArray(trim($alias['prefix'] . $field_name . "_url" . $alias['suffix']), $image_url, $items);
            }
        }

        $displa_status = 'display-image';
        if ($image_url == "/") {
            $displa_status = 'display-none';
        }
        $items = $this->replaceKeyInArray(trim($alias['prefix'] . 'display_' . $field_name . $alias['suffix']), $displa_status, $items);
        $items = $this->convertTokenBaseItem($entity, $field_name, $items, $alias);

        return $items;
    }

    public function convertTokenBaseItem($block, $field_name, $items, $alias = ['prefix' => '@']) {
        if (!isset($alias['prefix'])) {
            $alias['prefix'] = "";
        }
        if (!isset($alias['suffix'])) {
            $alias['suffix'] = "";
        }
        $is_ready_status = $this->is_field_ready($block, $field_name);
        $is_ready = ($is_ready_status)?true:false ;
        $items = $this->replaceValueInArray(trim($alias['prefix'].'show_'.$field_name.$alias['suffix']) ,$is_ready,$items);
        $is_no_ready = ($is_ready)?false:true ;
        $items = $this->replaceValueInArray(trim($alias['prefix'].'hidden_'.$field_name.$alias['suffix']) ,$is_no_ready,$items);
        if(is_object($block)){
            $items = $this->replaceKeyInArray('@block_id', "block-id-".$block->id() , $items);
        }
        return $items ;
    }
    public function convertTokenTextItem($block, $field_name, $items, $alias = ['prefix' => '@']) {
        if (!isset($alias['prefix'])) {
            $alias['prefix'] = "";
        }
        if (!isset($alias['suffix'])) {
            $alias['suffix'] = "";
        }
        $value = isset($block->{$field_name}) ? $block->{$field_name}->value  : "";
        $token = trim($alias['prefix'] . $field_name . $alias['suffix']);
        $items = $this->replaceValueInArray($token, $value, $items);
        $displa_status='display-text';
        if ($value == "") {
            $displa_status ='display-none';
        }
        $items = $this->replaceKeyInArray(trim($alias['prefix'] . 'display_' . $field_name . $alias['suffix']),  $displa_status, $items);
        $items = $this->convertTokenBaseItem($block, $field_name, $items, $alias);
        return $items;
    }
    public function convertTokenFunction($block, $field_name, $items, $alias = ['prefix' => '@'],$hook_alias = 'func') {

        $is_exist = method_exists($this, $hook_alias . "_" . $field_name);
        if($is_exist){


            $resutls = call_user_func_array(array($this,$hook_alias . "_" . $field_name),[$block, $field_name, $items, $alias]);
            if ($resutls) {
                $items = $resutls ;
            }
            $items = $this->convertTokenBaseItem($block, $field_name, $items, $alias);
        }
        return $items;
    }
    public function convertTokenImageItem($block, $field_name, $items, $alias = ['prefix' => '@']) {
        $alias += [
            'prefix' => '',
            'suffix' => '',
        ];
        $image_alt = "";
        $image_url = "/";
        if ($block->{$field_name}) {
            //webp integration
            $id = $block->{$field_name}->target_id;
            $query = \Drupal::entityQuery('media')
                ->condition('image.target_id', $id, '=');
            $ids = $query->execute();
            if (!empty($ids)) {
                $media_id = end($ids);
                $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
                if (is_object($media)) {
                    $is_ready_status = $this->is_field_ready($media, 'webp_image');
                    if ($is_ready_status) {
                        $webp = $this->getImageUrl($media->webp_image);
                        $items = $this->addContentInParentLevelArray(trim($alias['prefix'] . $field_name . "_url" . $alias['suffix']), ['webp' => ['dekstop' => $webp, 'tablet' => $webp, 'mobile' => $webp, 'thumbnail' => $webp]], $items, 'thumbnail');
                    }
                }
            }

            $image_alt = ($block->{$field_name}->alt && $block->{$field_name}->alt != '') ? $block->{$field_name}->alt : ' ';
            $image_title = ($block->{$field_name}->title && $block->{$field_name}->title != '') ? $block->{$field_name}->title : null;
            if ($image_title == null) {
                $items = $this->replaceChildContentInArray(trim($alias['prefix'] . $field_name . "_title" . $alias['suffix']), [], $items, 'thumbnail');
                $items = $this->replaceChildContentInArray(trim($alias['prefix'] . $field_name . "_title" . $alias['suffix']), [], $items, 'desktop');
            }
            $url_img = $this->getImageUrl($block->{$field_name});
            $image_url = ($url_img) ? $url_img : "/";
            $items = $this->replaceValueInArray(trim($alias['prefix'] . $field_name . "_alt" . $alias['suffix']), $image_alt, $items);
            $items = $this->replaceValueInArray(trim($alias['prefix'] . $field_name . "_title" . $alias['suffix']), $image_title, $items);
            $items = $this->replaceValueInArray(trim($alias['prefix'] . $field_name . "_url" . $alias['suffix']), $image_url, $items);
        }

        $displa_status = 'display-image';
        if ($image_url == "/") {
            $displa_status = 'display-none';
        }
        $items = $this->replaceKeyInArray(trim($alias['prefix'] . 'display_' . $field_name . $alias['suffix']), $displa_status, $items);
        $items = $this->convertTokenBaseItem($block, $field_name, $items, $alias);
        return $items;
    }
    public function convertTokenArrayItem($field_name ,$reference_name,$fields,$block,$items,$ref='id',$alias = ['prefix'=>'%','suffix'=>'%']){
        $item_news =[];
        if($block->{$field_name}){
            $object_list = method_exists($block->{$field_name}, 'referencedEntities') ? $block->{$field_name}->referencedEntities() : [] ;
            if(!empty($object_list)){
                foreach(array_values($object_list) as $key =>  $object){
                    $temp_array = $this->getChildContentInArray($reference_name,$items,$ref) ;
                    if(!empty($temp_array)){
                        $temp = ($temp_array[0]);
                        unset($temp[$ref]);// because it will take by another widget
                        $temp[$ref] = $reference_name."_".$key;
                        $temp = $this->replaceValueInArray('%index%',$key,$temp);
                        $temp = $this->replaceValueInArray('%'.$reference_name.'%', $temp[$ref],$temp);
                        $temp = $this->replaceKeyInArray('%'.$reference_name.'%', $temp[$ref],$temp);
                        $item_news[] = $this->convertTokenGlobalArray($object,$fields,$temp,$alias);
                    }
                }
                $displa_status= "display-".$reference_name;
            }else{
                $displa_status= "display-none";
            }
            $items = $this->replaceKeyInArray('@display_'.$reference_name,$displa_status,$items);
        }
        $items = $this->convertTokenBaseItem($block, $field_name, $items, $alias);
        $items = $this->replaceChildParentContentInArray($reference_name,$item_news,$items,$ref);
        return $items;
    }
    public function replaceChildParentContentInArray($id, $new_parent, $items, $key_name = 'id') {
        if (!empty($items)) {
            foreach ($items as $key => $item) {
                if (isset($item[$key_name]) && trim($id) == trim($item[$key_name])) {
                    $items = $new_parent;
                    break;
                }
                else {
                    if (is_array($item)) {
                        $items[$key] = $this->replaceChildParentContentInArray($id, $new_parent, $item, $key_name);
                    }
                }
            }
        }
        return $items;
    }
    /**  utility section **/
    public function getChildContentInArray($id, array $items, $key_name = 'id') {
        $child = [];
        foreach ($items as $key => $item) {
            if (isset($item[$key_name]) && trim($id) == trim($item[$key_name])) {
                $child[] = $items[$key];
            }
            else {
                if (is_array($item)) {
                    $child =  array_merge($this->getChildContentInArray($id, $item, $key_name), $child);
                }
            }
        }

        return $child;
    }
    public function replaceValueInArray($string,$new_string, $items, $token = TRUE) {
        if(!empty($items)){

            foreach ($items as $key => $item) {
           
                $item_content_list  = null ;
              
                if($item && is_string($item)){
                
                    $item_content_list = explode(" ",trim($item));
                   
                }
                if(is_string($item) && strpos($item,$string) !== false) {
                    $items[$key] = str_replace($string, $new_string, trim($item));
                }
                if (is_string($item) && trim($string) == trim($item)) {
                  
                    $items[$key] = $new_string;
                }
                elseif ( $item_content_list && is_string($item) && in_array($string, $item_content_list) && $token) {
                 
                    $items[$key] = str_replace($string, $new_string, trim($item));
                }
                else {
 
                    if (is_array($item)) {
                     
                        $items[$key] = $this->replaceValueInArray($string, $new_string, $item);
                    }
                }
            }
        }
        return $items;
    }
    public function objectToArray($object){
        if(is_object($object)){
            $object = get_object_vars($object);


        }
        if(is_array($object)){
            return array_map(__FUNCTION__,$object);
        }else{
            return $object ;
        }
    }
    public function is_field_ready($entity, $field) {
        $bool = false;
        if (is_object($entity) && $entity->hasField($field)) {
            $field_value = $entity->get($field)->getValue();
            if (!empty($field_value)) {
                $bool = true;
            }
        }
        return $bool;
    }
    public function replaceChildContentInArray($id, $new_child, $items, $key_name = 'id') {
        $items_new = [];
        if (!empty($items)) {
            foreach ($items as $key => $item) {
                if (isset($item[$key_name]) && trim($id) == trim($item[$key_name])) {
                    if (!empty($new_child)) {
                        $items[$key] = reset($new_child);
                    } else {
                        unset($items[$key]);
                    }
                    if (is_numeric($key)) {
                        $items = array_values($items);
                    }
                    break;
                } else {
                    if (is_array($item)) {
                        $items[$key] = $this->replaceChildContentInArray($id, $new_child, $item, $key_name);
                    }
                }
            }
        }
        return $items;
    }
    public function addContentInChildLevelArray($id, $new_parent, $items, $key_name = 'id') {
        if (!empty($items)) {
            foreach ($items as $key => $item) {
                if (isset($item[$key_name]) && trim($id) == trim($item[$key_name])) {
                    $items[$key] = array_merge($items[$key],$new_parent);
                    break;
                }
                else {
                    if (is_array($item)) {
                        $items[$key] = $this->addContentInChildLevelArray($id, $new_parent, $item, $key_name);
                    }
                }
            }
        }
        return $items;
    }
    public function addContentInParentLevelArray($id, $new_parent, $items, $key_name = 'id') {
        if (!empty($items)) {
            foreach ($items as $key => $item) {
                if (isset($item[$key_name]) && trim($id) == trim($item[$key_name])) {
                    $items = array_merge($items,$new_parent);
                    break;
                }
                else {
                    if (is_array($item)) {
                        $items[$key] = $this->addContentInParentLevelArray($id, $new_parent, $item, $key_name);
                    }
                }
            }
        }
        return $items;
    }
    public function replaceKeyInArray($string_key,$string_new_key,  $items, $token = TRUE) {
        if (!empty($items)) {
            foreach ($items as $key => $item) {
                $item_key_list  = null ;
                if($key  && is_string($key)){
                    $item_key_list = explode(" ",trim($key));
                }
                if (is_string($key) && trim($string_key) == trim($key)) {
                    $items[$string_new_key] = $item;
                    unset($items[$key]);
                }
                elseif ($item_key_list  && in_array($string_key, $item_key_list)  && is_string($key) && strpos(trim($key), $string_key) !== FALSE && $token) {

                    $items[str_replace($string_key, $string_new_key, trim($key))] = $item;
                    unset($items[$key]);
                }
                else {
                    if (is_array($item)) {
                        $items[$key] = $this->replaceKeyInArray($string_key, $string_new_key, $item);
                    }
                }
            }
        }

        return $items;
    }
    public function getImageUrl(FieldItemList $file_list) {
        if ($file_list && count($file_list) > 0) {
            $target_file = $file_list->entity;
            return file_create_url($target_file->getFileUri());
        }
    }
    public function autoConvertToken($entity,$items,$field_not_allowed=[],$alias=['prefix' => '@']) {
        $bundle = $entity->bundle();
        $entity_type = $entity->getEntityTypeId();
        $fields_list = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
        $field_selected = [];
        foreach( $fields_list as $key => $field){

            if(!in_array($key,$field_not_allowed)){
                $string_type = ['text_long','text','integer','string','string_long','boolean'];
                $link =['link'];
                $image_type = ['image'];
                $type = $field->getType();
                if($type=='entity_reference'){
                    $setting_field = $entity->get($key)
                    ->getFieldDefinition()
                    ->getSettings();

                    if(isset($setting_field['target_type'])
                        && is_string($setting_field['target_type'])
                        && $setting_field['target_type']=='media'){
                        $media = $entity->{$key}->entity ;
                        if(is_object($media)){
                            if($media->bundle() && $media->bundle() =='image'){
                                $field_selected[$key] = 'media_image';
                            }
                        }
                    }
                }
                if(in_array($type,$string_type)){
                    $field_selected[$key] = 'text';
                }
                if(in_array($type,$image_type)){
                    $field_selected[$key] = 'image';
                }
                if(in_array($type,$link)){
                    $field_selected[$key] = 'link';
                }
                $key_names = array_keys($field_selected);
                if(!in_array($key,$key_names)) {
                    $field_selected[$key] = 'function';
                }
            }
        }
        return $this->convertTokenGlobalArray($entity,$field_selected,$items,$alias);

    }

    public function executeJSON($node , $output ){
        global $base_url;
        $currentDateTime = new DrupalDateTime();
        $date = $currentDateTime->format('Y-m-d');
        $output = $this->replaceValueInArray('{%base_url%}',$base_url,$output);
        $output = $this->replaceValueInArray('{%date_now%}',$date,$output);
        return $output ;
    }


}
