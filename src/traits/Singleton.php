<?php

namespace Drupal\drupal_yext\traits;

/**
 * Implements the Singleton design pattern.
 */
trait Singleton {

  /**
   * Interal instance variable used with the instance() method.
   *
   * @var object
   */
  static private $instance;

  /**
   * Implements the Singleton design pattern.
   *
   * Only one instance of the class should exist per execution.
   *
   * @return mixed
   *   The single instance of the class.
   */
  public static function instance() {
    // See http://stackoverflow.com/questions/15443458
    $class = get_called_class();

    // Not sure why the linter tells me $instance is not used.
    if (!$class::$instance) {
      $class::$instance = new $class();
    }
    return $class::$instance;
  }

}
