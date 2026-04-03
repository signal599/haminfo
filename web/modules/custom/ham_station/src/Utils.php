<?php

namespace Drupal\ham_station;

class Utils {

  public static function formatSubsquare($code) {
    return sprintf('%s%s%s',
      strtoupper(substr($code, 0, 2)),
      substr($code, 2, 2),
      strtolower(substr($code, 4, 2))
    );
  }
}
