<?php

namespace Drupal\mz_mobile_page\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ApiController.
 */
class DefaultController extends ControllerBase
{
   
    protected function responseCacheableJson($data)
    {
        // Add Cache settings for Max-age and URL context.
        // You can use any of Drupal's contexts, tags, and time.

        $config = $this->config('system.performance');
        $build = [
            '#cache' => [
                // The max-age use system settings.
                'max-age' => $config->get('cache.page.max_age'),
                'contexts' => [
                    'url',
                ],
            ],
        ];

        $response = new CacheableJsonResponse($data);
        $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($build));
        return $response;
    }
    public function apiDetailsJson($entitype, $bundle, $id){
        $fields = \Drupal::request()->get('fields');
        $changes = \Drupal::request()->get('changes'); // change name field ouput
        $values = \Drupal::request()->get('values'); // change name field ouput
        $options = [];
         if(is_array($fields)){
            $item = \Drupal::service('entity_parser.manager')->loader_entity_by_type($id, $entitype, $fields, $options);
        }else{
            $item = \Drupal::service('entity_parser.manager')->loader_entity_by_type($id, $entitype);
        }
                if($values){            
                        foreach ($item as $key_field => $value_field) {
                            if(isset($values[$key_field])){
                                $key_name = ($values[$key_field]);
                                $val = $this->getValueArray($item,$key_name );
                                $item[$key_field] = $val ;
                            }
                        }
                }
                if($changes){
                        foreach ($item as $key_field => $value_field) {
                            if(isset($changes[$key_field])){
                                $item[$changes[$key_field]] = $value_field ;
                                unset($item[$key_field]);
                            }
                        }                
                }
        return new JsonResponse($item);
    }
    public function uploader(Request $request)
    {
        $file = $request->files->get('image');
        if ($file) {   
    
         $file = File::create([
            'uid' => 1,
            'uri' => $file->getPathname()      
         ]);
        $file->setPermanent();
        $file->save();
   
          return new JsonResponse([
            'success' => true,
            'message' => 'Image uploaded successfully.'
         // 'fid' => $file->getPathname()   ,
         //  'file' => $file->getClientOriginalName()   ,
         //  'file1' => $file->getFilename()   
          ]);
        }
        else {
          return new JsonResponse([
            'success' => false,
            'message' => 'Failed to upload image.',
          ]);
        }
    }
    public function apiListJson($entitype, $bundle)
    {
        $pager = \Drupal::request()->get('pager');
        $offset = \Drupal::request()->get('offset');
        $fields = \Drupal::request()->get('fields');
        $filters = \Drupal::request()->get('filters');
        $changes = \Drupal::request()->get('changes'); // change name field ouput
        $values = \Drupal::request()->get('values'); // change name field ouput
        $sort = \Drupal::request()->get('sort');
        if ($pager == null) {
            $pager = 0;
        }
        if ($offset == null) {
            $offset = 10;
        }
        $key_bundle = \Drupal::entityTypeManager()->getDefinition($entitype)->getKey('bundle');
        $query = \Drupal::entityQuery($entitype)->condition($key_bundle, $bundle);
        if($filters){
           foreach ($filters as $key => $filter) {
                   if (isset($filter['op']) && $filter['op'] != null) {
                    $query->condition($key, $filter['val'], $filter['op']);
                   } else {
                        if(is_array($filter['val'])){
                            $query->condition($key, $filter['val'],'IN');
                        }else{
                            $query->condition($key, $filter['val']);
                        }          
                   }
            }
        }
        if( $sort ){
          $query->sort($sort['val'],$sort['op']);
        }
            if ($pager) {
                if($pager == 'all'){}else{
                $query->range($offset * ($pager), $offset);
                }
            } else {
                $query->range(0, $offset);
            }
            
        $json = $query->execute();
        $results = [];
        $options = [];
        foreach ($json as $key => $id) {
            if(is_array($fields)){
                $results[] = \Drupal::service('entity_parser.manager')->loader_entity_by_type($id, $entitype, $fields, $options);
            }else{
                $results[] = \Drupal::service('entity_parser.manager')->loader_entity_by_type($id, $entitype);
            }
        }
        if($values){
            foreach ($results as $key => $item) {
                foreach ($item as $key_field => $value_field) {
                    if(isset($values[$key_field])){
                        $key_name = ($values[$key_field]);
                        $val = $this->getValueArray($item,$key_name );
                        $results[$key][$key_field] = $val ;
                    }
                }              
            }
        }
        if($changes){
            foreach ($results as $key => $item) {
                foreach ($item as $key_field => $value_field) {
                    if(isset($changes[$key_field])){
                        $results[$key][$changes[$key_field]] = $value_field ;
                        unset($results[$key][$key_field]);
                    }
                }
               
            }
        }

        
        return new JsonResponse($results);
       // return $this->responseCacheableJson($results);     
    }
    /**
     * Custom access funciton
     *
     * @return Drupal\Core\Access\AccessResult
     */
    public function apiJsonAccess()
    {
        return AccessResult::allowed();
    }
    function getValueArray($data,$key ){
        $keys = explode('.', $key);
        $result = $data;
       foreach ($keys as $key) {
         if (isset($result[$key])) {
            $result = $result[$key];
         } else {
             $result = null;
             break;
         }
       }
       return $result;
        
     }

}
