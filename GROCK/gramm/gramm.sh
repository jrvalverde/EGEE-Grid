#/bin/bash 
gunzip gramm-go.tar.gz
tar -xf gramm-go.tar
export GRAMMDAT=`pwd`
echo $GRAMMDAT
./gramm.lnx scan coord
tar -cf gramm-come.tar receptor-ligand_*.pdb *.res *.log 
gzip gramm-come.tar
