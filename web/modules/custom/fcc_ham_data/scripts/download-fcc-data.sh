#!/bin/bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
WEB_ROOT=$SCRIPT_DIR/../../../../../web

cd $WEB_ROOT/../downloads

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
