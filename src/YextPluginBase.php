<?php

namespace Drupal\drupal_yext;

// @codingStandardsIgnoreStart
use Drupal\Component\Plugin\PluginBase;
// @codingStandardsIgnoreEnd
use Drupal\drupal_yext\YextContent\YextSourceRecord;

/**
 * A base class to help developers implement YextPlugin objects.
 *
 * @see \Drupal\drupal_yext\Annotation\YextPluginAnnotation
 * @see \Drupal\drupal_yext\YextPluginInterface
 */
abstract class YextPluginBase extends PluginBase implements YextPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function alterNodeFromSourceRecord(array &$result, YextSourceRecord $record) {
    // Do nothing by default; subclasses must override.
  }

}
