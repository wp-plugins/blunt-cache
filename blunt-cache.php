<?php 

  /*
    Plugin Name: Blunt Cache
    Plugin URI: https://github.com/Hube2/blunt-cache
    Description: Simple Fragment and Object Caching using WP Transients API
    Author: John A. Huebner II
    Author URI: https://github.com/Hube2
    Version: 0.0.1
  */
  
  /*
    Usage:
        
        key names must be no more than 40 characters in length
        
        Set Default ttl
  
            function set_blunt_cache_ttl($ttl) {
              $ttl = 60 * 60 * 6; // 6 hours
              return $ttl;
            }
            add_filter('blunt_cache_ttl', 'set_cache_ttl');
        
        Fragment Cache
        
            if (!apply_filters('blunt_cache_frag_check', false, $key)) {
              // code to output and cache
            }
            do_action('blunt_cache_frag_output_save', $key, $ttl);
        
        
        Object Cache
            
            if (($object = apply_filters('blunt_cache_get_object', false, $key)) === false) {
              $object = 'built your object here';
              do_action('blunt_cache_object_save', $object, $key, $ttl);
            }
            
        Remove/clear a single fragment or object form the cache
            
            do_action('blunt_cache_uncache', $type, $key);
            
              $type = object or fragment
            
        
        Clear the entire Cache
            
            add the following query string to any page on your site
            
            ?blunt-cache=clear
            
  */
  
  if (!class_exists('blunt_cache')) {
  
    class blunt_cache {
      
      private $frag_keys = array();
      private $frag_keys_option = '_blunt_cache_frag_keys_';  // option to store frag keys
      
      private $object_keys = array();
      private $object_keys_option = '_blunt_cache_object_keys_'; // option to store object keys
      
      private $frag_cache = array();
      private $frag_caching = array();
      
      private $object_cache = array();
      
      private $debug = false;
      
      private $ttl = 3600;
      
      public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('init', array($this, 'set_default_ttl'), 100);
        
        add_filter('blunt_cache_get_object', array($this, 'object_get'), 10, 2);
        add_action('blunt_cache_object_save', array($this, 'object_save'), 10, 3);
        
        add_filter('blunt_cache_frag_check', array($this, 'frag_check'), 10, 2);
        add_action('blunt_cache_frag_output_save', array($this, 'frag_output_save'), 10, 2);
        
        add_action('blunt_cache_uncache', array($this, 'clear_item'), 10, 2);
      } // end public function __construct
      
      public function clear_item($type, $key) {
        $type = strtolower($type);
        $key = $this->sanitize_key($key);
        switch($type) {
          case 'object':
            $key = 'obj-'.$key;
            if (isset($this->object_keys[$key])) {
              delete_transient($key);
              if (isset($this->object_cache[$key])) {
                unset($this->object_cache[$key]);
              }
            }
            break;
          case 'fragment':
            $key = 'frag-'.$key;
            if (isset($this->frag_keys[$key])) {
              delete_transient($key);
              if (isset($this->frag_cache[$key])) {
                unset($this->frag_cache[$key]);
              }
            }
            break;
          default:
            // do nothing
            break;
        }
      } // end public function clear_item
      
      public function object_get($object, $key) {
        $key = $this->add_object_key($key);
        if (isset($this->object_cache[$key])) {
          $object = $this->object_cache[$key];
        } else {
          $object = get_transient($key);
          if ($object !== false) {
            $this->object_cache[$key] = $object;
          }
        }
        return $object;
      } // end public function get_object
      
      public function object_save($object, $key, $ttl=0) {
        $key = $this->add_object_key($key);
        $ttl = $this->set_ttl($ttl);
        $this->object_cache[$key] = $object;
        set_transient($key, $object, $ttl);
      } // end public function object_save
      
      public function frag_check($cached, $key) {
        $cached = false;
        $key = $this->add_frag_key($key);
        if (isset($this->frag_cache[$key])) {
          $cached = true;
        } else {
          $cache = get_transient($key);
          if ($cache !== false) {
            $cached = true;
            $this->frag_cache[$key] = $cache;
          }
        }
        if ($cached) {
          $this->frag_caching[$key] = false;
        } else {
          $this->frag_caching[$key] = true;
          ob_start();
        }
        return $cached;
      } // end public function frag_check
      
      public function frag_output_save($key, $ttl=0) {
        $key = $this->add_frag_key($key);
        $ttl = $this->set_ttl($ttl);
        if (!isset($this->frag_cache[$key])) {
          $this->frag_cache[$key] = '';
        }
        if ($this->frag_caching[$key]) {
          //echo 'From OB';
          $this->frag_cache[$key] = ob_get_clean();
          $this->frag_caching[$key] = false;
          set_transient($key, $this->frag_cache[$key], $ttl);
        }
        echo $this->frag_cache[$key];
      } // end public function frag_output_save
      
      private function add_object_key($key) {
        $key = 'obj-'.$this->sanitize_key($key);
        if (!isset($this->object_keys[$key])) {
          $this->object_keys[$key] = $key;
          $this->save_object_keys();
        }
        return $key;
      } // end private function add_object_key
      
      private function add_frag_key($key) {
        $key = 'frag-'.$this->sanitize_key($key);
        if (!isset($this->frag_keys[$key])) {
          $this->frag_keys[$key] = $key;
          $this->save_frag_keys();
        }
        return $key;
      } // end private function add_frag_key
      
      private function set_ttl($ttl) {
        $ttl = intval($ttl);
        if (!$ttl) {
          $ttl = $this->ttl;
        }
        return $ttl;
      } // end private function set_ttl
      
      public function set_default_ttl() {
        $ttl = intval(apply_filters('blunt_cache_ttl', $this->ttl));
        if ($ttl) {
          $this->ttl = $ttl;
        }
      } // end public function set_default_ttl
      
      private function get_frag_keys() {
        // get list of cache keys that blunt cache is tracking from options
        $keys = get_option($this->frag_keys_option, false);
        if (is_array($keys)) {
          $this->frag_keys = $keys;
        } else {
          $this->save_frag_keys();
        }
      } // end private function get_frag_keys
      
      private function get_object_keys() {
        // get list of cache keys that blunt cache is tracking from options
        $keys = get_option($this->object_keys_option, false);
        if (is_array($keys)) {
          $this->object_keys = $keys;
        } else {
          $this->save_object_keys();
        }
      } // end private function get_object_keys
      
      private function save_frag_keys() {
        // save list of cache keys that blunt cache is tracking in options
        update_option($this->frag_keys_option, $this->frag_keys);
      } // end private function save_frag_keys
      
      private function save_object_keys() {
        // save list of cache keys that blunt cache is tracking in options
        update_option($this->object_keys_option, $this->object_keys);
      } // end private function save_frag_keys
      
      public function activate() {
        // just in case I want to do anything on activate
      } // end public function activate
      
      public function deactivate() {
        // just in case I want to do anyting on deactivate
        $this->clear_cache();
        delete_option($this->frag_keys_option);
        delete_option($this->object_keys_option);
      } // end public function deactivate
      
      public function init() {
        $this->get_frag_keys();
        $this->get_object_keys();
        if (isset($_GET['blunt-cache']) && strtolower($_GET['blunt-cache']) == 'clear') {
          $this->clear_cache();
          $redirect = remove_query_arg('blunt-cache');
          wp_redirect($redirect);
          exit;
        }
      } // end public function init
      
      private function clear_cache() {
        // delete all cached data
        if (count($this->frag_keys)) {
          foreach ($this->frag_keys as $key) {
            delete_transient($key);
          }
          $this->frag_keys = array();
          $this->save_frag_keys();
        }
        if (count($this->object_keys)) {
          foreach ($this->object_keys as $key) {
            delete_transient($key);
          }
          $this->object_keys = array();
          $this->save_object_keys();
        }
        $this->frag_cache = array();
        $this->object_cache = array();
        $this->frag_caching = array();
      } // end private function clear_cache
      
      private function sanitize_key($key) {
        $key = md5($key);
        /*
        $key = trim($key);
        $key = strtolower($key);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        */
        return $key;
      } // end private function sanitize_key
      
    } // end class bluntSnippets
    
    new blunt_cache();
    
  } // end if (!class_exists('blunt_cache'))

?>
