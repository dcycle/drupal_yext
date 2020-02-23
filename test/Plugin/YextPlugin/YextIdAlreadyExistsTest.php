<?php

namespace Drupal\drupal_yext\Plugin\YextPlugin\Tests;

use Drupal\drupal_yext\Plugin\YextPlugin\YextIdAlreadyExists;
use PHPUnit\Framework\TestCase;

/**
 * Test YextIdAlreadyExists.
 *
 * @group myproject
 */
class YextIdAlreadyExistsTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(YextIdAlreadyExists::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
