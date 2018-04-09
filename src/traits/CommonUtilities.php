<?php

namespace Drupal\drupal_yext\traits;

use Drupal\drupal_yext\Yext\Yext;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Utility\Error;

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
   * Mockable wrapper around \Drupal::config()->get().
   */
  public function configGet($variable, $default = NULL) {
    $return = \Drupal::config('drupal_yext.general.settings')->get($variable, $default);
    return ($return === NULL) ? $default : $return;
  }

  /**
   * Mockable wrapper around setting a config item.
   */
  public function configSet($variable, $value) {
    $config = \Drupal::service('config.factory')->getEditable('drupal_yext.general.settings');
    $config->set($variable, $value)->save();
  }

  /**
   * Mockable wrapper around drupal_set_message().
   */
  protected function drupalSetMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    return drupal_set_message($message, $type, $repeat);
  }

  /**
   * Mockable wrapper around \Drupal::httpClient()->get().
   */
  public function httpGet($uri, $options = []) {
    $this->watchdog('Making request to ' . $uri . ' with the following options:');
    $this->watchdog(serialize($options));
    return \Drupal::httpClient()->get($uri, $options);
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
   * Log a string to the watchdog.
   *
   * @param string $string
   *   String to be logged.
   *
   * @throws Exception
   */
  public function watchdog(string $string) {
    \Drupal::logger('steward_common')->notice($string);
  }

  /**
   * Log an error to the watchdog.
   *
   * @param string $string
   *   String to be logged.
   *
   * @throws Exception
   */
  public function watchdogError(string $string) {
    \Drupal::logger('steward_common')->error($string);
  }

  /**
   * Log a \Throwable to the watchdog.
   *
   * @param \Throwable $t
   *   A \throwable.
   */
  public function watchdogThrowable(\Throwable $t, $message = NULL, $variables = array(), $severity = RfcLogLevel::ERROR, $link = NULL) {

    // Use a default value if $message is not set.
    if (empty($message)) {
      $message = '%type: @message in %function (line %line of %file).';
    }

    if ($link) {
      $variables['link'] = $link;
    }

    $variables += Error::decodeException($t);

    \Drupal::logger('steward_common')->log($severity, $message, $variables);
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
