<?php

namespace Drupal\Tests\google_appliance\Functional;

/**
 * Tests search results are output.
 *
 * @group google_appliance
 */
class SearchResultsTest extends GoogleApplianceFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'google_appliance_test',
  ];

  /**
   * Tests search results.
   */
  public function testSearchResults() {
    $assert = $this->assertSession();
    $this->drupalGet('gsearch/ponies');

    // Make sure we get a response, and that it is not an error message.
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('No Results');

    // Verify that the result index of last result on the page doesn't exceed
    // the results_per_page setting.
    $page = $this->getSession()->getPage();
    $results = $page->findAll('css', 'li.search-result');
    $this->assertNotEmpty($results);
    // No more than 20 results.
    $this->assertTrue(count($results) <= 20);

    // Change the results per page count to 1 to verify that we have an exact
    // match for the results per page setting.
    $config = $this->container->get('config.factory')->getEditable('google_appliance.settings');
    $config->set('display_settings.results_per_page', 1)->save();
    // Search via URL again.
    $this->drupalGet('gsearch/ponies');
    $results = $page->findAll('css', 'li.search-result');
    $this->assertNotEmpty($results);
    // 1 result.
    $this->assertCount(1, $results);
  }

}
