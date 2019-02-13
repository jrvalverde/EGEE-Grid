<?php

/**
 * GROCK functions
 *
 * Working routines used to carry on GROCK tasks. This module contains
 * functions used both by GROCK and by GROCK-output analyzer tools that
 * generate user reports.
 *
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
 * @author  	Jose R. Valverde <jr@cnb.uam.es>
 * @copyright 	CSIC
 * @license 	../c/lgpl.txt
 * @version 	$Id$
 * @see     	config.php
 * @see     	util.php
 * @see     	ssh.php
 * @see     	grid.php
 * @see     	database.php
 * @see     	dock.php
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 0.0
 */

require_once("config.php");	    // installer preferences
require_once("util.php");     // general utilities
require_once("ssh.php");  	    // to connect to back-end
require_once("grid.php"); 	    // to manipulate the grid
require_once("database.php");       // to manipulate databases
require_once("dock.php"); 	    // to manipulate docking jobs


/**
 * Maximum number of times a job should be retried before giving up
 */
define("MAX_TRIES", 5);     	    // 3 should be enough



/**
 *  Submit a high throughput docking search using a remote grid
 *  back-end.
 *
 *  We expect to be called with a session name that identifies
 * a directory in the working area which already contains the
 * probe molecule file as 'probe.pdb'.
 *
 *  The parameters allow us to
 *  - know the original name of the probe file
 *  - select the database to be searched
 *  - select the docking program to use and all its options
 *  - identify ourselves to the remote back-end
 *
 *  With this data in hand, we will be able to generate all
 * the separate jobs for performing the search with as much
 * parallelism as possible, and launch them to a remote
 * back end for processing.
 *
 *  In doing so, we will maintain a file with our status so
 * work may be monitored by an external process, a listing
 * file detailing all target molecules checked and a log file
 * in HTML format with a progress report (this last one may
 * be superfluous).
 *
 *  $options is an associative array containing
 *  	'probe'
 *  	'database'
 *  	'docker' => 'gramm'
 *  	    'resolution'
 *  	    'representation'
 *
 *  $auth is an associative array containing
 *  	'server'
 *  	'user'
 *  	'host'
 *  	'password'
 *  	'passphrase'
 *
 *  @param string $session_id	Session identifier
 *  @param mixed $options   	An array containing the user options
 *  @param mixed $auth	    	An array containing user authentication data
 */ 
function grock_submit($session_id, $options, $auth)
{   	
    // we use globals to retrieve configuration file values
    global $db_dir;
    global $debug;
    global $output;

    if ($debug) log_message("Running GROCK\n-------------\n\n");
    // for clarity
    $probe = $options['probe'];
    $database = $options['database'];

    // THIS SHOULD PROBABLY BE HANDLED IN GROCK TOPLEVEL ROUTINE
    $grock_status = grock_get_status();
    if ($debug) log_message("\nInitial status = ".$grock_status."\n");

    if (($grock_status == 'submitted') || 
        ($grock_status == 'partial')) {
        // We have been called to recover a dead session.
    	// All work here has already been done except for grid (re)activation
    	activate_grid($sesion_id, $auth);
    	return;
    }
      
    if ($grock_status == 'finished') {
    	// if we are being called then it may only be because
	// the previous run did NOT recover all finished jobs
	// in time and the user wants to reap them again.
	activate_grid($session_id, $auth);
	grock_set_status('submitted');
	return;
    }
    
    if (($grock_status == FALSE) || 
        ($grock_status == 'aborted') ||
        ($grock_status == 'started') ||
        ($grock_status == 'starting')) {
    	// we have been called 'de novo' or to recover a deceased
	// session that died before or while creating the jobs.
	// If a previous run was aborted there is no way to know
	// how far it went, so we run all again.
	
	grock_set_status('started');

    // THIS SHOULD PROBABLY HAVE BEEN HANDLED IN GROCK TOPLEVEL ROUTINE

	// Get data files HEURISTIC
	//  We have two cases here:
	//	    1- If the user selected a PDB database then his probe
	//	    data will be mobile during docking, and the database
	//	    macromolecules will be static. We want the user data
	//	    uploaded to $session_directory/ligand.pdb
	//
	//	    2- If the user selected a compound database, then
	//	    his probe data is a macromolecule that must remain fixed
	//	    and should be uploaded as $session_directory/target.pdb
	//	    while thedatabase compounds whill be mobile and stored
	//	    on ligand.pdb
	//
	// JR NOTE: if both files are in PDB format we may count ATOM lines,
	// and assign fixed and mobile molecules according to size. Problem
	// then is knowing which is which.. Think of this.
	//
	if (strcmp(substr($database, 0, 3), 'pdb') == 0) {
    	    //$probe_type = 'ligand';
    	    $user_file = 'ligand.pdb';  	// name to assign the user probe file
	    $db_file = 'receptor.pdb';  	// name to assign the DB target file
	}
	else {
    	    //$probe_type= 'receptor';
    	    $user_file = 'receptor.pdb';	// so that docker knows how to treate
	    $db_file = 'ligand.pdb';    	// the user and db molecules.
	}
	if ($debug) log_message("\n    Your data will be $user_file and matched against $db_file\n");

	// ensure user data goes into correct file
	grock_set_probe_type($user_file);
	// $probe is the original user file name
	if ($debug) log_message("\n    Successfully moved $probe to $user_file\n");

	$jobs = 0;
	$status = EODB;

	$status = db_get_entry($database, $db_file, $target, $description);

    	// the new need for (int) casting here mystifies me!!!
	while ( (int)$status != (int)EODB ) {

	    if ($status == FALSE) {
		log_message("\n    WARNING: skipping entry $target\n");
    		$status = db_get_entry($database, $db_file, $target, $description);
		// if this entry could not be retrieved, ignore it
		continue;
	    }
	    // Prepare a docking job to be sent over to the grid
	    //
	    //  The molecular data are already stored using always
	    // the same file names 'ligand.pdb' and 'receptor.pdb'
    	    $jobs++;
	    if (!dock_create_job($probe, $target, $options)) {
    		grock_letal("GROCK submit", "Couldn't create job for $probe+$target on $session_id");
	    }
	    unlink($db_file);   // no longer needed
    	    $status = db_get_entry($database, $db_file, $target, $description);
	}
        
	if ($jobs == 0)
    	    grock_letal("GROCK submit", "Couldn't get any molecule to search");

#        if ($debug) {
#    	    log_message("\n    Contents of $session_id\n");
#    	    exec("ls -l *", $out);
#    	    log_message(print_r($out, TRUE));
#    	    log_message("\n");
#        }
    }
    
    if ($grock_status == 'submitting') {
      // we must have been invoked to restart a deceased session
      // that died in the submitting status.
      // For the time being we'll fake submission recovery and let
      // the job-output-collection restart procedures take care of
      // the recovery.
      grock_set_status('submitted');
      return;
    }
    
    // OK, we are ready, let's go for the jugular
    // we need to pass $session_id for the remote back-end, do we?
    submit_session_jobs($session_id, $auth); 
    
    grock_set_status('submitted');
}


/**
 *  Ensure the user probe molecule has the appropriate name for further
 * processing by GROCK.
 *
 *  GROCK works internally using 'canonical' names for all its processing
 * (this makes the logic somewhat simpler, although it will probably be
 * removed at some future point).
 *
 *  This routine expects the user molecule to be located in the current
 * directory, named 'probe.pdb', and moves it to the actual name that
 * GROCK will need to process it.
 *
 *  The need to rename stems from the fact that the molecule will be 
 * processed as a ligand (mobile) or receptor (static) molecule depending
 * on its size and the database selected. Hence we want to make sure it
 * is identified appropriately for the processing to be applied.
 *
 *  @access private
 *
 *  @param string $user_file the name to assign the newly uploaded data file.
 *
 */
function grock_set_probe_type($user_file)
{
    global $debug;
    global $output;
    
    if ($debug) log_message("Moving user data to ".getcwd()."/$user_file\n");

    clearstatcache(); 
    if (! file_exists('./probe.pdb')) {
    	grock_letal("Get probe data", "The coordinates of the probe molecule are not available!");
    }
    if (copy('./probe.pdb', $user_file)) {
    	return TRUE;
    } 
    else {
    	grock_letal("Get probe data", "Could not access the coordinates of your probe molecule");
    }
}


/**
 * Activate the grid on the remote UI node.
 *
 *  Activate a grid session on the remote back end, using $session_id as
 * the working directory and location of log files.
 *
 *  This function will open a connection and create a proxy that is
 * valid for 48 hours. That should be enough for running our jobs.
 *
 *  @param string $session_id	session_id
 *  @param string $auth The authentication token to connect to a remote back-end
 */
function activate_grid($session_id, $auth)
{
    global $local_tmp_path;
    global $gridUI_tmp_path;
    global $debug;
    global $output;
    global $grid_server;
    
    $debug = FALSE;
    
    if ($debug) {
    	log_message("\nActivating the Grid\n");
    	//$debug_sexec = TRUE;
    	//$debug_grid = TRUE;
    }
    
    $eg = new Grid;
    
    if ($eg == FALSE) {
     	grock_letal("Activating grid", 
	"Cannot create a new grid object!\n");
    }
    $eg->set_host($auth['host']);
    $eg->set_user($auth['user']);
    $eg->set_password($auth['password']);
    $eg->set_passphrase($auth['passphrase']);
    $eg->set_work_dir("$gridUI_tmp_path/$session_id");
    $eg->set_error_log("$local_tmp_path/$session_id/error-output.txt");
    $eg->connect();
    

    $eg->initialize(960, 0); 	// ask for a 40 days proxy
    if ($debug) {
        echo "    ".$eg->get_init_status();
    	log_message("\n");
    }
    $eg->destruct();
}

/**
 *  Submit all session jobs at once to the remote back-end.
 *
 *  This function expects a session_id which identifies a directory
 * holding a collection of jobs to be submitted on the local temporary
 * work directory.
 *
 *  All jobs are sent to the remote back-end, stored there on the remote
 * temporary work directory and then launched for execution on the grid.
 *
 *  Since we are to use a remote back-end connected to the Grid, we need
 * to know all the authentication details:
 *  	$auth is an array that holds
 *  	    'server' => user@server identifier
 *  	    'password' => password to use to connect to 'server'
 *  	    'passphrase' => passphrase needed to unlock the Grid from 'server'
 *
 *  In order to convert a session_id into a directory we also need to access
 * the GLOBALS
 *  	$local_tmp_path
 *  	$gridUI_tmp_path
 *
 *  As a SIDE EFFECT, the LCG-tools will be installed on the remote temporary
 * work directory so they may be used for submission, AND BE LEFT there (so
 * they might be shared by other instances).
 *
 *  If anything goes wrong, clean up local and remote directories and die.
 *
 *  XXX JR XXX NOTE: reverse-engineer lcg-submitter and rewrite using PHP
 *
 *  @param  string $session_id     The identifier for our current session
 *  @param  mixed  $auth   	    an array holding authentication information
 *
 *  @return void if everything OK, die with an error otherwise.
 */
function submit_session_jobs($session_id, $auth)
{
    global $debug;
    global $debug_sexec;
    global $local_tmp_path;
    global $gridUI_tmp_path;
    global $app_dir;	    	// to locate LCG-tools
    global $output;
    
    //$debug= TRUE;
    grock_set_status('submitting');
    if ($debug) {
    	log_message("\nSubmitting session jobs\n\n");
	flush();
	//return;     // DO NOT SUBMIT ACTUALLY
	$debug_sexec = TRUE;
    }
    
    $local_dir = "$local_tmp_path/$session_id";
    $remote_dir = "$gridUI_tmp_path/$session_id";
    
    // connect to remote UI
    $rmt = new SExec($auth['server'], $auth['password']);
    if ($rmt == FALSE) {
    	grock_letal("Submit session jobs",
	    "Could not connect to remote system ${auth['server']}");
    }
    if ($debug) log_message("    Connected to ${auth['server']}\n");
    
    // Copy the whole session directory with all the job files 
    if (! $rmt->ssh_copy_to($local_dir, $remote_dir, $out)) {
    	$rmt->destruct();
    	// XXX clean-up directories here
	if (! $debug)	grock_clean();
	if ($debug) log_message(print_r($out, TRUE));
    	grock_letal("Submit session jobs",
	    "Could not copy jobs to remote back-end for execution");
    }
    if ($debug) log_message("    Copied session $session_id\n");
    
    // Remove files that we don't need in the remote directory.
    $cmd = "(cd $remote_dir; rm -f grock_error grock_output ".
    	    "grock_status options ligand.pdb receptor.pdb ".
	    "target_list.txt )";
    $exit = $rmt->ssh_exec("$cmd", $out);
    
    if ($exit != 0) {
    	log_warning("GROCK submission: Could not remove unneeded files from remote back-end");
	log_message("$out\n");
    }
    
    unset($out);
    if ($debug) log_message("    Removed unneeded files\n");

    // Install LCG tools
    //   We will be using auxiliary tools to launch all the jobs 
    //   'en masse'. Since they are not yet on the standard install,
    //   we must copy them over so we can use'm.
    //      The submitter will loop over all subdirs in $session_id,
    //      hence it must be placed outside $session_id.
    //	    We may as well share them between sessions: first come
    //	installs the tools, followers use them.
    $lcg_dir = "$app_dir/lcg-tools";
    // is it already there? 
    $exit = $rmt->ssh_exec("test -x $gridUI_tmp_path/lcg-tools/lcg-submitter-biomed-beta11.pl", $out);
    unset($out);    // we don't care about it
    if ($exit != 0) {
    	if ($debug) log_message("    Installing LCG-tools\n");
    	// It is not already there yet. Install it as $gridUI_tmp_path/lcg-tools.
    	if (! $rmt->ssh_copy_to($lcg_dir, "$gridUI_tmp_path/lcg-tools", $out)) {
    	    // we are toasted: clean and exit
	    if (! $debug) {
	    	$rmt->ssh_exec("rm -rf $remote_dir", $out);
	    	grock_clean();
	    }
	    $rmt->destruct();
	    if ($debug) log_message(print_r($out, TRUE));
    	    grock_letal("Submit session jobs",
	    "Couldn't make a copy of the LCG tools to $session_id<br>\n");
	}
    }
    
    // Prepare the grid for work
    activate_grid($session_id, $auth);

    // The submiter tool launches all the jobs.
    if ($debug) log_message("    Submitting jobs with lcg-submitter\n");
    $cmd = "/usr/bin/perl $gridUI_tmp_path/lcg-tools/lcg-submitter-biomed-beta11.pl -session $remote_dir";
    if ($debug) {
    	// save output on remote UI tmp dir for manual monitor with 'tail -f'
	log_message("    --> $cmd > $gridUI_tmp_path/$session_id/submitter_output 2>&1\n");
    	//$exit = $rmt->ssh_exec("$cmd > $gridUI_tmp_path/$session_id/submitter_output 2>&1", $out);
	$exit = $rmt->ssh_exec("$cmd", $out);
    } else
    	$exit = $rmt->ssh_exec("$cmd", $out);
	
    if ($exit != 0) {
	if (! $debug) {
	    // XXX we'll leave the lcg-tools cached remotely
	    $rmt->ssh_exec("rm -rf $remote_dir", $out);
	    grock_clean();
	}
    	$rmt->destruct();
	if ($debug) log_message(print_r($out, TRUE));
    	grock_letal("Submit session jobs",
	    "An error occurred during submission of $session_id");
    }

    if ($debug) {
    	// Stop here (move me around for debugging different sections)
	$rmt->destruct();
	log_message("\n");
	return;
    }
    
    $rmt->destruct();
    if ($debug) log_message("\n");
}

/**
 *  Set work progression status
 *
 *  Ours is a lengthy process, we do not want users to be tied
 * to a terminal waiting for this to finish. Since we are web
 * based, this is done by a separate web page, hence we need 
 * some persistent way to monitor work progress.
 *
 *  This routine isolates the persistence implementation of
 * progression status.
 *
 *  @param string $status   status to set
 *
 *  @return boolean  TRUE if all goes OK,
    	    	    FALSE and a warning if something goes awry
 */
function grock_set_status($status) {
    $sf = fopen('grock_status', 'w');
    if (! $sf) {
    	log_warning('GROCK set status: Can not create status file');
	return FALSE;
    }
    if (! fwrite($sf, $status)) {
    	log_warning('GROCK set status: Can not update status');
	return FALSE;
    }
    fclose($sf);
    flush();
    return TRUE;
}

/**
 *  Obtain status of GROCK process
 *
 *  As we need to provide access to a running GROCK jobs from
 * indpenendent instances of web pages invoked by the user at
 * his/her convenience, we need some way to access a presistent
 * instance of the progression status.
 *
 *  This routine allows us to access persistent storage hiding
 * implementation details.
 *
 *  @return string status of running GROCK or FALSE if the status
 *  	could not be obtained (denotes a possible error condition). 
 */
function grock_get_status()
{
    clearstatcache(); 

    if (!file_exists('grock_status'))
    	return FALSE;

    $sf = fopen('grock_status', "r");
    if (! $sf)
    	return FALSE;
    $status = fgets($sf);
    fclose($sf);
    return $status;
}


/*
 * POSSIBLE JOB STATUS (see LCG-2 user's guide)
 * 
 *  Before processing a job will be
[6]Current Status:     Submitted
 *  After submission a job will be
[6]Current Status:     Waiting
[7]reached on:
 *
[6]Current Status:     Ready
[7]Status Reason:      unavailable
 *
[6]Current Status:     Scheduled 
[7]Status Reason:      Job successfully submitted to Globus
 *
[6]Current Status:     Running 
 *
 * After completion it will be
[6]Current Status:     Done (Success)
[7]Exit code:          0
[8]Status Reason:      Job terminated successfully
 *
 *  After edg-job-get-output it will be
[6]Current Status:     Cleared
[7]Status Reason:      user retrieved output sandbox
 *
 * After being aborted by the WMS (e.g. proxy expired)
[6]Current Status:     Aborted
 *
 * After cancellation it will be
[6]Current Status:     Cancelled
[7]Status Reason:          Aborted by user
 *
 *
 * It may also happen that job-status fails because of other reasons
 * (e.g. network problems or remote node down). Job-status exits with
 * a value of 0 if the status could successfully be retrieved, >0 if
 * there were errors for each specified job-id, or <0 if there were
 * partial errors (e.g. status of one job could be got but not for the
 * others).
 */

/**
 *  Get output for GROCK jobs
 *
 *  This function will loop over all jobs and check their status. If
 * they are finished, then it will check the output code:
 * 
 * - if not OK then resubmit the job once
 * - if OK then get its output
 *
 *  XXX If timeout, jobs should be killed and resubmitted as well... Errr.
 *  	How do we timeout? Maybe the JDL is the answer... But how long do
 *  	we allow a job to run?
 *
 *  After it has finished the loop, if all output has been collected, 
 * then it will return TRUE.
 *
 *  If some output could not be collected because either the jobs
 * were not completed, they terminated with error or the output is
 * not available, then return FALSE
 *
 *  NOTE: that is too simplistic and it might make sense to 
 * indicate on return whether
 *  - there are incomplete jobs
 *  - there are error-terminated jobs
 *  - there are terminated jobs with no output
 *  - everything went OK
 *
 *  This function must be called repeatedly and periodically as
 * otherwise we risk a job being completed but its results having
 * been lost/discarded for some reason.
 *
 *  @param string $session_id the session whose jobs we will monitor
 *  @param array $auth the authentication information
 *
 *  @return boolean TRUE if all output has been collected,
 *  	    	    FALSE otherwise
 */
function grock_get_output($session_id, $auth)
{
    global $output;
    global $debug;
    global $local_tmp_path;
    global $gridUI_tmp_path;
    
    $debug = TRUE;
    
    if ($debug) 
    	log_message("\nGrock get output\n\n    connecting to the Grid\n");
    $eg = new Grid;
    if ($eg == FALSE) {
    	// bad luck this time, may be next time we are called we'll be
	// luckier
     	warning("Activating grid: ".
	"Cannot open connection to Grid back_end $grid_server!\n");
	return FALSE;
    }
    
    $eg->set_host($auth['host']);
    $eg->set_user($auth['user']);
    $eg->set_password($auth['password']);
    $eg->set_passphrase($auth['passphrase']);
    $eg->set_error_log("$local_tmp_path/$session_id/error-output.txt");
    $eg->set_work_dir("$gridUI_tmp_path");
    $eg->connect();
    // XXX DAVID XXX
    // a 2h window is not enough, this line activates a new proxy init 
    // with the hours indicated, the old grid-proxy init remains <defunct>
    // NOTE that this seems to be a change in behaviour: formerly proxy time
    // would be preserved or extended, not it is set to the shortest
    // interval. Meaning we must rethink this logic.
    //	It was OK when proxy time would not decrease: a magic number here
    // did no harm. It isn't now, and the magic number should be substituted
    // by either a constant or a variable.
    //$eg->initialize(2, 0); 	// a 2h window should be enough
    $eg->initialize(300, 0); 	
    
    // we are called from within $session_id, but need
    // to tell the Grid to access directories in the
    // remote corresponding session directory
    if ($debug) log_message("    Defining remote session as $session_id\n");
    $eg->session_define($session_id, $session_id);
    
    $tdb = fopen("target_list.txt", "r");
    if (!$tdb) {
    	// we're toasted, we won't be able to even show any results
	//  XXX JR XXX at this point a recovery attempt to build the 
	//  target_list.txt file scanning the subdirectories might be
	//  in place.
    	grock_letal("Get Results", "Cannot read the list of database molecules screened.");
    }
    
    // This is a centinel: the following loop will set it to false
    // if it detects any unfinished job.
    $complete = TRUE;
    
    while (! feof($tdb)) {
    	$target = strtok(trim(fgets( $tdb )), ' ');
	if (strcmp($target, '') == 0)	// ignore blank lines
	    continue;

	// check job status
	unset($out);	    	    	// clean up $out from previous run
	
	// NOTE this check is here to save a remote call to job_status():
	// if we already got the output for this job there is no sense
	// in remotely querying its status: it is done and OK.
	if (is_dir("$target/job_output"))   	// if already done
	    continue;
	    
	if ($debug) log_message("    Checking $target\n");
	
        if (!$eg->job_status($target, $out, $session_id)) {
	    // e.g. network down.
	    log_warning("Couldn't get status of $target");
    	    if (job_resubmit($eg, $session_id, $target)) {
	    	// give it a chance to run
		$complete = FALSE;
    	    	continue;
	    }
	    else {
	    	// the job can't be resubmitted at this time,
		// since there is nothing more we can do now, ignore it
	    	continue;
	    }
	}
	
	// SWITCH ... CASE!!!
	// Did it complete?
	if (strstr($out[6], 'Cancelled') || strstr($out[6], 'Aborted')) {
	    // either we manually cancelled the job or we have run on a
	    // hard limit (out of proxy or out of running time) In any
	    // case this job will have to be ignored.
	    continue;
	}
	
	if (! strstr($out[6], 'Done')) {    // No, we have to wait longer
	    $complete = FALSE;
	    if ($debug) log_message("    --> $target is not done yet\n");
	}
	// yes, it finished
	else if (strstr($out[6], 'Done (Success)')) {
	    // this one is ready, fetch it if not got yet
	    // NOTE: this test might go UP ABOVE and save a gridUI call
	    if ($debug) log_message("    --> $target is done: retrieving\n");
	    if (! is_dir("$target/job_output")) {
	    	unset($out);
	    	$eg->job_get_output($target, $out, $session_id);
		// we don't care for the result: if it failed, then
		// it will be repeated on the next iteration.
	    }
	} else {
	    // job is done but failed: resubmit once more
	    // If it has already been resubmitted, we give up
	    if ($debug) log_message("    --> $target failed\n");
    	    if (job_resubmit($eg, $session_id, $target)) {
		$complete = FALSE;  	// give it a chance to run
	    }
	    else
	    	// the job can't be resubmitted now, so we can only
		// ignore it for the time being
	    	if ($debug) log_message("        Giving up $target for now\n");
	    
	}
    }
    fclose($tdb);
    
    // $complete will hold true only if all jobs have reached one of either 
    // the 'Done', 'Cancelled' or 'Aborted' status (possibly after resubmission).
    //	If a job is resubmitted, then $complete will have been set to
    // FALSE to give it a chance to be rerun.
    if ($complete)
    	if (!$debug) {
	    $eg->session_destroy($session_id);	// clean up remote site
	    //$eg->destroy(); 	    // this may be dangerous as it might
	    	    	    	    // kill other user jobs (unused yet)
	}
    $eg->destruct();
    return $complete;
}

/**
 *  Resubmit a job to the grid up to a globally defined maximum number of times
 *
 *  We'll use an ancillary trace file in the job directory (named 'resubmitted')
 * to hold a count oof the number of times the job has been resubmitted.
 *
 *  @param Grid $eg	An open, active grid connection to use
 *  @param string $session_id The session identifier
 *  @param string $job The job identifier
 *
 *  return TRUE if the job was resubmitted, FALSE otherwise.
 */
function job_resubmit($eg, $session_id, $job)
{
    global $debug;
    global $output;

    if ($debug) log_message("job_resubmit($eg, $session, $job)\n");
    $prev_tries = 0;
    // Have we already resubmitted the job?
    if (file_exists("$job/resubmitted")) {
    	// How many times?
	$countf = fopen("$job/resubmitted", "r");
	if ($countf == FALSE) {
	    // something smelly is going on
	    if ($debug)  log_message("--> Cannot open existing resubmitted file\n");
	    return FALSE;
	}
	fscanf($countf, "%d", $prev_tries);
	fclose($countf);
    }
    if ($debug) log_message("--> Previously tried $prev_tries times\n");
    
    // Have we exceeded the maximum count?
    if ($prev_tries > MAX_TRIES) {
    	if ($debug) log_message("--> Maximum number of tries exceeded\n"); 
    	return FALSE;
    }
    
    // clean up
    if (file_exists("$job/identifier.txt")) {
    	unlink("$job/identifier.txt");
    }
    if (file_exists("$job/submit.txt")) {
    	unlink("$job/submit.txt");
    }
    
    // submit job
    // NOTE that we expect a valid Grid session/object
    $ok = $eg->job_submit($job, $out, $session_id);
    if (! $ok)
    	return FALSE;

    if ($debug) log_message("--> Resubmitted $job\n");
    // update count
    $countf = fopen("$job/resubmitted", "w");
    if ($countf == FALSE) {
    	// something's wrong, but we have already resubmitted
	// the job although we can't update the count.
	// In a worst case the job will be re-tried more than
	// MAX_TRIES times, but only until grock lifespan finishes.
	return TRUE;
    }
    fwrite($countf, $prev_tries + 1);
    fclose($countf);

    return TRUE;

}
/**
 *  Clean up all data subdirectories leaving them as ghosts
 *
 *  This function removes all subdirectory contents, and leaves
 * the subdirectories themselves as ghosts. This should help in
 * debugging and reporting to figure out how far we went in the
 * processing without eating unneeded space any longer.
 *
 *  This clean up function will leave untouched all report and
 * log files so an external monitor may still see them.
 */
function grock_clean()
{
    exec("rm -rf ./*/*");
}

/**
 *  Do a proper clean up of all data files
 *
 *  This function will delete the whole session directory including
 * log and error files, releasing all occupied space.
 *
 *  At some point in the future it might actually make a backup of
 * significant files to a cache directory for future reference of
 * additional searches with the same probe agains the same database.
 */
function grock_mrproper()
{
    exec("rm -rf ./*");
}

/**
 *  Output an error and die setting the program status to 'aborted'
 *
 *  @param string $where    A string identifying the location of the error
 *  @param string $what     A description of the error condition detected
 */
function grock_letal($where, $what)
{
    log_error($where, $what);
    grock_set_status('aborted');
    exit;
}

?>
