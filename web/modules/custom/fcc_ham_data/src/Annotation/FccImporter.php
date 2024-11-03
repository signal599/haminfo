<?php

namespace Drupal\fcc_ham_data\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FccImporter annotation object.
 *
 * @Annotation
 */
class FccImporter extends Plugin
{
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin name.
   *
   * @var string
   */
  public $name;

}
