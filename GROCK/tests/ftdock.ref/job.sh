./preprocess-pdb.perl -nowarn -pdb ligand.pdb
./preprocess-pdb.perl -nowarn -pdb receptor.pdb
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
echo "Running ftdock"
./ftdock -static $larger.parsed -mobile $smaller.parsed > 3ddock_output 
echo "Scoring"
./rpscore > rpscore_output
