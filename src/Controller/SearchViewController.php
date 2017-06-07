<?php

namespace Drupal\google_appliance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\google_appliance\Service\ParserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;
use Drupal\google_appliance\Form\SearchForm;

/**
 * Class SearchView.
 *
 * @package Drupal\google_appliance\Controller
 */
class SearchViewController extends ControllerBase {

  /**
   * Parser.
   *
   * @var \Drupal\google_appliance\Service\ParserInterface
   */
  protected $parser;

  /**
   * Constructs a new SearchViewController object.
   *
   * @param \Drupal\google_appliance\Service\ParserInterface $parser
   *   Parser.
   */
  public function __construct(ParserInterface $parser) {
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_appliance.parser')
    );
  }

  /**
   *
   */
  public function get(Request $request, $search_query = '', $result_sort = NULL) {
    $search_query = urldecode($search_query);

    $form = \Drupal::formBuilder()->getForm(SearchForm::class);

    if ($search_query !== '' && !$request->request->has('form_id')) {
      $requestContent = json_decode($request->getContent(), TRUE);
      $gsaSettings = \Drupal::configFactory()->get('google_appliance.settings')->get();

      $sort_param = $result_sort === 'date' ? 'date:D:S:d1' : '';
      $results_view_start = isset($requestContent['page']) ? (int) Html::escape($requestContent['page']) * (int) $gsaSettings['display_settings']['results_per_page'] : 0;

      // Build cURL request.
      $search_query_data = [
        'gsa_host' => Html::escape($gsaSettings['connection_info']['hostname'] . '/search'),
        'gsa_query_params' => [
          'site' => Html::escape($gsaSettings['connection_info']['collection']),
          'oe' => 'utf8',
          'ie' => 'utf8',
          'getfields' => '*',
          'client' => Html::escape($gsaSettings['connection_info']['frontend']),
          'start' => $results_view_start,
          'num' => Html::escape($gsaSettings['display_settings']['results_per_page']),
          'filter' => Html::escape($gsaSettings['query_param']['autofilter']),
          'q' => $search_query,
          'output' => 'xml_no_dtd',
          'sort' => $sort_param,
          'access' => 'p',
          // 'requiredfields' => $filter_param.
        ],
      ];

      // Alter request according to language filter settings.
      // @todo: FIX
      if (
        isset($gsaSettings['query_param']['language_filter_toggle'])
        && TRUE === $gsaSettings['query_param']['language_filter_toggle']
        && \Drupal::moduleHandler()->moduleExists('locale')
      ) {
        // $search_query_data['gsa_query_params']['lr'] = _google_appliance_get_lr($gsaSettings['language_filter_options']);.
      }

      // Allow implementation of
      // hook_google_appliance_query_alter() by other modules.
      \Drupal::moduleHandler()->alter('google_appliance_query', $search_query_data);

      // Build debug info in case we need to display it.
      if ($gsaSettings['query_param']['query_inspection'] === TRUE) {
        $search_query_data['debug_info'][] = $this->t('GSA host: @host', ['@host' => $search_query_data['gsa_host']]);
        $search_query_data['debug_info'][] = $this->t('Query Parameters: <pre>@qp</pre>',
          ['@qp' => print_r($search_query_data['gsa_query_params'], TRUE)]
        );
      }

      $curl_options = [];

      // @todo: Proxy stuff, see ::proxySettings().

      // Allow implementation of
      // hook_google_appliance_curl_alter() by other modules.
      \Drupal::moduleHandler()->alter('google_appliance_curl', $curl_options);

      /** @var \Drupal\google_appliance\Service\GoogleApplianceSearch $searchService */
      $searchService = \Drupal::service('google_appliance.search');

      // Query the GSA for search results.
      $gsa_response = $searchService->curlGet(
        $search_query_data['gsa_host'],
        $search_query_data['gsa_query_params'],
        $curl_options,
        $gsaSettings['connection_info']['timeout']
      );

      // Check for errors.
      if ($gsa_response['is_error'] === TRUE) {
        $response_data['error']['curl_error'] = $gsa_response['response'];
        // Displaying useful error messages depends upon the use of the array key
        // 'curl_error' ... the actual error code/response is displayed elsewhere.
        // @see google_appliance.theme.inc
      }
      // cURL gave us something back -> attempt to parse.
      else {
        $response_data = $this->parser->parseResponse($gsa_response['response']);
      }

      // Render the results.
      $search_query_data['gsa_query_params']['urlencoded_q'] = urlencode($search_query);

      $template_variables = [
        'search_query_data' => $search_query_data,
        'response_data' => $response_data,
        'gsa_settings' => $gsaSettings,
        'search_form' => $form,
      ];

      return [
        '#type' => 'markup',
        '#theme' => 'google_appliance__search_results',
        '#variables' => $template_variables,
      ];
    }

    return $form;
  }

  /**
   * @todo
   */
  private function proxySettings() {
    // Use drupal proxy if any.
    $drupal_proxy_server = variable_get('proxy_server', '');
    $drupal_proxy_port = variable_get('proxy_port', '');
    $drupal_proxy_username = variable_get('proxy_username', '');
    $drupal_proxy_password = variable_get('proxy_password', '');
    // NULL as default value.
    $drupal_proxy_user_agent = variable_get('proxy_user_agent', NULL);
    $drupal_proxy_exceptions = variable_get('proxy_exceptions', []);

    // Add drupal proxy to curl_options.
    if ($drupal_proxy_server != '') {
      // Parse gsa_hostname to obtain the host without the scheme (http/https).
      // Check if a proxy should be used for gsa_hostname using _drupal_http_use_proxy
      // If parsing fails (and it should not), use the proxy. The idea here is that we should always use the proxy
      // except for some exceptional hosts. If we are not able to confirm that this is indeed an exception
      // better to use the proxy.
      $gsa_hostname_components = \parse_url($gsa_hostname);
      if ($gsa_hostname_components === FALSE || _drupal_http_use_proxy($gsa_hostname_components['host'])) {
        $curl_options[CURLOPT_PROXY] = $drupal_proxy_server;
        // Add port, if provided.
        if ($drupal_proxy_port != '') {
          $curl_options[CURLOPT_PROXY] .= ':' . $drupal_proxy_port;
        }

        // Some proxies reject requests with any User-Agent headers, while others
        // require a specific one.
        if ($drupal_proxy_user_agent !== NULL) {
          $curl_options[CURLOPT_USERAGENT] = $drupal_proxy_user_agent;
        }
      }

      // Set authentication, if needed, checking if proxy_username is not empty.
      if ($drupal_proxy_username != '') {
        // The option CURLOPT_PROXYUSERPWD has format [username]:[password].
        $curl_options[CURLOPT_PROXYUSERPWD] = $drupal_proxy_username . ':' . $drupal_proxy_password;
      }
    }
  }

}
