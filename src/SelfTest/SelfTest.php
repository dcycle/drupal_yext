<?php

namespace Drupal\drupal_yext\SelfTest;

use Drupal\drupal_yext\traits\Singleton;
use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\YextContent\NodeMigrationAtCreation;
use Drupal\drupal_yext\YextContent\YextSourceRecord;
use Drupal\drupal_yext\YextContent\YextTargetNode;

/**
 * Run some self tests.
 *
 * Usage:
 *
 *   ./scripts/self-test-running-environment.sh
 */
class SelfTest {

  use Singleton;
  use CommonUtilities;

  /**
   * Assert that two values are equal.
   *
   * @param mixed $val1
   *   The first value.
   * @param mixed $val2
   *   The second value.
   * @param string $desc
   *   Description of the test.
   */
  public function assert($val1, $val2, string $desc) {
    $this->print('Testing that ' . $desc);
    $this->print('val1 (should be equal to val2):');
    $this->print($val1);
    $this->print(PHP_EOL);
    $this->print('val2 (should be equal to val1):');
    $this->print($val2);
    $this->print(PHP_EOL);
    if ($val1 === $val2) {
      $this->print('TEST PASSED');
    }
    else {
      $this->print('TEST FALED :( KILLING THE PROCESS');
      die(1);
    }
  }

  /**
   * Print something to the screen.
   *
   * @param mixed $data
   *   A string or anything else which can be printed.
   */
  public function print($data) {
    if (is_string($data)) {
      print_r($data . PHP_EOL);
    }
    else {
      print_r($data);
    }
  }

  /**
   * Perform a mock migration of one record from Yext to Drupal.
   *
   * @param array $structure
   *   A mock data structure on Yext.
   *
   * @return \Drupal\drupal_yext\YextContent\YextTargetNode
   *   A resulting target node on Drupal.
   */
  public function mockMigrate(array $structure) : YextTargetNode {
    $source = new YextSourceRecord($structure);
    $entity = $this->yext()->getOrCreateUniqueNode($source);
    (new NodeMigrationAtCreation($source, $entity))->migrate();
    return $entity;
  }

  /**
   * Run some self-tests. Exit with non-zero code if errors occur.
   *
   * Usage:
   *
   *   ./scripts/self-test-running-environment.sh
   */
  public function run() {
    $this->print('Starting self-test.');
    $this->assert(\Drupal::moduleHandler()->moduleExists('drupal_yext_find_by_title'), TRUE, 'Please enable the drupal_yext_find_by_title module before running selftests.');

    $this->print('Confirming we can create a new node based on Yext data.');

    $entity = $this->mockMigrate([
      'id' => '12345',
      'locationName' => 'Hello World',
      'timestamp' => 2,
    ]);

    $this->print('Created entity with id ' . $entity->id() . '.');
    $this->assert($entity->drupal_entity->getTitle(), 'Hello World', 'node title is location name');

    $entity2 = $this->mockMigrate([
      'id' => '12345',
      'locationName' => 'Hello World2',
      'timestamp' => 1,
    ]);

    $this->assert($entity->id(), $entity2->id(), 'the second time we try to get or creat the entity, the existing entity is returned because the yext id is the same.');

    $this->assert($entity->drupal_entity->getTitle(), 'Hello World', 'node title is NOT updated location name because timestamp of second migrated item is earlier than the first');

    $entity3 = $this->mockMigrate([
      'id' => '12345',
      'locationName' => 'Hello World2',
      'timestamp' => 3,
    ]);

    $this->assert($entity3->id(), $entity2->id(), 'the third time we try to get or creat the entity, the existing entity is returned because the yext id is the same.');

    $this->assert($entity3->drupal_entity->getTitle(), 'Hello World2', 'node title is updated location name because timestamp of third migrated item is later than the first');

    $this->print('Deleting the entities we created.');

    $entity->drupal_entity->delete();

    $this->print('Self-test completed successfully.');
  }

}
