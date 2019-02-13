<?php
;
/**
 *  Method independent docking functions
 *
 *  These functions hide the underlying method from the caller
 * by detecting which method has been selected and calling the
 * appropriate functions.
 *
 *  As such these are mainly placeholders for hooking in the 
 * appropriate plug-in routines. The actual routines doing the work
 * are defined elsewhere and included in the head of this file.
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
 * @license 	../c/lgpl.txt
 * @version 	$Id$
 * @see     	util.php
 * @see     	gramm.php
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 0.0
 */

require_once('util.php');

// Docker scripts
include_once('gramm.php');
include_once('ftdock.php');

//include_once('dock5.php');
//include_once('dot.php');

/**
 *  Create a docking job in the current directory
 *
 *  Jobs are created as subdirectories inside the current directory so
 * that each of them is independent and self-contained.
 *
 *  Since we support various docking methods, we just switch over all of
 * them.
 *
 *  Since we don't want to concern ourselves with the diferent kinds of
 * options supported by each method, we just pass them along to the
 * underlying method and leave to it the parameter verification.
 *
 *  We'll return whatever the underlying method does.
 *
 *  NOTE: these functions expect actual data to be located
 *  always in $session_id/ligand.pdb and $session_id/receptor.pdb
 *  independent of the names they receive.
 *
 *  	$session_id/ligand.pdb should be the smaller of the two
 *  	    molecules, which is to be moved around the bigger
 *  	$session_id/receptor.pdb should be the bigger, which is
 *  	    to remain statically fixed during the docking process.
 *
 *  There is NO WAY to associate $user_probe or $db_target with
 *  the actual filenames (ligand.pdb or receptor.pdb) in the
 *  current implementation.
 *
 *  @param  $probe  the user-specified probe molecule name
 *  @param  $target the target molecule name
 *  @param  $options	the options selected for docking (method and parameters)
 *
 *  @return  exit status of low-level job creation function.
 */

function dock_create_job($user_probe, $db_target, $options)
{
    // we just switch over available methods, defaulting to GRAMM
    //if (strcmp($options['docker'], 'gramm') == 0) {
	//return gramm_create_job($user_probe, $db_target, $options);
    //} else {
    	/* default case */
	//error("create dock job", "unknown method ".$options['docker']);
	//return FALSE;
    //}
    
    $i = $options['docker'];
    switch ($i) {
    	case "gramm":
    	    return gramm_create_job($user_probe, $db_target, $options);
	    break;
    	case "ftdock":
    	    return ftdock_create_job($user_probe, $db_target, $options);
	    break;
     	default:
     	    error("create dock job", "unknown method ".$options['docker']);
	    return FALSE;
    }
}

/**
 *  Generate scores files
 *
 *  This routine evaluates the output got to date and generates one or
 * more score files as needed (at a minimum):
 *
 * - top_scores.txt containing only the topmost score for each match of
 *  the probe molecule against each molecules screened from the database
 *  sorted by energy/quality.
 *
 *  As the scores files and their contents and format will be dependent
 * on the docking method used, we just pass on the task to the appropriate
 * routine for the method specified.
 *
 *  @param string $docker   The docking method selected by the user
 */

function dock_generate_scores($docker)
{
    switch ($docker) {
    	case 'gramm':
	    $func = $docker."_generate_scores";
    	    $func($docker);
	    break;
	case 'ftdock':
	    $func = $docker."_generate_scores";
    	    $func($docker);
	    break;
	/*
	case 'dock5':
	case 'dot':
    	    $func = $docker."_generate_scores";
    	    $func($docker);
	    break;
	*/
	default:
	    error("Generate scores", "Unknown method");
	    return;
    }
}

/**
 *  Show a selection from the listing of top scores
 *
 *  Since scores are dependent on the method, we relay to the docker the task
 * of knowing how to deal with score presentation to the user.
 *
 *  @param string $docker   The program used to perform the match (used to process
 *  	    	    	    job output)
 *  @param string $probe    The name of the probe molecule submitted by the user
 *  @param string $pt	    How to treat the probe molecule (ligand or receptor)
 *  @param integer $from    Top result at which display should start
 *  @param integer $to      Top result at which display should end
 */

function dock_show_top_scores($docker, $id, $probe, $pt, $from=1, $to=50)
{
    if (($from <= 0) || ($to <= 0)) {
    	error("Show top scores", "I can't show non-existing matches $from .. $to");
    }
    	
    if ($from > $to) {
    	// swap
	$aux = $to;
	$to = $from;
	$from = $aux;
    }

    // XXX JR XXX Caution, while this may work it doesn't check for
    // validity! This is just a proof of concept! ;-)
    switch ($docker) {
    
    	case 'gramm':
	    $func = $docker."_show_top_scores";
    	    $func($docker, $id, $probe, $pt, $from, $to);
	    break;
	case 'ftdock':
	    $func = $docker."_show_top_scores";
    	    $func($docker, $id, $probe, $pt, $from, $to);
	    break;
	/*
	case 'dock5':
	case 'dot':
    	    $func = $docker."_show_top_scores";
    	    $func($docker, $id, $probe, $pt, $from, $to);
	    break;
	*/
	default:
	    error("Show top scores", "Unknown method");
	    return;
    }
}

/**
 * Show a docked structure to the user.
 *
 *  As different methods deal with different data types, we want to relay
 * this work to the method, who should know how to deal with its data.
 *
 *  This is probably not fully appropriate and might be better handled by
 * a generic dock representation routine. While we wait to add more methods
 * and see if it is needed here or not, we'll leave it as is.
 *
 *  But beware! This may be moved away in a future release.
 *
 *
 *  @param string $docker   The program used to perform the match (used to process
 *  	    	    	    job output)
 *  @param string $id	    Session id the docking job belongs to
 *  @param string $probe    The name of the probe molecule submitted by the user
 *  @param string $pt	    How to treat the probe molecule (ligand or receptor)
 *  @param string $target   The name of the target molecule
 *  @param integer $from    Top result at which display should start
 *  @param integer $to      Top result at which display should end
 */

function dock_show_dock($docker, $id, $probe, $pt, $target, $from, $to)
{
    if (($from <= 0) || ($to <= 0)) {
    	error("Show dock", "I can't show non-existing matches $from .. $to");
    }
    	
    if ($from > $to) {
    	// swap
	$aux=$to;
	$to=$from;
	$from=$aux;
    }
    
    switch ($docker) {
    	case 'gramm':
	    $func = $docker."_show_dock";
	    $func($docker, $id, $probe, $pt, $target, $from, $to);
	    break;
	case 'ftdock':
	    $func = $docker."_show_dock";
	    $func($docker, $id, $probe, $pt, $target, $from, $to);
	    break;
	/*
	case 'dock5':
	case 'dot':
	    $func = $docker."_show_dock";
	    $func($docker, $id, $probe, $pt, $target, $from, $to);
	    break;
	*/
	default:
	    error("Show docking of $probe against $target", "Unknown method");
	    return;
    }
}

?>
