<?php

namespace Drupal\drupal_yext\Plugin\YextPlugin\Tests;

use Drupal\drupal_yext\Plugin\YextPlugin\YextNonNumericFieldParser;
use PHPUnit\Framework\TestCase;

/**
 * Test YextNonNumericFieldParser.
 *
 * @group myproject
 */
class YextNonNumericFieldParserTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(YextNonNumericFieldParser::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods(NULL)
      ->disableOriginalConstructor()
      ->getMock();

    $this->assertTrue(is_object($object));
  }

}
