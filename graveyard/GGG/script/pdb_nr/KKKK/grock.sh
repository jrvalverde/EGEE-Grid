#/bin/bash 
gunzip gramm-go.tar.gz
tar -xf gramm-go.tar
export GRAMMDAT=`pwd`/gramm
echo $GRAMMDAT
./gramm/gramm scan coord
tar -cf gramm-come.tar *.pdb *.res *.log *.gr
gzip gramm-come.tar
