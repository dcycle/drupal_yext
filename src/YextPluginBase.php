<?php

namespace Drupal\drupal_yext;

// @codingStandardsIgnoreStart
use Drupal\Component\Plugin\PluginBase;
// @codingStandardsIgnoreEnd

/**
 * A base class to help developers implement YextPlugin objects.
 *
 * @see \Drupal\expose_status\Annotation\ExposeStatusPluginAnnotation
 * @see \Drupal\expose_status\ExposeStatusPluginInterface
 */
abstract class YextPluginBase extends PluginBase implements YextPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function alterNodeFromSourceRecord(array &$result, YextSourceRecord $record) {
    // Do nothing by default; subclasses must override.
  }

}
