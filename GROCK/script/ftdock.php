<?php
/**
 *  3D-DOCK (a.k.a. ftdock) specific dock handling routines
 *
 *  This file contains routines to manipulate 3d-DOCK jobs: generate jobs,
 * analyze results and display and navigate the results.
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
 * @see     	util.php
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 0.0
 */

require_once('util.php');
define('FTDOCK_NJOBS', 100);
define('FTDOCK_DISPLAY', 50);

/**
 *  Create a ftdock job
 *
 *  This functions sets up a ftdock job for submission to the Grid.
 * The job created will match a mobile molecule against a fixed
 * one (where the mobile should be the smaller one and the fixed
 * the bigger for efficiency).
 *
 *  In order to submit a job, it must be self-contained: we create
 * a directory named after the probe and target molecules and store
 * there all info needed:
 *  	- the ftdock software
 *  	- ftdock configuration files (according to user preferences)
 *  	- the mobile molecule
 *  	- the fixed molecule
 *  	- a shell script to drive the execution
 *  	- the JDL describing the job
 *
 *  We'll take the executable and most of the configuration files
 * from the application directory (where we are installed, $app_dir).
 *
 *  For simplicity, we will use two molecules called 'ligand.pdb'
 * and 'receptor.pdb', and we expect them to be available in the 
 * current directory.
 *
 *  NOTE: these functions expect actual data to be located
 *  always in ./ligand.pdb and ./receptor.pdb
 *  independent of the names they receive.
 *
 *  There is NO WAY to associate $user_probe or $db_target with
 *  the actual filenames (ligand.pdb or receptor.pdb) in the
 *  current implementation.
 *
 *  Nor -for ftdock- do we care: we will check first which of them is
 *  bigger and tell ftdock which one to use as fixed and which as mobile.
 *
 *  @param $probe  is the original user-provided probe molecule name
 *  @param $target   is the original name of the db molecule being
 *  	    	    screened
 *  @param $options The user selected options
 *
 *  @return boolean TRUE is all went well, FALSE otherwise.
 *
 */
 
 
function ftdock_create_job($probe, $target, $options)
{
    global $debug;
    global $app_dir;	    // where exec and config files are located
    global $output;

    if (strcmp($options['docker'], 'ftdock') != 0) {
    	log_error( "create 3D-dock job", "method selected is not 3D-dock");
    	return FALSE;
    }
    if ($debug) fwrite($output, "<h3>Creating a FTDOCK job for $probe+$target</h3>\n");

    // To launch a job to the grid it must be fully self-contained
    // within its own subdirectory.
    // Create job directory
    // We use $target as the name to identify the job.
    $job_directory='./'.$target;
    if (is_dir($job_directory)) {
    	// it already exists from a previous run: ignore
	log_message("$job_directory already exists: ignoring\n");
 	return TRUE;
    }

    if (! mkdir($job_directory, 0700)) {
    	log_error("create ftdock job", 
	    "could not create directory for $probe+$target");
	return FALSE;
    }
        
    // Copy the ligand file from the current directory
    // into the job directory
    if (!copy("ligand.pdb", "$job_directory/ligand.pdb")) {
    	log_error("create ftdock job", 
	    "could not make a copy of ligand to dock $probe+$target");
    	return FALSE;
    }
    
    // Copy the receptor file from the session directory
    // into the job directory
    if (!copy("receptor.pdb", "$job_directory/receptor.pdb")) {
    	log_error("create ftdock job", 
	    "could not make a copy of ligand to dock $probe+$target");
    	return FALSE;
    }
     
    
    // Copy ftdock program and configuration files. 
    // $app_dir/ftdock is a reference template job directory containing
    //	the program, needed aux files, config files and JDL files.

    exec("cp -p $app_dir/ftdock/ftdock-in.tar.gz $job_directory/.", $out, $exit);
    if ($exit != 0) {
    	log_error("create ftdock job", 
	    "couldn't install ftdock-in.tar.gz files files for $target");
	fwrite($output, print_r($out, TRUE));
	return FALSE;
    }
    
    exec("cp -p $app_dir/ftdock/job.sh $job_directory/.", $out, $exit);
    if ($exit != 0) {
    	log_error("create ftdock job", 
	    "couldn't install job required files for $target");
	fwrite($output, print_r($out, TRUE));
	return FALSE;
    }
    
    // exec("cp -p $app_dir/ftdock/job.jdl $job_directory/.", $out, $exit);
    exec("cp -p $app_dir/ftdock/job.jdl $job_directory/.", $out, $exit);
    if ($exit != 0) {
    	log_error("create ftdock job", 
	    "couldn't install job.jdl file for $target");
	fwrite($output, print_r($out, TRUE));
	return FALSE;
    }
    return TRUE;
}

/**
 *  Generate scores files
 *
 *  This routine evaluates the output got to date and generates a top-most
 * scores file:
 *
 * - top_scores.txt containing only the topmost score for each match of
 *  the probe molecule against each molecules screened from the database
 *  sorted by energy.
 *
 *  Note that it may silently fail to generate any scores at all, e.g.
 * if no results are yet available, but also if there is an error.
 *
 *  @param string $docker   The docking method selected by the user
 */

function ftdock_generate_scores($docker)
{
    global $debug;
    
    if (strcmp($docker, "ftdock") != 0) {
        error("FTDOCK - generate scores",
	    "cannot process non-FTDOCK results");
    	log_error("FTDOCK - generate scores",
	    "cannot process non-FTDOCK results");
	return;
    }

    // generate scores file including the target entry description
    $target_list = fopen('target_list.txt', 'r');
    if ($target_list == FALSE) {
    	error("FTDOCK - generate scores",
	    "cannot open target_list.txt");
    	log_error("FTDOCK - generate scores",
	    "cannot open target_list.txt");
    	return;
    }

    while (!feof($target_list)) {
    	// get entry/directory name and description
    	$line = fgets($target_list);
    	if (trim($line, "\n") == '') continue;
	$target = strtok($line, ' ');    // ENTR_C (entry_chain)
    	$description = strtok("\n");   	// description
	// extract ftdock_rpscored.dat with accurately scored matches
	// It is already sorted in descending order, and so we only
	//  need to extract the highest-score match and label it
	if (file_exists("$target/job_output/ftdock-out.tar.gz")) {
	    exec("tar -zxOf $target/job_output/ftdock-out.tar.gz ".
	    	 "     ftdock_rpscored.dat | ".
    	    	 "tail -n +31 | head -n 1 | ".
    	    	 "sed -e 's#\$#  $target $description#g' ".
		 ">> sctmp", $out, $ret);  
	}
    }
    exec('sort -r -n -k 2 sctmp > top_scores.txt', $out, $ret);
    unlink('sctmp');
    return TRUE;
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
function ftdock_show_top_scores($docker, $id, $probe, $pt, $from=1, $to=50)
{
    /*
     * Use top_scores.txt as the reference. Data in top_scores.txt is arranged
     * as follows:
     *
     *	Type	ID  prvID   SCscore RPscore Coordinates Angles Target	Description
     *
     *	We can ignore ID (always 1) and prvID (discarded). The user won't be
     * usually interested in the Coordinates/Angles, so these need not be
     * shown either. Type... I don't know. That leaves us with showing a listing
     * that contains
     *
     *	Target	Description SCscore RPscore
     *
     *	From $from to $to.
     */ 
    global $debug;
    global $www_tmp;
    global $www_app_dir;
    global $app_dir;
    global $pdb2vrml1;
    global $pdb2vrml2;
    global $pdb2ps;
    global $pdb2png;
    global $www_server;
    
    if (strcmp($docker, "ftdock") != 0) {
    	error("FTDOCK - show top scores",
	    "cannot process non-FTDOCK results");
	log_error("FTDOCK - show top scores",
	    "cannot process non-FTDOCK results");
	return;
    }
    
    $top_scores = fopen("top_scores.txt", "r");
    if ($top_scores == FALSE) {
    	error("FTDOCK - show top scores",
	    "cannot find any scores");
    	log_error("FTDOCK - show top scores",
	    "cannot find any scores");
	return;
    }
    
    echo "<center><table border=\"1\" cellspacing=\"0\" width =\"90%\" ".
	 "style=\"background-image:url('$www_app_dir/images/none.png'); ".
	         "background-color:white; border:2 ridge #800000\"".
         ">\n";
    echo "<tr>".
    	 "  <th bgcolor=\"cyan\">Match</th>\n".
	 "  <th bgcolor=\"white\">Description</th>\n".
    	 "  <th bgcolor=\"pink\">SCscore</th>\n".
    	 "  <th bgcolor=\"LightGreen\">RPscore</th>\n".
	 "  <th bgcolor=\"white\">Explore</th>\n".
	 "</tr>\n";
   
    // skip initial $from lines
    for ($i = 1; $i < $from; $i++) { 
    	fgets($top_scores);
	if (feof($top_scores)) {
	    error("FTDOCK show top scores", "Specified start position is too high");
	    // what  to do now? END?
	    // think this
	}
    }

    // show until $to line
    for ($i = $from; $i <= $to; $i++) {
    	
    	// get entry
    	$line = fgets($top_scores);
	if (trim($line) == '') continue;
	
	// parse entry
    	sscanf($line, "%s %d %d %d %f %f %f %f %d %d %d %s",
	    $type, $no, $oldno, $scscore, $rcscore, 
	    $trx, $try, $trz, $a1, $a2, $a3, $target);
    	$target_desc = strstr($line, $target);
	
	// show data
	// 1. Match ligand vs. receptor
	echo "<tr>\n".
	     "  <td bgcolor=\"Cyan\">".
    	     "<strong>$probe</strong> vs. <strong>$target</strong></td>\n";
	// 2. Description
    	echo "  <td>$target_desc</td>\n";
	// 3. SCscore
	echo "  <td bgcolor=\"Pink\">$scscore</td>\n";
	// 3. RCscore
	echo "  <td bgcolor=\"LightGreen\">$rcscore</td>\n";
	// 5. Explore best 10 matches with this target
	echo "  <td><a href=\"$www_app_dir/script/explore_match.php?id=".
	     basename(getcwd()).
	     "&probe=$probe&pt=$pt&target=$target&docker=$docker\">Best 10 scores</a></td>".
	     "</tr>\n";
	if (feof($top_scores))
	    // we are done, even if we didn't reach $to
	    break;
    }
    fclose($top_scores);
    echo "</table></center>\n";
   
    return TRUE;
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
function ftdock_show_dock($docker, $id, $probe, $pt, $target, $from, $to)
{
    //return;

    global $www_server;
    global $www_app_dir;
    global $www_tmp;

    if ($docker != 'ftdock')
    	error('FTDOCK show dock', "Called erroneously for method $docker");

    // output headers
    echo "<html>\n<head>\n  <title>FTDOCK docking results of $probe ".
    	 "vs. $target</title>\n</head>\n".
    	 "<body bgcolor=\"white\" ".
	 "background=\"$www_app_dir/images/background.png\">\n".
	 "<center><H1>FTDOCK docking results of $probe with $target</h1>".
	 "</center>\n";
    
    // We are already positioned in the directory containing the jobs
    // Now, cd to the results directory
    if (! file_exists("$target/job_output/ftdock-out.tar.gz")) {
    	error('FTDOCK show dock', 'No results to explore');
	return FALSE;
    }
    chdir("$target/job_output");
    // current directory as seen from the web
    //	We need it to be able to show links to actual files
    $reldir = "$www_tmp/$id/$target/job_output/";
    
    // extract results we want to look into
    if (! file_exists('centres.pdb'))
    {	
    	exec('tar -zxf ftdock-out.tar.gz', $out, $exit);
    }
    
    // DISPLAY COMMON LINKS TO GENERAL OUTPUT FILES
    // output result listing
    echo "<center><table border=\"1\">";
    
    if (file_exists('job.out'))
    	echo '<tr><td colspan=\"7\">Seeeeeee the <strong>'.
	     "<a href=\"$reldir/job.out\">output</a></strong> ".
	     'of your FTDOCK job</td></tr>'."\n";
	     
    if (file_exists('ftdock_global.dat'))
    	echo '<tr><td colspan=\"7\">Listing of the <strong>'.
	     "<a href=\"$reldir/ftdock_global.dat\">initially</strong> ".
	     'scored 10000 matches</a></td></tr>'."\n";
	     
    if (file_exists('ftdock_rpscored.dat'))
    	echo '<tr><td colspan=\"7\">Listing of the <strong>'.
	     "<a href=\"$reldir/ftdock_rpscored.dat\">accurately</strong> ".
	     'rescored 10000 matches</a></td></tr>'."\n";
	     
    // if we can find the structure of the probe, show it
    if (file_exists("$pt.pdb")) 
    	    ftdock_show_structure("Structure of the <strong>probe ".
	    	"molecule $probe</strong>",
	    	"$reldir/$pt.pdb", $probe);
		
    // find how was the target used
    if ($pt == 'ligand')
    	$tt = 'receptor';
    else
    	$tt = 'ligand';
    // if we can find the structure of the target molecule, show it
    if (file_exists("$tt.pdb"))
    	ftdock_show_structure("Structure of the <strong>target ".
	    "molecule $target</strong>",
	    "$reldir/$tt.pdb", $target);
    // if we can find the clustered matches, show them
    if (file_exists("centres.pdb"))
    	ftdock_show_structure("Distribution of <strong>best ".
	    "matches</strong> around the target",
	    "$reldir/centres.pdb", "Match Distribution");
    echo '</table></center>'."\n";
    
    // generate results and display them
    echo "<h2>Best 10 matches</h2>\n";
    echo "<p>We have generated 10000 matches with their scores, which you \n".
    	 "may download to further analyze them with FTDOCK. In this page \n".
	 "you may have a look at the best 10 matches. These are the 10 \n".
	 "with highest revised score. A better (closer to reality) match \n".
	 "may have a lower score (e.g. due to our lack to include in the \n".
	 "docking other molecules like water).</p>".
	 "<p>These best 10 will allow you to get an idea of the match. For \n".
	 "a better analysis (if you are interested in this match) you are \n".
	 "encouraged to download the 10000 best scores and review them, and \n".
	 "possibly re-score them with other scoreing program or repeat this \n".
	 "specific match with other docking program.</p>\n";
    echo '<center><table border="1">'."\n<tr>\n";
    echo "  <th>Match</th><th>Structure</th>\n".
    /*
    	 "  <th><img src=\"$www_app_dir/images/png.png\" ALT=\"PNG\"></th>\n".
    	 "  <th><img src=\"$www_app_dir/images/postscript.png\" ALT=\"PS\"></th>\n".
    	 "  <th><img src=\"$www_app_dir/images/vrml1.png\" ALT=\"VRML1\"></th>\n".
    	 "  <th><img src=\"$www_app_dir/images/vrml2.png\" ALT=\"VRML2\"></th>\n".
    */
    	 "  <th><img src=\"$www_app_dir/images/Jmol.png\" ALT=\"Jmol\"></th>\n".
    	 "</tr>\n";
	 
    /* GRAMM docker stuff
    for ($i = 1; $i <= 10; $i++) 
    {
    	if (file_exists("receptor-ligand_$i.pdb"))
	{
            ftdock_show_complex("Receptor ligand <strong>complex $i</strong>", //TO CHANGE!!!
	  	"$reldir/Complex_".$i."g.pdb", $probe, $pt, $target);
	} 
    }
    */
    for ($i = 1; $i <= 10; $i++) 
    {
    	if (file_exists("Complex_".$i."g.pdb"))
	{  
            ftdock_show_complex("Receptor ligand <strong>complex $i</strong>", //TO CHANGE!!!
	  	"$reldir/Complex_".$i."g.pdb", $probe, $pt, $target);
	} 
    }
    echo '</table></center>'."\n";
    
    echo "</body></html>\n";
}

/**
 * Show the structure of a molecule using a choice of presentation options
 *
 *  @access private
 *
 *  @param string $message  A message to prepend the molecule
 *  @param string $relurl   The molecule location (relative URL)
 *  @param string $name     The molecule name
 */
function ftdock_show_structure($message, $relurl, $name)
{
    global $pdb2vrml1;
    global $pdb2vrml2;
    global $pdb2ps;
    global $pdb2png;
    global $www_server;
    global $www_app_dir;

    $url = "http://$www_server/$relurl";

	echo "<tr>";
	echo "<td>$message</td>"; 
	echo "<td><a href=\"$url\">". basename($url, '.pdb') ."</a></td>";
	/*
	echo "<td><a href=\"$pdb2png?url=$url\">See PNG</a></td>";
	echo "<td><a href=\"$pdb2ps?url=$url\">Print PS</a></td>";
	echo "<td><a href=\"$pdb2vrml1?url=$url\">VRML1</a></td>";
	echo "<td><a href=\"$pdb2vrml2?url=$url\">VRML2</a></td>";
	*/
	echo "<td><a href=\"$www_app_dir/script/explore_molecule?".
	     "relpdb=$relurl".
	     "&name=$name\">Explore</a></td>";
	echo "</tr>";	
}

/**
 * Show the structure of a docked complex using a variety of choices
 *
 *  @access private
 *
 *  @param string $message  A message to prepend
 *  @param string $relurl   A relative URL path to the complex file
 *  @param string $probe_name	Name of the molecule used as a probe
 *  @param string $probe_type	How the probe has been used by ftdock
 *  @param string $target_name	Name of the target molecule
 */
function ftdock_show_complex($message, $relurl, $probe_name='none', $probe_type='none', $target_name='none')
{
    global $pdb2vrml1;
    global $pdb2vrml2;
    global $pdb2ps;
    global $pdb2png;
    global $www_server;
    global $www_app_dir;

    $url = "http://$www_server/$relurl";

	echo "<tr>";
	echo "<td>$message</td>"; 
	echo "<td><a href=\"$url\">". basename($url, '.pdb') ."</a></td>";
	/*
	echo "<td><a href=\"$pdb2png?url=$url\">See PNG</a></td>";
	echo "<td><a href=\"$pdb2ps?url=$url\">Print PS</a></td>";
	echo "<td><a href=\"$pdb2vrml1?url=$url\">VRML1</a></td>";
	echo "<td><a href=\"$pdb2vrml2?url=$url\">VRML2</a></td>";
	*/
	echo "<td><a href=\"$www_app_dir/script/explore_complex?".
	     "relpdb=$relurl".
	     "&probe=$probe_name&probe_type=$probe_type".
	     "&target=$target_name\">Explore</a></td>";
	echo "</tr>";	
}


?>
