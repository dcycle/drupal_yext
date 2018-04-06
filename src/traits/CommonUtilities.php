<?php

namespace Drupal\drupal_yext\traits;

use Drupal\drupal_yext\Yext\Yext;

/**
 * General utilities trait.
 *
 * If your class needs to use any of these, add "use CommonUtilities" your class
 * and these methods will be available and mockable in tests.
 */
trait CommonUtilities {

  /**
   * Mockable wrapper around \Drupal::state()->get().
   */
  public function stateGet($variable, $default = NULL) {
    return \Drupal::state()->get($variable, $default);
  }

  /**
   * Mockable wrapper around \Drupal::state()->set().
   */
  public function stateSet($variable, $value) {
    \Drupal::state()->set($variable, $value);
  }

  /**
   * Get the Yext app singleton.
   *
   * @return Yext
   *   The Yext singleton.
   */
  public function yext() {
    return Yext::instance();
  }

}
