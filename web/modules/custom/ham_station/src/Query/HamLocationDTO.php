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

      /** @var HamStationDTO $a_station */
      $a_station = !empty($a_stations) ? reset($a_stations) : NULL;
      /** @var HamStationDTO $b_station */
      $b_station = !empty($b_stations) ? reset($b_stations) : NULL;

      // Put no license class at the bottom.
      $a_rank = 999;
      if (!empty($a_station)) {
        $a_rank = (HamStationDTO::CLASS_RANKINGS[$a_station->getOperatorClass()] ?? 999) . $a_station->getCallsign();
      }

      $b_rank = 999;
      if (!empty($b_station)) {
        $b_rank = (HamStationDTO::CLASS_RANKINGS[$b_station->getOperatorClass()] ?? 999) . $b_station->getCallsign();
      }

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
