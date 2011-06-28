<?php 
// $Id$

/**
* @file 
* 	helper functions for the Search Google Appliance module 
*
* @see 
*		google_search_appliance.module
*/

/*
 * default module settings
 */
define('SGA_DEFAULT_HOSTNAME', '');
define('SGA_DEFAULT_COLLECTION', 'default_collection');
define('SGA_DEFAULT_FRONTEND', 'default_frontend');
define('SGA_DEFAULT_TIMEOUT', 10);
define('SGA_DEFAULT_AUTOFILTER', 1);
define('SGA_DEFAULT_QUERY_INSPECTION', 0);
define('SGA_DEFAULT_SEARCH_TITLE', 'Search this site');
define('SGA_DEFAULT_RESULTS_PER_PAGE', 10);

/*
 * Minimize redundant trips to the db when looking for module settings. 
 * Hooks that change module settings should run this function with 
 * $refresh = TRUE so other hooks can just get settings without a db
 * query.
 *
 * @arg $refresh 
 *    TRUE >> query the db for the settings
 *
 * @return
 *    a keyed array of module settings keyed by $field_keys
 */
function _google_appliance_get_settings($refresh = FALSE) {

  static $settings; 

  $field_keys =  array(
    'hostname',
    'collection',
    'frontend',
    'timeout',
    'autofilter',
    'query_inspection',
    'search_title',
    'results_per_page',
  );
  
  if ($refresh || empty($settings)) {
    
    foreach ($field_keys as $field) {  
      $settings[$field] = trim(variable_get(
        'google_appliance_' . $field,
        constant('SGA_DEFAULT_' . strtoupper($field))
      ));
    
    }
  }
  return $settings;
}

/** 
 * Send a POST requst using cURL 
 * @param string $url to request 
 * @param array $post values to send 
 * @param array $options for cURL 
 * @return string 
 */ 
function _curl_post($url, array $post = NULL, array $options = array()) { 
    $defaults = array( 
        CURLOPT_POST => 1, 
        CURLOPT_HEADER => 0, 
        CURLOPT_URL => $url, 
        CURLOPT_FRESH_CONNECT => 1, 
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_FORBID_REUSE => 1, 
        CURLOPT_TIMEOUT => 4, 
        CURLOPT_POSTFIELDS => http_build_query($post, '', '&') 
    ); 

    $ch = curl_init(); 
    curl_setopt_array($ch, ($options + $defaults)); 
    $result = array(
      'is_error' => FALSE,
      'response' => curl_exec($ch),
    );
    if ($result['response'] === FALSE) { 
      $result['is_error'] = TRUE;
      $result['response'] = curl_error($ch);
    } 
    curl_close($ch); 
    return $result; 
} 

/** 
 * Send a GET requst using cURL 
 * @param string $url to request 
 * @param array $get values to send 
 * @param array $options for cURL 
 * @return string 
 */ 
function _curl_get($url, array $get = NULL, array $options = array(), $sga_timeout = 30) {    
  
  $defaults = array( 
    CURLOPT_URL => $url . (strpos($url, '?') === FALSE ? '?' : '') . http_build_query($get, '', '&'), 
    CURLOPT_HEADER => 0, 
    CURLOPT_RETURNTRANSFER => TRUE, 
    CURLOPT_TIMEOUT => check_plain($sga_timeout)
  ); 
    
  $ch = curl_init(); 
  curl_setopt_array($ch, ($options + $defaults)); 
  $result = array(
    'is_error' => FALSE,
    'response' => curl_exec($ch),
  );
  if ($result['response'] === FALSE) { 
    $result['is_error'] = TRUE;
    $result['response'] = curl_error($ch);
  } 
  curl_close($ch); 
  return $result; 
} 

/*
 * report search errors to the log
 */
function _google_appliance_log_search_error($search_keys = NULL, $error_string = NULL) {
  
  // build log entry
  $type = 'google_appliance';
  $message = 'Search for %keys produced error: %error_string';
  $vars = array(
    '%keys' => isset($search_keys) ? $search_keys : 'nothing (empty search submit)',
    '%error_string' => isset($error_string) ? $error_string : 'undefinded error',
  );
  $link = l(t('view reproduction'), 'gsearch/' . check_plain($search_keys));
  
  watchdog($type, $message, $vars, WATCHDOG_NOTICE, $link);
}