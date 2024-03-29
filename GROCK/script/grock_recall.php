#!/usr/bin/php
<?php
/**
 *  GROCK - GRid dOCK
 *
 *  A program to perform high-thoughput docking on the Grid.
 *
 *  The goal of this program is to provide a convenient way to generate
 * docking searches of a probe molecule against a database of target
 * molecules.
 *
 *  The probe may be a protein or a drug or small compound. The target
 * database to be searched may be a set of protein or small compound
 * structures.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package 	grock
 * @author  	David Garcia <david@cnb.uam.es>
 * @author  	Jose R. Valverde <david@cnb.uam.es>
 * @copyright 	CSIC
 * @license 	../c/gpl.txt
 * @version 	$Id$
 * @see     	config.php
 * @see     	grock_lib.php
 * @see     	dock.php
 * @see     	processor.php
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 0.0
 */

// add our install dir to the include path
$path=dirname($argv[0]);
set_include_path($path . PATH_SEPARATOR . get_include_path());

require_once('config.php');
require_once('grock_lib.php');
require_once('dock.php');

$debug = TRUE;
$debug_grid = TRUE;
$debug_ssh = TRUE;

// we are intended to be run in the background
//  all our putput will be to a special file
$output = fopen('grock_output', 'w+');
if (! $output)
    grock_letal("GROCK", "Can not open output file");

grock();

fclose($output);


/**
 *  Grock_recall
 *
 *  Will work on the current directory IFF run interactively (which
 *  is strongly discouraged as it may take days to weeks to finish)
 *  or run in the background WITHOUT a controlling terminal.
 *
 *	Interactive run:
 *		grock_recall
 *		please enter password for user@server:
 *		...
 *
 *	Background run:
 *		ssh -x -T localhost "(cd `pwd` ; \
 *			php /path/to/grock_recall.php \
 *			>> output 2>> error)&" &
 *
 *  Expects:
 * -  	a probe molecule named 'probe.pdb' in the CWD
 * -	a series of parameters in a file named 'options'
 *
 *  The 'options' file must be in the current directory, and contain
 * a series of option=value pairs, one on each line, divided in
 * two sections:
 * - first comes the authorization section containing details needed
 * to log in and activate the grid
 * - an empty line separates both sections
 * - last come the program actual options: which database to search,
 * docker to use and dosck-specific parameters.
 *
 * E.g.:
 *	server=server.example.org
 *	user=someone
 *	password=mypassword
 *	passphrase=mypassphrase
 *
 *	database=pdb90
 *	probe_type=ligand
 *	docker=gramm
 *	resolution=low
 *	representation=
 *	probe=myprobe
 *
 *  See processor.php for more details.
 *
 *  Once the options have been processed the file is deleted. NOTE that
 * since the 'options' file contains user authentication data, it should
 * be readable only by the user running GROCK and is anyway very sensitive.
 * For this reason, GROCK deletes the file as soon as it is read.
 *
 *  We might do without a file getting all in the command line, but this
 * would be much worst as any user would then access the auth info by
 * issuing a ps(1) while grock is running.
 *
 *  We might read from stdin but then we would have a problem running in
 * the background from PHP (sic).
 *
 *  Assuming jobs have been sent, we enter a timed loop to collect the
 * job output. If we can not collect all job output back before the
 * predefined timeout, unfinished jobs will be ignored.
 *
 *  Each iteration we'll reap all finished jobs output and recompute the
 * sorted list of best scores so the user may follow the progression of 
 * the search on the fly.
 *
 *  GROCK will generate a number of directories and files in the current
 * directory. Since these may be way too many, it is advisable to run
 * GROCK in a separate directory that is empty except for the 'probe.pdb'
 * and 'options' file to start with.
 *
 *  In the initial phase, GROCK has generated a directory for each docking job
 * to be submitted, i.e. one for each target molecule in the database, named
 * after the target entry name. Each directory will contain all the material
 * related to docking the probe and target molecules.
 *
 *  Additionally GROCK will generate a listing of target molecules being
 * explored, a status file to state the processing step it is at, and two
 * auxiliary trace files, one for normal output and a second for 'error'
 * or 'abnormal' output, which may be helpful in case of need to diagnose
 * a failure.
 */
function grock()
{
    global $debug;
    global $output;
    global $app_dir;
    
    // work on the current directory: it will become the session
    // directory by default
    $session_id = basename(getcwd());
    
    // XXX JR XXX
    //	we might as well get all parameters in one line as if it where
    //	a URL and parse them with parse_str();

    // First get run parameters from options file
    //	We might use $options = STDIN, but this way we may be run in the
    //	background and delete the options file ASAP
    // start with auth data
    $opt_file=fopen('options', 'r'); 
    if ($opt_file == FALSE) 
    	grock_letal('GROCK', 'Nothing to do!');
    $opt='auth';
    while (! feof($opt_file)) {
    	$inline = trim(fgets($opt_file), "\r\n");
	// an empty line signals the start of options data
	if ($inline == '') {
	    $opt='options';
	    continue;
	}
	fwrite($output, "\n$inline");
	
	// format is param=value
	$optval = explode('=', $inline, 2);
	${$opt}[$optval[0]] = $optval[1];
    }
    fclose($opt_file);
    
    //unlink('options');
    
    if ($debug) {
    	fwrite($output, "\nsession is $session_id\n");
	fwrite($output, "called from ". basename(getcwd()) . "\n");
    	//fwrite($output, print_r($auth,TRUE));
    	fwrite($output, print_r($options, TRUE));
    }
    
    // submit all jobs
    // we consider it has already been submitted and we are trying
    //	only to recover output.
    // grock_submit($session_id, $options, $auth);
    
    // try during at most 4 days (24 * 4) = 96
    // XXX JR XXX
    //	    This should be data- docker- and database-specific
    //	    e.g.:
    //	    	database_njobs 
    //	    	* docker_maxjoblen($probe_size) 
    //	    	* 1.20      	    	### allow for 20% resubmitted jobs
    //	    	/ expected number of working nodes
    //	    Note, whith docker_maxjoblen() we may tune the JDL file for timeouts
    //	Currently, 4 days works for a small compound against PDB40.
    //	With current code, it would require 40 days for a protein against PDB40
    //	We NEED to have data stored on remote SE's. THIS IS A MUST.
    //
 
    $i = 0;
    do {
	if ($debug) fwrite($output, "Checking job output availability\n");
    	$done = grock_get_output($session_id, $auth);

    	// generate scores file(s)
    	dock_generate_scores($options['docker']);
	
	if ($done)
	    break;
	grock_set_status('partial');
    	sleep(3600); 	// 3600: wait 1 hour before checking results again
	$i++;
    } while ($i < 960);
    grock_set_status('finished');

    exit;
}

?>
