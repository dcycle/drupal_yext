<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\YextContent\NodeMigrator;
use PHPUnit\Framework\TestCase;

/**
 * Test NodeMigrator.
 *
 * @group myproject
 */
class NodeMigratorTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(NodeMigrator::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
