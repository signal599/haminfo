#!/bin/bash

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

cd $SCRIPT_DIR
./download-fcc-data.sh

res=$?
if test "$res" != "0"; then
   exit
fi

./update-fcc-data.sh
