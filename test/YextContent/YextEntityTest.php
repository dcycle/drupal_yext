<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\YextContent\YextEntity;
use PHPUnit\Framework\TestCase;

/**
 * Test YextEntity.
 *
 * @group myproject
 */
class YextEntityTest extends TestCase {

  /**
   * Test for fieldValue().
   *
   * @param string $message
   *   The test message.
   * @param array $mock_entity
   *   A mock entity.
   * @param string $field_name
   *   A field name to fetch.
   * @param string $expected
   *   The expected result.
   *
   * @cover ::fieldValue
   * @dataProvider providerFieldValue
   */
  public function testFieldValue(string $message, array $mock_entity, string $field_name, string $expected) {
    $object = $this->getMockBuilder(YextEntity::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods([
        'drupalEntity'
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $object->method('drupalEntity')
      ->willReturn(new class($mock_entity, $field_name) {
        public function __construct($mock_entity, $field_name) {
          $this->mock_entity = $mock_entity;
          $this->field_name = $field_name;
        }

        public function get($field_name) {
          if (isset($this->mock_entity[$field_name])) {
            return new class($this->mock_entity, $this->field_name) {
              public function __construct($mock_entity, $field_name) {
                $this->mock_entity = $mock_entity;
                $this->field_name = $field_name;
              }

              public function getValue() {
                return $this->mock_entity[$this->field_name];
              }
            };
          }
          else {
            return NULL;
          }
        }
      });

    $output = $object->fieldValue($field_name);

    if ($output != $expected) {
      print_r([
        'output' => $output,
        'expected' => $expected,
      ]);
    }

    $this->assertTrue($output == $expected, $message);
  }

  /**
   * Provider for testFieldValue().
   */
  public function providerFieldValue() {
    return [
      [
        'message' => 'Happy path, field value exists.',
        'mock_entity' => [
          'some_field' => [
            [
              'value' => 'hello!',
            ],
          ],
        ],
        'field_name' => 'some_field',
        'expected' => 'hello!',
      ],
      [
        'message' => 'Happy path, field value does not exist.',
        'mock_entity' => [
          'some_field' => [],
        ],
        'field_name' => 'some_field',
        'expected' => '',
      ],
      [
        'message' => 'Sad path, field does not exist.',
        'mock_entity' => [
          'not_some_field' => [],
        ],
        'field_name' => 'some_field',
        'expected' => '',
      ],
    ];
  }

}
