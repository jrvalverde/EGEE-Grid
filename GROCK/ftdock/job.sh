#!/bin/sh
#
#	Script to run 3d-dock/ftdock for a pair of proteins
#
#	(C) Jose R. Valverde, EMBnet/CNB. Dec, 2005
#
#	$Id$
#	$Log$
#
# Extract executable and auxiliary files
#
tar -zxf ftdock-in.tar.gz
rm -f ftdock-in.tar.gz
#
# clean up input PDB files of unneeded/unwanted data
# result is a clean pdb file named XXX.parsed
#
./preprocess-pdb.perl -nowarn -pdb ligand.pdb
./preprocess-pdb.perl -nowarn -pdb receptor.pdb
#
# find out molecule sizes and make bigger one static
#
echo "Computing molecule sizes"
psize=`grep "^ATOM " ligand.parsed | wc -l`
tsize=`grep "^ATOM " receptor.parsed | wc -l`
if [ $psize -gt $tsize ] ; then
    larger=ligand
    smaller=receptor
else
    larger=receptor
    smaller=ligand
fi
#
# dock the molecules and generate 10.000 pairs with 'quick' scores
#
echo "Running ftdock"
./ftdock -static $larger.parsed -mobile $smaller.parsed
echo "Scoring"
#
# rescore using best-matrix
#
./rpscore > rpscore_output
#
# build models for the best 10 matches
#
echo "Building best 10 models"
./build -b1 1 -b2 10
#
# compute centre of mass of each dock and represent all together as
# water molecules: helps see if docks cluster around some position
#
echo "Computing pair centres"
./centres
#
# Done. We are done. Clean-up and collect output files
#
rm -f PDB_Parse.pm PDB_Types.pm preprocess-pdb.perl
rm -f best.matrix i90_p05_d4.5_2dp.matrix
rm -f ftdock rpscore build centres
rm -f scratch_* rpscore_scratch* ligand.pdb receptor.pdb
#
tar -zcf ftdock-out.tar.gz ligand.fasta ligand.parsed \
        receptor.fasta receptor.parsed \
	ftdock_global.dat ftdock_rpscored.dat \
	centres.pdb Complex_*g.pdb

