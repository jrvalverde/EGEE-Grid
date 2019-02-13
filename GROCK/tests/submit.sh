#!/bin/sh

(sleep 10; pkill -P $$ ) &	# maybe it should be $PPID (haven't tried)

#edg-job-submit $*
yes > /dev/null

