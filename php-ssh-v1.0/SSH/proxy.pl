#!/usr/local/bin/perl -w

$u="UUUUUU";
$h="villon.cnb.uam.es";
$rem = $u."\@".$h;
$p="XXXXXXXXXX";
$pp="YYYYYYYYYYYYYY";
$vo="biomed";

open(REMOTE, "|ssh -x -T localhost `pwd`/SSH.sh $rem");
print REMOTE <<END;
$p
voms-proxy-init --voms=$vo
$pp
exit
END
close(REMOTE);
exit;