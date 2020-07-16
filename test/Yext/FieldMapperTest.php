<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\Yext\FieldMapper;
use PHPUnit\Framework\TestCase;

/**
 * Test FieldMapper.
 *
 * @group myproject
 */
class FieldMapperTest extends TestCase {

  /**
   * Test for errors().
   *
   * @param string $message
   *   The test message.
   * @param array $all_fields
   *   All the fields returned returned by the mock allFields() method.
   * @param array $expected
   *   The expected result.
   *
   * @cover ::errors
   * @dataProvider providerErrors
   */
  public function testErrors(string $message, array $all_fields, array $expected) {
    $object = $this->getMockBuilder(FieldMapper::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods([
        'yext',
        'nodeTypeLoad',
        'fieldDefinitions',
        'allFields',
        't',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $object->method('yext')
      ->willReturn(new class {
        function yextNodeType() : string {
          return '';
        }
      });

    $object->method('fieldDefinitions')
      ->willReturn([]);

    $object->method('t')
      ->willReturn('A translated string');

    $object->method('allFields')
      ->willReturn($all_fields);

    $output = $object->errors();

    if ($output != $expected) {
      print_r([
        'output' => $output,
        'expected' => $expected,
      ]);
    }

    $this->assertTrue($output == $expected, $message);
  }

  /**
   * Provider for testErrors().
   */
  public function providerErrors() {
    return [
      [
        'message' => 'Name key exists and is empty',
        'all_fields' => [
          [
            'name' => '',
          ],
        ],
        'expected' => [],
      ],
      [
        'message' => 'Name and type exists',
        'all_fields' => [
          [
            'name' => 'some-name',
            'type' => 'some-type',
          ],
        ],
        'expected' => [
          [
            'text' => 'A translated string',
          ],
        ],
      ],
      [
        'message' => 'Name key does not exist',
        'all_fields' => [],
        'expected' => [],
      ],
    ];
  }

}
