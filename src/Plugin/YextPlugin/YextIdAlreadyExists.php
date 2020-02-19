<?php

namespace Drupal\drupal_yext\Plugin\YextPlugin;

use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\YextContent\YextEntityFactory;
use Drupal\drupal_yext\YextPluginBase;

/**
 * If a node with the Yext ID already exists in Drupal, return it.
 *
 * @YextPluginAnnotation(
 *   id = "drupal_yext_node_already_exists",
 *   description = @Translation("Potentially pre-existing Drupal node."),
 *   weight = -1,
 * )
 */
class YextIdAlreadyExists extends YextPluginBase {

  use CommonUtilities;

  /**
   * {@inheritdoc}
   */
  public function alterNodeFromSourceRecord(array &$result, YextSourceRecord $record) {
    if (empty($result['target'])) {
      $candidates = YextEntityFactory::instance()->preloadUniqueNodes($this->yext()->yextNodeType(), $this->yext()->uniqueYextIdFieldName(), [$record->getYextId()]);
      if (isset($candidates[$record->getYextId()])) {
        $result['target'] = $candidates[$record->getYextId()];
      }
    }
  }

}
