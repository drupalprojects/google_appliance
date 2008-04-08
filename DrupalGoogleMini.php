<?php
include_once 'GoogleMini.php';

class DrupalGoogleMini extends GoogleMini {

  var $cache = false;

  function __construct($debug = false, $debug_callback = null) {
    parent::__construct($debug,$debug_callback);
  }
  
  function query($iteratorClass = 'GoogleMiniResultIterator') {
    if (!db_table_exists('cache_google')) {
      $this->cache = false;
    }
    if (!$this->cache) {
      return parent::query($iteratorClass);
    } else {
      $cached_result_obj = null;
      $cache_key = md5($this->buildQuery());
      $_cached_result_xml = cache_get($cache_key,'cache_google');
      $cached_result_xml = $_cached_result_xml->data;
      if ($cached_result_xml) {
        $google_results = GoogleMini::resultFactory($cached_result_xml,$iteratorClass);
        $google_debug = variable_get('google_debug',0);
        if ($google_debug >= 2 ){
          if (function_exists('dpr')) {
            dpr("got cache for $cache_key");
          }
        } elseif ($google_debug == 1)  {
          watchdog('amnestysearch',"got cache for $cache_key at" . $_GET['q']);
        }
      } else {
        $google_results = parent::query($iteratorClass);
        //10 Min cache by default
        cache_set($cache_key,'cache_google',$google_results->payload->asXML(),time() + variable_get('google_appliance_cache_timeout',600));
        $google_debug = variable_get('google_debug',0);
        if ($google_debug >= 2 ){
          if (function_exists('dpr')) {
            dpr("setting cache for $cache_key");
          }
        } elseif ($google_debug == 1)  {
          watchdog('amnestysearch',"setting cache for $cache_key at" . $_GET['q']);
        }
      }
      return $google_results;
    }
  }
}
?>