<?php

namespace Drupal\fcc_ham_data;

use Drupal\Core\Extension\ModuleHandler;

/**
 * Parse the FCC schema data.
 */
class FccSchemaParser {

  /**
   * The module handler.
   *
   * @var ModuleHandler
   */
  private $moduleHandler;

  /**
   * The contents of the SQL table definitions file from FCC.
   *
   * @var string
   */
  private $fccDefs;

  /**
   * Drupal\Core\Extension\ModuleHandler\FccSchemaParser constructor.
   *
   * @param ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandler $module_handler) {
    $this->moduleHandler = $module_handler;
    $module_path = $this->moduleHandler->getModule('fcc_ham_data')->getPath();
    // Read the FCC schema file.
    $this->fccDefs = file_get_contents($module_path . '/definitions/FccTableDefs.sql');
  }

  /**
   * Get fields for a table for FCC import.
   *
   * @param string $table_id
   *   The two character code used by FCC to refer to the table.
   *
   * @return array
   *   The 'fields' part of the table definition to be used by hook_schema.
   *
   * @throws \Exception
   */
  public function getFields($table_id) {
    $table_id = strtoupper($table_id);

    $matches = [];
    if (preg_match('/(create\s+?table\s+?dbo\.PUBACC_' . $table_id . '\s*?\((.+?)\))\s+?go/s', $this->fccDefs, $matches) !== 1) {
      throw new \Exception('Cannot find definition for FCC data table ' . $table_id);
    }

    $column_defs = preg_split('/,\s*?\n/', $matches[2]);
    $fields = [];

    foreach ($column_defs as $column_def) {
      // Get just the SQL for this table.
      $matches = [];
      if (preg_match('/\s*?(\S+?)\s+?(\S+?(\(.+?\))?)\s+?(null|not null)/i', $column_def, $matches) !== 1) {
        throw new \Exception('Cannot parse definition for FCC data table ' . $table_id);
      }

      list(,$name, $type, $length, $null) = $matches;

      if (!empty($length)) {
        // The field is something like char(10).
        $type = substr($type, 0, strpos($type, '('));
      }

      $field = [
        'type' => $type,
        'not null' => (strtolower($null) === 'not null'),
      ];

      $length_parts = explode(',', trim($length, '()'));

      switch ($type) {
        case 'char':
        case 'varchar':
          $field['length'] = $length_parts[0];
          break;

        case 'numeric':
          $field['precision'] = $length_parts[0];
          if (count($length_parts) > 1) {
            $field['scale'] = $length_parts[1];
          }
          break;

        case 'datetime':
          // Most dates in the files are m/d/y strings.
          // Store the few fields which the FCC lists as datetime the same way.
          $field['type'] = 'char';
          $field['length'] = 10;
          break;

        case 'tinyint':
          $field['type'] = 'int';
          $field['size'] = 'tiny';
          break;
      }

      $fields[$name] = $field;
    }

    return $fields;
  }

}
