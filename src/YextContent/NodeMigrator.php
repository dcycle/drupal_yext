<?php

namespace Drupal\drupal_yext\YextContent;

/**
 * Migrator a NodeMigrateSourceInterface to a NodeMigrateDestinationInterface.
 *
 * Useful for importing nodes from Yext to Drupal.
 */
class NodeMigrator {

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
      $to->setProfileLink($from->getProfileLink());
      $to->setYextId($from->getYextId());
      $to->setYextLastUpdate($from->getYextLastUpdate());
      $to->setYextRawData($from->getYextRawData());
    }
  }

}
