<?php

namespace Drupal\fcc_ham_data\Plugin\Importer;

/**
 * Plugin class for AM file importer.
 *
 * @FccImporter(
 *   id = "am",
 *   name = @Translation("Importer for FCC AM file")
 * )
 */
class ImporterFccAm extends ImporterBase {

  /**
   * (@inheritdoc}
   */
  public function getTableId() {
    return 'am';
  }

}
