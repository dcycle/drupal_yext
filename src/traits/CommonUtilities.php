<?php

namespace Drupal\drupal_yext\traits;

use Drupal\drupal_yext\Yext\Yext;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * General utilities trait.
 *
 * If your class needs to use any of these, add "use CommonUtilities" your class
 * and these methods will be available and mockable in tests.
 */
trait CommonUtilities {

  /**
   * Checks to make sure a server is available.
   *
   * @param string $server
   *   A server such as http://drupal.
   *
   * @return bool
   *   Whether the server is accessible.
   */
  public function checkServer(string $server) : bool {
    try {
      $this->httpGet($server);
      return TRUE;
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
      return FALSE;
    }
  }

  /**
   * Get a link with text and a path.
   *
   * This is relatively equivalent to the l() function in Drupal 7.
   *
   * @return Link
   *   A displayable link.
   *
   * @throws Exception
   */
  public function link(string $text, string $path) : Link {
    return Link::fromTextAndUrl($text, Url::fromUri($path));
  }

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
