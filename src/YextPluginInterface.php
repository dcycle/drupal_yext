<?php

namespace Drupal\drupal_yext;

use Drupal\drupal_yext\YextContent\YextSourceRecord;

/**
 * An interface for all YextPlugin type plugins.
 *
 * This is based on code from the Examples module.
 */
interface YextPluginInterface {

  /**
   * Alter response; add "target" key to result if it is not already there.
   *
   * If a "target" key is added, it must be of type
   * \Drupal\drupal_yext\YextContent\YextTargetNode.
   *
   * @param array $result
   *   A result which should contain .
   * @param \Drupal\drupal_yext\YextContent\YextSourceRecord $record
   *   A source record from Yext.
   */
  public function alterNodeFromSourceRecord(array &$result, YextSourceRecord $record);

}
