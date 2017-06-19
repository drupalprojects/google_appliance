<?php

namespace Drupal\Tests\google_appliance\Functional;

use Drupal\google_appliance\Routing\SearchViewRoute;
use Drupal\Core\Url;

/**
 * Test related search block.
 *
 * @group google_appliance
 */
class ReleatedSearchesBlockTest extends GoogleApplianceFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'google_appliance_test',
  ];

  /**
   * Test search block form.
   */
  public function testSearchBlock() {
    $this->placeBlock('google_appliance_search');
    $this->placeBlock('google_appliance_related', [
      'visibility' => [
        'request_path' => [
          'pages' => 'search',
          'negate' => FALSE,
        ],
      ],
    ]);

    // Test redirect.
    // Go to the front page and submit the search form.
    $this->drupalGet(Url::fromRoute('<front>'));
    $terms = ['search_keys' => 'ponies'];
    $this->submitForm($terms, t('Search'));

    $this->assertEquals(Url::fromRoute(SearchViewRoute::ROUTE_NAME, [
      'search_query' => 'ponies',
    ])->setAbsolute()->toString(), $this->getSession()->getCurrentUrl());
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->linkByHrefExists(Url::fromRoute(SearchViewRoute::ROUTE_NAME, [
      'search_query' => 'foo',
    ])->toString());
    $assert->linkByHrefExists(Url::fromRoute(SearchViewRoute::ROUTE_NAME, [
      'search_query' => 'bar',
    ])->toString());
    $assert->linkExists('foo');
    $assert->linkExists('bar');
  }

}
