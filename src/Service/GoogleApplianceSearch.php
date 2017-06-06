<?php

namespace Drupal\google_appliance\Service;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

/**
 * Class GoogleApplianceSearch.
 *
 * @package Drupal\google_appliance\Service
 */
class GoogleApplianceSearch {

  /**
   * Send a GET request using cURL.
   *
   * @param $url
   * @param array|null $get
   * @param array $options
   * @param int $sga_timeout
   *
   * @return array
   */
  public function curlGet($url, array $get = NULL, array $options = [], $sga_timeout = 30) {
    $defaults = [
      CURLOPT_URL => $url . (strpos($url, '?') === FALSE ? '?' : '') . http_build_query($get, '', '&'),
      CURLOPT_HEADER => 0,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_TIMEOUT => Html::escape($sga_timeout),
    ];

    $curlHandler = curl_init();
    curl_setopt_array($curlHandler, $options + $defaults);
    $result = [
      'is_error' => FALSE,
      'response' => curl_exec($curlHandler),
    ];
    if ($result['response'] === FALSE) {
      $result['is_error'] = TRUE;
      $result['response'] = curl_error($curlHandler);
    }
    curl_close($curlHandler);
    return $result;
  }

  /**
   * Send a POST request using cURL.
   *
   * @param $url
   * @param array|null $post
   * @param array $options
   *
   * @return array
   */
  public function curlPost($url, array $post = NULL, array $options = []) {
    $defaults = [
      CURLOPT_POST => 1,
      CURLOPT_HEADER => 0,
      CURLOPT_URL => $url,
      CURLOPT_FRESH_CONNECT => 1,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_FORBID_REUSE => 1,
      CURLOPT_TIMEOUT => 4,
      CURLOPT_POSTFIELDS => http_build_query($post, '', '&'),
    ];

    $curlHandler = curl_init();
    curl_setopt_array($curlHandler, $options + $defaults);
    $result = [
      'is_error' => FALSE,
      'response' => curl_exec($curlHandler),
    ];
    if ($result['response'] === FALSE) {
      $result['is_error'] = TRUE;
      $result['response'] = curl_error($curlHandler);
    }
    curl_close($curlHandler);
    return $result;
  }

  /**
   * Parse the response from the GSA device into a PHP array.
   *
   * @arg $gsa_xml
   *    response text obtained from the device query
   * @arg $use_cached
   *    Whether or not to use the cached version of this query
   *
   * @return array
   *   PHP array structure to iterate when displaying results.
   */
  public function parseDeviceResponseXml($gsa_xml, $use_cached = TRUE) {
    $results = &drupal_static(__FUNCTION__);
    if (NULL === $results || FALSE === $use_cached) {

      // Look for xml parse errors.
      libxml_use_internal_errors(TRUE);
      /** @var \SimpleXMLElement $payload */
      $payload = simplexml_load_string($gsa_xml);

      if ($payload === FALSE) {
        // XML parse error(s)
        $errors = [];
        foreach (libxml_get_errors() as $error) {
          $errors[] = $error->message;
        }
        // Roll up the errors.
        $errors = implode(', ', $errors);
        $results['error'] = ['lib_xml_parse_error' => $errors];
        // Displaying useful error messages
        // depends upon the use of the array key.
        // 'lib_xml_parse_error' ... the actual error is displayed elsewhere.
        // @see google_appliance.theme.inc
      }
      else {
        // Store metrics for stat reporting.
        $results['last_result'] = (string) $payload->RES['EN'];

        $this->parseResultCount($payload, $results);

        // Search returned zero results.
        if ($results['total_results'] === 0) {
          $results['error'] = ['gsa_no_results' => TRUE];
          return $results;
        }

        // Spelling suggestions.
        if (isset($payload->Spelling->Suggestion)) {
          $spelling_suggestion = (string) $payload->Spelling->Suggestion;
          $results['spelling_suggestion'] = Xss::filter($spelling_suggestion, [
            'b',
            'i',
          ]);
        }

        $this->parseOneboxResults($payload, $results);

        foreach ($payload->xpath('//GM') as $km) {
          $keymatch = [];

          // Keymatch information.
          $keymatch['description'] = (string) $km->GD;
          $keymatch['url'] = (string) $km->GL;

          $results['keymatch'][] = $keymatch;
        }

        // If there are any synonyms returned by the appliance,
        // put them in the results as a new array.
        // @see http://code.google.com/apis/searchappliance/documentation/50/xml_reference.html#tag_onesynonym
        foreach ($payload->xpath('//OneSynonym') as $syn_element) {
          $synonym = [];

          // Synonym information.
          $synonym['description'] = (string) $syn_element;
          $synonym['url'] = (string) $syn_element['q'];

          $results['synonyms'][] = $synonym;
        }

        $this->parseResultEntries($payload, $results);

        \Drupal::moduleHandler()->alter('google_appliance_results', $results, $payload);
      }
    }

    return $results;
  }

  /**
   * Parse the count of total results.
   *
   * @param \SimpleXMLElement $payload
   * @param array $results
   */
  private function parseResultCount(\SimpleXMLElement $payload, array &$results) {
    // Note: Total is somewhat unreliable.
    $results['total_results'] = (integer) $payload->RES->M;

    // C check if there is an result at all ($payload->RES),
    // secure search doesn't provide a value for $payload->RES->M.
    if (isset($payload->RES) && (int) $results['total_results'] === 0) {
      $results['total_results'] = (integer) $payload->RES['EN'];

      $param_start = $payload->xpath('/GSP/PARAM[@name="start"]');
      $param_num = $payload->xpath('/GSP/PARAM[@name="num"]');
      $request_max_total = (integer) $param_start[0]['value'] + (integer) $param_num[0]['value'];

      if ($results['total_results'] === $request_max_total) {
        ++$results['total_results'];
      }
    }
  }

  /**
   * Parse results from the payload.
   *
   * @param \SimpleXMLElement $payload
   * @param array $results
   */
  private function parseResultEntries(\SimpleXMLElement $payload, array &$results) {
    foreach ($payload->xpath('//R') as $res) {
      $result = [];

      // Handy variants of the url for the result.
      $result['abs_url'] = (string) $res->U;
      // Urlencoded URL of result.
      $result['enc_url'] = (string) $res->UE;
      $result['short_url'] = substr($result['abs_url'], 0, 80)
        . (strlen($result['abs_url']) > 80 ? '...' : '');

      // Result info.
      $result['title'] = (string) $res->T;
      $result['snippet'] = (string) $res->S;
      $result['crawl_date'] = (string) $res->FS['VALUE'];
      $result['level'] = isset($res['L']) ? (int) $res['L'] : 1;

      // Result meta data.
      // Here we just collect the data from the device
      // and leave implementing display of meta data
      // to the themer (use-case specific).
      // @see google-appliance-result.tpl.php
      $meta = [];
      foreach ($res->xpath('./MT') as $mt) {
        $meta[(string) $mt['N']] = (string) $mt['V'];
      }
      $result['meta'] = $meta;

      // Detect the mime type to allow themes to decorate with mime icons.
      // @see google-appliance-result.tpl.php
      $result['mime']['type'] = (string) $res['MIME'];

      // Collect.
      $results['entry'][] = $result;
    }
  }

  /**
   * Parse onebox results.
   *
   * @param \SimpleXMLElement $payload
   * @param array $results
   */
  private function parseOneboxResults(\SimpleXMLElement $payload, array &$results) {
    // Onebox results.
    // @see https://developers.google.com/search-appliance/documentation/614/oneboxguide#providerresultsschema
    // @see https://developers.google.com/search-appliance/documentation/614/oneboxguide#mergingobs
    foreach ($payload->xpath('//ENTOBRESULTS/OBRES') as $mod) {
      $result_code = empty($mod->resultCode) ? '' : (string) $mod->resultCode;
      if (empty($result_code) || $result_code === 'success') {
        $module_name = (string) $mod['module_name'];
        $onebox = [];
        $onebox['module_name'] = $module_name;
        $onebox['provider'] = (string) $mod->provider;
        $onebox['url_text'] = (string) $mod->title->urlText;
        $onebox['url_link'] = (string) $mod->title->urlLink;
        $onebox['image'] = (string) $mod->IMAGE_SOURCE;
        $onebox['description'] = (string) $mod->description;
        foreach ($mod->xpath('./MODULE_RESULT') as $res) {
          $result = [];
          $result['abs_url'] = (string) $res->U;
          $result['title'] = (string) $res->Title;
          foreach ($res->xpath('./Field') as $field) {
            $field_name = (string) $field['name'];
            $result['fields'][$field_name] = (string) $field;
          }
          $onebox['results'][] = $result;
        }
        $results['onebox'][$module_name] = $onebox;
      }
    }
  }

}

// @todo: Finish these.

/**
 * Get related search via the Google Search Appliance clustering service.
 *
 * @return
 *   themed list of links
 */
function google_appliance_get_clusters() {

  // Grab module settings.
  $settings = _google_appliance_get_settings();

  // Get the search query.
  $query_pos = substr_count($settings['drupal_path'], '/') + 1;
  $search_query = urldecode(arg($query_pos));
  $cluster_content = NULL;

  // Perform POST to acquire the clusters  block.
  $clusterQueryURL = Html::escape($settings['hostname'] . '/cluster');
  $clusterQueryParams = [
    'q' => Html::escape($search_query),
    'btnG' => 'Google+Search',
    'access' => 'p',
    'entqr' => '0',
    'ud' => '1',
    'sort' => 'date:D:L:d1',
    'output' => 'xml_no_dtd',
    'oe' => 'utf8',
    'ie' => 'utf8',
    'site' => Html::escape($settings['collection']),
    'client' => Html::escape($settings['frontend']),
  ];

  // Alter request according to language filter settings.
  if (\Drupal::moduleHandler()
    ->moduleExists('locale') && $settings['language_filter_toggle']
  ) {
    $clusterQueryParams['lr'] = _google_appliance_get_lr($settings['language_filter_options']);
  }

  // cURL request for the clusters produces JSON result.
  $gsa_clusters_json = _curl_post($clusterQueryURL, $clusterQueryParams);

  // No error -> get the clusters.
  if (!$gsa_clusters_json['is_error']) {

    $clusters = json_decode($gsa_clusters_json['response'], TRUE);

    if (isset($clusters['clusters'][0])) {

      // Build the link list.
      $cluster_list_items = [];
      foreach ($clusters['clusters'][0]['clusters'] as $cluster) {
        // @FIXME
        // l() expects a Url object, created from a route name or external URI.
        // array_push($cluster_list_items, l($cluster['label'], $settings['drupal_path'] . '/' . $cluster['label']));
      }

      // Create theme-friendly list of links render array.
      $cluster_list = [
        '#theme' => 'item_list',
        '#items' => $cluster_list_items,
        '#title' => NULL,
        '#type' => 'ul',
        '#attributes' => [],
      ];

      // Allow implementation of hook_google_appliance_cluster_list_alter() by
      // other modules.
      \Drupal::moduleHandler()
        ->alter('google_appliance_cluster_list', $cluster_list, $cluster_results);

      $cluster_content = \Drupal::service('renderer')->render($cluster_list);
    }
  }

  return $cluster_content;
}

/**
 * Report search errors to the log.
 */
function _google_appliance_log_search_error($search_keys = NULL, $error_string = NULL) {
  $settings = _google_appliance_get_settings();

  // Build log entry.
  $type = 'google_appliance';
  $message = 'Search for %keys produced error: %error_string';
  $vars = [
    '%keys' => isset($search_keys) ? $search_keys : 'nothing (empty search submit)',
    '%error_string' => isset($error_string) ? $error_string : 'undefinded error',
  ];
  // @FIXME
  // l() expects a Url object, created from a route name or external URI.
  // $link = l(t('view reproduction'), $settings['drupal_path'] . '/' . \Drupal\Component\Utility\Html::escape($search_keys));
  \Drupal::logger($type)->notice($message, []);
}

/**
 * Get googleon/googleoff tags.
 */
function _google_appliance_get_googleonoff() {
  return [
    'index' => [
      'prefix' => '<!--googleoff: index-->',
      'suffix' => '<!--googleon: index-->',
    ],
    'anchor' => [
      'prefix' => '<!--googleoff: anchor-->',
      'suffix' => '<!--googleon: anchor-->',
    ],
    'snippet' => [
      'prefix' => '<!--googleoff: snippet-->',
      'suffix' => '<!--googleon: snippet-->',
    ],
    'all' => [
      'prefix' => '<!--googleoff: all-->',
      'suffix' => '<!--googleon: all-->',
    ],
  ];
}

/**
 * Format the value of the 'lr' parameter appropriately.
 *
 * @param $options
 *   Array of languages to filter, as defined by the config
 *   variable google_appliance_language_filter_options.
 *
 * @return
 *   String to be passed to the GSA using the 'lr' parameter.
 */
function _google_appliance_get_lr($options) {
  $langcodes = [];
  $options = array_filter($options);
  foreach ($options as $option) {
    switch ($option) {
      case '***CURRENT_LANGUAGE***':
        $language = \Drupal::languageManager()->getCurrentLanguage();
        $langcode = $language->language;
        break;

      case '***DEFAULT_LANGUAGE***':
        $langcode = language_default('language');
        break;

      default:
        $langcode = $option;
    }
    $langcodes[$langcode] = "lang_$langcode";
  }
  return implode('|', $langcodes);
}
