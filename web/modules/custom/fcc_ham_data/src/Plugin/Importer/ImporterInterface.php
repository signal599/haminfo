<?php

namespace Drupal\fcc_ham_data\Plugin\Importer;

/**
 * Interface for all importers.
 */
interface ImporterInterface {

  /**
   * Get database table id.
   *
   * @return string
   *   The two character FCC id of the database table.
   */
  public function getTableId();

  /**
   * Import file into database table.
   * 
   * @param $file_path
   *   File path for the file.
   * @param callable $callback
   *   Optional callable used to report progress. 
   */
  public function import($file_path, callable $callback = NULL);

  /**
   * Truncate table.
   */
  public function truncateTable();

}
