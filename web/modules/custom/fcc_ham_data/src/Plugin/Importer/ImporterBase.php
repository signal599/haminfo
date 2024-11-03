<?php

namespace Drupal\fcc_ham_data\Plugin\Importer;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\fcc_ham_data\FccSchemaParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for importers.
 */
abstract class ImporterBase extends PluginBase implements ImporterInterface, ContainerFactoryPluginInterface {

  /**
   * Number of rows to insert on one multi-insert query.
   *
   * @const int
   */
  const IMPORT_BLOCK_SIZE = 1000;

  /**
   * Callback every this many rows.
   *
   * @const int
   */
  const CALLBACK_BLOCK_SIZE = 10000;

  /**
   * Database connection
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $dbConnection;

  /**
   * The FCC schema parser.
   *
   * @var FccSchemaParser
   */
  private $fccSchemaParser;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * ImporterBase constructor.

   * {@inheritDoc}
   * @param \Drupal\Core\Database\Connection $db_connection
   *   The database connection.
   * @param \Drupal\fcc_ham_data\FccSchemaParser $fcc_schema_parser
   *   The FCC schema parser.
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(
    array $configuration, $plugin_id, $plugin_definition,
    Connection $db_connection,
    FccSchemaParser $fcc_schema_parser,
    LoggerInterface $logger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dbConnection = $db_connection;
    $this->fccSchemaParser = $fcc_schema_parser;
    $this->logger = $logger;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $plugin_id, $plugin_definition
  ) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('database'),
      $container->get('fcc_ham_data.fcc_schema_parser'),
      $container->get('logger.channel.fcc_ham_data')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function import($file_path, callable $callback = NULL) {

    if (!file_exists($file_path)) {
      throw new \Exception(sprintf('File %s does not exist.', $file_path));
    }

    // Read FCC schema SQL.
    $fields_info = $this->fccSchemaParser->getFields($this->getTableId());
    $field_names = array_keys($fields_info);
    $file_field_count = count($field_names);

    $fields_info = array_map(function ($value) {
      $value = (object) $value;
      $value->is_string = in_array($value->type, ['char', 'varchar']);
      return $value;
    }, $fields_info);

    // Add an extra fields.
    $this->alterFieldList($field_names);

    $this->truncateTable();

    $file = new \SplFileObject($file_path);
    $row_count = 0;
    $block_count = 0;
    $imported_count = 0;

    // Use a multi-insert query for performance.
    $query = $this->dbConnection
      ->insert($this->getTableName())
      ->fields($field_names);

    while(!$file->eof()) {
      $row_count++;
      $row_string = $file->fgets();
      $row_array = explode('|', $row_string);
      $row_array = array_map('trim', $row_array);

      if (count($row_array) === 1 && empty($row_array[0])) {
        // Blank line, probably at the end of file.
        continue;
      }

      if (count($row_array) < $file_field_count) {
        // Row has less columns than expected.
          $this->printFeedback(sprintf(
              'Row %s has %s rows, expected %s%s',
              $row_count,
              count($row_array),
              count($file_field_count)
            ),
            $callback
          );
        continue;
      }

      // Assemble key / value array data to insert.
      $values = [];
      $idx = 0;

      foreach ($fields_info as $name => $info) {
        $value = $row_array[$idx];

        if ($info->is_string) {
          // The files have no BOM so they are presumably UTF-8 or plain ASCII.
          // A few (like one in a million) have values that cannot be
          // represented in UTF-8. This avoids an error when saving to the db.
          $value = iconv('ASCII', 'UTF-8//IGNORE', $value);
        }

        if ($info->is_string && strlen($value) > $info->length) {
          $this->printFeedback(sprintf(
            '%s is length %s, expected %s at row %s',
            $name, strlen($value), $info->length, $row_count
          ), $callback);

          $value = substr($value, 0, $info->length);
        }

        $values[$name] = $value ?: NULL;
        $idx++;
      }

      // Add any extra values.
      $this->alterValues($row_string, $row_array, $values);

      $query->values($values);
      $block_count++;

      if ($block_count == static::IMPORT_BLOCK_SIZE) {
        // Insert the rows.
        try {
          $query->execute();
        }
        catch (\Exception $ex) {
          // The message contains all 10,000 inserts. Throw an exception with
          // a shorter readable message.
          throw new \Exception(substr($ex->getMessage(), 0, 2000));
        }

        // Start next query.
        $query = $this->dbConnection
          ->insert($this->getTableName())
          ->fields($field_names);

        $imported_count += $block_count;
        $block_count = 0;

        if (($imported_count % static::CALLBACK_BLOCK_SIZE) === 0) {
          // Report progress.
          $this->printFeedback($imported_count, $callback);
        }
      }
    }

    if ($block_count > 0) {
      // Insert the last partial block.
      $query->execute();
      $imported_count += $block_count;
    }

    $this->logger->info(sprintf('%s rows imported from %s', $imported_count, $file_path));
  }

  /**
   * Get database table name.
   *
   * @return string
   *   Database table name.
   */
  protected function getTableName() {
    return $table_name = 'fcc_license_' . $this->getTableId();
  }

  /**
   * Truncate database table.
   */
  public function truncateTable() {
    $this->dbConnection->truncate($this->getTableName())
      ->execute();
  }

  /**
   * Alter the list of fields beyond what we got from FCC.
   *
   * @param $fields
   *   Array of fields.
   */
  protected function alterFieldList(&$fields) {
    $fields[] = 'row_hash';
  }

  /**
   * Alter the list of values after read from the file.
   *
   * @param $row_string
   *   The raw row as a string from the file.
   * @param $row_array
   *   Array of exploded values.
   * @param $values
   *   Key / value array of data.
   */
  protected function alterValues($row_string, $row_array, &$values) {
    $values['row_hash'] = sha1($row_string);
  }

  /**
   * Print feedback if a callback from supplied. Use for drush commands.
   *
   * @param string $message
   *   The message to print.
   * @param callable|NULL $callback
   *   Callback.
   */
  protected function printFeedback($message, callable $callback = NULL) {
    if ($callback !== NULL) {
      $callback($message);
    }
  }

}
