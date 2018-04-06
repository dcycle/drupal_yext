<?php

namespace Drupal\drupal_yext\Yext;

use Drupal\drupal_yext\traits\Singleton;
use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\YextContent\NodeMigrator;
use Drupal\drupal_yext\YextContent\YextSourceRecord;
use Drupal\drupal_yext\YextContent\YextEntityFactory;

/**
 * Represents the Yext API.
 */
class Yext {

  use Singleton;
  use CommonUtilities;

  /**
   * Given a URL, adds filters.
   *
   * @param string $url
   *   The URL without the filters.
   * @param array $filters
   *   Filters to add.
   *
   * @return string
   *   The URL with the filters.
   */
  public function addFilters(string $url, array $filters = []) : string {
    $url2 = $url;
    if (!empty($filters)) {
      $url2 .= '&filters=' . urlencode(json_encode($filters));
    }
    return $url2;
  }

  /**
   * Yext API key getter/setter.
   *
   * @param string $api
   *   A hard-to-guess secret.
   */
  public function apiKey(string $api = '') : string {
    if (!empty($api)) {
      $this->stateSet('drupal_yext_api', $api);
    }
    return $this->stateGet('drupal_yext_api', '');
  }

  /**
   * The Yext API version to use.
   *
   * @return string
   *   The API version.
   */
  public function apiVersion() : string {
    return $this->stateGet('drupal_yext_api_version', '20180205');
  }

  /**
   * Getter/setter for the Yext base URL.
   *
   * @param string $base
   *   If set, changes the base URL.
   *
   * @return string
   *   The base URL.
   */
  public function base(string $base = '') : string {
    if (!empty($base)) {
      $this->stateSet('drupal_yext_base', $base);
    }
    return $this->stateGet('drupal_yext_base', $this->defaultBase());
  }

  /**
   * Build a URL for a Yext GET request.
   *
   * @param string $path
   *   For example /v2/api/...
   * @param string $key
   *   A key to use, defaults to the saved API key.
   * @param array $filters
   *   Filters as per the API documentation.
   * @param int $offset
   *   The offset.
   *
   * @return string
   *   A URL.
   *
   * @throws Exception
   */
  public function buildUrl(string $path, string $key = '', array $filters = [], int $offset = 0) : string {
    $key2 = $key ?: $this->apiKey();

    if (!$key2) {
      throw new \Exception('We are attempting to build a URL for Yext with an empty key; this will always fail.');
    }

    $return = $this->base() . $path . '?limit=50&offset=' . $offset . '&api_key=' . $key2 . '&v=' . $this->apiVersion();
    $return2 = $this->addFilters($return, $filters);
    $for_the_log = str_replace($key2, 'YOUR-API-KEY', $return2);
    $this->watchdog('Yext: built url ' . $for_the_log);
    return $return2;
  }

  /**
   * Get the default Yext base URL.
   *
   * @return string
   *   The default Yext base URL.
   */
  public function defaultBase() : string {
    return 'https://api.yext.com';
  }

  /**
   * Get total number of nodes having failed to import.
   *
   * @return int
   *   nodes having failed to import.
   */
  public function failed() {
    return count($this->stateGet('drupal_yext_failed', []));
  }

  /**
   * {@inheritdoc}
   */
  public function hookRequirements($phase) : array {
    $requirements = [];
    if ($phase == 'runtime') {
      $test = $this->test();
      $requirements['DrupalYext.yext.test'] = array(
        'title' => t('Yext API key'),
        'description' => t('The API key is set at /admin/config/yext, and is working.'),
        'value' => $test['message'],
        'severity' => $test['success'] ? REQUIREMENT_INFO : REQUIREMENT_ERROR,
      );
    }
    return $requirements;
  }

  /**
   * Get total number of imported nodes.
   *
   * @return int
   *   Imported nodes.
   */
  public function imported() : int {
    return $this->stateGet('drupal_yext_imported', 0);
  }

  /**
   * Import nodes from Yext until two days from now.
   */
  public function importNodesToNextDatePlusTwoDays() {
    $start = $this->nextDateToImport('Y-m-d');
    $end = $this->nextDateToImport('Y-m-d', 2 * 24 * 60 * 60);
    $this->watchdog('Yext: query between ' . $start . ' and ' . $end);
    $this->importYextAll($start, $end);
  }

  /**
   * Import nodes from an array of nodes.
   *
   * See also "Avoiding node collisions during gradual launch" in ./README.md.
   *
   * @param array $array
   *   An array of Nodes from Yext.
   *
   * @throws \Exception
   */
  public function importFromArray(array $array) {
    $all_ids = [];
    array_walk($array, function ($item, $key) use (&$all_ids) {
      if (isset($item['id'])) {
        $all_ids[$item['id']] = $all_ids[$item['id']];
      }
    });

    // Preload all nodes which have the Yext IDs.
    $nodes = YextEntityFactory::instance()->preloadUniqueNodes($this->yextNodeType(), $this->uniqueYextIdFieldName(), $all_ids);

    // Walk through all items from yext.
    foreach ($array as $item) {

      // Wrap the item in a YextSourceRecord object for manipulation.
      $source = new YextSourceRecord($item);

      // If a node already exists, use that one; otherwise create a new one.
      // This ensures that we should never have two nodes with the same
      // Yext ID.
      $destination = empty($nodes[$source->getYextId()]) ? YextEntityFactory::instance()->getOrCreateUniqueNode($this->yextNodeType(), $this->uniqueYextIdFieldName(), $source->getYextId()) : $nodes[$source->getYextId()];

      $migrator = new NodeMigrator($source, $destination);
      try {
        $migrator->migrate();
        $destination->save();
        $this->watchdog('Yext: migrated ' . $source->getYextId() . ' to ' . $destination->id());
        $this->incrementSuccess();
      }
      catch (\Throwable $t) {
        $this->watchdogThrowable($t);
        $this->incrementFailed($item);
      }
    }
  }

  /**
   * Import some nodes.
   *
   * @throws Exception
   */
  public function importSome() {
    try {
      if (!$this->apiKey()) {
        $this->watchdog('Yext: no API key has been set; skipping import of Yext items.');
        return;
      }
      $this->watchdog('Yext: starting to import some nodes.');
      $this->watchdog('Yext: try to import all nodes before our cutoff date plus two days.');
      // That way we can include all the latest nodes even if our cutoff
      // date was yesterday.
      $this->importNodesToNextDatePlusTwoDays();
      $this->watchdog('Yext: increment our cutoff date but not too much.');
      $this->updateRemaining();
      $this->importIncrementCutoffDateButNotTooMuch();
      $this->stateSet('drupal_yext_last_check', $this->date('U'));
      $this->watchdog('Yext: --- finished import session: success ---');
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
      $this->watchdog('Yext: --- finished import session: error ---');
    }
  }

  /**
   * Increment the cutoff date, but do not go past today's date.
   */
  public function importIncrementCutoffDateButNotTooMuch() {
    $this->watchdog('Yext: incrementing cutoff date');
    $previous = $this->nextDateToImport('U');
    $candidate = $previous + 24 * 60 * 60;
    $date = min($this->date('U'), $candidate);
    $this->watchdog('Yext: cutoff date incremented to ' . $this->date('Y-m-d H:i:s', $date));
    $this->stateSet('drupal_yext_next_import', $date);
  }

  /**
   * Import all Yext nodes from a start to an end date.
   *
   * This will import all nodes, even those which are not on the first
   * page of the Yext report.
   *
   * @param string $start
   *   YYYY-MM-DD.
   * @param string $end
   *   YYYY-MM-DD.
   * @param int $offset
   *   An offset. Using during recursion.
   *
   * @throws Exception
   */
  public function importYextAll(string $start, string $end, int $offset = 0) {
    $this->watchdog('Yext: importing with offset ' . $offset);
    $api_result = $this->queryYext($start, $end, $offset);
    $response_count = $api_result['response']['count'];
    $response_count_less_offset = $response_count - $offset;
    $response_locations = $api_result['response']['locations'];
    $response_locations_count = count($response_locations);
    $this->watchdog('Yext: Offset is ' . $offset);
    $this->watchdog('Yext: Response count is ' . $response_count);
    $this->watchdog('Yext: Response count less offset is ' . $response_count_less_offset);
    $this->watchdog('Yext: Location count on this page is ' . $response_locations_count);
    $this->importFromArray($response_locations);
    if ($response_count_less_offset > $response_locations_count) {
      $new_offset = $offset + $response_locations_count;
      $this->watchdog('Yext: incrementing offset to ' . $new_offset . ' because response count less offset > response location count');
      if ($new_offset > $offset) {
        $this->importYextAll($start, $end, $new_offset);
      }
    }
  }

  /**
   * Increment the number of nodes having failed to import.
   *
   * @param array $structure
   *   A node structure from Yext.
   */
  public function incrementFailed(array $structure) {
    $failed = $this->stateGet('drupal_yext_failed', []);
    $failed[$structure['id']] = $structure;
    $this->stateSet('drupal_yext_failed', $failed);
  }

  /**
   * Increment the number of nodes imported successfully.
   */
  public function incrementSuccess() {
    $imported = $this->imported();
    $this->stateSet('drupal_yext_imported', ++$imported);
  }

  /**
   * Get the last checked data.
   *
   * @param string $format
   *   For example Y-m-d.
   *
   * @return string
   *   The formatted last date checked.
   *
   * @throws Exception
   */
  public function lastCheck($format) {
    return date($format, $this->stateGet('drupal_yext_last_check', 0));
  }

  /**
   * Get the next date to import.
   *
   * @param string $format
   *   For example Y-m-d.
   * @param int $add
   *   How many seconds to addd.
   *
   * @return string
   *   The formatted next date to import.
   *
   * @throws Exception
   */
  public function nextDateToImport($format, int $add = 0) {
    return date($format, $this->stateGet('drupal_yext_next_import', strtotime('2017-12-10')) + $add);
  }

  /**
   * Query Yext for a given date.
   *
   * @param string $date
   *   From date: YYYY-MM-DD.
   * @param string $date2
   *   To date: YYYY-MM-DD.
   * @param int $offset
   *   The offset if there is one.
   *
   * @return array
   *   A response from the Yext API.
   *
   * @throws Exception
   */
  public function queryYext($date, $date2, $offset = 0) : array {
    $url = $this->buildUrl('/v2/accounts/me/locationsearch', '', [
      [
        'lastUpdated' => [
          'between' => [
            $date,
            $date2,
          ],
        ],
      ],
    ], $offset);
    $body = (string) $this->httpGet($url)->getBody();
    return json_decode($body, TRUE);
  }

  /**
   * Get the remaining nodes to fetch.
   *
   * @return int
   *   The number of known remaining nodes.
   */
  public function remaining() {
    return $this->stateGet('drupal_yext_remaining', 999999);
  }

  /**
   * Reset everything to factory defaults.
   */
  public function resetAll() {
    $this->stateSet('drupal_yext_remaining', 999999);
    $this->stateSet('drupal_yext_imported', 0);
    $this->stateSet('drupal_yext_next_import', strtotime('2017-12-10'));
    $this->stateSet('drupal_yext_failed', []);
    $this->stateSet('drupal_yext_last_check', 0);
  }

  /**
   * Set the next date to check.
   *
   * @param string $date
   *   A date in the format YYYY-MM-DD.
   */
  public function setNextDate(string $date) {
    $this->stateSet('drupal_yext_next_import', strtotime($date));
  }

  /**
   * Set the target node type for Yext data.
   *
   * @param string $type
   *   The node type such as 'article'.
   */
  public function setNodeType(string $type) {
    $this->stateSet('drupal_yext_node_type', $type);
  }

  /**
   * Set the field name which contains the Yext unique ID.
   *
   * @param string $field
   *   The field such as 'field_something'.
   */
  public function setUniqueYextIdFieldName(string $field) {
    $this->stateSet('drupal_yext_unique_id_field', $field);
  }

  /**
   * Test the connection to Yext.
   *
   * @param string $key
   *   The API key to use; if empty use the api key in memory.
   *
   * @return array
   *   An array with two keys, success and message.
   */
  public function test(string $key = '') : array {
    $key2 = $key ?: 'default';
    static $return;
    if (!empty(($return[$key2]))) {
      return $return[$key2];
    }
    try {
      $return[$key2]['success'] = $this->checkServer($this->buildUrl('/v2/accounts/me/locations', $key));
    }
    catch (\Exception $e) {
      $return[$key2] = [
        'success' => FALSE,
        'message' => $e->getMessage(),
      ];
    }
    $return[$key2]['message'] = $return[$key2]['success'] ? 'Connection successful' : 'Connection failed, check API key.';
    return $return[$key2];
  }

  /**
   * The Drupal field name which contains the Yext unique id.
   *
   * @return string
   *   A field name such as 'field_yext_unique_id.
   *
   * @throws \Throwable
   */
  public function uniqueYextIdFieldName() : string {
    return $this->stateGet('drupal_yext_unique_id_field', 'field_yext_unique_id');
  }

  /**
   * Update the number representing the nodes remaining to import.
   */
  public function updateRemaining() {
    $start = $this->nextDateToImport('Y-m-d', 3 * 24 * 60 * 60);
    $end = $this->date('Y-m-d', $this->date('U') + 24 * 60 * 60);
    $this->watchdog('Yext: query between ' . $start . ' and ' . $end);
    $result = $this->queryYext($start, $end);
    if (!empty($result['response']['count'])) {
      $count = $result['response']['count'];
      $this->watchdog('Yext: updating remaining to ' . $count . '.');
      $this->stateSet('drupal_yext_remaining', $count);
    }
    else {
      $this->watchdog('Yext: could not figure out the remaining nodes.');
    }
  }

  /**
   * The Drupal node type which will be populated by Yext data.
   *
   * @return string
   *   A node type such as 'article'.
   *
   * @throws \Throwable
   */
  public function yextNodeType() : string {
    return $this->stateGet('drupal_yext_node_type', 'YourNodeType');
  }

}
