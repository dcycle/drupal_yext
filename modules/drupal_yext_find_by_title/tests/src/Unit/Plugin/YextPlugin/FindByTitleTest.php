<?php

namespace Drupal\Tests\drupal_yext\Unit\Plugin\YextPlugin;

use Drupal\drupal_yext_find_by_title\Plugin\YextPlugin\FindByTitle;
use PHPUnit\Framework\TestCase;

/**
 * Test FindByTitle.
 *
 * @group expose_status
 */
class TestFindByTitleTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(FindByTitle::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
