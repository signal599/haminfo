<?php

namespace Drupal\ham_station\Query;

class HamLocationDTO {

  private $id;
  private $lat;
  private $lng;
  private $addresses  = [];

  public function __construct($id, $lat, $lng) {
    $this->id = $id;
    $this->lat = $lat;
    $this->lng = $lng;
  }

  public function getId() {
    return $this->id;
  }

  /**
   * @return mixed
   */
  public function getLat()
  {
    return $this->lat;
  }

  /**
   * @return mixed
   */
  public function getLng()
  {
    return $this->lng;
  }

  public function addAddress(HamAddressDTO $address) {
    $this->addresses[] = $address;
  }

  public function getAddresses() {
    return $this->addresses;
  }

  public function getAddress($idx) {
    return $this->addresses[$idx];
  }

  public function setAddress(HamAddressDTO $address, $idx) {
    $this->addresses[$idx] = $address;
  }

  /**
   * Sort addresses by license class.
   */
  public function sortAddresses() {
    // Sort stations, highest class at the top.
    foreach ($this->addresses as $address) {
      $address->sortStations();
    }

    // Sort addresses by first license class.
    usort($this->addresses, function (HamAddressDTO $a, HamAddressDTO $b) {
      $a_stations = $a->getStations();
      $b_stations = $b->getStations();

      $a_class = !empty($a_stations) ? reset($a_stations)->getOperatorClass() : NULL;
      $b_class = !empty($a_stations) ? reset($b_stations)->getOperatorClass() : NULL;

      // Put no license class at the bottom.
      $a_rank = !empty($a_class) ? (HamStationDTO::CLASS_RANKINGS[$a_class] ?? 999) : 999;
      $b_rank = !empty($b_class) ? (HamStationDTO::CLASS_RANKINGS[$b_class] ?? 999) : 999;

      return $a_rank <=> $b_rank;
    });
  }

  public function moveAddressToTop($top_idx) {
    $address = $this->addresses[$top_idx];

    if ($top_idx == 0) {
      return $address;
    }

    unset($this->addresses[$top_idx]);
    array_unshift($this->addresses, $address);
    return $address;
  }

}
