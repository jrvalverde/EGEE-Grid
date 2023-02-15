#!/bin/sh
tar -zxvf job.tgz               # extract contents of job with appropriate perms
rm -f job.tgz			# and remove job input package
rm -f job.sh job.jdl		# and ourselves to clean up output

# set up the environment to use installed package
export EMBOSS_ACDROOT=$VO_BIOMED_SW_DIR/GrEMBOSS-4.0/share/EMBOSS/acd
export PLPLOT_LIB=$VO_BIOMED_SW_DIR/GrEMBOSS-4.0/share/EMBOSS
export LD_LIBRARY_PATH=/lib:/usr/lib:$VO_BIOMED_SW_DIR/GrEMBOSS-4.0/lib:$LD_LIBRARY_PATH
export PATH=$VO_BIOMED_SW_DIR/GrEMBOSS-4.0/bin:$PATH

# do the work
wossname -auto | tee wn.output

showdb | tee sdb.output

pepwheel -graph=cps -auto atp6_human.fasta

# pack only interesting results
tar -zcvf output.tgz *
