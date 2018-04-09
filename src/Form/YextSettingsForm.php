<?php

namespace Drupal\drupal_yext\Form;

use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\Yext\Yext;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * The settings form for Yext.
 */
class YextSettingsForm extends FormBase {

  use CommonUtilities;

  /**
   * Ajax callback to factory-reset the Yext import system.
   */
  public function ajaxResetAll(array $form, FormStateInterface $form_state) {
    $this->yext()->resetAll();
    return $this->ajaxYextUpdateImportData($form, $form_state);
  }

  /**
   * Ajax callback to import some more Yext items.
   *
   * Note that this could timeout relatively easily.
   */
  public function ajaxYextImportSome(array $form, FormStateInterface $form_state) {
    $this->yext()->importSome();
    return $this->ajaxYextUpdateImportData($form, $form_state);
  }

  /**
   * Ajax callback to test Yext.
   */
  public function ajaxYextTest(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new HtmlCommand('#check-icon-wrapper', $this->yextTestIcon($input['DrupalYext_yextapi'])));
    $ajax_response->addCommand(new HtmlCommand('#ajax-yext-test', $this->yextTestString($input['DrupalYext_yextapi'])));
    return $ajax_response;
  }

  /**
   * Ajax callback to update via Ajax the Yext data presented on the form.
   */
  public function ajaxYextUpdateImportData(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $imported = $this->yext()->imported();
    $failed = $this->yext()->failed();
    $next_date = $this->yext()->nextDateToImport('Y-m-d');
    $last_check = $this->yext()->lastCheck('Y-m-d H:i:s');
    $remaining = $this->yext()->remaining();

    $ajax_response->addCommand(new HtmlCommand('#yext-imported', $imported));
    $ajax_response->addCommand(new HtmlCommand('#yext-failed', $failed));
    $ajax_response->addCommand(new HtmlCommand('#yext-next-date', $next_date));
    $ajax_response->addCommand(new HtmlCommand('#yext-last-check', $last_check));
    $ajax_response->addCommand(new HtmlCommand('#yext-remaining', $remaining));

    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'DrupalYext_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['#attached']['library'][] = 'drupal_yext/ajaxy';
    $form['yextbase'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic node information'),
      '#description' => $this->t('This website attempts to synchronize data from @yext using their @api, creating nodes. Enter information about the target nodes here.', [
        '@yext' => $this->link('Yext', 'https://www.yext.com')->toString(),
        '@api' => $this->link('API', 'http://developer.yext.ca/docs/guides/get-started/')->toString(),
      ]),
      '#open' => FALSE,
    ];
    $form['yextbase']['DrupalYextBase.nodetype'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The target node type'),
      '#description' => $this->t('Something like "article" or "doctor". This is not validated, so please make sure it exists.'),
      '#default_value' => $this->yext()->yextNodeType(),
    );
    $form['yextbase']['DrupalYextBase.uniqueidfield'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The target field ID'),
      '#description' => $this->t('Something like "field_yext_id". This is not validated, so please make sure it exists.'),
      '#default_value' => $this->yext()->uniqueYextIdFieldName(),
    );
    try {
      $form['yext'] = array(
        '#type' => 'details',
        '#title' => $this->t('Yext integration'),
        '#description' => $this->t('This website attempts to synchronize data from @yext using their @api, creating nodes. Once you have an "app" and API key set up, you can enter them here.', [
          '@yext' => $this->link('Yext', 'https://www.yext.com')->toString(),
          '@api' => $this->link('API', 'http://developer.yext.ca/docs/guides/get-started/')->toString(),
        ]),
        '#open' => FALSE,
      );
      $base = $this->yext()->base();
      $form['yext']['DrupalYext.yextbase'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Yext base URL'),
        '#description' => $this->t('Something like @b.', [
          '@b' => $this->yext()->defaultBase(),
        ]),
        '#default_value' => $base,
      );
      $form['yext']['DrupalYext.yextnext'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Next check date for Yext'),
        '#description' => $this->t('YYYY-MM-DD; this is set automatically during normal operation.'),
        '#default_value' => $this->yext()->nextDateToImport('Y-m-d'),
      );
      $key = $this->yext()->apiKey();
      $form['yext']['DrupalYext.yextapi'] = array(
        '#type' => 'password',
        '#title' => $this->t('Yext API key'),
        '#description' => '<strong>' . $this->t('For security reasons, you will not be able to see the API key even if it is entered.') . '</strong> ' . $this->t('Can be found in your "app" in the Yext developer console.'),
        '#default_value' => $key,
      );
      $checkmessage = $this->yextTestString();
      $icon = $this->yextTestIcon();
      $form['yext']['DrupalYext.ajaxYextTest'] = array(
        '#type' => 'button',
        '#value' => $this->t('Test the API key'),
        '#description' => $this->t('Attempts to connect to the Yext API.'),
        '#ajax' => array(
          'callback' => '::ajaxYextTest',
          'effect' => 'fade',
          'event' => 'click',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
        ),
        '#prefix' => <<< HEREDOC
<span class="system-status-counter system-status-counter--error">
  <span class="yext-ajaxy" id="check-icon-wrapper">$icon</span>
  <span class="system-status-counter__status-title">
    <span class="system-status-counter__title-count"><span class="yext-ajaxy" id="ajax-yext-test">$checkmessage</span>&nbsp;<span>
HEREDOC
        ,
        '#suffix' => '</span></span></span></span></span>',
      );
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
    }
    try {
      $form['yextimport'] = array(
        '#type' => 'details',
        '#title' => $this->t('Yext import status'),
        '#description' => $this->t('Assuming Yext integration works, this is where we are at in the import (imports are performed on cron runs).') . ' ' . $this->t('Imports some more data. Be careful using the Import more button because this can timeout with large amounts of data. It is recommended to use "drush ev \'drupal_yext_import_some()\'" on the command line for more heavy-duty imports.') . ' ' . $this->t('Resets the API importer to its initial state. Useful for testing.'),
        '#open' => FALSE,
      );
      $imported = $this->yext()->imported();
      $failed = $this->yext()->failed();
      $next_date = $this->yext()->nextDateToImport('Y-m-d');
      $last_check = $this->yext()->lastCheck('Y-m-d H:i:s');
      $remaining = $this->yext()->remaining();
      $form['yextimport']['DrupalYext.ajaxResetAll'] = array(
        '#type' => 'button',
        '#value' => $this->t('Reset the API importer'),
        '#ajax' => array(
          'callback' => '::ajaxResetAll',
          'effect' => 'fade',
          'event' => 'click',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
        ),
      );
      $form['yextimport']['details'] = array(
        '#type' => 'markup',
        '#markup' => '<ul>
          <li><span class="yext-ajaxy" id="yext-imported">' . $imported . '</span> nodes imported.</li>
          <li><span class="yext-ajaxy" id="yext-failed">' . $failed . '</span> nodes failed to import.</li>
          <li>We have updated nodes updated on or before <span class="yext-ajaxy" id="yext-next-date">' . $next_date . '<span>.</li>
          <li>Last check: <span class="yext-ajaxy" id="yext-last-check">' . $last_check . '</span>.</li>
          <li><span class="yext-ajaxy" id="yext-remaining">' . $remaining . '</span> nodes remaining.</li>
        <ul>',
      );
      $form['yextimport']['DrupalYext.ajaxImportSome'] = array(
        '#type' => 'button',
        '#value' => $this->t('Import more'),
        '#ajax' => array(
          'callback' => '::ajaxYextImportSome',
          'effect' => 'fade',
          'event' => 'click',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
        ),
      );
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
    }
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $this->yext()->setNodeType($input['DrupalYextBase_nodetype']);
    $this->yext()->setUniqueYextIdFieldName($input['DrupalYextBase_uniqueidfield']);
    $this->yext()->apiKey($input['DrupalYext_yextapi']);
    $this->yext()->base($input['DrupalYext_yextbase']);
    $this->yext()->setNextDate($input['DrupalYext_yextnext']);
    $this->drupalSetMessage($this->t('Settings saved successfully.'));
  }

  /**
   * Get the Yext singleton.
   *
   * @return Yext
   *   The Yext singleton.
   *
   * @throws Exception
   */
  public function yext() : Yext {
    return Yext::instance()->yext();
  }

  /**
   * Get a Yext icon to use for the test result.
   *
   * @param string $key
   *   An API key to use.
   *
   * @return string
   *   HTML markup for the icon.
   */
  public function yextTestIcon(string $key = '') : string {
    $yext_test = $this->yext()->test($key);
    if (!empty($yext_test['success'])) {
      $class = 'checked';
    }
    else {
      $class = 'error';
    }
    return '<span class="system-status-counter__status-icon system-status-counter__status-icon--' . $class . '"></span>';
  }

  /**
   * Return the string describing the result of a Yext test.
   *
   * @param string $key
   *   An API key to use.
   *
   * @return string
   *   A string.
   */
  public function yextTestString(string $key = '') : string {
    $yext_test = $this->yext()->test($key);
    if (isset($yext_test['message']) && is_string($yext_test['message'])) {
      return $yext_test['message'];
    }
    else {
      return 'Please make sure Yext::test() returns an array with a message key, not ' . serialize($yext_test);
    }
  }

}
