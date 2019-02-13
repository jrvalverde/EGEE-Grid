<?php
/**
 *  GRAMM specific dock handling routines
 *
 *  This file contains routines to manipulate GRAMM jobs: generate jobs,
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
 * @author  	Jose R. Valverde <david@cnb.uam.es>
 * @copyright 	CSIC
 * @license 	../c/lgpl.txt
 * @version 	$Id$
 * @see     	util.php
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 0.0
 */

require_once('util.php');
// require_once('db.php');  // to show entry description in reports

define('GRAMM_NJOBS', 1000);
define('GRAMM_DISPLAY', 50);

/**
 *  Create a GRAMM job
 *
 *  This functions sets up a GRAMM job for submission to the Grid.
 * The job created will match a mobile molecule against a fixed
 * one (where the mobile should be the smaller one and the fixed
 * the bigger for efficiency).
 *
 *  In order to submit a job, it must be self-contained: we create
 * a directory named after the probe and target molecules and store
 * there all info needed:
 *  	- the GRAMM command
 *  	- GRAMM configuration files (according to user preferences)
 *  	- the ligand/mobile molecule
 *  	- the receptor/fixed molecule
 *  	- a shell script to drive the execution
 *  	- the JDL describing the job
 *
 *  We'll take the executable and most of the configuration files
 * from the application directory (where we are installed, $app_dir).
 *
 *  For simplicity, we will use the molecules called 'ligand.pdb'
 * (the mobile, smaller one) and 'receptor.pdb' (the bigger one),
 * and we expect them to be available in the current directory.
 *
 *  We might as well use "$probe.pdb" and "$target.pdb" as the file
 * names, but this has two implications, a minor and a major one:
 *
 *  The minor one is that we would have to tweak other config files
 * as well and use $probe+$target as job name.
 *
 *  The major one is that we would need to pass this information to
 * the show_results.php script, which is run separately and independently
 * from us, making the logic somewhat more convoluted (for the time
 * being; may be we could pass this as hidden form variables?).
 *
 *  NOTE: these functions expect actual data to be located
 *  always in ./ligand.pdb and ./receptor.pdb
 *  independent of the names they receive.
 *
 *  	./ligand.pdb should be the smaller of the two
 *  	    molecules, which is to be moved around the bigger
 *  	./receptor.pdb should be the bigger, which is
 *  	    to remain statically fixed during the docking process.
 *
 *  There is NO WAY to associate $user_probe or $db_target with
 *  the actual filenames (ligand.pdb or receptor.pdb) in the
 *  current implementation.
 *
 *  @param $probe  is the original user-provided probe molecule name
 *  @param $target   is the original name of the db molecule being
 *  	    	    screened
 *  @param $options The user selected options
 *
 *  @return boolean TRUE is all went well, FALSE otherwise.
 *
 */
function gramm_create_job($probe, $target, $options)
{
    global $debug;
    global $app_dir;	    // where exec and config files are located
    global $output;

    if (strcmp($options['docker'], 'gramm') != 0) {
    	log_error( "create gramm job", "method selected is not GRAMM");
    	return FALSE;
    }
    
    if ($debug) fwrite($output, "<h3>Creating a GRAMM job for $probe+$target</h3>\n");
    // To launch a job to the grid it must be fully self-contained
    // within its own subdirectory.
    // Create job directory
    // We use $target as the name to identify the job.
    $job_directory='./'.$target;
    if (is_dir($job_directory)) {
    	// it might already exist from a previous run
	// if so we'll ignore it
   	log_message("$job_directory already exists: ignoring\n");
 	return TRUE;
    }
    if (! mkdir($job_directory, 0700)) {
    	log_error("create gramm job", 
	    "could not create directory for $probe+$target");
	return FALSE;
    }

    // Copy the ligand file from the current directory
    // into the job directory
    if (!copy("ligand.pdb", "$job_directory/ligand.pdb")) {
    	log_error("create gramm job", 
	    "could not make a copy of ligand to dock $probe+$target");
    	return FALSE;
    }
    
    // Copy the receptor file from the session directory
    // into the job directory
    if (!copy("receptor.pdb", "$job_directory/receptor.pdb")) {
    	log_error("create gramm job", 
	    "could not make a copy of ligand to dock $probe+$target");
    	return FALSE;
    }
     
    // Copy GRAMM program and configuration files. 
    // $app_dir/gramm is a reference template job directory containing
    //	the program, needed aux files, config files and JDL files.
    exec("cp $app_dir/gramm/*.* $job_directory/.", $out, $exit);
    if ($exit != 0) {
    	log_error("create gramm job", 
	    "couldn't install job files for $target");
	fwrite($output, print_r($out, TRUE));
	return FALSE;
    }
    // rpar.gr is generated under the user instructions from the submit form.
    if (!write_rpar_gr($job_directory, $options)) {
    	log_error("create_gramm job",
	    "couldn't configure job $target");
	return FALSE;
    }
    // copy JDL file describing the job to the grid
    // XXX JR XXX no longer needed, moved into gramm directory
    //if (! copy("$app_dir/jdl/job.jdl", "$job_directory/job.jdl")) {
    //	log_error("Create GRAMM job",
    //	    "couldn't install JDL file for $probe+$target");
    //	return FALSE;
    //}

    // Generate the gramm-go.tar.gz package
    chdir($job_directory);
    exec("tar -cf gramm-go.tar *.dat *.gr gramm.lnx", $out, $exit);
    if ($exit != 0) {
    	log_error("Create GRAMM job",
	    "couldn't create job package for $probe+$target");
	fwrite($output, print_r($out, TRUE));
	chdir('..');
	return FALSE;
    }
    unset($out);
    exec("gzip -f gramm-go.tar", $out, $exit);
    if ($exit == 1) {
    	log_error("Create GRAMM job",
	    "couldn't compress the job package $probe+$target ($exit)");
	fwrite($output, print_r($out, TRUE));
	chdir('..');
	return FALSE;
    }
    unset($out);
    // clean up
    // exec("rm -f *dat *gr gramm", $out, $exit);
    // for some reason, gzip is leaving the tar file behind:
    exec("rm -f *dat *gr gramm.lnx gramm-go.tar", $out, $exit);
    if ($exit != 0) {
    	log_warning("Create GRAMM job",
	    "couldn't remove unneeded files from $probe+$target");
	fwrite($output, print_r($out, TRUE));
    }
    unset($out);
    chdir("..");
    
    return TRUE;
}

/**
 *  Write parameter file to drive the docking job.
 *
 *  NOTE currently there is no error checking. If an fwrite fails we won't
 * notice.
 *
 *  @access private
 *
 *  @param  $directory where we want it located
 *  @param  $options array containing user selected preferences
 *
 *  @return boolean TRUE if all went well, FALSE otherwise
 */
function write_rpar_gr($directory, $options)
{
    global $debug;
    // XXX DAVID XXX
    print_r($options); 
    // XXX DAVID XXX
    
    $rpar="$directory/rpar.gr";
    
    // *** current form options ***  *** current form options ***
    
    // Low or High resolution docking
    
    if (isset($options['resolution'])) {
    	$resolution = $options['resolution'];
    	// we were called with a simplified interface
	// provide defaults for the rest of values
	if ( $resolution == "low") {
    	    $eta = "6.8";
	    $ro = "6.5";
	    $crang = "grid_step";
	} else {
    	    $eta = "1.7";
	    $ro = "30.";
	    $crang = "atom_radius";
	}
    }
    else {
    	if (! isset($options['eta']))
	    $eta = "2.0";
	else $eta = $options['eta'];
	
	if (! isset($options['ro']))
	    $ro = "2.0";
	else $ro = $options['ro'];
	
	if (! isset($options['crang']))
	    if (isset($options['eta']))
	    	$crang = "grid_step";
	    else $crang = "atom_radius";
	else $crang = $options['crang'];
    }
    
    // Docking type resolution 
    // XXX DAVID XXX DONT WORK
    if (! isset($options['crep']))
    	$crep = 'all';
    else $crep = $options['crep'];
    
    // *** END of current form options ***  *** END of current form options ***
    
    // Provide defaults if no options is provided
    if (! isset($options['mmode']))
    	$mmode = "generic";
    else $mmode = $options['mmode'];
    
    if (! isset($options['ccti'])) {
    	// base default on representation ($crep)
	if ($crep=="hydrophobic")
	{
            $ccti="blackwhite"; // NOTE won't output energies!
	} else
	{
            $ccti="gray";
	}
    }
    else $ccti = $options['ccti'];

    
    if (! isset($options['fr']))
    	$fr = "0.";
    else $fr = $options['fr'];

    $fp = fopen( "$rpar", "w" );

    fwrite($fp, "Matching mode (generic/helix) ....................... mmode= $mmode\n" );
    fwrite($fp, "Grid step ............................................. eta= $eta\n" );
    fwrite($fp, "Repulsion (attraction is always -1) .................... ro= $ro\n" );
    fwrite($fp, "Attraction double range (fraction of single range) ..... fr= $fr\n" );
    fwrite($fp, "Potential range type (atom_radius, grid_step) ....... crang= $crang\n" );
    fwrite($fp, "Projection (blackwhite, gray) ................ ....... ccti= $ccti\n" );
    fwrite($fp, "Representation (all, hydrophobic) .................... crep= $crep\n" );
    fwrite($fp, "Number of matches to output .......................... maxm= 1000\n" );
    fwrite($fp, "Angle for rotations, deg (10,12,15,18,20,30, 0-no rot.)  ai= 20\n" );

    fclose($fp);
    // tired of error checking... wish we could catch exceptions!
    return TRUE;
}

/**
 *  Generate scores files
 *
 *  This routine evaluates the output got to date and generates one or
 * more scores files:
 *
 * - scores.txt containing all the sorted scores for all matches between the
 *  probe molecule and all target molecules screened from the database
 * - top_scores.txt containing only the topmost score for each match of
 *  the probe molecule against each molecules screened from the database
 *  sorted by energy.
 *
 *  Note that it may silently fail to generate any scores at all, e.g.
 * if no results are yet available, but also if there is an error.
 *
 *  @param string $docker   The docking method selected by the user
 */
function gramm_generate_scores($docker)
{
    global $output;
    global $app_dir;
    global $debug;
    
    if (strcmp($docker, "gramm") != 0) {
    	error("GRAMM - generate scores",
	    "cannot process non-GRAMM results");
    	log_error("GRAMM - generate scores",
	    "cannot process non-GRAMM results");
	return;
    }

    // generate scores file including the target entry description
    $target_list = fopen('target_list.txt', 'r');
    if ($target_list == FALSE) {
    	error("GRAMM - generate scores",
	    "cannot open target_list.txt");
    	log_error("GRAMM - generate scores",
	    "cannot open target_list.txt");
        return;
    }
    
    while (!feof($target_list)) {
    	$line = fgets($target_list);
    	if (trim($line, "\n") == '') continue;
	$target = strtok($line, ' ');    // ENTR_C (entry_chain)
    	$description = strtok("\n");   	// description
	if (file_exists("$target/job_output/gramm-come.tar.gz")) {
	    exec("tar -zxOf $target/job_output/gramm-come.tar.gz ".
	    	 "     receptor-ligand.res | ".
    	    	 "tail -n +32 | ".
    	    	 "sed -e 's#\$#  $target $description#g' >> sctmp", $out, $ret);  
	}
	exec("echo \"\" >> sctmp",$out, $ret);
    }
    exec('sort -r -n -k 2 sctmp > scores.txt', $out, $ret);
    unlink('sctmp');
    

    // generate top_scores file
    if ($debug) fwrite($output,
	"grep \"^   1\" scores.txt > top_scores.txt\n");
    exec("grep \"^   1\" scores.txt > top_scores.txt 2> /dev/null", $out, $ret);
    
}

/**
 *  Show a selection from the listing of top scores
 *
 *  NOTE: we expect to be called through dock::show_top_scores() and
 *  that $from and $to have already been checked for validity there.
 *
 *  @param string $docker   The program used to perform the match (used to process
 *  	    	    	    job output)
 *  @param string $probe    The name of the probe molecule submitted by the user
 *  @param string $pt	How to treat the probe molecule (ligand or receptor)
 *  @param integer $from    Top result at which display should start
 *  @param integer $to      Top result at which display should end
 */
function gramm_show_top_scores($docker, $id, $probe, $pt, $from=1, $to=GRAMM_DISPLAY)
{
    global $debug;
    global $www_tmp;
    global $www_app_dir;
    global $app_dir;
    global $pdb2vrml1;
    global $pdb2vrml2;
    global $pdb2ps;
    global $pdb2png;
    global $www_server;
    
    if (strcmp($docker, "gramm") != 0) {
    	log_error("GRAMM - show top scores",
	    "cannot process non-GRAMM results");
	//exit;
	return;
    }
    
    $top_scores = fopen("top_scores.txt", "r");
    if ($top_scores == FALSE) {
    	error('Top scores', 'Top scores file not available, cannot find any scores');
    	log_error("GRAMM - show top scores",
	    "cannot find any scores");
	exit;
    }
    
    
    echo "<center><table border=\"1\" cellspacing=\"0\" width =\"90%\" ".
	 "style=\"background-image:url('$www_app_dir/images/none.png'); ".
	         "background-color:white; border:2 ridge #800000\"".
         ">\n";
    echo "<tr>".
    	 "  <th bgcolor=\"cyan\">Match</th>\n".
    	 "  <th bgcolor=\"pink\">Energy (-)</th>\n".
	 "  <th bgcolor=\"white\">Description</th>\n".
	 "  <th bgcolor=\"white\">Explore</th>\n".
	 "</tr>\n";

    //$reldir = "$www_tmp/$id/$target/job_output/";
    
   
    
    // skip initial $from lines
    for ($i = 1; $i < $from; $i++) {
    	fgets($top_scores);
	if (feof($top_scores)) {
	    error("GRAMM show top scores", "Specified start position is too high");
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
    	sscanf($line, "%d %d %d %d %d %f %f %f %s",
	    $matchno, $energy, $rotx, $roty, $rotz, $trx, $try, $trz, $target);
    	$target_desc = strstr($line, $target);
	
	// show data
	// 1. Match ligand vs. receptor
	echo "<tr>\n".
	     "  <td bgcolor=\"LightGreen\">".
    	     "<strong>$probe</strong> vs. <strong>$target</strong></td>\n";
	// 2. Energy
	echo "  <td bgcolor=\"Yellow\">$energy</td>\n";
	// 3. Description
    	echo "  <td>$target_desc</td>\n";
	// 4. Explore best 10 matches with this target
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
}

/**
 *  Show results of running the docking program GRAMM for a probe and a
 * target.
 *
 *  Called by explore_match.php
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
function gramm_show_dock($docker, $id, $probe, $pt, $target, $from, $to)
{
    global $www_server;
    global $www_app_dir;
    global $www_tmp;

    if ($docker != 'gramm')
    	error('GRAMM show dock', "Called erroneously for method $docker");

    // output headers
    echo "<html>\n<head>\n  <title>GRAMM docking results of $probe ".
    	 "vs. $target</title>\n</head>\n".
    	 "<body bgcolor=\"white\" ".
	 "background=\"$www_app_dir/images/background.png\">\n".
	 "<center><H1>GRAMM docking results of $probe with $target</h1>".
	 "</center>\n";
    
    // We are already positioned in the directory containing the jobs
    // Now, cd to the results directory
    if (! file_exists("$target/job_output/gramm-come.tar.gz")) {
    	error('GRAMM show dock', 'No results to explore');
	return FALSE;
    }
    chdir("$target/job_output");
    // current directory as seen from the web
    //	We need it to be able to show links to actual files
    $reldir = "$www_tmp/$id/$target/job_output/";
    
    // extract results we want to look into
    if (! file_exists('receptor-ligand.res'))
    	exec('tar -zxf gramm-come.tar.gz', $out, $exit);
    
    // DISPLAY COMMON LINKS TO GENERAL OUTPUT FILES
    // output result listing
    echo '<center><table border="1">';
    if (file_exists('gramm.log'))
    	echo '<tr><td colspan=\"7\">See the <strong>'.
	     "<a href=\"$reldir/gramm.log\">output</a></strong> ".
	     'of your GRAMM job</td></tr>'."\n";
	     
    if (file_exists('receptor-ligand.res'))
    	echo '<tr><td colspan=\"7\">Listing of the <strong>'.
	     "<a href=\"$reldir/receptor-ligand.res\">best ".GRAMM_NJOBS."</a></strong> ".
	     'scoring matches</td></tr>'."\n";
	     
    // if we can find the structure of the probe, show it
    if (file_exists("$pt.pdb")) 
    	    gramm_show_structure("Structure of the <strong>probe ".
	    	"molecule $probe</strong>",
	    	"$reldir/$pt.pdb", $probe);
		
    // find how was the target used
    if ($pt == 'ligand')
    	$tt = 'receptor';
    else
    	$tt = 'ligand';
    // if we can find the structure of the target molecule, show it
    if (file_exists("$tt.pdb"))
    	gramm_show_structure("Structure of the <strong>target ".
	    "molecule $target</strong>",
	    "$reldir/$tt.pdb", $target);
    echo '</table></center>'."\n";

    // next is unneeded for now as we only show the top 10
    // gramm_show_nav_links($docker, $id, $probe, $pt, $target, $from, $to);
    
    // generate results and display them
    echo "<h2>Best 10 matches</h2>\n";
    echo "<p>We have generated 1000 matches with their scores, which you \n".
    	 "may download to further analyze them with GRAMM. In this page \n".
	 "you may have a look at the best 10 matches. These are the 10 \n".
	 "with highest energy score. A better (closer to reality) match \n".
	 "may have a lower score (e.g. due to our lack to include in the \n".
	 "docking other molecules like water).</p>".
	 "<p>These best 10 will allow you to get an idea of the match. For \n".
	 "a better analysis (if you are interested in this match) you are \n".
	 "encouraged to download the 1000 best scores and review them, and \n".
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

    for ($i = 1; $i <= 10; $i++) 
    	if (file_exists("receptor-ligand_$i.pdb"))
            gramm_show_complex("Receptor ligand <strong>complex $i</strong>",
	    	"$reldir/receptor-ligand_$i.pdb", $probe, $pt, $target);

    echo '</table></center>'."\n";
    
    // unneeded for now as we only show the top 10
    //gramm_show_nav_links($id, $docker, $probe, $target, $from, $to);
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
function gramm_show_structure($message, $relurl, $name)
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
 *  @param string $probe_type	How the probe has been used by GRAMM
 *  @param string $target_name	Name of the target molecule
 */
function gramm_show_complex($message, $relurl, $probe_name='none', $probe_type='none', $target_name='none')
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
