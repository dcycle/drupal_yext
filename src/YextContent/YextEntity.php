<?php

namespace Drupal\drupal_yext\YextContent;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\drupal_yext\traits\CommonUtilities;

/**
 * Represents a Yext-specific entity, wrapper around Drupal entities.
 *
 * Any Drupal node which has a corresponding Yext entity can be
 * represented as a subclass of this, for example, see YextTargetNode.
 */
class YextEntity {

  use CommonUtilities;
  use StringTranslationTrait;

  /**
   * The Drupal entity.
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface|null
   */
  protected $drupalEntity;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->drupalEntity = NULL;
  }

  /**
   * The domain-specific alias, for example /about-us, or an empty string.
   *
   * @return string
   *   If empty, then no alias could be found, otherwise an alias string
   *   like /about-us. This does not necessary correspond to the internal
   *   alias for an entity, as many hospitals can use, for example,
   *   /about-us, which is then stored internally as
   *   /domain-specific/<hospital-nid>/about-us. See ./README.md for details.
   */
  public function domainAlias() : string {
    return '';
  }

  /**
   * Getter for $this->drupalEntity.
   */
  public function drupalEntity() {
    if ($this->drupalEntity === NULL) {
      throw new \Exception('Please generate or set an entity using ::setEntity() before calling drupalEntity().');
    }
    return $this->drupalEntity;
  }

  /**
   * Given a field name, return from the value all mails separated by commas.
   *
   * @param string $field
   *   A field name which can exist in this entity.
   *
   * @return string
   *   A list of mails such as '', 'one@example.com' or
   *   'one@example.com,two@example.com'.
   */
  public function fieldToCommaSeparatedMailAddresses(string $field) : string {
    $mails = [];
    $entity = $this->drupalEntity();

    $field = $entity->get($field);
    if (!$field) {
      return '';
    }
    $value = $field->getValue();
    foreach ($value as $row) {
      if (!empty($row['value'])) {
        $comma_separated = $row['value'];
        $candidates = explode(',', $comma_separated);
        foreach ($candidates as $candidate) {
          $trimmed_candidate = trim($candidate);
          if ($this->drupalService('email.validator')->isValid($trimmed_candidate)) {
            $mails[] = $trimmed_candidate;
          }
        }
      }
    }

    return implode(',', $mails);
  }

  /**
   * Get a field value.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return mixed
   *   Value of the field or an empty string.
   */
  public function fieldValue(string $field_name) {
    $field = $this->drupalEntity()->get($field_name);
    if (!$field) {
      return '';
    }
    $field = $field->getValue();
    return $field[0]['value'] ?? '';
  }

  /**
   * Generate a new entity, and save it.
   */
  public function generate() {
    throw new \Exception('I do not know how to generate an entity of this type.');
  }

  /**
   * Set the entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $drupal_entity
   *   A Drupal entity.
   */
  public function setEntity(FieldableEntityInterface $drupal_entity) {
    $this->drupalEntity = $drupal_entity;
  }

  /**
   * Set a simple text field value.
   *
   * @param string $field_name
   *   The field name.
   * @param string $field_value
   *   The string value to set.
   */
  public function setFieldValue(string $field_name, string $field_value) {
    $this->drupalEntity->set($field_name, $field_value);
  }

  /**
   * The entity id.
   *
   * @return int
   *   The entity id.
   */
  public function id() : int {
    return $this->drupalEntity->id();
  }

  /**
   * Saves this entity.
   */
  public function save() {
    return $this->drupalEntity->save();
  }

  /**
   * A single boolean value.
   *
   * @param string $field
   *   A field name.
   *
   * @return bool
   *   A value.
   */
  public function singleBoolValue(string $field) : bool {
    $value = $this->drupalEntity()->get($field)->getValue();
    return (!empty($value[0]['value']));
  }

  /**
   * Get a single string value from a field of this entity.
   *
   * @param string $field
   *   A field name.
   *
   * @return string
   *   A field value, or ''.
   */
  public function singleStringValue(string $field) : string {
    try {
      $value = $this->drupalEntity()->get($field)->getValue();
      return !empty($value[0]['value']) ? $value[0]['value'] : '';
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
      return 't';
    }
  }

  /**
   * Get an array of spoke sites where to view this entity if possible.
   *
   * @return array
   *   Associative array with "spokes", itself an array, and "error", a string.
   */
  public function spoke() : array {
    return [
      'error' => 'You can only view certain nodes on the spoke site, not this one.',
    ];
  }

  /**
   * The system alias, for example /about-us, or an empty string.
   *
   * @return string
   *   If empty, then no alias could be found, otherwise an system alias string
   *   like /domain-specific/<hospital-nid>/about-us. This does not necessary
   *   correspond to the string actually used to access content, as many
   *   hospitals can use, for example, /about-us, which is then stored
   *   internally as /domain-specific/<hospital-nid>/about-us. See ./README.md
   *   for details.
   */
  public function systemAlias() {
    return '';
  }

  /**
   * Unpublish this entity if possible.
   */
  public function unpublish() {
    // Entities cannot be unpublished. The subclass YextTargetNode overrides
    // this.
  }

}
