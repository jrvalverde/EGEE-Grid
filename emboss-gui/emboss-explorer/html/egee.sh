#!/bin/bash

# build input package
#	This will include job.sh and job.jdl, but they're lightweight
#	and job.sh will delete job.tgz job.jdl and job.sh first thing
#	after unpacking to clean up the input sandbox
tar -zcvf job.tgz *

# submit the job and save its ID
edg-job-submit -o job.id job.jdl

# Wait for job to finish -Done (Success or Fail)-
/bin/echo -n "Waiting"
edg-job-status -i job.id | grep -q "Current Status:     Done"

while [ $? -eq 1 ] ; do
    /bin/echo -n "."
    sleep 15
    edg-job-status -i job.id | grep -q "Current Status:     Done"
done

/bin/echo ""

# Get job output into local dir and cleanup Job details
edg-job-get-output -i job.id --dir .
#rm -f job.id
#rm -f edglog.log
#rm -f job.sh
#rm -f job.jdl

# Move job output to local dir and cleanup output directory
mv -i ${USER}_*/* .
rmdir ${USER}_*

# Extract output package and cleanup it
tar -zxvf output.tgz
rm output.tgz

# set up job stderr and stdout as emboss-gui expects them
mv err .stderr
mv out .stdout
