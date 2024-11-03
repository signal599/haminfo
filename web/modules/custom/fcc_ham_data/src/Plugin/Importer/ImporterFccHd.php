<?php

namespace Drupal\fcc_ham_data\Plugin\Importer;

/**
 * Plugin class for HD file importer.
 *
 * @FccImporter(
 *   id = "hd",
 *   name = @Translation("Importer for FCC HD file")
 * )
 */
class ImporterFccHd extends ImporterBase {

  /**
   * (@inheritdoc}
   */
  public function getTableId() {
    return 'hd';
  }

}
