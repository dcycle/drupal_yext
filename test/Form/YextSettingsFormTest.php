<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\Form\YextSettingsForm;
use PHPUnit\Framework\TestCase;

/**
 * Test YextSettingsForm.
 *
 * @group myproject
 */
class YextSettingsFormTest extends TestCase {

  /**
   * Test for configSetFromUserInput().
   *
   * @param string $message
   *   The test message.
   * @param string $config_name
   *   A config name.
   * @param array $input
   *   A user input.
   * @param string $key
   *   A key, which can exist or not in the user input.
   * @param mixed $default
   *   A default value.
   * @param string $expected
   *   An expected value with which configSet() will be called.
   *
   * @cover ::configSetFromUserInput
   * @dataProvider providerConfigSetFromUserInput
   */
  public function testConfigSetFromUserInput(string $message, string $config_name, array $input, string $key, $default, string $expected) {
    $object = $this->getMockBuilder(YextSettingsForm::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods([
        'configSet',
      ])
      ->disableOriginalConstructor()
      ->getMock();

    $object->expects($this->once())
      ->method('configSet')
      ->with($config_name, $expected);

    $output = $object->configSetFromUserInput($config_name, $input, $key, $default);
  }

  /**
   * Provider for testConfigSetFromUserInput().
   */
  public function providerConfigSetFromUserInput() {
    return [
      [
        'message' => 'key exists',
        'config_name' => 'whatever',
        'input' => [
          'some_key' => 'hello',
        ],
        'key' => 'some_key',
        'default' => 'goodbye',
        'expected' => 'hello',
      ],
      [
        'message' => 'key does not exist',
        'config_name' => 'whatever',
        'input' => [
          'not_some_key' => 'hello',
        ],
        'key' => 'some_key',
        'default' => 'goodbye',
        'expected' => 'goodbye',
      ],
    ];
  }

}
