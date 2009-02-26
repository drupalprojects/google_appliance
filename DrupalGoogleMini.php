<?php
// $Id$

define('GOOGLE_MINI_MAX_RESULTS', variable_get('google_appliance_max_results', 1000));

include_once 'GoogleMini.php';

class DrupalGoogleMini extends GoogleMini {

  var $cache = FALSE;

  function __construct($debug = FALSE, $debug_callback = NULL) {
    parent::__construct($debug, $debug_callback);
  }
  
  function log($message = NULL) {
    if ($this->debug_callback) {
      $callback = $this->debug_callback;
      call_user_func($callback, $message);
    }
    watchdog("google_appliance", $message);
  }
  
  function query($iteratorClass = 'GoogleMiniResultIterator') {
    if (!db_table_exists('cache_google')) {
      $this->cache = FALSE;
    }
    if (!$this->cache) {
      return parent::query($iteratorClass);
    }
    else {
      $cached_result_obj = NULL;
      $cache_key = md5($this->buildQuery());
      $_cached_result_xml = cache_get($cache_key, 'cache_google');
      $cached_result_xml = $_cached_result_xml->data;
      if ($cached_result_xml) {
        $google_results = GoogleMini::resultFactory($cached_result_xml, $iteratorClass);
        $google_debug = variable_get('google_debug', 0);
        if ($google_debug >= 2 ){
          if (function_exists('dpr')) {
            dpr("got cache for $cache_key");
          }
        }
        elseif ($google_debug == 1)  {
          watchdog('google_appliance', "got cache for !cache_key at !url", array('!cache_key' => $cache_key, '!url' => $_GET['q']));
        }
      }
      else {
        $google_results = parent::query($iteratorClass);
        //10 Min cache by default
        cache_set($cache_key, $google_results->payload->asXML(), 'cache_google', time() + variable_get('google_appliance_cache_timeout', 600));
        $google_debug = variable_get('google_debug', 0);
        if ($google_debug >= 2 ){
          if (function_exists('dpr')) {
            dpr("setting cache for $cache_key");
          }
        }
        elseif ($google_debug == 1)  {
          watchdog('google_appliance', "setting cache for !cache_key at !url", array('!cache_key' => $cache_key, '!url' => $_GET['q']));
        }
      }
      return $google_results;
    }
  }
}
?>