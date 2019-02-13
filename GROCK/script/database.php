<?php
/**
 * Database management routines
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
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 0.0
 */
require_once('util.php');

define('EODB', -1);

// a handle to the file containing the list of entries in the database
$dblist = FALSE;

// a handle to a file containing the list of entries processed
$targetlist = FALSE;

/**
 *  Get the next available entry from a database
 *
 *  Given a database, this routine will fetch and return the next
 * entry as a file named 'target.XXX' in the local directory. The
 * XXX extension is database dependent (e.g. 'pdb' for PDB based
 * datasets). Additional descriptive information regarding the entry
 * extracted is returned in the $entry and $description parameters
 * as a convenience for further use. Upon successful completion, the
 * routine will return TRUE.
 *
 *  If retrieval of the next entry fails (e.g. due to an error), FALSE
 *  is returned.
 *
 *  If the routine can not fetch any additional entry from the database
 * (e.g. because it has already returned all existing entries), then
 * EODB will be returned instead.
 *
 *  This function is actually a jump table entry point to the actual
 * routines that carry out the work. As such it acts as the plug-in
 * socket for database specific routines.
 *
 *  To add support for a new database, simply follow the example.
 * Basically, you must detect the database type from the $db parameter
 * and then call the appropriate routine with the same parameters.
 *
 *  Your routine should follow the footprint of this one (recommended)
 * and be defined in a separate, database-dependent module that is included
 * at the beginning of this file.
 *
 *  @param string $db	    The database type/name
 *  @param string $db_file  The name of the actual database file
 *  @param string $entry    A reference parameter where we can store the name
 *  	    	    	    of the entry returned
 *  @param string $description A short description (one liner) of the entry
 *  	    	    	    contents
 *
 *  @return boolean TRUE on success, FALSE on error, EODB on end of data
 */
function db_get_entry($db, $db_file, &$entry, &$description)
{
    if (strcmp(substr($db, 0, 3), 'pdb') == 0) {
    	// 'Tis a PDB database
	return PDB_get_entry($db, $db_file, $entry, $description);
    }
    else if (strcmp($db, 'msdchem')) {
    	return;
    }
    else if (strcmp(substr($db, 0, 4), 'zinc') == 0) {
    	// Zinc-derived
	return;
    }
    else
    	return EODB;
}


/**
 *  Extract PDB entry into a file and get entry name and description
 *  updating a listing file for progress monitoring.
 *
 *  Return EODB when there are no more entries, FALSE if the current
 * entry failed to be retrieved, TRUE if all is well.
 *
 *  This way we would be able to skip over wrong entries... but do we
 * want? How would the user know? Wouldn't it be better if we were told?
 * At any rate, as it currently is, if an entry is wrong we stop but don't
 * know, meaning the user is led to believe the whole database was
 *
 *  @param string $db	    The database type/name
 *  @param string $db_file  The name of the actual database file
 *  @param string $entry    A reference parameter where we can store the name
 *  	    	    	    of the entry returned
 *  @param string $description A short description (one liner) of the entry
 *  	    	    	    contents
 *
 *  @return boolean TRUE on success, FALSE on error, EODB on end of data
 */
function PDB_get_entry($db, $db_file, &$entry, &$description)
{
    global $debug;
    global $pdb_path;
    global $mmtsb_tools;
    global $output;
    global $db_dir;
    global $dblist; 	    // we'd like it static!
    global $targetlist;
    
    $entry='';	    	    // Clear data
    $database='';

    if ($debug) fwrite($output, "PDB_get_entry() called\n");

    if ($dblist == FALSE) {
    	// if not already open, open it for reading
    	$dblist = fopen("$db_dir/$db.lst", "r");
	if (!$dblist) {
	    log_error('PDB get entry', "Cannot open database $db_dir/$db.lst");
	    exit;
	    return EODB;
	}
    }
    
    if ($targetlist == FALSE) {
    	// if not already open, open it for writing
	$targetlist = fopen("./target_list.txt", "w");
	if (! $targetlist) {
	    log_error('PDB get entry', "Cannot open listing target_list.txt");
	    exit;
	    return EODB;
	}
    }
    
    // get next entry line (skip blank lines)
    do {
    	if (feof($dblist)) {
	    fclose($dblist);
	    fclose($targetlist);
	    return EODB;
	}
    	$line = trim(fgets($dblist));
    } while ($line == '');

    //
    // got a new entry description line: parse it
    //
    $entry = rtrim(substr("$line", 5, 6)); 	// ENTR_{C} (entry_chain)
    $description = substr($line, 11);	    	// description

    // $target is ENTR_C (entry_chain)
    //      entry is the first four letters in lower case
    //	    entry filename is $pdb_dir/pdb${entry}.ent
    //	    chain is the sixth letter if any
    $entry_file="pdb".strtolower( substr($entry, 0, 4) ).".ent";
    $chain=substr($entry, 5, 1);
    if ($debug) fwrite($output, "\nProcessing $entry ($entry_file - $chain)\n");
    
    // create the target database file
    if ($chain=="") {
    	// if there is no special chain to use, then use
    	// the full entry
	if ($debug) {
	    fwrite($output, "copying $pdb_path/$entry_file to ./$db_file\n");
    	    $exit = 0;
	}
	exec("cp $pdb_path/$entry_file ./$db_file", $out, $exit);
	if ($exit != 0) {
	    log_error("Get PDB entry", "couldn't get $entry");
	    fwrite($output, print_r($out, TRUE));
	    return FALSE;
	}
    } else { 
    	// extract chain into output file
	if ($debug) {
	    fwrite($output, "extracting chain $chain from $pdb_path/$entry_file into ./$db_file\n");
	    $exit = 0;
	}
	exec("$mmtsb_tools/convpdb.pl -chain $chain $pdb_path/$entry_file > ./$db_file", $out, $exit);
	if ($exit != 0) {
	    log_error("Get PDB entry", "couldn't create $db_file from $entry_file");
	    fwrite($output, print_r($out, TRUE));
	    return FALSE;
	}
    }
    unset($out);
    
    if ($debug) {
    	exec("ls -l ./$db_file", $out);
	fwrite($output, print_r($out, TRUE));
    	fwrite($output, "\n");
    }

    // Update listing of processed entries
    // This is needed only for tracking the job preparation process
    //	although it is used as a convenience for processing listings
    //	The format of target_list.txt is simple: each line contains
    //	an entry followed by its description.
    if (fwrite($targetlist, "$entry $description\n") == FALSE) {
	log_error("Get PDB entry", "couldn't add $entry to list file");
	fwrite($output, print_r($out, TRUE));
	return FALSE;
    }
    return TRUE;
}

/**
 *  Extract MSDchem entry into a file and get entry name and description
 *  updating a listing file for progress monitoring.
 *
 *  Return EODB when there are no more entries, FALSE if the current
 * entry failed to be retrieved, TRUE if all is well.
 *
 *  This way we would be able to skip over wrong entries... but do we
 * want? How would the user know? Wouldn't it be better if we were told?
 * At any rate, as it currently is, if an entry is wrong we stop but don't
 * know, meaning the user is led to believe the whole database was
 *
 *  @param string $db	    The database type/name
 *  @param string $db_file  The name of the actual database file
 *  @param string $entry    A reference parameter where we can store the name
 *  	    	    	    of the entry returned
 *  @param string $description A short description (one liner) of the entry
 *  	    	    	    contents
 *
 *  @return boolean TRUE on success, FALSE on error, EODB on end of data
 * /
function MSDchem_get_entry($db, $db_file, &$entry, &$description)
{
    global $debug;
    global $msdchem_path;
    global $mmtsb_tools;
    global $output;
    global $db_dir;
    global $dblist; 	    // we'd like it static!
    global $targetlist;
    
    $entry='';	    	    // Clear data
    $database='';

    if ($debug) fwrite($output, "MSDchem_get_next_entry() called\n");

    if ($dblist == FALSE) {
    	// if not already open, open it for reading
    	$dblist = fopen("$db_dir/$db.lst", "r");
	if (!$dblist) {
	    log_error('MSDchem get entry', "Cannot open database $db_dir/$db.lst");
	    exit;
	    return EODB;
	}
    }
    
    if ($targetlist == FALSE) {
    	// if not already open, open it for writing
	$targetlist = fopen("./target_list.txt", "w");
	if (! $targetlist) {
	    log_error('MSDchem get entry', "Cannot open listing target_list.txt");
	    exit;
	    return EODB;
	}
    }
    
    // get next entry line (skip blank lines)
    do {
    	if (feof($dblist)) {
	    fclose($dblist);
	    fclose($targetlist);
	    return EODB;
	}
    	$line = trim(fgets($dblist));
    } while ($line == '');

    // Got a new entry line: parse it
    $entry = $line;  	    	    	    	    	    // entry filename
    $description = "NO NAME (please look it up at EBI)";    // description
    $entry_file = $entry;
    if ($debug) fwrite($output, "\nProcessing $entry ($entry_file)\n");
    
    // create the target database file
    // if there is no special chain to use, then use
    // the full entry
    if ($debug) {
	fwrite($output, "copying $msdchem_path/$entry_file to ./$db_file\n");
    	$exit = 0;
    }
    exec("cp $msdchem_path/$entry_file ./$db_file", $out, $exit);
    if ($exit != 0) {
	log_error("Get MSDchem entry", "couldn't get $entry");
	fwrite($output, print_r($out, TRUE));
	return FALSE;
    }
    unset($out);
    
    if ($debug) {
    	exec("ls -l ./$db_file", $out);
	fwrite($output, print_r($out, TRUE));
    	fwrite($output, "\n");
    }

    // Update listing of processed entries
    // This is needed only for tracking the job preparation process
    //	although it is used as a convenience for processing listings
    //	The format of target_list.txt is simple: each line contains
    //	an entry followed by its description.
    if (fwrite($targetlist, "$entry $description\n") == FALSE) {
	log_error("Get MSDchem entry", "couldn't add $entry to list file");
	fwrite($output, print_r($out, TRUE));
	return FALSE;
    }
    return TRUE;
}
*/
?>
