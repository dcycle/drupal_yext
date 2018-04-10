<?php

namespace Drupal\drupal_yext\YextContent;

use Drupal\node\Entity\Node;

/**
 * A Yext-specific node entity.
 */
class YextTargetNode extends YextEntity implements NodeMigrateDestinationInterface {

  /**
   * {@inheritdoc}
   */
  public function generate() {
    $type = $this->yext()->yextNodeType();

    $node = Node::create([
      'type' => $type,
      'title' => 'Generated ' . $type,
    ]);

    $node->save();

    $this->setEntity($node);
  }

  /**
   * {@inheritdoc}
   */
  public function getYextLastUpdate() : int {
    $value = $this->fieldValue($this->yext()->uniqueYextLastUpdatedFieldName());
    if (empty($value)) {
      // Never updated. This can happen on the first try.
      return 0;
    }
    if (!is_numeric($value)) {
      throw new \Exception('Yext last updated should be numeric, not ' . $value);
    }
    return (int) $value;
  }

  /**
   * Get the type of this node if possible.
   *
   * @return string
   *   The node type.
   *
   * @throws Exception
   */
  public function nodeType() : string {
    return $this->drupalEntity()->getType();
  }

  /**
   * {@inheritdoc}
   */
  public function setBio(string $bio) {
    // TODO.
  }

  /**
   * {@inheritdoc}
   */
  public function setHeadshot(string $url) {
    // TODO.
  }

  /**
   * {@inheritdoc}
   */
  public function setName(string $name) {
    $this->drupal_entity->setTitle($name);
  }

  /**
   * {@inheritdoc}
   */
  public function setProfileLink(string $url) {
    // TODO.
  }

  /**
   * {@inheritdoc}
   */
  public function setYextId(string $id) {
    $this->drupal_entity->set($this->yext()->uniqueYextIdFieldName(), $id);
  }

  /**
   * {@inheritdoc}
   */
  public function setYextLastUpdate(int $timestamp) {
    $this->drupal_entity->set($this->yext()->uniqueYextLastUpdatedFieldName(), $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function setYextRawData(string $data) {
    // TODO.
  }

}
