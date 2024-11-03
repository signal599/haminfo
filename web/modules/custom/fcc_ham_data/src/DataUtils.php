<?php

namespace Drupal\fcc_ham_data;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drush\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * A few data utilities for the imported FCC data.
 */
class DataUtils {

  const MIN_ROW_COUNTS = [
    'hd' => 1400000,
    'en' => 1400000,
    'am' =>1400000,
  ];

  /**
   * The database connnection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $dbConnection;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * EntityUpdater constructor.
   *
   * @param \Drupal\Core\Database\Connection $db_connection
   *   The database connnection.
   * @param LoggerInterface $logger
   *   The logger.
   */
  public function __construct(Connection $db_connection, LoggerInterface $logger) {
    $this->dbConnection = $db_connection;
    $this->logger = $logger;
  }

  /**
   * Update the hash for the joined table result.
   *
   * @param callable $callback
   *   Optional callable used to report progress.
   */
  public function updateHash(callable $callback = NULL) {
    // Calculate and update a hash for the joined table result to use when
    // updating the entity. Note: MySQL specific code.

    $sql = '
    UPDATE fcc_license_hd hd
    INNER JOIN fcc_license_en en ON en.unique_system_identifier = hd.unique_system_identifier
    INNER JOIN fcc_license_am am ON am.unique_system_identifier = hd.unique_system_identifier
    SET hd.total_hash = SHA1(CONCAT(hd.row_hash, en.row_hash, am.row_hash))';

    $stmt = $this->dbConnection->prepareStatement($sql, [], TRUE);
    $stmt->execute();

    $msg = sprintf('%s hashes updated', $stmt->rowCount());
    $this->logger->info($msg);

    if ($callback !== NULL) {
      $callback($msg);
    }
  }

  /**
   * Get row counts for imported data.
   *
   * @return array
   *   Array of counts with keys 'hd', 'en', 'am'.
   */
  private function getImportCounts() {
    return [
      'hd' => $this->dbConnection->query('SELECT COUNT(*) FROM fcc_license_hd')->fetchCol()[0],
      'en' => $this->dbConnection->query('SELECT COUNT(*) FROM fcc_license_en')->fetchCol()[0],
      'am' => $this->dbConnection->query('SELECT COUNT(*) FROM fcc_license_am')->fetchCol()[0],
    ];
  }

  /**
   * Report import counts.
   *
   * @return string
   *   Multiline report.
   */
  public function reportImportCounts() {
    $counts = $this->getImportCounts();
    $msg = [];
    $error = FALSE;

    foreach (['hd', 'en', 'am'] as $key) {
      $err = ($counts[$key] < self::MIN_ROW_COUNTS[$key]);
      $error |= $err;

      if ($err) {
        $text = 'Error: fcc_license_%s has only %s rows';
      }
      else {
        $text = 'fcc_license_%s has %s rows';
      }

      $msg[] = sprintf($text, $key, $counts[$key]);
    }

    $msg = implode(PHP_EOL, $msg);

    if ($error) {
      $this->logger->error($msg);
    }
    else {
      $this->logger->info($msg);
    }

    return $msg;
  }

}
