#!/bin/bash

cd ../web

../vendor/bin/drush fcc_ham_data:truncate hd
../vendor/bin/drush fcc_ham_data:truncate en
../vendor/bin/drush fcc_ham_data:truncate am

cd ../downloads

rm l_amat.zip
rm HD.dat
rm EN.dat
rm AM.dat

curl ftp://wirelessftp.fcc.gov/pub/uls/complete/l_amat.zip --remote-name
res=$?
if test "$res" != "0"; then
   echo "Curl command failed with: $res"
   exit $res
fi

unzip l_amat.zip HD.dat EN.dat AM.dat
res=$?
if test "$res" != "0"; then
   echo "Unzip failed with: $res"
   exit $res
fi
