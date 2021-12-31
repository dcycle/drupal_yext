<?php

namespace Drupal\drupal_yext\YextContent;

use Drupal\drupal_yext\traits\CommonUtilities;

/**
 * Migrator a NodeMigrateSourceInterface to a NodeMigrateDestinationInterface.
 *
 * Useful for importing nodes from Yext to Drupal.
 */
class NodeMigrationOnSave {

  use CommonUtilities;

  /**
   * The source.
   *
   * @var \Drupal\drupal_yext\YextContent\NodeMigrateSourceInterface
   */
  protected $from;

  /**
   * The destination.
   *
   * @var \Drupal\drupal_yext\YextContent\NodeMigrateDestinationInterface
   */
  protected $to;

  /**
   * Constructor.
   *
   * @param \Drupal\drupal_yext\YextContent\NodeMigrateSourceInterface $from
   *   A source node.
   * @param \Drupal\drupal_yext\YextContent\NodeMigrateDestinationInterface $to
   *   A destination node.
   */
  public function __construct(NodeMigrateSourceInterface $from, NodeMigrateDestinationInterface $to) {
    $this->from = $from;
    $this->to = $to;
  }

  /**
   * Migrate data to from the source to the destination, but don't save it.
   *
   * @return bool
   *   TRUE if a change was made or attempted.
   */
  public function migrate() : bool {
    $to = $this->to;
    $from = $this->from;
    $to->setBio($from->getBio());
    $to->setGeo($from->getGeo());
    $to->setHeadshot($from->getHeadshot());
    $to->setName($from->getName());
    foreach ($this->fieldmap()->customFieldInfo() as $custom) {
      if (!empty($custom[1])) {
        $to->setCustom($custom[1], $from->getCustom($custom[0]));
      }
    }
    $to->setYextId($from->getYextId());
    $to->setYextLastUpdate($from->getYextLastUpdate());
    return TRUE;
  }

}
