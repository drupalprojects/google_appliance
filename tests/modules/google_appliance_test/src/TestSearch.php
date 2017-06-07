<?php

namespace Drupal\google_appliance_test;

use Drupal\google_appliance\Response\SearchResponse;
use Drupal\google_appliance\Response\SearchResult;
use Drupal\google_appliance\Service\Search;

/**
 * Defines a test only search implementation.
 */
class TestSearch extends Search {

  /**
   * {@inheritdoc}
   */
  public function search($searchQuery, $sort = NULL, $page = 0, array $languages = []) {
    $perPage = $this->configFactory->get('google_appliance.settings')->get('display_settings.results_per_page');
    $result = new SearchResponse();
    $result->setTotal(100);
    foreach (range(1, $perPage) as $item) {
      $result->addResult(new SearchResult('http://example.com', 'http://example.com', sprintf('Result %d', $item), sprintf('this is a snippet from item %d', $item), date('Y-m-d'), 'text/html'));
    }
    return $result;
  }

}
