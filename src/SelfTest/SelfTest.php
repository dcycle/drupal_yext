<?php

namespace Drupal\drupal_yext\SelfTest;

use Drupal\drupal_yext\traits\Singleton;
use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\YextContent\NodeMigrationAtCreation;
use Drupal\drupal_yext\YextContent\YextEntityFactory;
use Drupal\drupal_yext\YextContent\YextSourceRecord;
use Drupal\drupal_yext\YextContent\YextTargetNode;
// drupal_yext_find_by_title is not a dependency of drupal_yext, however
// in this context this is self-test code and it's being run by the
// the CI script, so we can control whether drupal_yext_find_by_title is
// enabled, plus we're explicitly checking that it's enabled before using
// it.
use Drupal\drupal_yext_find_by_title\YextFindByTitle;

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
   * Generate a dummy node.
   *
   * @param string $title
   *   The node title.
   *
   * @return array
   *   An array with one item whose key is the node id, and the value is
   *   an object of class YextTargetNode.
   *
   * @throws \Exception
   */
  public function generateDummy(string $title) : array {
    $node = YextEntityFactory::instance()->generate('node', 'article');
    $node->drupal_entity->setTitle($title);
    $node->drupal_entity->save();
    return [
      $node->id() => $node,
    ];
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

    $this->print('Creating some dummy nodes.');

    $dummy_nodes = [];
    $dummy_nodes += $this->generateDummy('ONE OF THESE');
    $dummy_nodes += $this->generateDummy('TWO OF THESE');
    $dummy_nodes += $this->generateDummy('TWO OF THESE');
    $dummy_nodes += $this->generateDummy('ONE OF THESE IS EMPTY');
    $has_yext_id = $this->generateDummy('ONE OF THESE IS EMPTY');
    $has_yext_id = array_pop($has_yext_id);
    $has_yext_id->setYextId('whatever');
    $has_yext_id->drupal_entity->save();
    $dummy_nodes += [
      $has_yext_id->id() => $has_yext_id,
    ];

    $this->assert(FALSE, is_null(YextFindByTitle::instance()->candidate('ONE OF THESE')), 'If there is only one node with no yext ID and a specific title, use it.');
    $this->assert(TRUE, is_null(YextFindByTitle::instance()->candidate('TWO OF THESE')), 'If there is more than one node with a specific title, do not use it as it is too ambiguous.');
    $this->assert(FALSE, is_null(YextFindByTitle::instance()->candidate('ONE OF THESE IS EMPTY')), 'If there are two nodes with a specific title, but one already has a yext id, then use the other one.');

    $entity4 = $this->mockMigrate([
      'id' => 'not important',
      'locationName' => 'ONE OF THESE',
      'timestamp' => 1,
    ]);

    $entity5 = $this->mockMigrate([
      'id' => 'not important',
      'locationName' => 'TWO OF THESE',
      'timestamp' => 1,
    ]);

    $entity6 = $this->mockMigrate([
      'id' => 'not important',
      'locationName' => 'TWO OF THESE IS EMPTY',
      'timestamp' => 1,
    ]);

    $this->assert(TRUE, in_array($entity4->id(), $dummy_nodes), 'Uses existing node if possible.');
    $this->assert(FALSE, in_array($entity5->id(), $dummy_nodes), 'Uses new node if existing title is ambiguous.');
    $this->assert(TRUE, in_array($entity6->id(), $dummy_nodes), 'Uses existing node if possible.');

    $this->print('Delete our dummy nodes.');

    foreach ($dummy_nodes as $node) {
      $node->drupal_entity->delete();
    }
    $entity5->drupal_entity->delete();
    $this->print('Self-test completed successfully.');
  }

}
