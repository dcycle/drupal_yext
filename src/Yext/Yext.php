<?php

namespace Drupal\drupal_yext\Yext;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\drupal_yext\SelfTest\SelfTest;
use Drupal\drupal_yext\traits\Singleton;
use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\YextContent\NodeMigrationOnSave;
use Drupal\drupal_yext\YextContent\NodeMigrationAtCreation;
use Drupal\drupal_yext\YextContent\YextSourceRecord;
use Drupal\drupal_yext\YextContent\YextEntity;
use Drupal\drupal_yext\YextContent\YextEntityFactory;
use Drupal\drupal_yext\YextContent\YextSourceRecordFactory;
use Drupal\drupal_yext\YextContent\YextTargetNode;
use Drupal\drupal_yext\YextPluginCollection;

/**
 * Represents the Yext API.
 */
class Yext {

  use CommonUtilities;
  use Singleton;
  use StringTranslationTrait;

  /**
   * Calls will break Yext if the offset is greater than this.
   */
  const MAX_OFFSET = 9999;

  /**
   * Yext account number getter/setter.
   *
   * @param string $acct
   *   An account number provided by Yext.
   */
  public function accountNumber(string $acct = '') : string {
    if (!empty($acct)) {
      $this->stateSet('drupal_yext_acct', $acct);
    }
    return $this->stateGet('drupal_yext_acct', 'me');
  }

  /**
   * Perform a function on all active nodes of a target type.
   *
   * Active nodes are nodes that have a non-empty Yext ID.
   *
   * @param string $log_function
   *   A log function such as 'print_r'.
   * @param int $chunk_size
   *   If you have a very large number of nodes, to avoid memory issues, you
   *   might want to have a chunk size of, say, 100.
   * @param string $log_message
   *   A log message.
   * @param string $function
   *   A function such as delete or save.
   * @param bool $increment
   *   Whether or not to increment between chunks. If deleting, it is best to
   *   set to FALSE.
   * @param int $start_at
   *   The first node id to use.
   */
  protected function actionOnAllExisting(string $log_function, int $chunk_size, string $log_message, string $function, bool $increment = TRUE, int $start_at = 0) {
    $start = 0;
    $i = 0;
    while ($nodes = $this->getAllExisting($start, $chunk_size, $start_at)) {
      $log_function('   => Processing chunk ' . $i++ . PHP_EOL);
      foreach ($nodes as $node) {
        $log_function($log_message . ' ' . $node->id() . PHP_EOL);
        $node->$function();
      }
      if ($increment) {
        $start += $chunk_size;
      }
    }
  }

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
   * Merge the user-defined filters with the internal lastUpdated filter.
   *
   * @param string $date
   *   First date in range.
   * @param string $date2
   *   Last date in range.
   *
   * @return array
   *   An array suitable for jsonization, to be passed as a "filter" get
   *   parameter to Yext's API.
   */
  public function allFilters($date, $date2) : array {
    return array_merge([
      [
        'lastUpdated' => [
          'between' => [
            $date,
            $date2,
          ],
        ],
      ],
    ], $this->filtersAsArray());
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
   *   Any instance of /me/ will be replaced with the actual account.
   * @param string $key
   *   A key to use, defaults to the saved API key.
   * @param array $filters
   *   Filters as per the API documentation.
   * @param int $offset
   *   The offset.
   * @param string $base
   *   The base URL to use; if empty use the base URL in memory..
   *
   * @return string
   *   A URL.
   */
  public function buildUrl(string $path, string $key = '', array $filters = [], int $offset = 0, string $base = '') : string {
    if ($offset > self::MAX_OFFSET) {
      throw new \Exception('Due to a limitation with the version of the Yext API we are using, if the offset is above ' . self::MAX_OFFSET . ', it will cause a failure. Please avoid making such calls.');
    }

    $key2 = $key ?: $this->apiKey();
    $base2 = $base ?: $this->base();
    $path2 = str_replace('/me/', '/' . $this->accountNumber() . '/', $path);

    if (!$key2) {
      throw new \Exception('We are attempting to build a URL for Yext with an empty key; this will always fail.');
    }

    $return = $base2 . $path2 . '?limit=50&offset=' . $offset . '&api_key=' . $key2 . '&v=' . $this->apiVersion();
    $return2 = $this->addFilters($return, $filters);
    $for_the_log = str_replace($key2, 'YOUR-API-KEY', $return2);
    $this->watchdog('Yext: built url ' . $for_the_log);
    return $return2;
  }

  /**
   * Return TRUE if the entity is of the correct type.
   *
   * In /admin/config/yext/yext, a specific entity type can be mapped to
   * Yext. Only that type will be accepted.
   *
   * @return bool
   *   TRUE if types match.
   */
  public function checkEntityType(FieldableEntityInterface $entity) : bool {
    if ($entity->getEntityType()->id() != 'node') {
      return FALSE;
    }
    return method_exists($entity, 'getType') && $entity->getType() == $this->yextNodeType();
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
   * See ./README.md for how this works.
   *
   * @param string $log_function
   *   A log function such as 'print_r'.
   * @param int $chunk_size
   *   If you have a very large number of nodes, to avoid memory issues, you
   *   might want to have a chunk size of, say, 100.
   */
  public function deleteAllExisting(string $log_function = 'print_r', int $chunk_size = PHP_INT_MAX) {
    return $this->actionOnAllExisting($log_function, $chunk_size, 'permanently deleting node', 'delete', FALSE);
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
   * Get all existing nodes of the target type if it has a Yext ID.
   *
   * This is meant to load only the nodes which are linked to Yext entities.
   * We will want to ignore nodes which were created manually.
   *
   * @param int $start
   *   The offset, by default 0.
   * @param int $length
   *   The length of the desired array, by default all items. If you get out
   *   of memory errors, you can try something like 50 here. In which case
   *   in the next call you can call this with a start of 50.
   * @param int $start_at
   *   The first node id to use.
   *
   * @return array
   *   Array of Drupal nodes.
   */
  public function getAllExisting(int $start = 0, int $length = PHP_INT_MAX, int $start_at = 0) : array {
    $nids = $this->drupalEntityQuery('node')
      ->condition('nid', $start_at, '>=')
      ->condition('type', $this->yextNodeType())
      ->condition($this->uniqueYextIdFieldName(), NULL, '<>')
      ->range($start, $length)
      ->execute();
    return Node::loadMultiple($nids);
  }

  /**
   * Given a unique ID such as 0013800002eNtybAAC, return its record.
   *
   * An exception is thrown if the record does not exist.
   *
   * @param string $id
   *   A unique Yext ID.
   *
   * @return array
   *   A unique Yext record.
   */
  public function getRecordByUniqueId($id) : array {
    $url = $this->buildUrl('/v2/accounts/me/locations/' . $id);
    $body = (string) $this->httpGet($url)->getBody();
    return json_decode($body, TRUE);
  }

  /**
   * Get/set filters as text, with one per line.
   *
   * @param string $filters
   *   Get filters such as: '[{"locationType":{"is":[2]}}]'. One per
   *   line.
   *
   * @return string
   *   The filters as config text.
   */
  public function filtersAsText(string $filters = '') : string {
    if (!empty($filters)) {
      $this->configSet('drupal_yext_filters', $filters);
    }
    return $this->configGet('drupal_yext_filters', '');
  }

  /**
   * Get one filter as an array.
   *
   * @return array
   *   The filter as an array.
   */
  public function filterAsArray(string $filter) : array {
    $return = @json_decode($filter, TRUE);
    if (!is_array($return)) {
      throw new \Exception('Cannot json decode: ' . $filter);
    }
    return $return;
  }

  /**
   * Get get parameters as array.
   *
   * @return array
   *   The get params as an array.
   */
  public function filtersAsArray() : array {
    $return = [];
    $as_string = $this->filtersAsText();
    $as_array = explode(PHP_EOL, $as_string);
    foreach ($as_array as $line) {
      $line = trim($line);
      if (!$line) {
        continue;
      }
      try {
        $return = array_merge($return, $this->filterAsArray($line));
      }
      catch (\Exception $e) {
        $this->watchdogThrowable($e);
      }
    }
    return $return;
  }

  /**
   * Given a source Yext record, return a new or existing node.
   *
   * @param \Drupal\drupal_yext\YextContent\YextSourceRecord $record
   *   A record from Yext.
   *
   * @return \Drupal\drupal_yext\YextContent\YextTargetNode
   *   A node on Drupal.
   */
  public function getOrCreateUniqueNode(YextSourceRecord $record) : YextTargetNode {
    $result = [];

    $this->plugins()->alterNodeFromSourceRecord($result, $record);

    // As a last resort, create a brand new node.
    if (empty($result['target'])) {
      $result['target'] = YextEntityFactory::instance()->generate('node', $this->yextNodeType());
      $result['target']->setFieldValue($this->uniqueYextIdFieldName(), $record->getYextId());
      $result['target']->save();
    }

    return $result['target'];
  }

  /**
   * Testable implementation of hook_entity_presave().
   */
  public function hookEntityPresave(EntityInterface $entity) {
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }
    try {
      if ($this->checkEntityType($entity)) {
        $this->updateRaw($entity);
        $dest = YextEntityFactory::instance()->destinationIfLinkedToYext($entity);
        $source = YextSourceRecordFactory::instance()->sourceRecord($dest->getYextRawDataArray());
        $migrator = new NodeMigrationOnSave($source, $dest);
        // Migrating will do nothing if the dest and source are set to
        // "ignore"-type classes.
        $migrator->migrate();
      }
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hookRequirements($phase) : array {
    $requirements = [];
    if ($phase == 'runtime') {
      $test = $this->test();
      $requirements['DrupalYext.yext.test'] = [
        'title' => $this->t('Yext API key'),
        'description' => $this->t('The API key is set at /admin/config/yext, and is working.'),
        'value' => $test['message'],
        'severity' => $test['success'] ? REQUIREMENT_INFO : REQUIREMENT_ERROR,
      ];
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
    if ($start_from_failure = $this->stateGet('drupal_yext_remember_in_case_of_failure')) {
      $this->watchdog('Yext: starter where we left off after a failure.');
      $start = $start_from_failure['start'];
      $end = $start_from_failure['end'];
      $offset = $start_from_failure['offset'];
    }
    else {
      $this->watchdog('Yext: no previous failure detected; starting in the next date to import.');
      $start = $this->nextDateToImport('Y-m-d');
      $end = $this->nextDateToImport('Y-m-d', 2 * 24 * 60 * 60);
      $offset = 0;
    }
    $this->watchdog('Yext: query between ' . $start . ' and ' . $end . ' at offset ' . $offset);
    $this->importYextAll($start, $end, min(self::MAX_OFFSET, $offset));
  }

  /**
   * Import nodes from an array of nodes.
   *
   * See also "Avoiding node collisions during gradual launch" in ./README.md.
   *
   * @param array $array
   *   An array of Nodes from Yext.
   */
  public function importFromArray(array $array) {
    $all_ids = [];
    array_walk($array, function ($item, $key) use (&$all_ids) {
      if (isset($item['id'])) {
        $all_ids[$item['id']] = $item['id'];
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
      $destination = empty($nodes[$source->getYextId()]) ? $this->getOrCreateUniqueNode($source) : $nodes[$source->getYextId()];

      $migrator = new NodeMigrationAtCreation($source, $destination);
      try {
        $result = $migrator->migrate() ? 'migration occurred' : 'migration skipped, probably because update time is identical in source/dest.';
        $this->watchdog('Yext ' . $result . ' for ' . $source->getYextId() . ' to ' . $destination->id());
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
      $this->stateSet('drupal_yext_remember_in_case_of_failure', NULL);
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
    $previous = intval($this->nextDateToImport('U'));
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

    // This call might result in an out-of-memory error for large datasets.
    // Remember where were were first.
    $this->stateSet('drupal_yext_remember_in_case_of_failure', [
      'start' => $start,
      'end' => $end,
      'offset' => $offset,
    ]);
    $this->importFromArray($response_locations);
    if ($response_count_less_offset > $response_locations_count) {
      $new_offset = $offset + $response_locations_count;
      $this->watchdog('Yext: incrementing offset to ' . $new_offset . ' because response count less offset > response location count');
      if ($new_offset > $offset && $new_offset <= self::MAX_OFFSET) {
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
   */
  public function nextDateToImport($format, int $add = 0) {
    return date($format, $this->stateGet('drupal_yext_next_import', strtotime('2017-12-10')) + $add);
  }

  /**
   * Get all YextPlugin plugins.
   *
   * @return \Drupal\drupal_yext\YextPluginCollection
   *   All plugins.
   */
  public function plugins() : YextPluginCollection {
    return YextPluginCollection::instance();
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
   */
  public function queryYext($date, $date2, $offset = 0) : array {
    $url = $this->buildUrl('/v2/accounts/me/locations', '', $this->allFilters($date, $date2), $offset);
    $body = (string) $this->httpGet($url)->getBody();
    return json_decode($body, TRUE);
  }

  /**
   * TRUE if the raw field should be updated for this entity.
   *
   * If the config variable "update_raw_on_save" is set, or if the raw field is
   * empty for the entity, this will return TRUE.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   A Drupal entity.
   *
   * @return bool
   *   TRUE if the raw field should be updated for this entity.
   */
  public function rawUpdatable(FieldableEntityInterface $entity) {
    if ($this->configGet('update_raw_on_save', FALSE)) {
      return TRUE;
    }

    $candidate = YextEntityFactory::instance()->entity($entity);

    $raw = $candidate->fieldValue($this->fieldmap()->raw());
    if (!$raw) {
      return TRUE;
    }

    return FALSE;
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
   * See ./README.md for how this works.
   *
   * @param string $log_function
   *   A log function such as 'print_r'.
   * @param int $chunk_size
   *   If you have a very large number of nodes, to avoid memory issues, you
   *   might want to have a chunk size of, say, 100.
   * @param int $start_at
   *   The first node id to use.
   */
  public function resaveAllExisting(string $log_function = 'print_r', int $chunk_size = PHP_INT_MAX, int $start_at = 0) {
    return $this->actionOnAllExisting($log_function, $chunk_size, 'resaving existing node', 'save', TRUE, $start_at);
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
   * Run some self-tests. Exit with non-zero code if errors occur.
   *
   * Usage:
   *
   *   ./scripts/self-test-running-environment.sh
   */
  public function selftest() {
    SelfTest::instance()->run();
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
    $this->configSet('target_node_type', $type);
  }

  /**
   * Set the field name which contains the Yext unique ID.
   *
   * @param string $field
   *   The field such as 'field_something'.
   */
  public function setUniqueYextIdFieldName(string $field) {
    $this->configSet('target_unique_id_field', $field);
  }

  /**
   * Set the field name which contains the Yext last updated time.
   *
   * @param string $field
   *   The field such as 'field_something'.
   */
  public function setUniqueYextLastUpdatedFieldName(string $field) {
    $this->configSet('target_unique_last_updated_field', $field);
  }

  /**
   * Test the connection to Yext.
   *
   * @param string $key
   *   The API key to use; if empty use the api key in memory.
   * @param string $account
   *   The account number to use; if empty use the account in memory.
   * @param string $base
   *   The base URL to use; if empty use the base URL in memory.
   *
   * @return array
   *   An array with two keys, success and message.
   */
  public function test(string $key = '', string $account = '', string $base = '') : array {
    $key2 = $key ?: $this->apiKey();
    $acct2 = $account ?: $this->accountNumber();
    $base2 = $base ?: $this->base();
    static $return;
    if (!empty(($return[$base2][$acct2][$key2]))) {
      return $return[$base2][$acct2][$key2];
    }
    try {
      $message = '';
      $return[$base2][$acct2][$key2]['success'] = $this->checkServer($this->buildUrl('/v2/accounts/' . $acct2 . '/locations', $key2, [], 0, $base2), $message);
      if (!$return[$base2][$acct2][$key2]['success']) {
        $return[$base2][$acct2][$key2]['message'] = 'Connection failed';
      }
      $return[$base2][$acct2][$key2]['more'] = $message;
    }
    catch (\Exception $e) {
      $return[$base2][$acct2][$key2] = [
        'success' => FALSE,
        'message' => 'Exception thrown while connecting',
        'more' => $e->getMessage(),
      ];
    }
    if ($return[$base2][$acct2][$key2]['success']) {
      $return[$base2][$acct2][$key2]['message'] = 'Connection successful';
    }
    $return[$base2][$acct2][$key2]['more'] = str_replace($key2, 'API-KEY-HIDDEN-FOR-SECURITY', $return[$base2][$acct2][$key2]['more']);
    return $return[$base2][$acct2][$key2];
  }

  /**
   * The Drupal field name which contains the Yext unique id.
   *
   * @return string
   *   A field name such as 'field_yext_unique_id.
   */
  public function uniqueYextIdFieldName() : string {
    return $this->configGet('target_unique_id_field', 'field_yext_unique_id');
  }

  /**
   * The Drupal field name which contains the Yext last updated info.
   *
   * @return string
   *   A field name such as 'field_yext_last_updated.
   */
  public function uniqueYextLastUpdatedFieldName() : string {
    return $this->configGet('target_unique_last_updated_field', 'field_yext_last_updated');
  }

  /**
   * Given an entity, update it based on a response from the server.
   *
   * @param \Drupal\drupal_yext\YextContent\YextEntity $candidate
   *   An entity to be updated.
   */
  public function updateEntityFromId(YextEntity $candidate) {
    $id = $candidate->fieldValue($this->uniqueYextIdFieldName());
    if (!$id) {
      // No ID, nothing to do.
      return;
    }
    if ($this->stateGet('drupal_yext_dryrun', FALSE)) {
      // For example, during self-tests.
      return;
    }
    try {
      // We now have an ID, we need to populate the raw data and, if we have
      // an error, unpublish the node.
      $data = $this->getRecordByUniqueId($id);
      if (!empty($data['response'])) {
        if (is_a($candidate, YextTargetNode::class)) {
          $candidate->setYextRawData(json_encode($data['response']));
        }
      }
      else {
        throw new \Exception('Got data from Yext but it does not contain a "response" key.');
      }
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
      $t_args = [
        '@i' => $candidate->id(),
        '@t' => $t->getMessage(),
        '@yext_id' => $id,
      ];
      if ($this->configGet('unpublish_node_if_id_invalid', FALSE)) {
        $message = $this->t('Unpublishing node with nid @i (Yext id @yext_id) because we got the error @t from Yext.', $t_args);
        $this->drupalSetMessage($message);
        if (is_a($candidate, YextTargetNode::class)) {
          $candidate->setYextRawData(json_encode($message));
        }
        $candidate->unpublish();
      }
      else {
        $this->drupalSetMessage($this->t('We got error @t from the server but we will not unpublish node @i (Yext id @yext_id)', $t_args));
      }
    }
  }

  /**
   * Update the raw data field but only if this is required.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   A Drupal entity.
   */
  public function updateRaw(FieldableEntityInterface $entity) {
    if ($this->rawUpdatable($entity)) {
      $candidate = YextEntityFactory::instance()->entity($entity);
      $this->drupalSetMessage($this->t('Will try to update data from Yext for node with nid @i', ['@i' => $entity->id()]));

      $this->updateEntityFromId($candidate);
    }
    else {
      $this->drupalSetMessage($this->t('Will not try to update data from Yext for node with nid @i', ['@i' => $entity->id()]));
    }
  }

  /**
   * Update the number representing the nodes remaining to import.
   */
  public function updateRemaining() {
    $start = $this->nextDateToImport('Y-m-d', 3 * 24 * 60 * 60);
    $end = $this->date('Y-m-d', intval($this->date('U')) + 24 * 60 * 60);
    $this->watchdog('Yext: query between ' . $start . ' and ' . $end);
    try {
      $result = $this->queryYext($start, $end);
    }
    catch (\Exception $e) {
      $this->watchdog('Yext: ' . $e->getMessage());
      $result = [];
    }
    if (!empty($result['response']['count'])) {
      $count = $result['response']['count'];
      $this->watchdog('Yext: updating remaining to ' . $count . '.');
      $this->stateSet('drupal_yext_remaining', $count);
    }
    else {
      $this->watchdog('Yext: could not figure out the remaining nodes.');
    }
  }

}
