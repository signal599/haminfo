<?php

namespace Drupal\fcc_ham_data\Commands;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\fcc_ham_data\DataUtils;
use Drupal\fcc_ham_data\Plugin\Importer\ImporterInterface;
use Drupal\fcc_ham_data\Plugin\Importer\ImporterManager;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class FccHamDataCommands extends DrushCommands {

  /**
   * The importer plugin manager.
   *
   * @var \Drupal\fcc_ham_data\Plugin\Importer\ImporterManager
   */
  private $importerManager;
  /**
   * Data utilities service.
   *
   * @var \Drupal\fcc_ham_data\DataUtils
   */
  private $dataUtils;

  /**
   * FccHamDataCommands constructor.
   *
   * @param \Drupal\fcc_ham_data\Plugin\Importer\ImporterManager $importer_manager
   *   The import manager.
   * @param \Drupal\fcc_ham_data\DataUtils $data_utils
   *   Data utilities service.
   */
  public function __construct(ImporterManager $importer_manager, DataUtils $data_utils) {
    $this->importerManager = $importer_manager;
    $this->dataUtils = $data_utils;
  }

  /**
   * Import data file.
   *
   * @param $file_type
   *   Two character FCC code.
   * @param $file_path
   *   Full path to the file..
   * @usage fcc_ham_data:import file_type file_path
   *
   * @command fcc_ham_data:import
   */
  public function import($file_type, $file_path) {

    try {
      /** @var ImporterInterface $importer */
      $importer = $this->importerManager->createInstance($file_type);
    }
    catch (PluginNotFoundException $ex) {
      throw new \InvalidArgumentException('Invalid file type: ' . $file_type);
    }

    if (!is_readable($file_path)) {
      throw new \InvalidArgumentException('File not found or not readable: ' . $file_path);
    }

    // Pass the writer to report progress.
    $importer->import($file_path, [$this->io(), 'writeln']);
  }

  /**
   * Truncate database table.
   *
   * @param $file_type
   *   Two character FCC code.
   * @usage fcc_ham_data:trucate-db-table file_type
   *
   * @command fcc_ham_data:truncate
   */
  public function truncateTable($file_type) {
    try {
      /** @var ImporterInterface $importer */
      $importer = $this->importerManager->createInstance($file_type);
    }
    catch (PluginNotFoundException $ex) {
      throw new \InvalidArgumentException('Invalid file type: ' . $file_type);
    }

    $importer->truncateTable();
  }

  /**
   * Update the hash for the joined query.
   *
   * @usage fcc_ham_data:update-hash
   *
   * @command fcc_ham_data:update-hash
   */
  public function updateHash() {
    $this->dataUtils->updateHash([$this->io(), 'writeln']);
  }

  /**
   * Report import counts.
   *
   * @usage fcc_ham_data:report-import-counts
   *
   * @command fcc_ham_data:report-input-counts
   */
  public function reportImportCounts() {
    $this->io()->writeln($this->dataUtils->reportImportCounts());
  }

}
