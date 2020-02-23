<?php

namespace Drupal\drupal_yext_sync_deleted;

use Drupal\Core\Entity\EntityInterface;
use Drupal\drupal_yext\traits\Singleton;
use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\node\Entity\Node;

/**
 * Module functions.
 */
class YextSyncDeleted {

  use Singleton;
  use CommonUtilities;

  /**
   * Get the next node we need to process.
   *
   * @return int
   *   The nid to process.
   */
  public function getFirst() : int {
    return $this->stateGet('drupal_yext_sync_deleted_first', 0);
  }

  /**
   * Mockable implementation of hook_cron().
   */
  public function hookCron() {
    $type = $this->yextNodeType();

    $drupal_nids = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->condition($this->yext()->uniqueYextIdFieldName(), NULL, 'IS NOT NULL')
      ->condition($this->yext()->uniqueYextIdFieldName(), '%DELETED%', 'NOT LIKE')
      ->condition('status', TRUE)
      ->execute();

    $nids = $this->someNids($drupal_nids);

    print_r([
      $drupal_nids,
      $nids,
    ]);

    foreach (Node::loadMultiple($nids) as $node) {
      $this->syncDeleted($node);
    }

  }

  /**
   * Mark an entity as deleted and save it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @param string $old_value
   *   The Yext entity id.
   */
  public function markAsDeleted(EntityInterface $entity, string $old_value) {
    $new_value = 'DELETED FROM YEXT ' . $old_value;

    $raw = $entity->get(drupal_yext()->fieldmap()->raw())->getValue();
    if (isset($raw[0]['value'])) {
      $new_raw = str_replace($old_value, $new_value, $raw[0]['value']);
      $entity->set(drupal_yext()->fieldmap()->raw(), $new_raw);
    }
    else {
      $entity->set($this->yext()->uniqueYextIdFieldName(), $new_value);
    }

    if (is_a($entity, Node::class)) {
      $entity->setPublished(FALSE);
    }

    $entity->save();
  }

  /**
   * Get the number of nodes to process on each cron run.
   *
   * @return int
   *   The number of nodes to process on each cron run.
   */
  public function numItemsPerCronRun() : int {
    return $this->stateGet('drupal_yext_sync_num_items_per_run', 1);
  }

  /**
   * Set the next node to process during a cron run.
   *
   * @param int $first
   *   The next node id to process.
   */
  public function setFirst(int $first) {
    return $this->stateSet('drupal_yext_sync_deleted_first', $first);
  }

  /**
   * Given all node ids of the Yext type, return those to process in cron.
   *
   * @param array $nids
   *   All nids.
   *
   * @return array
   *   Only the nids to process.
   */
  public function someNids(array $nids) : array {
    if (!count($nids)) {
      return [];
    }

    $unique = $sorted = array_unique($nids);
    sort($sorted);

    $first = $this->getFirst();
    $num = min(count($sorted), $this->numItemsPerCronRun());

    $pos = array_search($first, $sorted);
    $pos = $pos === FALSE ? 0 : $pos;

    $before = array_slice($sorted, 0, $pos);
    $after = array_slice($sorted, $pos);

    $merged = array_values(array_merge($after, $before));

    $next_first = isset($merged[$num]) ? $merged[$num] : $merged[0];

    $this->setFirst($next_first);

    return array_slice($merged, 0, $num);
  }

  /**
   * Synchronize a given node if it's been deleted in Yext.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity to sync.
   */
  public function syncDeleted(EntityInterface $entity) {
    $field_value = $entity->get($this->yext()->uniqueYextIdFieldName())->getValue();
    if (isset($field_value[0]['value'])) {
      if (!$this->yextEntityExists($field_value[0]['value'])) {
        $this->markAsDeleted($entity, $field_value[0]['value']);
      }
    }
  }

  /**
   * Return TRUE if a Yext entity exists.
   *
   * @param string $yext_entity_id
   *   A Yext entity ID.
   */
  public function yextEntityExists(string $yext_entity_id) : bool {
    try {
      $this->yext()->getRecordByUniqueId($yext_entity_id);
      return TRUE;
    }
    catch (\Exception $e) {
      print_r(get_class($e));
      return FALSE;
    }
  }

}
