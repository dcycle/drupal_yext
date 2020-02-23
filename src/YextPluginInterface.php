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

  /**
   * Parse an id from a source record.
   *
   * Yext data is stored differently in different contexts: numeric fields are
   * stored under "customFields"; sometime field data is stored as arrays, and
   * sometimes directly as strings. Different plugins can manage different
   * types of data.
   *
   * @param \Drupal\drupal_yext\YextContent\YextSourceRecord $source_record
   *   A Yext source record.
   * @param string $field_id
   *   A field id.
   * @param array $data
   *   Resulting data, always in the form of an array of strings.
   */
  public function parseSourceElem(YextSourceRecord $source_record, string $field_id, array &$data);

}
