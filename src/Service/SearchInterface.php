<?php

namespace Drupal\google_appliance\Service;

/**
 * Defines an interface for performing a GSA search.
 */
interface SearchInterface {

  /**
   * Performs search.
   *
   * @param string $searchQuery
   *   Search terms.
   * @param string|null $sort
   *   Sort field. Pass 'Date' for date search. Leave empty for default.
   * @param int $page
   *   Result to page from.
   * @param array $languages
   *   Array of language codes.
   *
   * @return \Drupal\google_appliance\Response\SearchResponse
   *   Search response.
   */
  public function search($searchQuery, $sort = NULL, $page = 0, array $languages = []);

}
