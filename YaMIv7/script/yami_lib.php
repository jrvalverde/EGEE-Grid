<?

/**
 * YaMI functions
 *
 * Working routines used to carry on YaMI tasks. This module contains
 * functions used both by YaMI and by YaMI-output analyzer tools that
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
 
 */
 
require_once('config.php');	    // installer preferences
require_once('util.php');     // general utilities
require_once('ssh.php');  	    // to connect to back-end
require_once('grid.php'); 	    // to manipulate the grid
//require_once('database.php');       // to manipulate databases
require_once('modeller.php');	    // to manipulate Modeller jobs



/**
 *  Obtain status of YAMI process
 *
 *  This routine allows us to access persistent storage hiding
 * implementation details.
 *
 *  @return string status of running YAMI or FALSE if the status
 *  	could not be obtained (denotes a possible error condition). 
 */

function yami_set_status($status) {

    $sf = fopen('yami_status', 'w');
    if (! $sf) {
    	log_warning('YAMI set status: Can not create status file');
	return FALSE;
    }
    if (! fwrite($sf, $status)) {
    	log_warning('YAMI set status: Can not update status');
	return FALSE;
    }
    fclose($sf);
    flush();
    return TRUE;
}

/**
 *  Obtain status of YAMI process
 *
 *
 *  This routine allows us to access persistent storage hiding
 * implementation details.
 *
 *  @return string status of running YAMI or FALSE if the status
 *  	could not be obtained (denotes a possible error condition). 
 */
function yami_get_status()
{
    clearstatcache(); 

    if (!file_exists('yami_status'))
    	return FALSE;

    $sf = fopen('yami_status', "r");
    if (! $sf)
    	return FALSE;
    $status = fgets($sf);
    fclose($sf);
    return $status;
}

function yami_submit($session_id, $auth)
{   	
	// if ($debug) log_message("Running YAMI\n-------------\n\n");
    // for clarity TO CHANGE
    //$probe = $options['probe'];
    //$database = $options['database'];

    // THIS SHOULD PROBABLY BE HANDLED IN YaMI TOPLEVEL ROUTINE
    $yami_status = yami_get_status();
    //if ($debug) log_message("\nInitial status = ".$yami_status."\n");

    if (($yami_status == 'submitted') ||
        ($yami_status == 'partial')) {
        // We have been called to recover a dead session.
    	// All work here has already been done except for grid (re)activation
    	activate_grid($sesion_id, $auth);
    	return;
    }
      
    if ($yami_status == 'finished') {
    	// if we are being called then it may only be because
	// the previous run did NOT recover all finished jobs
	// in time and the user wants to reap them again.
	activate_grid($session_id, $auth);
	yami_set_status('submitted');
	return;
    }
    
    if (($yami_status == FALSE) || 
        ($yami_status == 'aborted') ||
        ($yami_status == 'started') ||
        ($yami_status == 'starting')) {
    	// we have been called 'de novo' or to recover a deceased
	// session that died before or while creating the jobs.
	// If a previous run was aborted there is no way to know
	// how far it went, so we run all again.
	
		yami_set_status('started');

		exec("touch A1");
		if (!modeller_create_job($sequence, $aligment, $topfile) )
		{
			yami_letal("YAMI submit", "Couldn't create job on $session_id");
		}
		exec("touch A2");
	
	}
}

/**
 *  Get output for YaMI jobs
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
 
 /*
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
    //$eg->initialize(2, 0); 	// a 2h window should be enough
    //TO CHANGE???
    $eg->initialize(300, 0); 	
    
    // we are called from within $session_id, but need
    // to tell the Grid to access directories in the
    // remote corresponding session directory
    if ($debug) log_message("    Defining remote session as $session_id\n");
    $eg->session_define($session_id, $session_id);
	
	 // This is a centinel: the following loop will set it to false
	// if it detects any unfinished job.
	$complete = TRUE;
 */
 
function yami_letal($where, $what)
{
    log_error($where, $what);
    yami_set_status('aborted');
    exit;
}
 ?>