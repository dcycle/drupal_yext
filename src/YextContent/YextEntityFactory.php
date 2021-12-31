<?php

namespace Drupal\drupal_yext\YextContent;

use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\traits\Singleton;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\node\Entity\Node;

/**
 * An entity factory. Returns an object to manipulate Drupal entities.
 */
class YextEntityFactory {

  use CommonUtilities;
  use Singleton;

  /**
   * Get a NodeMigrateDestinationInterface based on an entity.
   *
   * If the entity does not have raw Yext data in it, ignore it.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   A Drupal entity.
   *
   * @return \Drupal\drupal_yext\YextContent\NodeMigrateDestinationInterface
   *   A destination for migration.
   */
  public function destinationIfLinkedToYext(FieldableEntityInterface $entity) : NodeMigrateDestinationInterface {
    if ($entity->getEntityType()->id() != 'node') {
      return new YextIgnoreNode();
    }
    if (method_exists($entity, 'getType') && $entity->getType() != $this->yextNodeType()) {
      return new YextIgnoreNode();
    }
    $candidate = $this->entity($entity);
    $raw = $candidate->fieldValue($this->fieldmap()->raw());
    if (!$raw) {
      return new YextIgnoreNode();
    }
    if (is_a($candidate, YextTargetNode::class)) {
      return $candidate;
    }
    return new YextIgnoreNode();
  }

  /**
   * Given a Drupal entity, return a Yext Entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   A Drupal entity.
   *
   * @return \Drupal\drupal_yext\YextContent\YextEntity
   *   A Yext-specific wrapper for a Drupal entity.
   */
  public function entity(FieldableEntityInterface $entity) : YextEntity {
    if (is_a($entity, Node::class)) {
      $class = YextTargetNode::class;
    }
    else {
      $class = YextEntity::class;
    }
    $return = new $class();
    $return->setEntity($entity);
    return $return;
  }

  /**
   * Generates a new entity, and saves it.
   *
   * @param string $bundle
   *   A bundle such as "node".
   * @param string $type
   *   A type such as "article".
   *
   * @return YextEntity
   *   The generated entity.
   */
  public function generate(string $bundle, string $type) {
    if ($bundle == 'node') {
      $class = YextTargetNode::class;
    }
    else {
      $class = YextEntity::class;
    }
    $return = new $class();
    $return->generate();
    return $return;
  }

  /**
   * Assuming a unique field_name, load nodes for those field names.
   *
   * An exception is thrown if two or more nodes share the same value for
   * field_name.
   *
   * @param string $node_type
   *   A node type such as 'doctor' or 'article'.
   * @param string $field_name
   *   A field name such as 'field_external_system_id'.
   * @param array $field_values
   *   An array of possible field values for $field_name.
   *
   * @return array
   *   An associativ array keyed by field value with objects of type
   *   YextTargetNode.
   */
  public function preloadUniqueNodes(string $node_type, string $field_name, array $field_values) : array {
    if (empty($field_values)) {
      return [];
    }

    $query = $this->drupalEntityQuery('node');
    $query->condition('type', $node_type);
    $query->condition($field_name, $field_values, 'IN');

    $nids = $query->execute();
    $nodes = Node::loadMultiple($nids);
    $return = [];
    foreach ($nodes as $node) {
      $entity = $this->entity($node);
      $unique = $entity->fieldValue($field_name);
      if (isset($return[$unique])) {
        if ($this->stateGet('drupal_yext_autodelete_duplicates', FALSE)) {
          $node->delete();
        }
        else {
          throw new \Exception('More than one node seems to have the same value (' . $unique . ') which should be unique for ' . $field_name . ', ' . $entity->id() . ' and ' . $return[$unique]->id());
        }
      }
      $return[$unique] = $entity;
    }
    return $return;
  }

}
