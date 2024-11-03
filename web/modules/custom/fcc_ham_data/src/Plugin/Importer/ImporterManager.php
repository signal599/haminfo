<?php

namespace Drupal\fcc_ham_data\Plugin\Importer;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
/**
 * Manages discovery and instantiation of importer plugins.
 */
class ImporterManager extends DefaultPluginManager {

  /**
   * {@inheritDoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/Importer',
      $namespaces,
      $module_handler,
      'Drupal\fcc_ham_data\Plugin\Importer\ImporterInterface',
      'Drupal\fcc_ham_data\Annotation\FccImporter'
    );
  }

}
