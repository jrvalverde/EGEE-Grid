#!/bin/sh

tar -zxvf job.tgz		# extract input data

# set up the environment to use installed package
export LD_LIBRARY_PATH=/lib:/usr/lib:$VO_BIOMED_SW_DIR/tinker-4.2/lib:$LD_LIBRARY_PATH
export PATH=$VO_BIOMED_SW_DIR/tinker-4.2/bin:$PATH

echo "parameters     $VO_BIOMED_SW_DIR/tinker-4.2/params/amber99" > tinker.key

ls
cat tinker.key
echo "----"

# do the work
pdbxyz coordinates.pdb 
minimize coordinates < ./min.in
anneal coordinates < ./ann.in
analyze coordinates < ./ana.in
xyzpdb coordinates

# pack only interesting results
tar -zcvf output.tgz coordinates.pdb* analyze.out anneal.out minimize.out coordinates.xyz*
