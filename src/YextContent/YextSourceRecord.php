<?php

namespace Drupal\drupal_yext\Yext;

use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\YextContent\NodeMigrateSourceInterface;

/**
 * Represents a Node on the Yext API.
 */
class YextSourceRecord implements NodeMigrateSourceInterface {

  const PROFILE_LINK_CUSTOM_FIELD = 12819;

  use CommonUtilities;

  /**
   * Constructor.
   *
   * @param array $structure
   *   A associative array structure from Yext.
   */
  public function __construct(array $structure) {
    $this->structure = $structure;
  }

  /**
   * {@inheritdoc}
   */
  public function getBio() : string {
    return $this->parseElem('string', ['description'], '');
  }

  /**
   * {@inheritdoc}
   */
  public function getHeadshot() : string {
    return $this->parseElem('string', ['headshot', 'url'], '');
  }

  /**
   * {@inheritdoc}
   */
  public function getName() : string {
    return $this->parseElem('string', ['locationName'], '');
  }

  /**
   * {@inheritdoc}
   */
  public function getProfileLink() : string {
    return $this->parseElem('string', ['customFields', self::PROFILE_LINK_CUSTOM_FIELD], '');
  }

  /**
   * {@inheritdoc}
   */
  public function getYextId() : string {
    return $this->parseElem('string', ['id'], '', TRUE, 'The Node ID on Yext is required because that is how we track which Drupal nodes are linked to which nodes');
  }

  /**
   * {@inheritdoc}
   */
  public function getYextLastUpdate() : int {
    return $this->parseElem('integer', ['timestamp'], 0, TRUE, 'The last update (timestamp) field on Yext is required because that is how we track whether nodes are out of date on Drupal.');
  }

  /**
   * {@inheritdoc}
   */
  public function getYextRawData() : string {
    return json_encode($this->structure, TRUE);
  }

  /**
   * Wrapper around CommonUtilities::assocparseElem() using our structure.
   */
  public function parseElem(string $type, array $keys, $default, bool $required = FALSE, $required_message = '') : string {
    return $this->assocArrayElem($this->structure, $type, $keys, $default, $required, $required_message);
  }

}
