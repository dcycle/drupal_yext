<?php

namespace Drupal\drupal_yext\Plugin\YextPlugin;

use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\YextContent\YextSourceRecord;
use Drupal\drupal_yext\YextPluginBase;

/**
 * Parse a non-numeric field from Yext.
 *
 * @YextPluginAnnotation(
 *   id = "drupal_yext_non_numeric_field_parser",
 *   description = @Translation("Parse a non-numeric field from Yext."),
 *   weight = 2,
 * )
 */
class YextNonNumericFieldParser extends YextPluginBase {

  use CommonUtilities;

  /**
   * {@inheritdoc}
   */
  public function canParseSourceRecord(YextSourceRecord $source_record, string $field_id) : bool {
    return !is_numeric($field_id);
  }

  /**
   * {@inheritdoc}
   */
  public function doParseSourceRecord(YextSourceRecord $source_record, string $field_id, array &$data) {
    $candidate = $source_record->parseElem(['array', 'string'], explode('][', $field_id), '', FALSE, '');

    $data = is_array($candidate) ? $candidate : [$candidate];
  }

}
