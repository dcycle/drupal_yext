<?php

namespace Drupal\drupal_yext\YextContent;

/**
 * Defines an interface for a Yext record.
 */
interface NodeMigrateSourceInterface {

  /**
   * Get a bio.
   *
   * @return string
   *   A plain text string representing a bio, or empty string.
   */
  public function getBio() : string;

  /**
   * Get geo coordinates.
   *
   * @return array
   *   An array with, if possible, lat and lon keys.
   */
  public function getGeo() : array;

  /**
   * Get a headshot.
   *
   * @return string
   *   A URL or empty string.
   */
  public function getHeadshot() : string;

  /**
   * Get a full name as a string.
   *
   * @return string
   *   A full name, or empty string.
   */
  public function getName() : string;

  /**
   * Get a custom field.
   *
   * @param string $id
   *   A field ID.
   *
   * @return array
   *   An array of values.
   */
  public function getCustom(string $id) : array;

  /**
   * Get a unique Yext ID.
   *
   * @return string
   *   A unique Yext ID, or empty string.
   */
  public function getYextId() : string;

  /**
   * Get the last time Yext was updated or synchronized.
   *
   * @return int
   *   The last time Yext was updated or synchronized as microtime.
   */
  public function getYextLastUpdate() : int;

  /**
   * Get the raw Yext data.
   *
   * @return string
   *   Json string.
   */
  public function getYextRawData() : string;

}
