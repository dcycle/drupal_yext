<?php

namespace Drupal\drupal_yext\YextContent;

/**
 * Migrator of data which just arrived from Yext.
 *
 * Contrary to NodeMigrator, this will only migrate if the last update
 * date and time are different. This is because we are assuming that
 * everything was migrated properly last time around.
 *
 * (The NodeMigrator is also used in hook_presave(), meaning that if
 * new field mapping was added after an initial migration, we want to
 * re-migrate new fields even if the update times are identical on the
 * source -- which in the case of NodeMigrator comes from its Yext Raw
 * data field -- and destination, which generally will be the case unless
 * someone manually changed the update time).
 */
class DirectNodeMigrator extends NodeMigrator {

  /**
   * {@inheritdoc}
   */
  public function migrate() : bool {
    $to = $this->to;
    $from = $this->from;
    if ($to->getYextLastUpdate() != $from->getYextLastUpdate()) {
      return parent::migrate();
    }
    return FALSE;
  }

}
