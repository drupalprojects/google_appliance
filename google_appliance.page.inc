<?php

/**
 * @file
 * Contains google_appliance.page.inc.
 */

/**
 * Prepares variables for google_appliance__search_results templates.
 *
 * Default template: google-appliance--search-results.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_google_appliance__search_results(array &$variables) {
  $templateData = $variables['elements']['#variables'];
  /*
   * Responsedata structure
   *
   * error (array)
   *   curl_error (array),
   *   lib_xml_parse_error (array),
   *   gsa_no_results (bool)
   * last_result (string)
   * total_results (int)
   * spelling_suggestion (string)
   * onebox (array)
   *   module_name (array)
   *      module_name (string)
   *      provider (string)
   *      url_text (string)
   *      url_link (string)
   *      image (string)
   *      description (string)
   *      results (array)
   *        abs_url (string)
   *        title (string)
   *        fields (array) [fieldname => string]
   * keymatch (array)
   *   description (string)
   *   url (string)
   * synonyms (array)
   *   description (string)
   *   url (string)
   * entry (array)
   *   abs_url (string)
   *   enc_url (string)
   *   short_url (string)
   *   title (string)
   *   snippet (string)
   *   crawl_date (string)
   *   level (int)
   *   meta (array) [name => string]
   *   mime (array) ['type' => string]
   */
  $responseData = $templateData['response_data'];
  $gsaSettings = $templateData['gsa_settings'];
  $searchQueryData = $templateData['search_query_data'];

  $variables['form'] = $templateData['search_form'];
  $variables['search_title'] = $gsaSettings['display_settings']['search_title'];

  $variables['search_stats'] = '';
  $variables['sort_headers'] = '';
  $variables['pager'] = '';

  $displayKeys = [
    'error',
    'last_result',
    'total_results',
    'onebox',
    'keymatch',
    'synonyms',
    'entry',
  ];

  if ($gsaSettings['display_settings']['spelling_suggestions'] === TRUE) {
    $displayKeys[] = 'spelling_suggestion';
  }

  foreach ($displayKeys as $displayKey) {
    $variables[$displayKey] = empty($responseData[$displayKey]) ? [] : $responseData[$displayKey];
  }

  dpm($variables);
}
