<?php

namespace Drupal\drupal_yext_find_by_title\Plugin\YextPlugin;

use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\YextContent\YextSourceRecord;
use Drupal\drupal_yext\YextPluginBase;
use Drupal\drupal_yext_find_by_title\YextFindByTitle;

/**
 * Check if exactly one node exists with no yext id but with the same title.
 *
 * @YextPluginAnnotation(
 *   id = "drupal_yext_find_by_title",
 *   description = @Translation("Find a node by title, not Yext ID."),
 *   weight = 100,
 * )
 */
class FindByTitle extends YextPluginBase {

  use CommonUtilities;

  /**
   * {@inheritdoc}
   */
  public function alterNodeFromSourceRecord(array &$result, YextSourceRecord $record) {
    if (empty($result['target'])) {
      $candidate = YextFindByTitle::instance()->candidate($record->getName());
      if ($candidate) {
        $result['target'] = $candidate;
      }
    }
  }

}
