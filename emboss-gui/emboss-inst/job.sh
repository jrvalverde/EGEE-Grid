#!/bin/sh
tar -zxvf job.tgz		# extract contents of job with appropriate perms

# set up the environment to use installed package
export EMBOSS_ACDROOT=$VO_BIOMED_SW_DIR/emboss/share/EMBOSS/acd
export PLPLOT_LIB=$VO_BIOMED_SW_DIR/emboss/share/EMBOSS
export LD_LIBRARY_PATH=/lib:/usr/lib:$VO_BIOMED_SW_DIR/emboss/lib:$LD_LIBRARY_PATH
export PATH=$VO_BIOMED_SW_DIR/emboss/bin:$PATH

# do the work
wossname -auto

# pack only interesting results
touch output.tgz
