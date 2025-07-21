<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 6/7/18
 * Time: 5:52 PM
 */

namespace Drupal\drupal_helper;

use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Database\Database;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\FileStorage;
use Drupal\media\Entity\Media;
use GuzzleHttp\Exception\RequestException;
use NumberFormatter;
class DrupalCommonHelper
{   public function convert_number_to_words($number) {
    $formatter = new NumberFormatter('fr', NumberFormatter::SPELLOUT);
    return $formatter->format($number);
    }

    public function generate_machine_name($inputString) {
        // Convert the input string to lowercase.
        $machineName = strtolower($inputString);
      
        // Replace spaces with underscores.
        $machineName = str_replace(' ', '_', $machineName);
      
        // Remove any characters that are not allowed in a machine name.
        $machineName = preg_replace('/[^a-z0-9_]/', '', $machineName);
      
        return $machineName;
    }
    public function storage_set($name,$values,$cache_time= 604800){
        $expiration_time = REQUEST_TIME + $cache_time;
        $uid = \Drupal::currentUser()->id();
        $tempstore = \Drupal::service('tempstore.private')->get('drupal_helper');
        $tempstore->set($name."_".$uid ,$values,$expiration_time);
    }
    public function storage_get($name){
        $tempstore = \Drupal::service('tempstore.private')->get('drupal_helper');
        $uid = \Drupal::currentUser()->id();
        return  $tempstore->get($name."_".$uid);
    }
    public function storage_delete($name){
        $tempstore = \Drupal::service('tempstore.private')->get('drupal_helper');
        $uid = \Drupal::currentUser()->id();
        return  $tempstore->delete($name."_".$uid);
    }
    public static function is_module_exist($module_name)
    {
        return \Drupal::moduleHandler()->moduleExists($module_name);
    }
    public function image($media,$option = [] ){
        if(is_string($option)){
            $array = explode('x',$option);
            $option = [];
            if(!empty($array)){
                $option['width'] =  $array[0];
                $option['height'] =  $array[1];
            }
        }
        if(is_numeric($media)){
            $media = \Drupal::entityTypeManager()->getStorage('media')->load($media);   
        }
        
        $url = "";
        if($media instanceof Media && empty($option)){
            if(isset($option['field']) && $this->get_type_field($media,$option['field']) == 'image'){
                $field_selected = $option['field'] ;
            }else{
                $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('media', 'image');
                foreach ($fields as $key => $field) {
                    if ($field->getType() === 'image' && $this->is_field_ready($media,$key)) {
                        $field_selected = $key;
                        continue ;
                    }
                }
            }
            $file = $media->{$field_selected}->entity ;
            $image_uri = $file->getFileUri();
            return file_create_url($image_uri);
        }

    
        if($media instanceof Media && isset($option['width']) && isset($option['height'])){
            $name = (isset($option['name'])) ? $option['name'] :  $option['width']."x".$option['height'] ;    
            $url = "https://via.placeholder.com/".$name.".png?text=Not+Found+".$name;

            if(isset($option['field']) && $this->get_type_field($media,$option['field']) == 'image'){
                $field_selected = $option['field'] ;
            }else{
                $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('media', 'image');
                foreach ($fields as $key => $field) {
                    if ($field->getType() === 'image' && $this->is_field_ready($media,$key)) {
                        $field_selected = $key;
                        continue ;
                    }
                }
            }
            $file = $media->{$field_selected}->entity ;
            $image_uri = $file->getFileUri();
            $image_style = ImageStyle::load($name);
            if($image_style){
                $url = $image_style->buildUrl($image_uri);
            }else{
                $image_style = ImageStyle::create([
                    'name' => $name, // @TODO This will create a new image derivative on each request.
                    'label' => $name
                  ]);
                $image_style->addImageEffect([
                    'id' => 'image_scale_and_crop',
                    'weight' => 0,
                    'data' => [
                      'width' => $option['width'],
                      'height' => $option['height'],
                    ],
                  ]);
                  $image_style->save();
                  $url = $image_style->buildUrl($image_uri);
            }
            $success = file_exists($url) || $image_style->createDerivative($image_uri, $url);

        }
        return $url ;
    }
    public function isExistUrl($url){
        $file_headers = @get_headers($url);
        if(!$file_headers || 
         $file_headers[0] == 'HTTP/1.1 404 Not Found' || 
         $file_headers[0] == 'HTTP/1.0 404 Not Found' 
         ) {
            
            $exists = false;
        }
        else {
            $exists = true;
        }
        return $exists ;
    }
    public function getThemeList(){
        $list = \Drupal::service('extension.list.theme')->getList();
        $themes = [];
        foreach ($list as $theme){
            if(is_object($theme)){
                $path = $theme->getPath();
                $path_array = explode('/',$path);
                if(!empty($path_array)
                    &&$path_array[0]
                    && $path_array[1]
                    && $path_array[0] =='themes' && $path_array[1] =='custom'
                    && $theme->status == 1
                ){
                    $themes[$theme->getName()] = [
                        "name"=>$theme->getName(),
                        "path"=> $theme->getPath()
                    ];
                }
            }
        }
        return $themes ;

    }
    public function getThemePath($item){
        $config = \Drupal::config($item) ;
        $theme_name = $config->get('theme') ;
        $list = \Drupal::service('extension.list.theme')->getList();
        if(in_array($theme_name,$list)){
            return \Drupal::service('extension.list.theme')->getPath($theme_name);
        }else{
            return false ;
        }

    }
    public function taxonomy_get_children($tid)
    {
        $results = [];
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($tid);

        if (!empty($children)) {
            $results = array_keys($children);

            foreach ($children as $key => $child) {
                $next = $this->taxonomy_get_children($key);
                if (!empty($next)) {
                    $results = array_merge($results, ($next));

                }
            }

        }

        return $results;

    }

    public function theme_logo()
    {
        return file_create_url(theme_get_setting('logo.url'));
    }

    public function count_comments($nid)
    {
        $num_comment = db_query('SELECT comment_count FROM {comment_entity_statistics} WHERE entity_id = ' . $nid)->fetchAssoc();
        return $num_comment['comment_count'];
    }

    public function serialize_array($params)
    {
        $str = "";
        foreach ($params as $key => $param) {
            if (is_array($param)) {
                foreach ($param as $sparam) {
                    $str .= $key . "=" . $sparam . "&";
                }
            } else {
                $str .= $key . "=" . $param . "&";
            }

        }


        return substr($str, 0, strlen($str) - 1);
    }

    public function entity_render_tostring($id, $entity_type = 'node', $mode_view = 'teaser')
    {
        $result = $this->entity_render_toview($id, $entity_type, $mode_view);
        return \Drupal::service('renderer')->renderRoot($result);
    }

    public function entity_render_toview($id, $entity_type = 'node', $mode_view = 'teaser')
    {
        $entity_type_manager = \Drupal::entityTypeManager();
        $entity = $entity_type_manager->getStorage($entity_type)->load($id);

        $view_builder = $entity_type_manager->getViewBuilder($entity_type);
        return $view_builder->view($entity, $mode_view);
    }

    public function get_router_list($route_name_list = [])
    {
        $db = Database::getConnection();
        $query = $db->select('router', 'rt');
        $query->fields('rt', ['name', 'pattern_outline', 'path', 'number_parts']);
        $query->condition('rt.name', ($route_name_list), 'IN');
        return $query->execute()->fetchAllAssoc('name');
    }

    public function user_load_object_by_nid($uid)
    {
        $entity = $this->entity_load_by_id('user', $uid);
        return $entity;
    }

    public function entity_load_by_id($entity_type, $id)
    {
        $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($id);
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        if ($entity && method_exists($entity, 'hasTranslation') && $entity->hasTranslation($language)) {
            $entity = $entity->getTranslation($language);
        }
        return $entity;
    }

    public function taxonomy_url($term_id)
    {
        return ('taxonomy/term/' . $term_id);
    }

    public function get_current_path_theme()
    {
        $theme = \Drupal::theme()->getActiveTheme();
        return $theme->getPath();
    }

    public function switch_language_url($url, $lang = 'en')
    {
        $lang_list = \Drupal::languageManager()->getLanguages();
        $code = null;
        $url_array = explode('/', $url);
        $status = true;
        foreach ($url_array as $key_lg => $lg) {
            if (in_array($lg, array_keys($lang_list))) {
                $url_array[$key_lg] = $lang;
                $status = false;
            }
        }
        if ($status) {
            return '/' . $lang . '/' . $url;
        }
        return implode('/', $url_array);
    }

    public function get_route_name_by_url($path)
    {
        $url_object = \Drupal::service('path.validator')->getUrlIfValid($path);
        if (is_object($url_object)) {
            return $url_object->getRouteName();
        } else {
            return null;
        }
    }

    public function get_route_name_by_url_current()
    {
        $url = $this->current_url();
        $url_object = \Drupal::service('path.validator')->getUrlIfValid($url);
        if (is_object($url_object)) {
            return $url_object->getRouteName();
        } else {
            return null;
        }
    }

    public function current_url()
    {
        $url = Url::fromRoute('<current>');
        return $url->getInternalPath();
    }

    public function generate_url_node_edit($nid)
    {
        $options = ['absolute' => TRUE];
        $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $nid], $options);
        return $url->toString();
    }

    public function taxonomy_load_by_name($term_name, $vid = null)
    {
        $taxonomy_terms = taxonomy_term_load_multiple_by_name($term_name, $vid);
        $result = [];
        if (!empty($taxonomy_terms)) {
            foreach ($taxonomy_terms as $key => $taxonomy_term) {
                $result[] = array('name' => $taxonomy_term->label(), 'tid' => $taxonomy_term->id());
            }
        }
        if (count($result) == 1) {
            return array_shift($result);
        }
        return $result;
    }

    public function taxonomy_getallparent($tid)
    {
        $parent = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadAllParents($tid);
        $terms = [];
        foreach ($parent as $key => $term) {
            $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
            if ($term && method_exists($term, 'hasTranslation') && $term->hasTranslation($language)) {
                $terms[$key] = $term->getTranslation($language);
            }
        }
        return $terms;
    }

    public function taxonomy_getparent_tid($tid)
    {
        $parent = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadParents($tid);
        if (!empty($parent)) {
            return array_keys($parent)[0];
        } else {
            return null;
        }
    }

    public function taxonomy_first_level_by_vid($vid)
    {
        $terms = $this->taxonomy_load_multi_by_vid($vid);
        $first_level = [];
        if (!empty($terms)) {
            foreach ($terms as $term) {
                $parent = $this->taxonomy_getparent($term['tid']);
                if (!$parent) {
                    $first_level[] = $term;
                }
            }
        }
        return $first_level;
    }

    public function taxonomy_load_multi_by_vid($vid)
    {
        $connection = Database::getConnection();
        $res = $connection->select('taxonomy_term_data', 'n')
            ->fields('n', array('tid', 'vid'))
            ->condition('n.vid', $vid, '=')
            ->execute()
            ->fetchAllAssoc('tid');
        $items = [];
        foreach (array_keys($res) as $key => $tid) {
            //taxonomy_term
            $taxonomy_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
            $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
            if ($taxonomy_term && method_exists($taxonomy_term, 'hasTranslation') && $taxonomy_term->hasTranslation($language)) {
                $taxonomy_term = $taxonomy_term->getTranslation($language);
            }
            if (is_object($taxonomy_term)) {
                $items[] = array(
                    'name' => strtolower($taxonomy_term->label()),
                    'tid' => $taxonomy_term->id(),
                    'url' => $this->taxonomy_url_alias($tid),
                    'object' => $taxonomy_term
                );
            }
        }

        return $items;
    }

    public function taxonomy_url_alias($term_id)
    {
        if (is_numeric($term_id)) {
            return \Drupal::service('path_alias.manager')->getAliasByPath('/taxonomy/term/' . $term_id);
        }
        if (is_string($term_id)) {
            return \Drupal::service('path_alias.manager')->getAliasByPath($term_id);
        }
    }

    public function taxonomy_getparent($tid)
    {
        $parent = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadParents($tid);
        if (!empty($parent)) {
            $parent = reset($parent);
        } else {
            $parent = null;
        }
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        if ($parent && method_exists($parent, 'hasTranslation') && $parent->hasTranslation($language)) {
            $parent = $parent->getTranslation($language);
        }
        return $parent;
    }

    public function current_lang()
    {
        return \Drupal::languageManager()->getCurrentLanguage()->getId();
    }

    public function get_list_content_type()
    {
        $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
        $options = [];
        foreach ($node_types as $node_type) {
            $options[$node_type->id()] = $node_type->label();
        }
        return $options;
    }

    public function get_node_type_by_url($url)
    {
        $nid = $this->get_numeric_args_url($url);
        $type = null;
        if ($nid) {
            $node_object = $this->node_load_object_by_nid($nid);
            if (is_object($node_object)) {
                $type = $node_object->getType();
            }
        }
        return $type;
    }

    public function get_numeric_args_url($url = null)
    {
        $alias_manager = \Drupal::service('path_alias.manager');
        $alias = $this->current_url_alias();
        try {
            $path = $alias_manager->getPathByAlias($alias);
            $route = Url::fromUserInput($path);
            if ($route && $route->isRouted()) {
                $params = $route->getRouteParameters();
                if (!empty($params['node'])) {
                    return $params['node'];
                }
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    public function node_load_object_by_nid($nid)
    {
        $entity = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        if ($entity && method_exists($entity, 'hasTranslation') && $entity->hasTranslation($language)) {
            $entity = $entity->getTranslation($language);
        }
        return $entity;
    }

    public function current_url_alias()
    {
        $url = Url::fromRoute('<current>');
        $url_alias = \Drupal::service('path_alias.manager')->getAliasByPath($url->toString());
        return $url_alias;
    }
    public function node_url_alias($nid)
    {
        return \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nid);
    }

    public function getEntityByAlias($url_alias = null)
    {
        if($url_alias == null){
            $url_alias = \Drupal::service('path.current')->getPath();
        }
        $alias = \Drupal::service('path_alias.manager')->getPathByAlias($url_alias);
        $is_routed = (Url::fromUri("internal:" . $alias)->isRouted());
       
        if(!$is_routed){
            return false ;
        }
        $params = Url::fromUri("internal:" . $alias)->getRouteParameters();
        $entity_type = key($params);
        if(isset($params[$entity_type]) && $params[$entity_type] != ""){
            $object =  \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type]);
            return $object  ;
        }else{
            return false ;
        }
   
    }

    public function get_menu_tree($menu)
    {
        $tree = \Drupal::menuTree()->load($menu, new MenuTreeParameters());
        $menu = [];
        foreach ($tree as $item) {
            $menu["url"] = $item->link->getUrlObject()->toString();
            $menu["title"] = $item->link->getTitle();
            $menu["link"] = $item->link;
        }
        return $tree;
    }

    public function str_ends_with($haystack, $needle)
    {
        return strrpos($haystack, $needle) + strlen($needle) ===
            strlen($haystack);
    }

    public function get_uri_image_by_fid($fid, $style = null)
    {
        $file = \Drupal\file\Entity\File::load($fid);
        if (is_object($file)) {
            if ($style != null) {
                return \Drupal\image\Entity\ImageStyle::load($style)->buildUrl($file->getFileUri());
            } else {
                return $file->getFileUri();
            }
        } else {
            return null;
        }
    }

    public function is_login()
    {
        $user = \Drupal::currentUser();
        return !$user->isAnonymous();
    }

    public function current_user()
    {
        $userCurrent = \Drupal::currentUser();
        if ($userCurrent->id() != 0) {
            return array(
                "name" => $userCurrent->getAccountName(),
                "uid" => $userCurrent->id(),
                "email" => $userCurrent->getEmail(),
                "user" => $userCurrent
            );
        } else {
            return array(
                "user" => $userCurrent
            );
        }


    }

    public function get_block_custom_type($block_content)
    {
        if (is_object($block_content)) {
            return $block_content->bundle();
        } else {
            return null;
        }
    }

    public function node_id_by_current_node_url()
    {
        $path = $this->current_url();
        if ($this->str_starts_with($path, "node/")) {
            $fs_refer = explode("node/", $path);
            return $fs_refer[1];
        } else {
            return null;
        }
    }

    function str_starts_with($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    public function taxonomy_children_all($tid)
    {
        $results = [];
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($tid);

        if (!empty($children)) {
            $results = array_keys($children);

            foreach ($children as $key => $child) {
                $next = $this->taxonomy_children_all($key);
                if (!empty($next)) {
                    $results = array_merge($results, ($next));
                }
            }

        }

        return $results;

    }

    public function get_vocabulary_by_taxonomy_url()
    {
        $tid = $this->taxonomy_id_by_current_url();
        return $this->get_vocabulary_by_tid($tid);
    }

    public function taxonomy_id_by_current_url()
    {
        $path = $this->current_url();
        if ($this->str_starts_with($path, "taxonomy/term/")) {
            $fs_refer = explode("taxonomy/term/", $path);
            return $fs_refer[1];
        } else {
            return null;
        }
    }

    public function get_vocabulary_by_tid($tid)
    {
        $term = $this->taxonomy_load_by_tid($tid, "full");
        $vid = $term->get("vid")->getValue();
        return $vid[0]['target_id'];

    }

    public function send_mail_simple($message,$to,$from,$subject) {
       // $to = "miandrilala9@yahoo.fr";
       //  $subject = "Download link for file";
       // $message = "Click the link below to download the file:<br>";
       // $message .= "<a href='http://example.com/path/to/file.pdf'>Download File</a>";
        // Headers
        $headers = "From: ".$from."\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Send email
        if (mail($to, $subject, $message, $headers)) {
           $message = "Email sent successfully.";
           \Drupal::logger('drupal_helper')->notice($message);
           return true ;
        } else {
           $message = "Email sending failed.";
          \Drupal::logger('drupal_helper')->error($message);
          return false;
        }
        
      }
    public function taxonomy_load_by_tid($tid, $type = "teaser")
    {
        if (is_numeric($tid)) {
            $taxonomy_term = \Drupal::entityTypeManager()
                ->getStorage('taxonomy_term')->load($tid);
            $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
            if ($taxonomy_term && method_exists($taxonomy_term, 'hasTranslation') && $taxonomy_term->hasTranslation($language)) {
                $taxonomy_term = $taxonomy_term->getTranslation($language);
            }

            if (is_object($taxonomy_term)) {
                if ($type == "full") {
                    return $taxonomy_term;
                } else {
                    return array('name' => $taxonomy_term->label(), 'tid' => $taxonomy_term->id());
                }
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    public function get_parameter($param = null)
    {
        $method = \Drupal::request()->getMethod();
        if ($param == null) {
            if ($method == "GET") {
                return \Drupal::request()->query->all();
            } elseif ($method == "POST") {
                return \Drupal::request()->request->all();
            } else {
                return null;
            }
        } else {
            if ($method == "GET") {
                return \Drupal::request()->query->get($param);
            } elseif ($method == "POST") {
                return \Drupal::request()->request->get($param);
            } else {
                return null;
            }
        }
    }

    function get_multi_query_request()
    {
        if ($_SERVER['QUERY_STRING']) {
            foreach (explode("&", $_SERVER['QUERY_STRING']) as $tmp_arr_param) {
                $tmp_arr_param_1 = explode("=", $tmp_arr_param);
                $filters[$tmp_arr_param_1[0]][] = $tmp_arr_param_1[1];
            }
            foreach ($filters as $key => $filter) {
                if (count($filter) == 1) {
                    $filters[$key] = array_shift($filter);
                }
            }
        } else {
            $filters = null;
        }

        return $filters;
    }
    public function get_block_custom_by_info($info){
         $block_content_storage = \Drupal::entityManager()->getStorage('block_content');
         return $block_content_storage->loadByProperties(['info' => $info]);
    }
    //insert block custom content
    public function insert_content_block($type, $title, $fields = array())
    {

        // Grab a block entity manager from EntityManager service
        $blockEntityManager = \Drupal::service('entity.manager')
            ->getStorage('block_content');
        $block_type = $this->block_custom_load_by_type($type);
        if (!empty($block_type)) {

            // Tell block entity manager to create a block of type "ad_block"
            $block = $blockEntityManager->create(array(
                'type' => $type
            ));
            // Every block should have a description, but strangely it's property
            // is not 'description' but 'info'
            // in my case, I want it to be equal to my ad_group's term name.
            $block->info = $title;
            // This is optional part, my ad_block has a field field_ad_group
            // which is a taxonomy reference to the ad_group taxonomy,
            // that way I link ad_group and ad_block together.
            foreach ($fields as $key => $field) {
                $is_exist = $this->is_field_ready($block, $key);
                if ($is_exist) {
                    $block->{$key} = $field;
                } else {
                    drupal_set_message('Block type:' . $type . 'do not have Field:' . $key, 'warning');
                }
            }
            //$block->field_ad_group = $entity;
            // In the end, save our new block.
            $block->save();
        } else {
            drupal_set_message('Block type:' . $type . ' not exist', 'error');
        }
    }

    //get module path

    public function block_custom_load_by_type($type)
    {
        $query = \Drupal::entityTypeManager()->getStorage("block_content")->getQuery();
        $query->condition("type", $type);
        $resultat = $query->execute();
        $blocks = [];
        if (!empty($resultat)) {
            foreach ($resultat as $block_id) {
                $blocks[] = \Drupal::entityTypeManager()->getStorage("block_content")->load($block_id);
            }
        }
        return $blocks;
    }


    public function is_field_ready($entity, $field)
    {
        $bool = false;
        if (is_object($entity) && $entity->hasField($field)) {
            $field_value = $entity->get($field)->getValue();
            if (!empty($field_value)) {
                $bool = true;
            }
        }
        return $bool;
    }

    public function get_module_path($module_name)
    {
        $module_handler = \Drupal::service('module_handler');
        return $module_handler->getModule($module_name)->getPath();
    }

    public function is_url_admin()
    {
        $route = \Drupal::routeMatch()->getRouteObject();
        return \Drupal::service('router.admin_context')->isAdminRoute($route);
    }

    public function paragraph_load_object($key)
    {
        $paragraph = \Drupal::entityTypeManager()
            ->getStorage('paragraph')
            ->load($key);
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

        if ($paragraph && method_exists($paragraph, 'hasTranslation') && $paragraph->hasTranslation($language)) {
            $paragraph = $paragraph->getTranslation($language);
        }
        return $paragraph;
    }
    public function set_config($name,$value){
        $status = \Drupal::configFactory()->getEditable('drupal_helper.values')
        ->set($name, $value)
        ->save();
    }
    public function get_config($name){
        $config = \Drupal::config('drupal_helper.values');
        return $config->get($name);
    }
    public function delete_config($name){
        $config = \Drupal::configFactory()->getEditable('drupal_helper.values');
        $config->clear($name);           
        return $config->save();
    }
    public function clean_config(){
        $config = \Drupal::configFactory()->getEditable('drupal_helper.values')->delete();
        return $config->save();
    }

    public function getConfigContains($filter){
        $configs = \Drupal::configFactory()->listAll();
        $result = [];
        foreach ($configs as $config_name){
            if (is_string($filter) && strpos($config_name, $filter) !== false) {
                $result[] = $config_name ;
            }
            if (is_array($filter) && in_array($config_name, $filter) !== false) {
                $result[] = $config_name ;
            }
        }
        return $result ;
    }
    public function exportConfig($config_name,$path){
        $config = \Drupal::config($config_name) ;
        $data = $config->getOriginal();
        try {
            $output = Yaml::encode($data);
            $status = $this->generateFileForce(DRUPAL_ROOT.$path ,$config_name.'.yml',$output);
            if($status){
                \Drupal::messenger()->addMessage('exported Config '.$config_name);
            }
        }
        catch (InvalidDataTypeException $e) {
            \Drupal::messenger()->addError($this->t('Invalid data detected for @name : %error', array('@name' => $config_name, '%error' => $e->getMessage())));
            return;
        }

    }
    public function generateFileForce($directory, $filename, $content)
    {
        $fileSystem = \Drupal::service('file_system');
        if (!is_dir($directory)) {
            if ($fileSystem->mkdir($directory, 0777, TRUE) === FALSE) {
                drupal_set_message(t('Failed to create directory ' . $directory), 'error');
                return FALSE;
            }
        }else{
            @chmod($directory  , 0777);
        }

        if (file_put_contents($directory . '/' . $filename , $content) === FALSE) {
            drupal_set_message(t('Failed to write file ' . $filename), 'error');
            return FALSE;
        }
        return TRUE;
    }
    public function importConfig($config_name,$path){
        $source = new FileStorage(DRUPAL_ROOT.$path);
        $config_storage = \Drupal::service('config.storage');
        if($source->exists($config_name)){
            if($config_storage->exists($config_name)){
                $status =  $config_storage->write($config_name, $source->read($config_name));
                if($status){
                  \Drupal::messenger()->addMessage('updated Config '.$config_name);
                }
           }else{
                $status = \Drupal::configFactory()->getEditable($config_name)
                    ->setData($source->read($config_name))
                    ->save();
                if($status){
                    \Drupal::messenger()->addMessage('Create Config '.$config_name);
                }
            }
        }else{
            \Drupal::messenger()->addError('File yml '.$config_name.' not exit in folder '.$path);
            return false;
        }
    }

    /**
     * @param entity_type can be 'taxonomy_term' or 'node' or 'user'
     * entity_name is the name of your entity
     * @note : for get field user list $entity_type='user' and $entity_name = 'user'
     * @deprecated
     *   Use helper->get_entity_fields() instead in most cases.
     */
    public function get_fields_by_entity_info($entity_type, $entity_name = null)
    {
        $entity_type_list = array("taxonomy_term", "node", "user");
        if (in_array($entity_type, $entity_type_list)) {
            $fields_node_config = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $entity_name);
            $fields = [];
            foreach ($fields_node_config as $key => $field) {
                $fields[] = array(
                    "name" => $key,
                    "type" => $field->getType(),
                    "target_type" => (isset($field->getSettings()['target_type'])) ? $field->getSettings()['target_type'] : ""
                );
            }
            return $fields;
        } else {
            return null;
        }
    }
    public  function readDirectory($directory,$format = 'json'){
            $path_file = [];
            if (is_dir($directory)) {
                $it = scandir($directory);
                if (!empty($it)) {
                    foreach ($it as $fileinfo) {
                        $element =  $directory . "/" . $fileinfo;
                        if (is_dir($element) && substr($fileinfo, 0, strlen('.')) !== '.') {
                            $childs = $this->readDirectory($element,$format);
                            $path_file = array_merge($childs , $path_file);
                        }else{
                            if ($fileinfo && strpos($fileinfo, '.'.$format) !== FALSE) {
                                if (file_exists($element)) {
                                    $path_file[] =  $directory . "/" . $fileinfo;
                                }
                            }
                        }
                    }
                }
            }else{
                drupal_set_message(t('No permission to read directory ' . $directory), 'error');
                @chmod($directory  , 0777);
            }
            return $path_file;
    }
    public function get_current_nid_by_custom_pattern_url()
    {
        $url = $this->current_url();
        $url_array = explode("/", $url);
        $nid = null;
        foreach ($url_array as $u) {
            if (is_numeric($u)) {
                $nid = $u;
            }
        }
        return $nid;
    }

    public function get_fields_by_type($entity_type, $bundle, $type)
    {
        $info = $this->get_entity_fields($entity_type, $bundle);
        $items = [];
        if (!empty($info)) {
            foreach ($info as $key => $field) {
                if ($field['type'] == $type) {
                    $items[] = $field;
                }
            }
        }
        return $items;
    }

    public function get_entity_fields($entity_type, $entity_name = null)
    {
        $field_map = \Drupal::service('entity_field.manager')->getFieldMap();
        $items = [];
        if (in_array($entity_type, array_keys($field_map))) {
            $entity_fields = $field_map[$entity_type];

            foreach ($entity_fields as $key => $field) {
                if (in_array($entity_name, $field['bundles'])) {
                    $items[] = [
                        'type' => $field['type'],
                        'name' => $key
                    ];
                }
            }

        }
        return $items;
    }

    public function get_type_field($entity, $field_name)
    {
        if (is_object($entity) && $entity->hasField($field_name)) {
            $field_type = $entity->get($field_name)->getFieldDefinition()->getType();
            return $field_type;
        } else {
            return null;
        }
    }

    public function get_setting_field($entity, $field_name)
    {
        $resulat = null;
        if (is_object($entity) && $entity->hasField($field_name)) {
            $setting_field = $entity->get($field_name)->getFieldDefinition()->getSettings(); //->getValue();
            if (isset($setting_field['target_type'])) {
                $resulat = $setting_field['target_type'];
            }
        }
        return $resulat;
    }

    public function taxonomy_tree($vid)
    {
        return \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0);
    }

    public function taxonomy_children($vid, $tid)
    {
        $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0);
        $childre_term = [];
        foreach ($tree as $term) {
            if (in_array($tid, $term->parents)) {
                $childre_term[] = array(
                    'name' => strtolower($term->name),
                    'tid' => $term->tid,
                    'url' => $this->taxonomy_url_alias($term->tid)
                );

            }
        }
        return $childre_term;
    }

    public function render_block_by_title($title, $type = 'all')
    {
        $factory = \Drupal::entityTypeManager()->getStorage("block_content")->getQuery();
        $factory->condition('info', $title);
        if ($type != 'all') {
            $factory->condition('type', $type);
        }
        $resultat = $factory->execute();
        $items = [];
        if (!empty($resultat)) {
            foreach ($resultat as $block_id)
                $block = \Drupal::entityTypeManager()->getStorage('block_content')->load($block_id);
            $items[] = \Drupal::entityTypeManager()->getViewBuilder('block_content')->view($block);
        }
        return $items;
    }

    public function get_block_by_title($title, $type = 'all')
    {
        $filter['info'] = $title;
        if ($type != 'all') {
            $filter['type'] = $type;
        }
        return \Drupal::entityTypeManager()
            ->getStorage('block_content')
            ->loadByProperties($filter);
    }

    public function node_url($node_or_nid)
    {
        if (is_numeric($node_or_nid)) {
            $nid = $node_or_nid;
            $options = ['absolute' => TRUE];
            $url_object = Url::fromRoute('entity.node.canonical', ['node' => $nid], $options)->toString();
        } else if (is_object($node_or_nid)) {
            $url_path = explode("/", $node_or_nid->toUrl()->getInternalPath());
            $nid = $url_path[sizeof($url_path) - 1];
            $options = ['absolute' => TRUE];
            $url_object = Url::fromRoute('entity.node.canonical', ['node' => $nid], $options)->toString();
        } else {
            $url_object = null;
        }
        return $url_object;
    }

    public function entity_object_load($nid, $entity = 'node')
    {
        if (is_numeric($nid)) {
            return \Drupal::entityTypeManager()->getStorage($entity)->load($nid);
        }
        {
            return null;
        }
    }

    public function get_list_paragraphs_by_type($type)
    {
        return \Drupal::entityTypeManager()
            ->getStorage('paragraph')
            ->loadByProperties(array('type' => $type));
    }
    /***/ 
    function array_csv_download( $array, $filename = "export.csv", $delimiter=";" )
    {
        header( 'Content-Type: application/csv' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
    
        // clean output buffer
        ob_end_clean();
        
        $handle = fopen( 'php://output', 'w' );
    
        // use keys as column titles
        fputcsv( $handle, array_keys( $array['0'] ));
    
        foreach ( $array as $value ) {
            fputcsv( $handle, $value);
        }
        fclose( $handle );
        // flush buffer
        ob_flush();
        
        // use exit to get rid of unexpected output afterward
        exit();
    }
    public function redirectTo($url, $lang = null)
    {
        global $base_url;
      
        //if domain exit
        $url = str_replace($base_url, "", $url);

        //if lang exist
        $lang_list = \Drupal::languageManager()->getLanguages();
        $url_array = explode('/', $url);
        foreach ($url_array as $key_lg => $lg) {
            if (in_array($lg, array_keys($lang_list))) {
                unset($url_array[$key_lg]);
            }
        }
        $url = implode('/', $url_array);
        if ($lang == null) {
            $path = $base_url  . '/' . $url;
        }else{
            $path = $base_url . '/' . $lang . '/' . $url;
        }     
        $response = new RedirectResponse($path, 302);
        $response->send();
        return;
    }
    function replaceKeyInArray($search_key ,$new_key , $items , $token = TRUE){
        if(!empty($items)){
            foreach ($items as $key => $item){
                $item_key_list = null ;
                if($key && is_string($key)){
                    $item_key_list = explode(" ", trim($key)) ;
                }
                //replace if same
                if($key && is_string($key) && trim($search_key) == trim($key)){
                    $items[$new_key] = $item ;
                    unset($items[$key]) ;
                }
                //replace if contains
                elseif ($item_key_list && in_array($search_key , $item_key_list)
                && is_string($key) && strpos(trim($key),$search_key) && $token){
                    $items[str_replace($search_key,$new_key,trim($key))] = $item ;
                    unset($items[$key]);
                }else{
                    if(is_array($item)){
                        $items[$key] = $this->replaceKeyInArray($search_key ,$new_key , $item , $token);
                    }
                }
            }
        }
    }
    function replaceValueInArray($search_item ,$new_item , $items , $token = TRUE){
        if(!empty($items)){
            foreach ($items as $key => $item){
                $item_list = null ;
                if($item && is_string($item)){
                    $item_list = explode(" ", trim($item)) ;
                }
                //replace if same
                if($item && is_string($item) && trim($search_item) == trim($item)){
                    $items[$key] = $new_item ;
                }
                //replace if contains
                elseif ($item_list && in_array($search_item , $item_list)
                    && is_string($item) && strpos(trim($item),$search_item) && $token){
                    $items[str_replace($search_item,$new_item,trim($item))] = $item ;
                    unset($items[$key]);
                }else{
                    if(is_array($item)){
                        $items[$key] = $this->replaceValueInArray($search_item ,$new_item , $item , $token);
                    }
                }
            }
        }
    }

    function exportToCSV($array,$fields,$filename = "export.csv"){
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        ob_end_clean();

        $handle = fopen('php://output', 'w');
        fprintf($handle,chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($handle, $fields);
        foreach($array as $value){
            fprintf($handle,chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $value);
        }
        fclose($handle);
        ob_flush();
        exit();
    }
    function sendGetRequest($url, $param = '')
    {
        $url_path = $url.'?'. $param;
        $status = $this->urlExist($url_path);
        if($status){
            try {
                    $response = \Drupal::httpClient()
                        ->get($url_path);
                    return $response;
                } catch (RequestException $e) {
                    \Drupal::logger('drupal_helper')->error('Guzzle HTTP request error: @error', ['@error' => $e->getMessage()]);                  
                    return NULL;
                  } catch (\Exception $e) {
                    \Drupal::logger('drupal_helper')->error('An unexpected error occurred: @error', ['@error' => $e->getMessage()]);    
                    return NULL;
                  }       
        }else{
            return false ;
        }
    }
    function urlExist($url) {
        $file_headers = @get_headers($url);
        if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return false;
        }
        return true;
    }
}