<?php

namespace Drupal\drupal_yext\YextContent;

use Drupal\node\Entity\Node;

/**
 * A Yext-specific node entity.
 */
class YextTargetNode extends YextEntity {

  /**
   * {@inheritdoc}
   */
  public function generate() {
    $type = $this->nodeType();

    $node = Node::create([
      'type' => $type,
      'title' => 'Generated ' . $type,
    ]);
    $node->save();

    $this->setEntity($node);
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

}
