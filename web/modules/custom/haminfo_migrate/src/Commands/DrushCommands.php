<?php

namespace Drupal\haminfo_migrate\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands as Base;

class DrushCommands extends Base {

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Import export users from old site.
   *
   * @command haminfo:import-users
   */
  public function importUsers() {
    /** @var \Drupal\user\UserStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('user');

    $file = new \SplFileObject('private://migration/users.txt', 'r');

    while (!$file->eof()) {
      $row = $file->fgetcsv('|');
      $row = array_map('trim', $row);

      if (empty($row[1])) {
        continue;
      }

      /** @var \Drupal\user\Entity\User $user */
      $user = $storage->loadByProperties(['mail' => $row[2]]);

      if (!empty($user)) {
        continue;
      }

      /** @var \Drupal\user\UserInterface $user */
      $user = $storage->create([
        'name' => $row[1],
        'mail' => $row[2],
        'pass' => [
          'value' => $row[3],
          'pre_hashed' => TRUE,
        ],
        'status' => TRUE,
      ]);

      $user->addRole('exporter');
      $user->save();
      break;
    }
  }

}
