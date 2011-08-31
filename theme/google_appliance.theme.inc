<?php
// $Id: google-appliance.theme.inc $
/**
 * @file 
 *    theme registry and preprocess functions for the default templates
 */
 
/**
 * Implements hook_theme().
 */
function google_appliance_theme() {
  
  $registry = array();
  $ga_template_dir = drupal_get_path('module', 'google_appliance') . '/theme';
  
  // full search form on results pages
  $registry['google_appliance_search_form'] = array(
    'arguments' => array('form' => NULL),
    'render element' => 'form',
    'template' => 'google-appliance-search-form',
    'path' => $ga_template_dir,
  );
  
  // block search form
  $registry['google_appliance_block_form'] = array(
    'render element' => 'form',
    'template' => 'google-appliance-block-form',
    'path' => $ga_template_dir,
  );
  
  // results page (top level template for the rest)
  $registry['google_appliance_results'] = array(
    'variables' => array('search_query_data' => NULL, 'response_data' => NULL),
    'template' => 'google-appliance-results',
    'path' => $ga_template_dir,
  );
  
  // single result within the results page
  $registry['google_appliance_result'] = array(
    'variables' => array('result_data' => NULL),
    'template' => 'google-appliance-result',
    'path' => $ga_template_dir,
  );
  
  // pager
  $registry['google_appliance_pager'] = array(
    'varaibles' => array('pager_data' => NULL),
  );
  
  // sort headers
  $registry['google_appliance_sort_headers'] = array(
    'variables' => array('sort_header_data' => NULL),
    'template' => 'google-appliance-sort-headers',
    'path' => $ga_template_dir,
  );
  
  // search stats
  $registry['google_appliance_search_stats'] = array(
    'variables' => array('search_stats_data' => NULL),
    'template' => 'google-appliance-search-stats',
    'path' => $ga_template_dir,
  );
  
  return $registry;
}

 
/**
 * preprocess for block search form 
 */
function template_preprocess_google_appliance_block_form(&$vars) {
  
  $vars['block_search_form'] = array();
  $hidden = array();
  
  // vars named after form keys so themers can print each element independently.
  foreach (element_children($vars['form']) as $key) {
    
    $type = $vars['form'][$key]['#type'];
    if ($type == 'hidden' || $type == 'token') {
      $hidden[] = drupal_render($vars['form'][$key]);
    }
    else {
      $vars['block_search_form'][$key] = drupal_render($vars['form'][$key]);
    }
  }
  
  // no theming value on hidden elements, so collapse them
  $vars['block_search_form']['hidden'] = implode($hidden);
  
  // collect the whole shebang so the complete form can be rendered with one var
  $vars['block_search_form_complete'] = implode($vars['block_search_form']);
   
  // render template
}

/**
 * preprocess for the full search form 
 */
function template_preprocess_google_appliance_search_form(&$vars) {
  
  $vars['search_form'] = array();
  $hidden = array();
  
  // vars named after form keys so themers can print each element independently.
  foreach (element_children($vars['form']) as $key) {
    
    $type = $vars['form'][$key]['#type'];
    if ($type == 'hidden' || $type == 'token') {
      $hidden[] = drupal_render($vars['form'][$key]);
    }
    else {
      $vars['search_form'][$key] = drupal_render($vars['form'][$key]);
    }
  }
  
  // no theming value on hidden elements, so collapse them
  $vars['search_form']['hidden'] = implode($hidden);
  
  // collect the whole shebang so the complete form can be rendered with one var
  $vars['search_form_complete'] = implode($vars['search_form']);
  
  // render template
}

/**
 * preprocess for google-search-appliance-results.tpl.php (results page)
 */
function template_preprocess_google_appliance_results(&$vars) {

  // grab module settings
  $settings = _google_appliance_get_settings();
  
  // report debug info to admins if module settings 
  if ($vars['is_admin'] && $settings['query_inspection'] == '1') {
    if (isset($vars['search_query_data']['debug_info'])) {
      foreach ($vars['search_query_data']['debug_info'] as $info_slice) {
        drupal_set_message(filter_xss_admin($info_slice));
      }
    }
  }
  
  // grab the search form
  $vars['search_form'] = drupal_get_form(
    'google_appliance_search_form',
    $vars['search_query_data']['gsa_query_params']['q']
  );
  
  // if we have errors, decode them and skip building results entities
  if (isset($vars['response_data']['error'])) {
    
    $vars['results_heading'] = t('No Results Found');
    $vars['error_reason'] = '';
    $log_message_errors = '';
    
    // build hook_help-based error messages for display
    foreach ($vars['response_data']['error'] as $error_key => $error_response) {
      // replace error responses with hook_help messages that exist
      // if they don't exist, just relay the error response
      $help_message = google_appliance_help(
        'google_appliance#error_' . $error_key, 
        drupal_help_arg()
      );
      $vars['error_reason'] .= ($help_message != '') ? $help_message : $error_response;
      
      // build error message for the log (ignore 'no results' condition)
      if ($error_key != 'gsa_no_results') {
        $log_message_errors .= '{ ' . $error_response . ' } ';
      }
    }

    // report communication errors to the log
    if ($log_message_errors != '') {
      _google_appliance_log_search_error(
        $vars['search_query_data']['gsa_query_params']['q'], 
        $log_message_errors
      );
    }
    
  }
  else {  // build results entities
    
    $vars['results_heading'] = t('Search Results');
    
    // get themed sort headers
    $vars['sort_headers'] = theme(
      'google_appliance_sort_headers', $vars['search_query_data']
    );
    
    // get themed results listing
    $vars['search_results'] = '';
    $count = 0;
    foreach ($vars['response_data']['entry'] as $result) {
      $result['result_idx'] = ++$count;
      $vars['search_results'] .= theme('google_appliance_result', $result);
    }
        
    // get themed search stats
    $search_stats_data = array(
      'response_data' => $vars['response_data'],
      'search_query_data' => $vars['search_query_data']
    );
    $vars['search_stats'] = theme('google_appliance_search_stats', $search_stats_data);
    
    // get themed drupal pager
    $pager_data = array(
      'total_results_count' => $vars['response_data']['total_results'],
      'last_result_index' => $vars['response_data']['last_result'],
    );
    $vars['pager'] = theme('google_appliance_pager', $pager_data);
  }
  
  // render template
}

/**
 * preprocess for a single search result
 */
function template_preprocess_google_appliance_result(&$vars) {

  // sanatize urls
  $vars['short_url'] = check_url($vars['short_url']);
  $vars['enc_url'] = check_url($vars['enc_url']);
  $vars['abs_url'] = check_url($vars['abs_url']);

  // sanatize snippet and title ... 
  // allow boldface through for keywork highlighting
  $vars['snippet'] = filter_xss($vars['snippet'], array('b', 'strong'));
  $vars['title'] = filter_xss($vars['title'], array('b', 'strong'));
  
  // sanitize crawl date
  $vars['crawl_date'] = check_plain($vars['crawl_date']);

  // Add a google-appliance-levelx class if we're sub-levels
  if ((int) $vars['level'] > 1) {
    $vars['classes_array'][] = 'google-appliance-level' . $vars['level'];
  }

  // render template
}

/**
 * Set up Drupal pager for pagination of GSA-provided search results
 *
 * We're not paging druppal-database content in this version, so the core pager is not aware 
 * of the page-able results provided by the GSA ...  we're only requesting one page of the 
 * results at a time, but we get enough stats from that query to fake the pager, and provide 
 * a familiar interface.
 */
function theme_google_appliance_pager(&$vars) {

  // grab module settings
  $settings = _google_appliance_get_settings();
  
  // globals required to manually configure the pager
  global $pager_page_array, $pager_total, $pager_total_items;
  
  $control_tags = array();  // default labels
  $element = 0;             
  $limit = $settings['results_per_page'];  
  
  // total # of pages in list
  $total_pages = ceil($vars['total_results_count'] / $limit);
  // NOTE: the total results count from teh GSA is unreliable. The docs state 
  // that it is an *approximation*, but if you click around on enough searches, you'll find that
  // it's off by a considerable amount ... enough to break the math used to create pager
  // links. It's not all that noticeable unless you click on the "last" link in the pager, and
  // notice that in some searches, the last page is paginated at a number less than was previously
  // viewable when the first page of results came up on the initial search view.
  
  // The problem is rooted in public vs. access-controlled indexing. see...
  // groups.google.com/group/Google-Search-Appliance-Help/browse_thread/thread/019b77fb3e7950c7
  // ... access-controled results are counted before we know if we can actually view it. Device
  // configuration can help.
  
  //@TODO: perhaps a better solution is to just query the device for the first X (up to 1000)
  // results and cache them locally.
  
  //
  // manually configure (fake) the pager
  //
  
  $page = ( isset($_GET['page']) ) ? check_plain($_GET['page']) : 0;
  
  // convert page id to array
  $pager_page_array = explode(',', $page);
  
  // set the total results 
  $pager_total_items[$element] = (int)$vars['total_results_count']; 
  
  // set the total # of pages 
  $pager_total[$element] = ceil($pager_total_items[$element] / $limit);  
  $pager_page_array[$element] = max(0, min((int)$pager_page_array[$element], ((int)$pager_total[$element]) - 1));
  
  return theme('pager');
}

/** 
 * preprocess for google-appliance-sort-headers.tpl.php
 */
function template_preprocess_google_appliance_sort_headers(&$vars) {

  // possible sort options
  $vars['sort_options'] = array(
    array(  // default sort
      'sort' => 'rel',
      'label' => t('Relevance'),
      'gsa_key' => '',
    ),
    array(
      'sort' => 'date',
      'label' => t('Date'),
      'gsa_key' => 'date:D:S:d1'
    ),
    /*
    array(
      'sort' => 'date_asc',
      'label' => t('Oldest First'),
      'gsa_key' => 'date:A:S:d1',
    )
    */
  );
  
  // figure out which page we are on
  $cur_page = 0;
  if (isset($_GET['page']) ) {
    $cur_page = check_plain($_GET['page']);
  }
  
  
  $vars['sorters'] = array();
  $sort_idx = 0;
  $sort_found = FALSE;
  foreach ($vars['sort_options'] as $sort_opt ) {
    
    // if we haven't yet found the current sort, look for params again
    $sort_is_current = FALSE;
    $sort_param = $vars['gsa_query_params']['sort'];
    // was a sort requested in the query?
    if ( (!$sort_found) && ($sort_param != '') ) {
      
      // is the sort param one of the valid ones?
      if ( ($sort_param == $sort_opt['gsa_key']) ) {
        $sort_is_current = TRUE;
        $sort_found = TRUE;
      } 
    } 
    elseif ( (!$sort_found) && ($sort_param == '') ) { // we had no sort parameters
      if ($sort_opt['sort'] == 'rel') { // and we are looking at the default sort
        $sort_is_current = TRUE;
        $sort_found = TRUE;
      }
    }
    
    // if we are on the current sort, then we just create non-linked text
    // for the sort label
    $sorter_display_content = '';
    if ($sort_is_current) {
      $vars['sorters'][$sort_idx]['display'] = '<span class="active-sort">' . $sort_opt['label'] . '</span>';
    }
    else {
      // we're not on the current sort, so create a link
      $link_attributes = array();
      $link_attributes['query'] = array(
        'page' => $cur_page,
      );
            
      // append the query to the base url of the serach page
      $link_path = 'gsearch/' . $vars['gsa_query_params']['q'];
      
      // append the sort param if any
      $link_path .= '/' . $sort_opt['sort'];
      
      $vars['sorters'][$sort_idx]['display'] = l($sort_opt['label'], $link_path, $link_attributes);
    }
    $sort_idx++;
  }
  
  // render template
}

/**
 * preprocess for google-appliance-search-stats.tpl.php
 */
function template_preprocess_google_appliance_search_stats(&$vars) {
  
  $vars['stat_entries'] = array(
    '@first' => $vars['response_data']['last_result'] - count($vars['response_data']['entry']) + 1,
    '@last' => $vars['response_data']['last_result'],
    '%query' => urldecode($vars['search_query_data']['gsa_query_params']['urlencoded_q']),
    '@total' => $vars['response_data']['total_results'],
    // the total results reported by the GSA is unreliable, display at your own risk
    // @see: theme_google_appliance_pager 
  );
  
  // render
}
