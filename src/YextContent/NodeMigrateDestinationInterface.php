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
   *
   * @throws \Throwable
   */
  public function getYextLastUpdate() : int;

  /**
   * Set a bio.
   *
   * @param string $bio
   *   A plain text string representing a bio, or empty string.
   *
   * @throws \Throwable
   */
  public function setBio(string $bio);

  /**
   * Set a headshot.
   *
   * @param string $url
   *   A URL or empty string.
   *
   * @throws \Throwable
   */
  public function setHeadshot(string $url);

  /**
   * Set a full name as a string.
   *
   * @param string $name
   *   A full name, or empty string.
   *
   * @throws \Throwable
   */
  public function setName(string $name);

  /**
   * Set a custom field.
   *
   * @param string $id
   *   A field name.
   * @param string $value
   *   A value.
   *
   * @throws \Throwable
   */
  public function setCustom(string $id, string $value);

  /**
   * Set the last time Yext was updated or synchronized.
   *
   * @param int $timestamp
   *   The last time Yext was updated or synchronized as a unix timestamp.
   *
   * @throws \Throwable
   */
  public function setYextLastUpdate(int $timestamp);

  /**
   * Set the raw Yext data.
   *
   * @param string $data
   *   Json string.
   *
   * @throws \Throwable
   */
  public function setYextRawData(string $data);

}
