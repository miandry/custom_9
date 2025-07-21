<?php

namespace Drupal\drupal_helper\TwigExtension;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\views\Views;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Class DrupalHelperTwigExtension.
 */
class DrupalHelperTwigExtension extends AbstractExtension {

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
  public function getFilters(){
    return [
        new TwigFilter('image_style', [$this, 'applyImageStyle']),
      ];
  }
  public function applyImageStyle($uri, $style_name) {
    if(is_string($style_name) && $uri){
        return ImageStyle::load($style_name)->buildUrl($uri);
    }
    return "";
  }
    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
      return [
        new \Twig_Test('numeric', function ($value) { return  is_numeric($value); }),
      ];
    }
    /**
     * {@inheritdoc}
     */
    public function getFunctions() {
        return [
            new TwigFunction('floatval',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'floatval_twig']),
            new TwigFunction('time_ago_fr',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'time_elapsed_string']),
            new TwigFunction('current_user',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_current_user']),
            new TwigFunction('current_lang',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_current_lang']),
            new TwigFunction('is_login',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'isLoginTwig']),
            new TwigFunction('current_url',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_current_url']),
            new TwigFunction('get_parameter',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_get_parameter']),
            new TwigFunction( 'node_url' ,['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'node_url']),
            new TwigFunction('block_load_by_type',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'block_load_by_type']),
            new TwigFunction( 'get_module_path',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'get_module_path']),
            new TwigFunction( 'base_url',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'base_url']),
            new TwigFunction( 'taxonomy_url',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'taxonomy_url']),
            new TwigFunction('node_url_alias',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_node_url_alias']),
            new TwigFunction('die',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'die_twig']),
            new TwigFunction('array_values',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'array_value_twig']),
            new TwigFunction('hasRole',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'has_role_twig']),
            new TwigFunction('paragraph_form',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'render_form_paragraph_twig']),
            new TwigFunction('id_by_url',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_id_by_url']),
            new TwigFunction('taxonomy_children',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twgi_taxonomy_children']),
            new TwigFunction('in_array' ,['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_in_array']),
            new TwigFunction('current_url_alias',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_current_url_alias']),
            new TwigFunction('config',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_config']),
            new TwigFunction( 'var_dump' ,['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_var_dump']),
            new TwigFunction('path_theme',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_get_current_path_theme']),
            new TwigFunction( 'modulo',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'modulo_twig']),
            new TwigFunction( 'render_view',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_render_view']),
            new TwigFunction('theme_logo',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_theme_logo']),
            new TwigFunction( 'price',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_price_format']),
            new TwigFunction( 'count_comments',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'count_comments']),
            new TwigFunction('views_result',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'views_result']),
            new TwigFunction( 'strtotime',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_strtotime']),
            new TwigFunction('array_push',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_array_push']),
            new TwigFunction('terms',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'terms']),
            new TwigFunction( 'json_encode',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'json_encode_twig']),
            new TwigFunction( 'unRender',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'unRender']),
            new TwigFunction( 'parameter',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_parameter']),
            new TwigFunction( 'message',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_message']),
            new TwigFunction(  'array_column',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_array_column']),
            new TwigFunction( 'redirect',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_redirect']),
            new TwigFunction('time',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_time']),
            new TwigFunction( 'render_form_entity',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'render_form_entity_twig']),
            new TwigFunction( 'is_url_exist',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'is_url_exist_twig']),
            new TwigFunction('json_decode',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'json_decode_twig']),
            new TwigFunction( 'get_block_custom_by_info',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'get_block_custom_by_info_twig']),
            new TwigFunction( 'get_block_custom_id_by_info',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'get_block_custom_id_by_info_twig']),
            new TwigFunction('render_block_custom',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'render_block_custom_twig']),
            new TwigFunction('render_views',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'render_views_twig']),
            new TwigFunction( 'image',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'image_twig']),
            new TwigFunction('render_block',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'render_block_twig']),
            new TwigFunction('query',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'query_twig']),
            new TwigFunction('current_route',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'current_route_twig']),
            new TwigFunction('current_node_type',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'current_node_type_twig']),
            new TwigFunction('user_login',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'user_login_twig']),
            new TwigFunction('user_register',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'user_register_twig']),
            new TwigFunction('storage_set',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'storage_set']),
            new TwigFunction('storage_get',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'storage_get']),
            new TwigFunction('storage_delete',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'storage_delete']),
            new TwigFunction('user_password_forget',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'user_password_forget_twig'])  ,     
            new TwigFunction('pathlocal_theme',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'twig_pathlocal_theme']),
            new TwigFunction('set_config',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'set_config']),
            new TwigFunction('get_config',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'get_config']),
            new TwigFunction('delete_config',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'delete_config']),
            new TwigFunction('loader_twig_file',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'loader_twig_file']) ,         
            new TwigFunction('number_to_words',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'convert_number_to_words']) ,
            new TwigFunction('str_replace',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'str_replace_twig']) ,
            new TwigFunction('str_contains',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'str_contains_twig']) ,
            new TwigFunction('get_object_by_url',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'get_object_by_url_twig']) ,
            new TwigFunction('get_id_by_url',['Drupal\drupal_helper\TwigExtension\DrupalHelperTwigExtension', 'get_id_by_url_twig']) 
   
        ];
    }
    public static function get_object_by_url_twig($entity = 'node'){
        return \Drupal::routeMatch()->getParameter($entity); 
    }
    public static function get_id_by_url_twig($entity = 'node'){
        $object =  \Drupal::routeMatch()->getParameter($entity); 
        if(is_object($object)){
            return  $object->id();
        }
        return false ;
    }
    public static function str_contains_twig(string $haystack, string $needle){
       return str_contains($haystack,$needle);
    }

    public static function str_replace_twig(string $search, string $replace,
    string $subject,$count = null){
       return str_replace($search,$replace,$subject,$count);
    }

    public static function convert_number_to_words($price){
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        return  $twig_base->helper->convert_number_to_words($price);
    }
    // templates/file.twig.html
    public static function loader_twig_file($uri,$values){
            // Get the absolute path of the file.
            $path = DRUPAL_ROOT.$uri;
            // Check if the file exists and is readable.
            if (file_exists($path) && is_readable($path)) {
              $output = file_get_contents($path);
              return [
                '#type' => 'inline_template',
                '#template' => $output,
                '#context' => [
                    'content' => $values,
                ],
              ];
            }
            else {
              \Drupal::messenger()->addError('The file does not exist or is not readable.');
     
            }
    }
    public static function set_config($name,$value){
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        return  $twig_base->helper->set_config($name,$value);
    }
    public static function get_config($name){
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        return  $twig_base->helper->get_config($name);
    }
    public  static function delete_config($name){
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        return  $twig_base->helper->delete_config($name);
    }
    public static function twig_pathlocal_theme($theme_name){
        return \Drupal::service('extension.list.theme')->getPath($theme_name);
    }
    public static function floatval_twig($string){
       return floatval($string);
    }
  public static function user_register_twig(){
    $entity = \Drupal::entityTypeManager()->getStorage('user')->create(array());
    $formObject = \Drupal::entityTypeManager()
      ->getFormObject('user', 'register')
      ->setEntity($entity);
    $form = \Drupal::formBuilder()->getForm($formObject);
    return \Drupal::service('renderer')->render($form);
  }
  public static function user_login_twig(){
              $form = \Drupal::formBuilder()->getForm(\Drupal\user\Form\UserLoginForm::class) ;
              $render = \Drupal::service('renderer');
              return $render->renderPlain($form);
  }
  public static function user_password_forget_twig(){
    $form = \Drupal::formBuilder()->getForm(\Drupal\user\Form\UserPasswordForm::class) ;
    $render = \Drupal::service('renderer');
    return $render->renderPlain($form);
}
    public static function current_route_twig(){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->get_route_name_by_url_current();
    }
    public static function current_node_type_twig($url_alias = null){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        $node =  $twig_base->helper->getEntityByAlias($url_alias);
        if(is_object( $node )){
           return $node->bundle();
        }
        return false ;
    }
    public static function query_twig($entity_type,$options = []){
        $query = \Drupal::entityTypeManager()->getStorage($entity_type)->getQuery();
        foreach($options  as $key => $item){
            switch($item['filter']){
                case "condition":
                    if(isset($item['operation'])){
                        $query->{$item['filter']}($item['field'],$item['value'],$item['operation']);
                    }else{
                        $query->{$item['filter']}($item['field'],$item['value']);
                    }
                break;
                case "sort":
                    $query->{$item['filter']}($item['field'],$item['value']);
                break;
                case "range":
                    $query->{$item['filter']}($item['start'], $item['limit']);
                case "pager":
                    $query->{$item['filter']}($item['limit'], $item['order']);
                break;
            }
        }
        return $query->execute();
    }
    public static function load_by_property_twig($entity_type,$filter){
        $result=[];
        $entities = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->loadByProperties($filter);
        foreach ($entities as $id => $entity ) {
        $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
        $storage->resetCache([$id]);
        $result[$id] = $storage ;
        }
        return  $result ;
    }
    public static function image_twig($media,$option = []){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->image($media,$option);
    }
    public static function render_views_twig($view_name,$diplay_id,$argument=[]){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->views->render_views($view_name,$diplay_id,$argument);
    }
    public static function views_result($view_name,$diplay_id,$page = 0,$query = [],$args = []){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->views->views_result($view_name,$diplay_id,$page,$query,$args);
    }
    public static function render_block_custom_twig($block_id) {
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->views->render_block_custom($block_id);
    }
    public static function render_block_twig($block_id) {
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->views->render_block($block_id);
    }

    public static function get_block_custom_id_by_info_twig($info){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        $array =  $twig_base->helper->get_block_custom_by_info($info);
        if(!empty($array)){
              return end(array_keys($array));
        }
        return [];
    }

    public static function get_block_custom_by_info_twig($info){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->get_block_custom_by_info($info);
    }
    public static function json_decode_twig($json){
        return json_decode($json, true);
    }
    public static function is_url_exist_twig($url){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->isExistUrl($url);
    }
    public static function twig_redirect($url,$params = null){
        if($params){
            $url = \Drupal\Core\Url::fromUri('internal:'.$url, ['query' =>  $params]);
            $response = new RedirectResponse($url->toString());
            $response->send();
        }else{
            $twig_base = new \Drupal\drupal_helper\DrupalHelper();
            return $twig_base->helper->redirectTo($url);
        }   
    }
    public static function twig_array_column($array, $string){
        return array_column($array, $string);
    }
    public static function storage_delete($name,$cache_time= 604800){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->storage_delete($name,$cache_time);
    }
    public static function storage_get($name,$cache_time= 604800){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->storage_get($name,$cache_time);
    }
    public static function storage_set($name,$values,$cache_time= 604800){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->storage_set($name,$values,$cache_time);
    }
    public static function twig_message($message , $status = null){
        $message = t($message) ;
        \Drupal::messenger()->addMessage($message,$status);
    }
    public static function twig_parameter($parameter_name = null){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->get_parameter($parameter_name);
    }
    public static function unRender($item){
        return $item->__toString();
    }
    public static function json_encode_twig($array){
        $InText = json_encode($array);
        return str_replace('"',"'",$InText);
    }
    public static function time_elapsed_string($time_ago)
    {
        $cur_time   = time();
        $time_elapsed   = $cur_time - $time_ago;
        $seconds    = $time_elapsed ;
        $minutes    = round($time_elapsed / 60 );
        $hours      = round($time_elapsed / 3600);
        $days       = round($time_elapsed / 86400 );
        $weeks      = round($time_elapsed / 604800);
        $months     = round($time_elapsed / 2600640 );
        $years      = round($time_elapsed / 31207680 );
        // Seconds
        if($seconds <= 60){
            return "juste maintenant";
        }
        //Minutes
        else if($minutes <=60){
            if($minutes==1){
                return "il y a une minute";
            }
            else{
                return "il y a $minutes minutes";
            }
        }
        //Hours
        else if($hours <=24){
            if($hours==1){
                return "il y a une heure";
            }else{
                return "il y a $hours heures ";
            }
        }
        //Days
        else if($days <= 7){
            if($days==1){
                return "hier";
            }else{
                return "il y a $days jours";
            }
        }
        //Weeks
        else if($weeks <= 4.3){
            if($weeks==1){
                return "il y a une semaine";
            }else{
                return "il y a $weeks semaines ";
            }
        }
        //Months
        else if($months <=12){
            if($months==1){
                return "il y a un mois";
            }else{
                return "il y a $months mois";
            }
        }
        //Years
        else{
            if($years==1){
                return "il y a un an";
            }else{
                return "il y a $years années";
            }
        }


    }

    public static function terms($vid){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->taxonomy_load_multi_by_vid($vid);
    }
    public static function twig_array_push($array,$item,$key = null){
        if($key){
          $array[$key] = $item ;
        }else{
            $array[] = $item ;
        }
        return $array;
    }
    public static function twig_time(){
        return time();
    }
    public static function twig_strtotime($date){
        $date = new DrupalDateTime($date);
        return $date->getTimestamp();
    }
    public static function count_comments($nid){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->count_comments($nid);
    }
    public static function helper_twig(){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base;
    }
    public static function twig_price_format($price,$decimal_number=0 ,$decimal_point=',' , $thousands_sep=' ') {
        if($price == null ){ $price = 0 ;}
        return number_format($price,$decimal_number, $decimal_point, $thousands_sep);
    }
    public static function twig_theme_logo(){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->theme_logo();
    }
    public static function twig_render_view($view_mode ,$item, $entity_type='node'){
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
        $result =  $view_builder->view($item, $view_mode);
        return \Drupal::service('renderer')->renderRoot($result);
    }
    public static function modulo_twig($a , $b){
        return  ($a % $b);
    }
    public static function twig_config($config_base,$config_name){
        $config = \Drupal::config($config_base);
        return  $config->get($config_name);
    }
    public static function twig_get_current_path_theme(){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        global  $base_url ;
        return $base_url.'/'.$twig_base->helper->get_current_path_theme();
    }
    public static function twig_current_url_alias(){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->current_url_alias();
    }
    public static function twig_node_url_alias($nid){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->node_url_alias($nid);
    }

    public static function twig_var_dump($content){
        return var_dump($content);
    }
    public static function twig_in_array($item , $array){
        return in_array($item,$array);
    }
    public static function twgi_taxonomy_children($vid , $tid){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->taxonomy_children($vid , $tid);
    }
    public static function twig_id_by_url($url = null){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->get_numeric_args_url($url);
    }
    public static function render_form_paragraph_twig($bundle,$id=null){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->form->render_form_paragraph($bundle,$id);
    }
    public static function render_form_entity_twig($entity,$bundle,$id = null){
        $twig_base = new \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->form->render_form_entity($entity,$bundle,$id);
    }


    public static function has_role_twig($role_name,$uid){
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->user->hasRole($role_name,$uid);
    }
    public static function array_value_twig($array){
        if($array==null){
            $array =[];
        }
        return  array_values($array);
    }
    public static function twig_current_lang(){
        return  $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    public static function twig_current_user(){
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        return  $twig_base->helper->current_user();
    }
    public  static function die_twig(){
        return die();
    }
    public static function isLoginTwig() {
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        return  $twig_base->helper->is_login();
    }
    public static function twig_current_url(){
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        return  $twig_base->helper->current_url();
    }
    public static function twig_get_parameter(){
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        return  $twig_base->helper->get_parameter();
    }


    public static function node_url($nid){
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->node_url($nid);
    }
    public static function block_load_by_type($type){
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->block_custom_load_by_type($type);

    }

    public static function  get_module_path($module_name){
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        return $twig_base->helper->get_module_path($module_name);
    }

    public  static function base_url(){
        global $base_url ;
        return $base_url;
    }

    public  static function taxonomy_url($tid){
        $twig_base = new  \Drupal\drupal_helper\DrupalHelper();
        $alias = $twig_base->helper->taxonomy_url_alias($tid);

        return $alias  ;
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
        return 'drupal_helper.twig.extension';
    }

}
