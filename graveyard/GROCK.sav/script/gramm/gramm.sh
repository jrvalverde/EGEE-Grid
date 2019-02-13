#/bin/bash 
gunzip gramm-go.tar.gz
tar -xf gramm-go.tar
export GRAMMDAT=`pwd`
echo $GRAMMDAT
./gramm scan coord
tar -cf gramm-come.tar *.pdb *.res *.log 
gzip gramm-come.tar
