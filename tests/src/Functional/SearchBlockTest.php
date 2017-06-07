<?php

namespace Drupal\Tests\google_appliance\Functional;

use Drupal\Core\Url;
use Drupal\simpletest\BlockCreationTrait;
use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Test search block.
 *
 * @group google_appliance
 */
class SearchBlockTest extends BrowserTestBase {

  use UserCreationTrait;
  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'block',
    'google_appliance',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Let anonymous users access search results.
    $role = Role::load(RoleInterface::ANONYMOUS_ID);
    $role->grantPermission('access google appliance content')->save();
  }

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
