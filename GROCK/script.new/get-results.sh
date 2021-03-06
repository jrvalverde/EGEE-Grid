#!/bin/sh
#
#	get-results.sh directory
#
#	Extracts the results from a grid tar file inside a job directory.
# This is designed to be used inside GROCK (GRid dOCK).
#
#	The problem: we get all the results as independent files for each
# job inside separate directories, one for each job. Each directory/job
# contains the I/O data for a different dataset. Output is stored as a
# gzipped tarball in a subdirectory. All result files follow the same
# format, which does NOT include info about the original dataset. What
# we want is to extract the results and tag them with info regarding the
# original data set.
#
#	Now: each dataset is composed of two files, one is constant along
# ALL the jobs (the probe molecule) while the other one is the different
# element that distinguishes each job. The directory holding the results
# for each job is named after this distinguishing component.
#
#	Hence: we extract the results file from the tarball in the given
# directory and use the directory name (same as distinguishing element in
# the data set) to tag every line of the results.
#
#	We tag at the end of the lines for one major reason: the component
# name has no standard length, and thusly it is less disruptive. It is also
# easier to select e.g. the highest scoring match, by grep'ping for '^   1'
#
#	Usage (simple):
#		get-results.sh COMPOUND
#
#	where COMPOUND is the name of the directory holding the results
#
#	USAGE (FULL):
#	TO GATHER ALL RESULTS AND SORT THEM BY ENERGY
#		cd $output_dir
#		# where $output_dir contains all the results collected
#		# each in its own subdir named after the distinguishing
#		# compound
#		ls | xargs -i -t get-results.sh {} | sort -r -n -k 2
#
#
# Initial, raw metod:
#  find . -name gramm-come.tar.gz -exec tar -zOxvf {} receptor-ligand.res \; |\
#         sort -r -n -k 2 > sorted.res
#
# Now, this has a problem: it does not save the receptor name and we need it.
# For this we need to know the receptor name...
#
# Let us go for xargs:
# 
# ls | xargs -i -t get-results.sh {} | sort -r -n -k 2
#
#    -i : go line by line and substitute {} by name
#    -t : print to stderr each processed name (useful for debugging and
#         tracing
#
#	AUTHOR: Jos� R. Valverde
#		EMBnet/CNB
#		jrvalverde@es.embnet.org
#
#	DATE:	10-Mar-2005
#
#  This library is free software; you can redistribute it and/or
#  modify it under the terms of the GNU Lesser General Public
#  License as published by the Free Software Foundation; either
#  version 2.1 of the License, or (at your option) any later version.
#  
#  This library is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
#  Lesser General Public License for more details.
#  
#  You should have received a copy of the GNU Lesser General Public
#  License along with this library; if not, write to the Free Software
#  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#	$Id: get-results.sh,v 1.1.1.1 2005/09/07 13:15:49 root Exp $
#	$Log: get-results.sh,v $
#	Revision 1.1.1.1  2005/09/07 13:15:49  root
#	GROCK High Throughput Docking on the Grid
#	
#
if [ -f $1/job_output/gramm-come.tar.gz ] ; then
    tar -zxvOf $1/job_output/gramm-come.tar.gz receptor-ligand.res | \
    	tail -n +32 | \
    	sed -e "s/\$/  $1/g" 
    # fix lack of \n on last line of results file.
    echo ""
fi
exit 1

