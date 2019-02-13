#!/bin/sh

( /opt/structure/modeller/run.csh $* ; touch done ; rm modeller.log ; rm modeller.sh ) >> modeller.log 2>&1 &

exit


