#!/bin/sh
tar -zxvf job.tgz		# extract contents of job with appropriate perms

# set up the environment to use installed package
export LD_LIBRARY_PATH=/lib:/usr/lib:$VO_BIOMED_SW_DIR/tinker-4.2/lib:$LD_LIBRARY_PATH
export PATH=$VO_BIOMED_SW_DIR/tinker-4.2/bin:$PATH

echo "parameters     $VO_BIOMED_SW_DIR/tinker-4.2/params/mm3pro" > crambin.key

# do the work
sh crambin.run

# pack only interesting results
tar -zcvf output.tgz crambin.xyz crambin.seq
