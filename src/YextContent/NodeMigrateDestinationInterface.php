<?php

namespace Drupal\drupal_yext\YextContent;

/**
 * Defines an interface for a target node.
 */
interface NodeMigrateDestinationInterface {

  /**
   * Get the last time Yext was updated or synchronized.
   *
   * @return int
   *   The last time Yext was updated or synchronized as microtime.
   */
  public function getYextLastUpdate() : int;

  /**
   * Get the Yext raw data as a jsonized-string.
   *
   * @return string
   *   Json string representation of an array.
   */
  public function getYextRawDataString() : string;

  /**
   * Get the Yext raw data as an array.
   *
   * @return array
   *   Yext structure array.
   */
  public function getYextRawDataArray() : array;

  /**
   * Save this item.
   */
  public function save();

  /**
   * Set a bio.
   *
   * @param string $bio
   *   A plain text string representing a bio, or empty string.
   */
  public function setBio(string $bio);

  /**
   * Set a geofield.
   *
   * @param array $geo
   *   An array with, if possible, lat and lon keys.
   */
  public function setGeo(array $geo);

  /**
   * Set a headshot.
   *
   * @param string $url
   *   A URL or empty string.
   */
  public function setHeadshot(string $url);

  /**
   * Set a full name as a string.
   *
   * @param string $name
   *   A full name, or empty string.
   */
  public function setName(string $name);

  /**
   * Set a custom field.
   *
   * @param string $id
   *   A field name.
   * @param array $values
   *   Array of values.
   */
  public function setCustom(string $id, array $values);

  /**
   * Set the Yext ID.
   *
   * @param string $id
   *   The Yext ID.
   */
  public function setYextId(string $id);

  /**
   * Set the last time Yext was updated or synchronized.
   *
   * @param int $timestamp
   *   The last time Yext was updated or synchronized as a unix timestamp.
   */
  public function setYextLastUpdate(int $timestamp);

  /**
   * Set the raw Yext data.
   *
   * @param string $data
   *   Json string.
   */
  public function setYextRawData(string $data);

}
