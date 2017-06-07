<?php

namespace Drupal\google_appliance\Response;

/**
 * Defines a value object for a search response.
 */
class SearchResponse {

  const ERROR_PARSING = 'lib_xml_parse_error';
  const ERROR_NO_RESULTS = 'gsa_no_results';
  const ERROR_HTTP = 'http_error';

  /**
   * Response errors keyed by type.
   *
   * @var string[]
   */
  protected $errors = [];

  /**
   * Total results.
   *
   * @var int
   */
  protected $total = 0;

  /**
   * Result objects.
   *
   * @var \Drupal\google_appliance\Response\SearchResult[]
   */
  protected $results = [];

  /**
   * Spelling suggestions.
   *
   * @var string[]
   */
  protected $spellingSuggestions = [];

  /**
   * Key matches.
   *
   * @var \Drupal\google_appliance\Response\KeyMatch[]
   */
  protected $keyMatches = [];

  /**
   * Synonyms.
   *
   * @var \Drupal\google_appliance\Response\Synonym[]
   */
  protected $synonyms = [];

  /**
   * Last result.
   *
   * @var string
   */
  protected $lastResult = '';

  /**
   * One box result keyed by module name.
   *
   * @var \Drupal\google_appliance\Response\OneBoxResultSet[]
   */
  protected $oneBoxResultSets = [];

  /**
   * Check if response is in error.
   *
   * @return bool
   *   TRUE if errors occurred.
   */
  public function hasErrors() {
    return (bool) array_filter($this->errors);
  }

  /**
   * Sets total.
   *
   * @param int $total
   *   Total.
   *
   * @return $this
   */
  public function setTotal($total) {
    $this->total = $total;
    return $this;
  }

  /**
   * Adds error.
   *
   * @param string $message
   *   Error.
   * @param string $type
   *   Error type.
   *
   * @return $this
   */
  public function addError($message, $type = self::ERROR_PARSING) {
    $this->errors[$type][] = $message;
    return $this;
  }

  /**
   * Adds spelling suggestion.
   *
   * @param string $string
   *   Suggestion.
   *
   * @return $this
   */
  public function addSpellingSuggestion($string) {
    $this->spellingSuggestions[] = $string;
    return $this;
  }

  /**
   * Adds key match.
   *
   * @param \Drupal\google_appliance\Response\KeyMatch $match
   *   Match.
   *
   * @return $this
   */
  public function addKeyMatch(KeyMatch $match) {
    $this->keyMatches[] = $match;
    return $this;
  }

  /**
   * Adds synonym.
   *
   * @param \Drupal\google_appliance\Response\Synonym $synonym
   *   Synonym.
   *
   * @return $this
   */
  public function addSynonym(Synonym $synonym) {
    $this->synonyms[] = $synonym;
    return $this;
  }

  /**
   * Adds a result.
   *
   * @param \Drupal\google_appliance\Response\SearchResult $result
   *   Result.
   *
   * @return $this
   */
  public function addResult(SearchResult $result) {
    $this->results[] = $result;
    return $this;
  }

  /**
   * Gets value of errors.
   *
   * @return array
   *   Value of errors
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Gets value of total.
   *
   * @return int
   *   Value of total
   */
  public function getTotal() {
    return $this->total;
  }

  /**
   * Gets value of results.
   *
   * @return \Drupal\google_appliance\Response\SearchResult[]
   *   Value of results
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * Gets value of spellingSuggestions.
   *
   * @return array
   *   Value of spellingSuggestions
   */
  public function getSpellingSuggestions() {
    return $this->spellingSuggestions;
  }

  /**
   * Gets value of keyMatches.
   *
   * @return array
   *   Value of keyMatches
   */
  public function getKeyMatch() {
    return $this->keyMatches;
  }

  /**
   * Gets value of synonyms.
   *
   * @return array
   *   Value of synonyms
   */
  public function getSynonyms() {
    return $this->synonyms;
  }

  /**
   * Gets value of lastResult.
   *
   * @return string
   *   Value of lastResult
   */
  public function getLastResult() {
    return $this->lastResult;
  }

  /**
   * Sets last result.
   *
   * @param string $lastResult
   *   New value for last result.
   *
   * @return $this
   */
  public function setLastResult($lastResult) {
    $this->lastResult = $lastResult;
    return $this;
  }

  /**
   * Adds one box result set.
   *
   * @param string $module_name
   *   One box module.
   * @param \Drupal\google_appliance\Response\OneBoxResultSet $onebox
   *   Results set.
   *
   * @return $this
   */
  public function addOneBoxResultSet($module_name, OneBoxResultSet $onebox) {
    $this->oneBoxResultSets[$module_name] = $onebox;
    return $this;
  }

  /**
   * Check if there are results.
   *
   * @return bool
   *   TRUE if there are results.
   */
  public function hasResults() {
    return (bool) count($this->results);
  }

  /**
   * Check if there are synonyms.
   *
   * @return bool
   *   TRUE if synonyms exist.
   */
  public function hasSynonyms() {
    return (bool) count($this->synonyms);
  }

  /**
   * Check if there are key matches.
   *
   * @return bool
   *   TRUE if key matches exist.
   */
  public function hasKeyMatch() {
    return (bool) count($this->keyMatches);
  }

  /**
   * Check if there are suggestions.
   *
   * @return bool
   *   TRUE if suggestions exist.
   */
  public function hasSpellingSuggestions() {
    return (bool) count($this->spellingSuggestions);
  }

}
