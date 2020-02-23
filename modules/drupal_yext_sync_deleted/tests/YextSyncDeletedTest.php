<?php

namespace Drupal\drupal_yext_sync_deleted\Tests;

use Drupal\drupal_yext_sync_deleted\YextSyncDeleted;
use PHPUnit\Framework\TestCase;

/**
 * Test YextSyncDeleted.
 *
 * @group myproject
 */
class YextSyncDeletedTest extends TestCase {

  /**
   * Test for someNids().
   *
   * @param string $message
   *   The test message.
   * @param array $input
   *   The input.
   * @param int $first
   *   The dummy first item to check.
   * @param int $count
   *   The dummy number of items to return.
   * @param array $expected
   *   The expected output.
   * @param mixed $expected_next_first
   *   The expected next first item, or NULL if we shouldn't call it.
   *
   * @cover ::someNids
   * @dataProvider providerSomeNids
   */
  public function testSomeNids(string $message, array $input, int $first, int $count, array $expected, $expected_next_first) {
    $object = $this->getMockBuilder(YextSyncDeleted::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods([
        'numItemsPerCronRun',
        'getFirst',
        'setFirst',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $object->method('numItemsPerCronRun')
      ->willReturn($count);
    $object->method('getFirst')
      ->willReturn($first);

    $object->expects($expected_next_first === NULL ? $this->never() : $this->once())
      ->method('setFirst')
      ->with($expected_next_first);

    $output = $object->someNids($input);

    if ($output != $expected) {
      print_r([
        'message' => $message,
        'output' => $output,
        'expected' => $expected,
      ]);
    }

    $this->assertTrue($output == $expected, $message);
  }

  /**
   * Provider for testSomeNids().
   */
  public function providerSomeNids() {
    return [
      [
        'message' => 'Empty input',
        'input' => [],
        'first' => 0,
        'count' => 10,
        'expected' => [],
        'expected_next_first' => NULL,
      ],
      [
        'message' => 'Basic case',
        'input' => [
          0 => 100,
          1 => 111,
          2 => 222,
        ],
        'first' => 0,
        'count' => 2,
        'expected' => [
          0 => 100,
          1 => 111,
        ],
        'expected_next_first' => 222,
      ],
      [
        'message' => 'Too big count',
        'input' => [
          0 => 100,
          1 => 111,
          2 => 222,
        ],
        'first' => 0,
        'count' => 200,
        'expected' => [
          0 => 100,
          1 => 111,
          2 => 222,
        ],
        'expected_next_first' => 100,
      ],
      [
        'message' => 'Wraparound too big number',
        'input' => [
          0 => 100,
          1 => 111,
          2 => 222,
        ],
        'first' => 222,
        'count' => 200,
        'expected' => [
          0 => 222,
          1 => 100,
          2 => 111,
        ],
        'expected_next_first' => 222,
      ],
      [
        'message' => 'Wraparound',
        'input' => [
          0 => 100,
          1 => 111,
          2 => 222,
        ],
        'first' => 222,
        'count' => 2,
        'expected' => [
          0 => 222,
          1 => 100,
        ],
        'expected_next_first' => 111,
      ],
    ];
  }

}
