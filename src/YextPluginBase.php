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

  /**
   * Check whether this plugin can parse a source record from its ID.
   *
   * @param \Drupal\drupal_yext\YextContent\YextSourceRecord $source_record
   *   A Yext source record.
   * @param string $field_id
   *   A field id.
   *
   * @return bool
   *   TRUE if we should proceed using this plugin to parse a source record id.
   */
  public function canParseSourceRecord(YextSourceRecord $source_record, string $field_id) : bool {
    return FALSE;
  }

  /**
   * Whether to parse a source record from its ID if previous plugins got data.
   *
   * By default plugins with lower weights (in their annotation) will have
   * first dibs at parsing data, and other plugins will not have a chance to
   * parse the data. If your plugin would like to modify data previously parsed
   * by other plugins, you can override this method.
   *
   * @param array $data
   *   Existing data.
   *
   * @return bool
   *   TRUE if we should proceed using this plugin to parse a source record id.
   */
  public function canOverwriteData(array $data) : bool {
    return empty($data);
  }

  /**
   * Assume we can and should parse a source record field, and parse it.
   *
   * @param \Drupal\drupal_yext\YextContent\YextSourceRecord $source_record
   *   A Yext source record.
   * @param string $field_id
   *   A field id.
   * @param array $data
   *   Resulting data, always in the form of an array of strings.
   */
  public function doParseSourceRecord(YextSourceRecord $source_record, string $field_id, array &$data) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function parseSourceElem(YextSourceRecord $source_record, string $field_id, array &$data) {
    if ($this->canParseSourceRecord($source_record, $field_id) && $this->canOverwriteData($data)) {
      $this->doParseSourceRecord($source_record, $field_id, $data);
    }
  }

}
