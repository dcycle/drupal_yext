<?php

namespace Drupal\drupal_yext\Plugin\YextPlugin\Tests;

use Drupal\drupal_yext\Plugin\YextPlugin\YextNumericFieldParser;
use PHPUnit\Framework\TestCase;

/**
 * Test YextNumericFieldParser.
 *
 * @group myproject
 */
class YextNumericFieldParserTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(YextNumericFieldParser::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
