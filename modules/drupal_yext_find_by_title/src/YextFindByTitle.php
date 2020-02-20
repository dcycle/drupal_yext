<?php

namespace Drupal\drupal_yext_find_by_title;

use Drupal\drupal_yext\traits\Singleton;
use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\YextContent\YextTargetNode;
use Drupal\drupal_yext_find_by_title\YextFindByTitleResponse\YextFindByTitleResponse;

/**
 * Module functions.
 */
class YextFindByTitle {

  use Singleton;
  use CommonUtilities;

  /**
   * Find a candidate Drupal node based on a YextSourceRecord.
   *
   * If there is ambiguity (several nodes with the same title) we do not
   * want to return any. We are only looking for nodes which dot have
   * a yext id.
   *
   * @param string $yext_title
   *   A location title from Yext.
   *
   * @return NULL|\Drupal\drupal_yext\YextContent\YextTargetNode
   *   The target node if possible.
   */
  public function candidate(string $yext_title) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', $this->yextNodeType());
    $query->condition('title', $yext_title);
    $query->condition($this->yextNodeType(), NULL);
    $entity_ids = $query->execute();



    return NULL;
  }

}
