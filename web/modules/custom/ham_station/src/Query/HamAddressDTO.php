<?php

namespace Drupal\ham_station\Query;

use Drupal\ham_station\Entity\HamStation;

class HamAddressDTO {

  const ROAD_SHORTS = [
    'Road' => 'Rd',
    'Street' => 'St',
    'Avenue' => 'Ave',
    'Drive' => 'Dr',
    'Lane' => 'Ln',
    'Circle' => 'Cir',
  ];

  private static $roadShorts;

  private $address1;
  private $address2;
  private $city;
  private $state;
  private $zip;
  private $stations = [];

  public function __construct($address1, $address2, $city, $state, $zip) {
    $this->address1 = $this->normalizeAddress($address1);
    $this->address2 = $address2;
    $this->city = $city;
    $this->state = $state;
    $this->zip = explode('-', $zip)[0];
  }

  /**
   * Normalize some common abbreviations.
   *
   * @param [type] $address
   * @return void
   */
  private function normalizeAddress($address) {
    if (empty(self::$roadShorts)) {
      // Addresses tend to be proper case or all upper case.
      $all = self::ROAD_SHORTS;
      foreach (self::ROAD_SHORTS as $long => $short) {
        $all[strtoupper($long)] = strtoupper($short);
      }
      self::$roadShorts = $all;
    }

    foreach (self::$roadShorts as $long => $short) {
      $count = 0;
      $address = preg_replace("/ {$long}$/", " $short", $address, 1, $count);
      if ($count > 0) {
        break;
      }
    }

    return rtrim($address, '.');
  }

  public function addStation(HamStationDTO $station) {
    $this->stations[] = $station;
  }

  /**
   * @return mixed
   */
  public function getAddress1()
  {
    return $this->address1;
  }

  /**
   * @return mixed
   */
  public function getAddress2()
  {
    return $this->address2;
  }

  /**
   * @return mixed
   */
  public function getCity()
  {
    return $this->city;
  }

  /**
   * @return mixed
   */
  public function getState()
  {
    return $this->state;
  }

  /**
   * @return mixed
   */
  public function getZip()
  {
    return $this->zip;
  }

  /**
   * @return array
   */
  public function getStations()
  {
    return $this->stations;
  }

  /**
   * Sort by license class, highest at the top.
   */
  public function sortStations() {
    usort($this->stations, function(HamStationDTO $a, HamStationDTO $b) {
      $a_rank = (HamStationDTO::CLASS_RANKINGS[$a->getOperatorClass()] ?? 999) . $a->getCallsign();
      $b_rank = (HamStationDTO::CLASS_RANKINGS[$b->getOperatorClass()] ?? 999) . $b->getCallsign();

      return $a_rank <=> $b_rank;
    });
  }

  public function moveStationToTop($top_idx) {
    if ($top_idx == 0) {
      return;
    }

    $station = $this->stations[$top_idx];
    unset($this->stations[$top_idx]);
    array_unshift($this->stations, $station);
  }

  /**
   * Create a case insenstive string key.
   *
   * @return string
   *   Key.
   */
  public function getKey() {
    return strtolower(implode('|', [$this->address1, $this->address2, $this->city, $this->state, $this->zip]));
  }

}
