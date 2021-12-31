<?php

namespace Drupal\drupal_yext;

use Drupal\drupal_yext\traits\Singleton;
use Drupal\drupal_yext\YextContent\YextSourceRecord;

/**
 * Abstraction around a collection of plugins.
 */
class YextPluginCollection implements YextPluginInterface {

  use Singleton;

  /**
   * {@inheritdoc}
   */
  public function parseSourceElem(YextSourceRecord $source_record, string $field_id, array &$data) {
    foreach ($this->plugins() as $plugin) {
      $plugin->parseSourceElem($source_record, $field_id, $data);
    }
  }

  /**
   * Mockable wrapper around \Drupal::service('plugin.manager.drupal_yext').
   *
   * @return mixed
   *   The YextPluginManager service. We are not specifying its type
   *   here because during testing we want to mock pluginManager() without
   *   extending YextPluginManager; when we do, it works fine in
   *   PHPUnit directly. However when attempting to run within Drupal we
   *   get an unhelpful message as described in
   *   https://drupal.stackexchange.com/questions/252930. Therefore we simply
   *   use an anonymous class.
   */
  public function pluginManager() {
    // PHPStan complains that dependency injection is better here than using
    // the \Drupal class, however dependency injection on custom classes is
    // rather complex, as described in
    // https://drupal.stackexchange.com/questions/195165/dependency-injection-in-a-custom-class,
    // and is of little value to us because or manner of mocking this in
    // tests is to mock the entire ::pluginManager() method, so our code
    // ends up testable even if we don't have dependency injection.
    // @phpstan-ignore-next-line
    return \Drupal::service('plugin.manager.drupal_yext');
  }

  /**
   * Get plugin objects.
   *
   * @param bool $reset
   *   Whether to re-fetch plugins; otherwise we use the static variable.
   *   This can be useful during testing.
   *
   * @return array
   *   Array of plugin objects.
   */
  public function plugins(bool $reset = FALSE) : array {
    static $return = NULL;

    if ($return === NULL || $reset) {
      $return = [];
      foreach (array_keys($this->pluginDefinitions()) as $plugin_id) {
        $return[$plugin_id] = $this->pluginManager()->createInstance($plugin_id, ['of' => 'configuration values']);
      }
    }

    return $return;
  }

  /**
   * Get plugin definitions based on their annotations.
   *
   * @return array
   *   Array of plugin definitions.
   */
  public function pluginDefinitions() : array {
    $return = $this->pluginManager()->getDefinitions();

    uasort($return, function (array $a, array $b) : int {
      if ($a['weight'] == $b['weight']) {
        return 0;
      }
      return ($a['weight'] < $b['weight']) ? -1 : 1;
    });

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function alterNodeFromSourceRecord(array &$result, YextSourceRecord $record) {
    foreach ($this->plugins() as $plugin) {
      $plugin->alterNodeFromSourceRecord($result, $record);
    }
  }

}
