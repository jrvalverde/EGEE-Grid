#!/bin/sh

tar -zxvf job.tgz		# extract contents of job with appropriate perms

# set up the environment to use shipped shared libraries
export LD_LIBRARY_PATH=/lib:/usr/lib:./tinker/lib:$LD_LIBRARY_PATH
export PATH=./tinker/bin:$PATH

# do the work
pdbxyz coordinates.pdb 
minimize coordinates < ./min.in
anneal coordinates < ./ann.in
analyze coordinates < ./ana.in
xyzpdb coordinates

# pack only interesting results
tar -zcvf output.tgz coordinates.pdb_3 analyze.out anneal.out minimize.out
