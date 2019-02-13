#!/bin/bash

/opt/structure/ftdock/scripts/preprocess-pdb.perl -nowarn -pdb probe.pdb
/opt/structure/ftdock/scripts/preprocess-pdb.perl -nowarn -pdb target.pdb
psize=`grep "^ATOM " probe.parsed | wc -l`
tsize=`grep "^ATOM " target.parsed | wc -l`
if [ $psize -gt $tsize ] ; then
    larger=probe
    smaller=target
else
    larger=target
    smaller=probe
fi
/opt/structure/bin/ftdock -static $larger.parsed -mobile $smaller.parsed > 3ddock_output 
cp /opt/structure/3D_Dock/progs/i90_p05_d4.5_2dp.matrix best.matrix
/opt/structure/bin/rpscore > rpscore_output
