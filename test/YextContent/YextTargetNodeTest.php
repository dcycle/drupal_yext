<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\drupal_yext\YextContent\YextTargetNode;
use PHPUnit\Framework\TestCase;
use Drupal\drupal_yext\Yext\FieldMapper;

/**
 * Test YextTargetNode.
 *
 * @group myproject
 */
class YextTargetNodeTest extends TestCase {

  /**
   * Test for setBio().
   *
   * @param string $message
   *   The test message.
   * @param string $bio_field
   *   The bio field name, which can be empty.
   * @param array $expected
   *   The expected result.
   *
   * @cover ::setBio
   * @dataProvider providerSetBio
   */
  public function testSetBio(string $message, string $bio_field, array $expected) {
    $object = $this->getMockBuilder(YextTargetNode::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods([
        'fieldmap',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $object->method('fieldmap')
      ->willReturn(new class($bio_field) extends FieldMapper {
        public function __construct(string $bio_field) {
          $this->bio_field = $bio_field;
        }
        public function bio() : string {
          return $this->bio_field;
        }
      });

    $object->setEntity(new class implements FieldableEntityInterface {});

    $object->setBio('some bio');

    $output = (array) $object->drupalEntity();

    if ($output != $expected) {
      print_r([
        'output' => $output,
        'expected' => $expected,
      ]);
    }

    $this->assertTrue($output == $expected, $message);
  }

  /**
   * Provider for testSetBio().
   */
  public function providerSetBio() {
    return [
      [
        'message' => 'Bio field is empty',
        'bio_field' => '',
        'expected' => [],
      ],
      [
        'message' => 'Bio field is non-empty',
        'bio_field' => 'whatever',
        'expected' => [
          'whatever' => [
            'value' => 'some bio',
            'format' => 'basic_html',
          ],
        ],
      ],
    ];
  }

}
