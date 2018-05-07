<?php

namespace Drupal\drupal_yext\YextContent;

use Drupal\drupal_yext\traits\CommonUtilities;

/**
 * Migrator a NodeMigrateSourceInterface to a NodeMigrateDestinationInterface.
 *
 * Useful for importing nodes from Yext to Drupal.
 */
class NodeMigrator {

  use CommonUtilities;

  /**
   * Constructor.
   *
   * @param NodeMigrateSourceInterface $from
   *   A source node.
   * @param NodeMigrateDestinationInterface $to
   *   A destination node.
   */
  public function __construct(NodeMigrateSourceInterface $from, NodeMigrateDestinationInterface $to) {
    $this->from = $from;
    $this->to = $to;
  }

  /**
   * Migrate data to from the source to the destination, but don't save it.
   */
  public function migrate() {
    $to = $this->to;
    $from = $this->from;
    if ($to->getYextLastUpdate() != $from->getYextLastUpdate()) {
      $to->setBio($from->getBio());
      $to->setHeadshot($from->getHeadshot());
      $to->setName($from->getName());
      foreach ($this->fieldmap()->customFieldInfo() as $custom) {
        if (!empty($custom[1])) {
          $to->setCustom($custom[1], $from->getCustom($custom[0]));
        }
      }
      $to->setYextId($from->getYextId());
      $to->setYextLastUpdate($from->getYextLastUpdate());
      $to->setYextRawData($from->getYextRawData());
    }
  }

}
