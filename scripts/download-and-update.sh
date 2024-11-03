#! /bin/bash

cd /srv/haminfo/scripts

./download-fcc-data.sh
res=$?
if test "$res" != "0"; then
   exit
fi

./update-fcc-data.sh
