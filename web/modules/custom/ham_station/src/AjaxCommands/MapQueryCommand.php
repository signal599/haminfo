<?php

namespace Drupal\ham_station\AjaxCommands;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\ham_station\Query\MapQueryResult;

/**
 * Ajax command to return ham map data.
 */
class MapQueryCommand implements CommandInterface {

  public function __construct(
    private readonly MapQueryResult $result
  ) {
  }

  /**
   * {@inheritDoc}
   */
  public function render() {

    $return = [
      'command' => 'hamMapQuery',
      'result' => $this->result,
    ];

    return $return;
  }

}
