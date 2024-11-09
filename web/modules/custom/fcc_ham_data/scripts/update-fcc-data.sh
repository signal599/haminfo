#!/bin/bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
WEB_ROOT=$SCRIPT_DIR/../../../../../web
DRUSH=$WEB_ROOT/../vendor/bin/drush

cd $WEB_ROOT

$DRUSH fcc_ham_data:truncate hd
$DRUSH fcc_ham_data:truncate en
$DRUSH fcc_ham_data:truncate am

$DRUSH fcc_ham_data:import hd ../downloads/HD.dat
$DRUSH fcc_ham_data:import en ../downloads/EN.dat
$DRUSH fcc_ham_data:import am ../downloads/AM.dat

msg=$($DRUSH fcc_ham_data:report-input-counts 2>&1)
if [[ "$msg" =~ Error* ]]
then
  echo "$msg"
  exit
fi

$DRUSH fcc_ham_data:update-hash
$DRUSH ham_station:import-fcc-update
$DRUSH ham_station:import-fcc-new
$DRUSH ham_station:import-fcc-new-addresses
$DRUSH ham_station:delete-fcc-inactive
$DRUSH ham_station:delete-fcc-inactive-addresses
$DRUSH ham_station:delete-fcc-inactive-locations
$DRUSH ham_station:set-po-box

$DRUSH fcc_ham_data:truncate hd
$DRUSH fcc_ham_data:truncate en
$DRUSH fcc_ham_data:truncate am

$DRUSH cr
