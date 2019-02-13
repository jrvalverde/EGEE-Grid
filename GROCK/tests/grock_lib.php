<?php
/**
 * GROCK functions
 *
 * @package grock
 */

require_once("config.php");	    // installer preferences
require_once("util.php"); 	    // general utilities
require_once("ssh.php");  	    // to connect to back-end
require_once("grid.php"); 	    // to manipulate the grid
require_once("database.php");     // to manipulate databases
require_once("dock.php"); 	    // to manipulate docking jobs

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
 */ 
function grock_submit($session_id, $options, $auth)
{   	
    // we use globals to retrieve configuration file values
    global $db_dir;
    global $debug;
    global $output;

    if ($debug) fwrite($output, "Running GROCK\n-------------\n\n");
    // for clarity
    $probe = $options['probe'];
    $database = $options['database'];

    grock_set_status('started');
    
    // Get data files
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
    if (strcmp(substr($database, 0, 3), 'pdb') == 0) {
    	$user_file = 'ligand.pdb';
	$db_file = 'receptor.pdb';
    }
    else {
    	$user_file = 'receptor.pdb';
	$db_file = 'ligand.pdb';
    }
    if ($debug) fwrite($output, "\n    Your data will be $user_file and matched against $db_file\n");
    
    // ensure user data goes into correct file
    grock_set_probe_type($user_file);
    // $probe is the original user file name
    if ($debug) fwrite($output, "\n    Successfully moved $probe to $user_file\n");
    
    // check if the list of entries database file is available
    if ($debug) fwrite($output, "\n    Opening $db_dir/$database.lst\n\n");
    $fp = fopen("$db_dir/$database.lst", "r");
    if (!$fp) {
    	grock_letal("GROCK submit", "Cannot read the target database list file.");
    }
    // Start a loop to prepare all the jobs to be sent to the grid.
    // There is a job for each ligand-receptor pair.
    //
    //  Note that we don't submit them one by one, we wait until
    // all jobs are ready and then launch them at once 'en masse'.
    // ************************************************************
    // NOTE THIS SHOULD BE DATABASE INDEPENDENT!
    // e.g. by setting $get_db_entry = "get_${database}_entry"
    //  	and calling $$get_db_entry() or some such
    // ************************************************************

    $i = 0;
    while (!feof($fp)) 
    {	
	// Read the DB list file.
    	$line = fgets( $fp );
	$i++;
	if ($debug) {
	    fwrite($output, "    Processing\t".$i."\t:[".$line . "]\n");
	    flush();
	}
	if (strcmp(trim($line), '') == 0)
	{
	    continue;
	}

    	// retrieve the entry chain from PDB, overwriting any
	// previous one
	$target = PDB_get_entry_name($line);
    	if (!PDB_get_entry_file($target, $db_file)) {
	    // complain and die
	    grock_letal("GROCK submit", "Error processing database entry $target");
	}
	// Prepare a docking job to be sent over to the grid
	//
	//  The molecular data are already stored using always
	// the same file names 'ligand.pdb' and 'receptor.pdb'
	if (!dock_create_job($probe, $target, $options)) {
    	    // at this point, if we are not debugging, we should
	    // delete all data directories created to date...
	    #if (! $debug) {
	    #	exec("rm -rf ./*/*"); //we won't care about its output
	    #}
	    grock_letal("GROCK submit", "couldn't create job for $probe+$target on $session_id");
	}
    }
    if ($debug) {
    	fwrite($output, "\n    Contents of $session_id\n");
    	exec("ls -l *", $out);
    	fwrite($output, print_r($out, TRUE));
    	fwrite($output, "\n");
    }

    // OK, we are ready, let's go for the jugular
    // we need to pass $session_id for the remote back-end, do we?
    submit_session_jobs($session_id, $auth);

    // Close the target list DB
    rewind ( $fp  );
    fclose( $fp );

    // The signal to notice the job is done is the "jobs_sent.txt" file
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
 *  @param $user_file the name to assign the newly uploaded data file.
 *
 */
function grock_set_probe_type($user_file)
{
    global $debug;
    global $output;
    
    if ($debug) fwrite($output, "Moving user data to ".getcwd()."/$user_file\n");

    clearstatcache(); 
    if (! file_exists('./probe.pdb')) {
    	grock_letal("Get probe data", "The coordinates of the probe molecule are not available!");
    }
    if (rename('./probe.pdb', $user_file)) 
    {
    	return TRUE;
    } 
    else 
    {
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
 *  @param $session_id	session_id
 *  @param $auth The authentication token to connect to a remote back-end
 */
function activate_grid($session_id, $auth)
{
    global $local_tmp_path;
    global $gridUI_tmp_path;
    global $debug;
    global $output;
    
    //$grid_server = $auth['server'];
    //$pos = strpos($grid_server, "@");
    //$ru = substr($grid_server, 0, $pos);
    //$rh = substr($grid_server, $pos+1, strlen($grid_server));
    
    if ($debug) {
    	fwrite($output, "\nActivating the Grid\n");

    	//$debug_sexec = TRUE;
    	//$debug_grid = TRUE;
    }
    $eg = new Grid;
    if ($eg == FALSE) {
     	grock_letal("Activating grid", 
	"Cannot open connection to Grid back_end $grid_server!\n");
    }
    $eg->set_host($auth['host']);
    $eg->set_user($auth['user']);
    $eg->set_password($auth['password']);
    $eg->set_passphrase($auth['passphrase']);
    $eg->set_work_dir("$gridUI_tmp_path/$session_id");
    $eg->set_error_log("$local_tmp_path/$session_id/error-output.txt");
    $eg->connect();
    $eg->initialize(48, 0); 	// ask for a 48h proxy
    if ($debug) {
        echo "    ".$eg->get_init_status();
    	fwrite($output, "\n");
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
 *  @param  $session_id     The identifier for our current session
 *  @param  $auth   	    an array holding authentication information
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
 
    if ($debug) {
    	fwrite($output, "\nSubmitting session jobs\n\n");
	flush();
	//return;     // DO NOT SUBMIT WHILE DEBUGGING
	//$debug_sexec = TRUE;
    }
    
    $local_dir = "$local_tmp_path/$session_id";
    $remote_dir = "$gridUI_tmp_path/$session_id";
    
    $rmt = new SExec($auth['server'], $auth['password']);
    if ($rmt == FALSE) {
    	grock_letal("Submit session jobs",
	    "Could not connect to remote system ${auth['server']}");
    }
    if ($debug) fwrite($output, "    Connected to ${auth['server']}\n");
    
    // Copy the whole session directory with all the job files 
    if (! $rmt->ssh_copy_to($local_dir, $remote_dir, $out)) {
    	$rmt->destruct();
    	// XXX clean-up directories here
	if (! $debug)	grock_clean();
	if ($debug) fwrite($output, print_r($out, TRUE));
    	grock_letal("Submit session jobs",
	    "Could not copy jobs to remote back-end for execution");
    }
    if ($debug) fwrite($output, "    Copied session $session_id\n");
    
    // There are files that we don't need in the remote directory, 
    // ligand.pdb, receptor.pdb and pdb_list.txt
    $cmd = "rm -f $remote_dir/grock_log $remote_dir/grock_status $remote_dir/ligand.pdb $remote_dir/receptor.pdb $remote_dir/target_list.txt";
    $exit = $rmt->ssh_exec("$cmd", $out);
    if ($exit != 0) {
    	echo_warning("GROCK submission: Could not remove unneeded files from remote back-end");
	fwrite($output, "$out\n");
    }
    unset($out);
    if ($debug) fwrite($output, "    Removed unneeded files\n");

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
    	if ($debug) fwrite($output, "    Installing LCG-tools\n");
    	// It is not already there yet. Install it as $gridUI_tmp_path/lcg-tools.
    	if (! $rmt->ssh_copy_to($lcg_dir, "$gridUI_tmp_path/lcg-tools", $out)) {
    	    // we are toasted: clean and exit
	    if (! $debug) {
	    	$rmt->ssh_exec("rm -rf $remote_dir", $out);
	    	grock_clean();
	    }
	    $rmt->destruct();
	    if ($debug) fwrite($output, print_r($out, TRUE));
    	    grock_letal("Submit session jobs",
	    "Couldn't make a copy of the LCG tools to $session_id<br>\n");
	}
    }
    
    // Prepare the grid for work
    activate_grid($session_id, $auth);
    
    // The submiter tool launches all the jobs.
    if ($debug) fwrite($output, "    Submitting jobs with lcg-submitter\n");
    $cmd = "/usr/bin/perl $gridUI_tmp_path/lcg-tools/lcg-submitter-biomed-beta11.pl -session $remote_dir";
    $exit = $rmt->ssh_exec("$cmd", $out);
    if ($exit != 0) {
	if (! $debug) {
	    // XXX we'll leave the lcg-tools cached remotely
	    $rmt->ssh_exec("rm -rf $remote_dir", $out);
	    grock_clean();
	}
    	$rmt->destruct();
	if ($debug) fwrite($output, print_r($out, TRUE));
    	grock_letal("Submit session jobs",
	    "An error occurred during submission of $session_id</pre>");
    }

    if ($debug) {
    	// Stop here (move me around for debugging different sections)
	$rmt->destruct();
	fwrite($output, "\n");
	return;
    }
    
    $rmt->destruct();
    if ($debug) fwrite($output, "\n");
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
 *  @param $status   status to set
 *
 *  @return boolean  TRUE if all goes OK,
    	    	    FALSE and a warning if something goes awry
 */
function grock_set_status($status) {
    $sf = fopen('grock_status', 'w');
    if (! $sf) {
    	echo_warning('GROCK set status: Can not create status file');
	return FALSE;
    }
    if (! fwrite($sf, $status)) {
    	echo_warning('GROCK set status: Can not update status');
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
 * After completion it will be
[6]Current Status:     Done (Success)
[7]Exit code:          0
[8]Status Reason:      Job terminated successfully
 *
 *  After edg-job-get-output it will be
[6]Current Status:     Cleared
[7]Status Reason:      user retrieved output sandbox
 *
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
    
    if ($debug) 
    	fwrite($output, "\nGrock get output\n\n    connecting to the Grid\n");
    $eg = new Grid;
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
    $eg->initialize(2, 0); 	// a 2h window should be enough

    // we are called from within $session_id, but need
    // to tell the Grid to access directories in the
    // remote corresponding session directory
    if ($debug) fwrite($output, "    Defining remote session as $session_id\n");
    $eg->session_define($session_id, $session_id);
    
    $tdb = fopen("target_list.txt", "r");
    if (!$tdb) {
    	// we're toasted, we won't be able to even show any results
	//  XXX JR XXX at this point a recovery attempt to build the 
	//  target_list.txt file scanning the subdirectories might be
	//  in place.
    	grock_letal("Get Results", "Cannot read the list of database molecules screened.");
    }
    $complete = TRUE;
    while (! feof($tdb)) {
    	$target = trim(fgets( $tdb ));
	if (strcmp($target, '') == 0)
	    continue;
	// check job status
	unset($out);
	if ($debug) fwrite($output, "    Checking $target\n");
        if (!$eg->job_status($target, $out, $session_id)) {
            echo_warning("Couldn't get status of $target");
	    if (! file_exists("$target/resubmitted")) {
		if (file_exists("$target/intifier.txt")) 
		    unlink("$target/identifier.txt");
		if (file_exists("$target/submit.txt")) 
		    unlink("$target/submit.txt");
		touch("$target/resubmitted");
		$eg->job_submit($target, $out, $session_id);
	    	$complete = FALSE;
    	    	continue;
	    }
	    else {
	    	// something's wrong with this job submission
		//  ignore it after one resubmission
	    	continue;
	    }
	}
	// Did it complete?
	if (! strstr($out[6], 'Done')) {    // No
	    if ($debug) fwrite($output, "    --> $target is not done yet\n");
	    $complete = FALSE;
	}
	// yes, it finished
	else if (strstr($out[6], 'Done (Success)')) {
	    // this one is ready, fetch it if not got yet
	    if ($debug) fwrite($output, "    --> $target is done: retrieving\n");
	    if (! is_dir("$target/job_output")) {
	    	unset($out);
	    	$eg->job_get_output($target, $out, $session_id);
		// we don't care for result: if it failed, then
		// it will be repeated on the next iteration.
	    }
	} else {
	    // job is done but failed: resubmit once more
	    // If it has already been resubmitted, we give up
	    if ($debug) fwrite($output, "    --> $target failed\n");
	    if (! file_exists("$target/resubmitted")) {
	    	if ($debug) fwrite($output, "        Retrying\n");
	    	if (file_exists("$target/identifier.txt"))
		    unlink("$target/identifier.txt");
		if (file_exists("$target/submit.txt"))
	    	    unlink("$target/submit.txt");
	    	touch("$target/resubmitted");
	    	$eg->job_submit($target, $out, $session_is);
		$complete = FALSE;  	// give it a chance to run
	    }
	    else
	    	if ($debug) fwrite($output, "        Giving up\n");
	    // else do not retry and do not retrieve output
	}
    }
    fclose($tdb);
    
    // $complete will hold true only if all jobs have reached 
    // the 'Done' status (possibly after one resubmission).
    //	If a job is resubmitted, then $complete will be set to
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
 */
function grock_letal($where, $what)
{
    echo_error($where, $what);
    grock_set_status('aborted');
    exit;
}

// L E G A C Y

function grock_get_available_results($session_id, $grid_server, $grid_password, $grid_passphrase)
{   	    
    global $gridUI_tmp_path;

    // Activate grid and extract job output from grid into jobs
    activate_grid($grid_server, $grid_password, $grid_passphrase);

    $rmt = new SExec($grid_server, $grid_password);
    
    // Note that this will NOT wait for all output to be available
    // Hence calling it only ensures we get the output of any jobs
    // that may have finished but nothing else!
    $exit = $rmt->ssh_exec("/usr/bin/perl " .
    	    	    	   "$gridUI_tmp_path/lcg-tools/get_output.pl ".
			   "-session $gridUI_tmp_path/$session_id", $out);

    // Copy the grid output to the local machine
    $fp = fopen("$local_tmp_path/$session_id/target_list.txt", "r");
    if (!$fp) {
    	letal("Get Results", "Cannot read the list of database molecules screened.");
    }

    $i = 0;
    while (!feof($fp)) 
    { 
    	// Read the molecule list file
    	$target = trim(fgets( $fp ));
	$i++;
	if ($debug) echo $i."\t:["."$remote_wd/$target" . "]<br>\n";
	if (strcmp($target, '') == 0) {
	    continue;
	}

    	// WRONG:
	//  	We should first check the job status to ensure it
	//  has finished.
	// Get the job output if it doesn't exist
 	if (! is_dir("$local_wd/$target/OUTPUT/test_x.outputdir"))
    	    if (! $rmt->ssh_copy_from("$remote_wd/$target/OUTPUT", 
	    	    	"$local_wd/$target/OUTPUT", $out)) {
	    	if ($debug) warning("Get Results: Cannot fetch output for $target");
	}
    	if (! is_dir("$local_wd/$target/OUTPUT/test_x.outputdir")) {
	    // no output got after copy
	    if ($debug) warning("Get Results: Cannot fetch output for $target");
	    $incomplete = TRUE;
	}
    }
    // Close the receptors list
    rewind ($fp );
    fclose($fp);
    
    $rmt->destruct();
    if ($incomplete) return FALSE;
    else return TRUE;	    # only if ALL output has been recovered
}


function score_file($local_wd, $app_dir, $session_ligand)
{
    /**
     * The score.txt file is generated comparing all receptor-ligand.res files.
     * SEE 
     */
     
    chdir($local_wd);
    $fp_score = fopen("./score.txt", "w");
    //chmod("./score.txt", 0644);
    
    if (!$fp_score)
    {
    	   letal("Score file", "Cannot write the score file.");
    }
    
    fwrite($fp_score, "We show in this file the GROCK score results, using the\n"); 
    fwrite($fp_score, "$session_ligand ligand and multiple receptors [R]\n\r\r");
    
    fwrite($fp_score, "___________________________________________________________\n");
    fwrite($fp_score, "  No. Energy\tRotation\t   Translation\t       [R]\n");
    fwrite($fp_score, "       (-)\n");
    fwrite($fp_score, "___________________________________________________________\n");
    fwrite($fp_score, "[match]\n");
    
    fclose($fp_score);
    fwrite($output, "ls | xargs -i -t $app_dir/script/get-results.sh {} | sort -r -n -k 2 >> score.txt");
    exec("ls | xargs -i -t $app_dir/script/get-results.sh {} | sort -r -n -k 2 >> ./score.txt"); 
}

function grock_results($local_wd)
{ 
 
  chdir("$local_wd");
  if ($handle = opendir(".")) 
  {
    fwrite($output, "<center><table border=\"1\">");
    fwrite($output, "<tr><td><b>Receptors</b></td></tr>");
    /* This is the correct way to loop over the directory. */
    while (false !== ($file = readdir($handle))) 
    {    
    	if ( "$file" !== "." && "$file" !== ".." && is_dir($file) )
	{            
	    $receptor="$local_wd/$file";
            fwrite($output, "<tr><td><a href=\"browse-results.php?receptor=$receptor\" \">$file</a></td></tr>\n");
	}
	clearstatcache();
    }
    fwrite($output, "</table></center>");
    closedir($handle);
 } 
    	
}

?>
