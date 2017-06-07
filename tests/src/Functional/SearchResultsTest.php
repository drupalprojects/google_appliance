<?php

namespace Drupal\Tests\google_appliance\Functional;
use Drupal\Core\Url;
use Drupal\google_appliance_test\TestSearch;

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
    $config
      ->set('display_settings.results_per_page', 1)
      ->set('display_settings.search_title', 'Here are the results')
      ->save();
    // Search via URL again.
    $this->drupalGet('gsearch/ponies');
    $results = $page->findAll('css', 'li.search-result');
    $this->assertNotEmpty($results);
    // 1 result.
    $this->assertCount(1, $results);
    $title = $page->find('css', 'h2:contains("Here are the results")');
    $this->assertNotEmpty($title);
  }

  /**
   * Tests pager, header and sorting.
   */
  public function testPagerHeaderAndSorting() {
    // Submit the search specified in $file_spec via URL (form submission already tested)
    $this->drupalGet('gsearch/ponies');

    $assert = $this->assertSession();
    // Make sure we get a response, and that it is not an error message.
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('No Results');

    // Verify that we have search stats report correctly.
    $expectation = 'Showing results 1 - 20 for <em class="placeholder">ponies</em>';
    $assert->responseContains($expectation);

    // Verify that sort headers display correctly
    // default state is "Relevance" not linked and "Date" linked.
    $assert->pageTextContains('Relevance');
    $assert->linkNotExists('Relevance');
    $assert->linkExists('Date');

    // Verify that we have the pager, and that page 1 is marked current.
    $assert->responseContains('<ul class="pager">');
    $assert->responseContains('<li class="pager-current first">1</li>');

    // Verify paging function.
    $this->clickLink('2');
    // Make sure resulting page doesn't have error message.
    $assert->pageTextNotContains('No Results');
    // Verify that we have the pager, and that page 2 is marked current.
    $assert->responseContains('<ul class="pager">');
    $assert->responseContains('<li class="pager-current">2</li>');

    // Verify sorting function.
    $this->clickLink('Date');
    $assert->pageTextNotContains('No Results');
    $this->assertEquals(Url::fromRoute('google_appliance.search_view', [
      'search_query' => 'ponies',
      'result_sort' => 'date',
    ])->setAbsolute()->setOption('query', [
      'page' => 1,
    ])->toString(), $this->getSession()->getCurrentUrl());
    $assert->linkNotExists('Date');
    // Bottom bar (second) link.
    $this->clickLink('Relevance', 1);
    $assert->pageTextNotContains('No Results');
    $assert->linkNotExists('Relevance');
    $this->assertEquals(Url::fromRoute('google_appliance.search_view', [
      'search_query' => 'ponies',
      'result_sort' => 'rel',
    ])->setAbsolute()->setOption('query', [
      'page' => 1,
    ])->toString(), $this->getSession()->getCurrentUrl());
  }

}
