<?php

namespace Drupal\fcc_ham_data\Plugin\Importer;

/**
 * Plugin class for EN file importer.
 *
 * @FccImporter(
 *   id = "en",
 *   name = @Translation("Importer for FCC EN file")
 * )
 */
class ImporterFccEn extends ImporterBase {

  /**
   * (@inheritdoc}
   */
  public function getTableId() {
    return 'en';
  }

  /**
   * (@inheritdoc}
   */
  protected function alterFieldList(&$fields) {
    parent::alterFieldList($fields);
    $fields[] = 'address_hash';
  }

  /**
   * (@inheritdoc}
   */
  protected function alterValues($row_string, $row_array, &$values) {
    parent::alterValues($row_string, $row_array, $values);

    // Insert dash in 9 digit zip code.
    $zip_code = $values['zip_code'];
    if (strlen($zip_code) > 5) {
      $values['zip_code'] = substr_replace($zip_code, '-', 5, 0);
    }

    // Some have street address, some PO Box and some both.
    // Combine these into one line.
    $address = [];
    if (!empty($values['street_address'])) {
      $address[] = $values['street_address'];
    }

    if (!empty($values['po_box'])) {
      $address[] = 'PO Box ' . $values['po_box'];
    }

    $values['street_address'] = implode(', ', $address);

    // Hash just the address fields. This is used to help know when we need to
    // geocode again.
    $values['address_hash'] = sha1(
      $values['street_address'] .
      $values['city'] .
      $values['state'] .
      $values['zip_code']
    );
  }

}
