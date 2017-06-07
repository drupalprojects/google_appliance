<?php

namespace Drupal\Tests\google_appliance\Functional;

use Drupal\Core\Url;

/**
 * Test search block.
 *
 * @group google_appliance
 */
class SearchBlockTest extends GoogleApplianceFunctionalTestBase {

  /**
   * Test search block form.
   */
  public function testSearchBlock() {
    $this->placeBlock('google_appliance_search');

    // Test redirect.
    // Go to the front page and submit the search form.
    $this->drupalGet(Url::fromRoute('<front>'));
    $terms = ['search_keys' => 'ponies'];
    $this->submitForm($terms, t('Search'));

    $this->assertEquals(Url::fromRoute('google_appliance.search_view', [
      'search_query' => 'ponies',
    ])->setAbsolute()->toString(), $this->getSession()->getCurrentUrl());
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
  }

}
