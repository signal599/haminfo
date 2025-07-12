<?php

namespace Drupal\ham_station\Query;

use Drupal\ham_station\Entity\HamStation;

class HamAddressDTO {

  private $address1;
  private $address2;
  private $city;
  private $state;
  private $zip;
  private $stations = [];

  public function __construct($address1, $address2, $city, $state, $zip) {
    $this->address1 = $address1;
    $this->address2 = $address2;
    $this->city = $city;
    $this->state = $state;
    $this->zip = $zip;
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
      $a_rank = HamStationDTO::CLASS_RANKINGS[$a->getOperatorClass()] ?? 0;
      $b_rank = HamStationDTO::CLASS_RANKINGS[$b->getOperatorClass()] ?? 0;

      return $a <=> $b;
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
    // 5 digit zip.
    $zip = explode('-', $this->zip)[0];
    return strtolower(implode('|', [$this->address1, $this->address2, $this->city, $this->state, $zip]));
  }

}
