<?php

namespace Drupal\drupal_yext\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Yext Plugin annotation object.
 *
 * See the plugin_type_example module of the examples module for how this works.
 *
 * @see http://drupal.org/project/examples
 * @see \Drupal\drupal_yext\YextPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class YextPluginAnnotation extends Plugin {

  /**
   * A brief, human readable, description of the modifier.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * How this modifier should be ordered.
   *
   * @var float
   */
  public $weight;

}
