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
 * @author  	Jose R. Valverde <jr@cnb.uam.es>
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


$debug=TRUE;
$debug_grid=TRUE;
$debug_ssh=TRUE;

/*
$debug=FALSE;
$debug_grid=FALSE;
$debug_ssh=FALSE;
*/

/* * * * * To see the main code look at the end of the script * * * * */

/**
 *  Grock
 *
 * Perform a docking screen of a structural database with a probe molecule.
 *
 *  This program will work on the current directory. It can be used to
 * recover a deceased previous run that was terminated by whatever
 * (normal or abnormal) reasons.
 *
 *  Expects:
 * -  	a probe molecule named 'probe.pdb' in the CWD
 * -	a series of parameters in a file named 'options'
 * -	optionally an status file from a previous run
 *
 *  The 'options' file must be in the current directory, and contain
 * a series of option=value pairs, one on each line, divided in
 * two sections:
 * - first comes the authorization section containing details needed
 * to log in and activate the grid
 * - an empty line separates both sections
 * - last come the program actual options: which database to search,
 * docker to use and dock-specific parameters.
 *
 *  See processor.php for more details.
 *
 *  Once the options have been processed the file is deleted. NOTE that
 * since the 'options' file contains user authentication data, it should
 * be readable only by the user running GROCK and is anyway very sensitive.
 * For this reason, processor.php should use a pipe instead of an actual file,
 * and GROCK deletes the file as soon as it is done.
 *
 *  NB: We might do without a file getting all in the command line, but this
 * would be much worst as any user would then access the auth info by
 * issuing a ps(1) while grock is running.
 *
 *  NB: We might read from stdin but then we would have a problem running in
 * the background from PHP (sic).
 *
 *  After all options have been processed we call grock_submit() to
 * generate all the jobs needed (one per database entry) and submit
 * them to the Grid.
 *
 *  Once the jobs have been sent, we enter a timed loop to collect the
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
 *  In the initial phase, GROCK will generate a directory for each docking job
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
    global $ckpt_auth;
    global $ckpt_opts;

    // work on the current directory: it will become the session
    // directory by default
    $session_id = basename(getcwd());
    
    // XXX JR XXX
    //	we might as well get all parameters in one line as if it where
    //	a URL and parse them with parse_str();
    //  at least we'll need AUTH info if we want to encrypt the options file
    //	that or either we use the session as key (which is totally unsafe)
    //	except for the casual observer or use a separate key (which is too
    //	cumbersome)

    // First get run parameters from options file
    //	We might use $options = STDIN, but this way we may be run in the
    //	background and delete the options file ASAP
    // start with auth data
    $opt_file=fopen('options', 'r'); 
    if ($opt_file == FALSE) 
    	grock_letal('GROCK', 'Nothing to do!');

    $auth = array();
    $options = array();
    $opt='auth';
    while (! feof($opt_file)) {
    	$inline = trim(fgets($opt_file), "\r\n");
	// if ($debug) fwrite($output, "\n$inline");
	// an empty line signals the start of options data
	if ($inline == '') {
	    $opt='options';
	    continue;
	}
	
	// format is param=value
	$optval = explode('=', $inline, 2);
	${$opt}[$optval[0]] = $optval[1];
    }
    fclose($opt_file);
    
    // save for checkpointing
    $ckpt_auth = $auth;
    $ckpt_opts = $options;
    
    // XXX JR XXX
    // WARNING SECURITY RISK!!!
    // Until we can get the checkpoint/restart system working with signals,
    //	we need to keep this sensitive file in order to be able to restart
    //	a session. That or either we generate a recovery web form that 
    //  prompts again for authentication data.
    unlink('options');
    
    
    if ($debug) {
    	fwrite($output, "\nsession is $session_id\n");
	fwrite($output, "called from ". basename(getcwd()) . "\n");
    	//fwrite($output, print_r($auth,TRUE));
    	fwrite($output, print_r($options, TRUE));

    	ob_flush(); flush();
    	#sleep(20);
        #log_message("Sending USR1\n"); posix_kill(posix_getpid(), SIGUSR1); sleep(1);
        #log_message("Sending TERM\n"); posix_kill(posix_getpid(), SIGTERM); sleep(1);
        #log_message("Sending KILL\n"); posix_kill(posix_getpid(), SIGKILL);
   }
    // submit all jobs
    grock_submit($session_id, $options, $auth);
    
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
    
 
    for ($i = 0; $i < 960; $i++) {
    //for ($i = 0; $i < 430; $i++) {
    	sleep(3600); 	// 3600: wait 1 hour before checking results again
	//sleep(600);
	if ($debug) fwrite($output, "Checking job output availability\n");
    	$done = grock_get_output($session_id, $auth);

    	// generate scores file(s)
    	dock_generate_scores($options['docker']);
	
	if ($done)
	    break;
	grock_set_status('partial');
    }
    grock_set_status('finished');

    exit;
}

//  //  //  //  H  E  R  E      W  E      G  o  //  //  //  // 

// enable signal handling for checkpointing
declare(ticks=1);
$ckpt_auth = array();
$ckpt_opts = array();

$ver = explode( '.', PHP_VERSION );
$ver_num = $ver[0] . $ver[1] . $ver[2];

/* auxiliary compatibility functions */
if ($ver_num < 500) {
    /* This function is only needed on PHP4 */
    define('FILE_APPEND', 1);
    function file_put_contents($n, $d, $flag = false) {
	$mode = ($flag == FILE_APPEND || strtoupper($flag) == 'FILE_APPEND') ? 'a' : 'w';
	$f = @fopen($n, $mode);
	if ($f === false) {
            return 0;
	} else {
            if (is_array($d)) $d = implode($d);
            $bytes_written = fwrite($f, $d);
            fclose($f);
            return $bytes_written;
	}
    }
}
if ($ver_num < 430) {
    /* and this only on PHP < 4.3.0 */
    function file_get_contents($file) {
       return implode(file($file));
    }
}

function handler($nsig)
{
    global $ckpt_auth;
    global $ckpt_opts;
    global $debug;
    
    #if ($debug) {
    #	echo "signal handler: caught $nsig\n"; ob_flush(); flush();
    #}
    
    // save checkpoint info just in case
    if (! file_exists('options')) {
        // save options on a file to read them when restarted
        $grockopt = fopen("options", "w+");
        foreach ($ckpt_auth as $idx => $value) 
	    fwrite($grockopt, "$idx=$value\n");
        fwrite($grockopt, "\n");
        foreach ($ckpt_opts as $idx => $value) 
	    fwrite($grockopt, "$idx=$value\n");
        fclose($grockopt);
    }
/*  We might as well encrypt data with MCRYPT if supported */
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    // A global key should be obtained from the user and passed to Grock.
    $key = 'hardcoded 4 now\n';	// a 128 bit (16 byte) key
    file_put_contents(
        'snoitpo', 
        mcrypt_encrypt(
            MCRYPT_RIJNDAEL_256,
            $key,		// this may depend on array order
            file_get_contents('options'),
            MCRYPT_MODE_ECB, $iv)
        );
/**/
    switch ($nsig) {
    	case SIGHUP:
	case SIGUSR1:
	    return;
	    break;
	case SIGINT:
	case SIGTERM:
	case SIGQUIT:
	default:
	    grock_set_status('aborted');
	    exit;
	    break;
    }

}


pcntl_signal(SIGUSR1, 'handler');
pcntl_signal(SIGHUP, 'handler');
pcntl_signal(SIGINT, 'handler');
pcntl_signal(SIGABRT, 'handler');
pcntl_signal(SIGQUIT, 'handler');
pcntl_signal(SIGTERM, 'handler');


// we are intended to be run in the background
//  all our output will be to a special file
$output = fopen('grock_output', 'w+');
if (! $output)
    grock_letal("GROCK", "Can not open output file");

grock();

fclose($output);

// That's all, folks!

?>


