<?php
//It's mandatory to access GROCK session variables
session_start();

/**
 *  Monitor GROCK status and analyze its output.
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
 * @see     	grid.php
 * @see     	grock_lib.php
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 0.0
 */

// include everything needed before we change directory to our work space
require_once("config.php");
require_once("grid.php");
require_once("grock_lib.php");    // for grock_get_status()

$debug=TRUE;
//$debug=FALSE;

$id=$_GET['id'];
$probe=$_GET['probe'];
$pt=$_GET['pt'];
$db=$_GET['db'];
$docker=$_GET['docker'];    	// needed for program-specific output display
$from=$_GET['from'];
$to=$_GET['to'];

// NOTE:
// $from and $to are special: they may be omitted from the URL:
//  $from and $to are only needed when we have results to show,
//  i.e. on show_partial_results() and show_complete_results()
//  which default to showing results 1-50 as a safeguard
// We will check them anyway and set them to the defaults if
//  not present so we may call the two mentioned functions
//  properly.
// This is needed so we may re-invoke ourselves for following up
//  an ongoing job while at the same time allowing users to
//  navigate through partial results.
// The choice of numbers, which is harcoded here in a number of
//  places (sic, sic, SIC!) is based on the presumption that users
//  won't be interested in huge listings while at the same time
//  their interest will probably concentrate on the top 50-100
//  best matches.
if (!isset($from))
    $from = 0;
if (!isset($to))
    $to = 50;

if ((strcmp($docker, '') == 0) || (strcmp($id, '') == 0) || (strcmp($probe, '') == 0) || (strcmp($db, '') == 0)) {
    letal("GROCK results", "Which results do you want me to show?");  
}

// go there
if (! chdir("$local_tmp_path/$id")) {
    letal("GROCK results", "The session you specified does not exist!!!");
}

// from now on we work on the session directory


// decide what to do
$status = grock_get_status();

if ($status == FALSE) {
    // special case:
    // the status file does not exist
    //	Probably GROCK was aborted and/or cleaned
    //	Something went airy with it. Maybe there
    // is something we can show
    //	Requires some thinking
    //      Should we kill GROCK and clean-up?
    //	    Should we try to show anything left?
    //	    should we wait and retry?
    //	    Should this be the default case?
    letal('GROCK results', "Cannot find status of your job $id");
}
else 
    $status = trim($status);

// TO CHANGE: this is a manual switch...case
if (strcmp($status, 'starting') == 0) {
    show_submission_log($id, $probe, $pt, $db, $docker, $from, $to);
}
else if (strcmp($status, 'started') == 0) {
    // copying jobs over to remote GridUI
    show_submission_log($id, $probe, $pt, $db, $docker, $from, $to);
}
else if (strcmp($status, 'submitting') == 0) {
    show_submitting_page($id, $probe, $pt, $db, $docker, $from, $to);
}
else if (strcmp($status, 'submitted') == 0) {
    // sent, none finished yet
    show_wait_page($id, $probe, $pt, $db, $docker, $from, $to);
}
else if (strcmp($status, 'partial') == 0) {
    // we have started getting results
    show_partial_results($id, $probe, $pt, $db, $docker, $from, $to);
}
else if (strcmp($status, 'finished') == 0) {
    // OK, we are done
    show_results($id, $probe, $pt, $db, $docker, $from, $to);
}
else if (strcmp($status, 'aborted') == 0) {
    // Something went wrong, terribly wrong
    show_abort($id, $probe, $pt, $db, $docker, $from, $to);
}
else {
    // unknown status
    show_abort($id, $probe, $pt, $db, $docker, $from, $to);
    letal('GROCK results', "Unknown status of your job $id ($status)");
}

/**
 *  Show an error message stating that the process was aborted
 * for some specific reason.
 *
 *  This page should:
 * - apologize profusely
 * - notify the user of the error situation
 * - show the error log of GROCK
 * - display the target_list so the user may know how far it
 *   went before aborting
 * - offer a contact address and notification form
 * - offer a return link to the submission form
 *
 *  @param string $id	The Job ID (to be used in the output report and to back-link
 *  	to ourselves for auto-updating)
 *  @param string $probe The name of the probe molecule submitted by the user
 *  @param string $pt	How to treat the probe molecule (ligand or receptor)
 *  @param string $db The name of the database being screened
 *  @param string $docker The program used to perform the match (used to process
 *  	job output)
 *  @param integer $from Top result at which display should start
 *  @param integer $to Top result at which display should end
 */
function show_abort($id, $probe, $pt, $db, $docker, $from=1, $to=50)
{
    global $maintainer;
    global $www_app_dir;
    
    echo <<< ABRTPRELUDE
    <html>
    <head>
        <title>GROCK Results for probe vs $db using $docker ($id): Job aborted</title>
    </head>
    <body bgcolor="white" background="$www_app_dir/images/background.png">
    <center>\n<h1>Job aborted</h1>
    <h2>Your job to check $probe against $db has aborted</h2>
    </center>
    <p>This implies that some serious error preventing it to successfully
    complete has occurred. Most probably it has taken place before the jobs
    could be submitted for processing.</p>
    
    <p>At this point you have several options:</p>
    <ul>
	<li><b>Review what has happened and make a decision<b></li>
    	<li>Contact the <a href="mailto:$maintainer">service maintainer</a></li>
	<li>Go back to the 
	    <a href="$www_app_dir/interface/index.html">submission form</a>
	    and try again to resubmit your job</li>
    </ul>
    <p>Please, review the following data and see if you can find any reason
    why your job may have failed in it.</p>
    
    <p>If it all looks like mind-bogging spaghetti babble to you, do not
    be afraid, you are not expected to understand it, you may just contact
    the <a href="mailto:$maintainer">service maintainers</a> and send them 
    a copy so they can see what went wrong and try to fix it.</p>
    
    <center>
    <table border="1">
ABRTPRELUDE;

    clearstatcache();
    // Show progress report if available
    if (file_exists("target_list.txt"))
      echo "<tr><td><a href=\"target_list.txt\">GROCK progression report</a></td></tr>\n";
    
    // Show grock output/error if available
    echo "  <tr><td><b>GROCK output</b></td></tr>\n" .
    	 "  <tr><td><pre>\n";
    if (file_exists('grock_output'))
    	echo file_get_contents('grock_output');
    else
    	echo "Not available";
    echo "      </pre></td></tr>\n";
    echo "    </table>\n</center>\n";
}

/**
 *  Show submission progression
 *
 *  This function is called when GROCK has started submitting jobs to
 * the grid, but has not finished yet submitting all of them.
 *
 *  To allow for persistence, GROCK logs its submission progress report
 * to a file in the current session directory. We must be there (in the
 * session directory) when being called, so we can open the GROCK report
 * and pass it along to the user so s/he may monitor its progress.
 *
 *  If the submission has any problem, then GROCK won't procceed to 
 * the 'submitted' stage, and this will allow us to see what happened
 * during the submission process (and hopefully what went wrong).
 *
 *  Since the submission is an ongoing process and will be followed by 
 * further processing, we want to monitor it on an ongoing basis: we
 * just resubmit ourselves every so often and notify the user so they
 * may update the report manually as well or make a bookmark.
 *
 *  @param string $id	The Job ID (to be used in the output report and to back-link
 *  	to ourselves for auto-updating)
 *  @param string $probe The name of the probe molecule submitted by the user
 *  @param string $pt	How to treat the probe molecule (ligand or receptor)
 *  @param string $db The name of the database being screened
 *  @param string $docker The program used to perform the match (used to process
 *  	job output)
 *  @param integer $from Top result at which display should start
 *  @param integer $to Top result at which display should end
 */
function show_submission_log($id, $probe, $pt, $db, $docker, $from=1, $to=50) {
    global $www_app_dir;

    // unneeded as of now since this page is only called once submission
    // has finished. We will need it though very soon.
    auto_update('Preparing jobs for submission', 20,
    	    	$id, $probe, $pt, $db, $docker, $from, $to);
    echo "<body bgcolor=\"white\" background=\"$www_app_dir/images/background.png\">\n".
    	 "<center>\n<h1>GROCK results</h1>\n" .
	 "<table  border=\"0\">\n<tr>\n<td><img src=\"$www_app_dir/images/Rodin_Penseur.jpg\"></td>\n".
	 "<td>".
	 "<h2>Submitting docking jobs to the Grid:</h2>\n" .
	 "<H3><A HREF=\"$www_app_dir/script/report.php?".
	               "id=$id&probe=$probe&pt=$pt&db=$db&docker=$docker".
		       "&from=$from&to=$to\" ".
	 "    style=\"color:blue; text-decoration:none\">Reload</A> ".
	 "    this page to update your job status</H3>\n" .
	 "<H3>or</H3>\n" .
    	 "<H3>Bookmark this page to access it later with [CTRL]+[D]</H3>\n" .
	 "</td></tr></table>\n".
	 "<p><STRONG>Note:</STRONG> ".
	 "This page updates itself automatically every 20 seconds</p>\n" .
	 "<table border=\"2\" bgcolor=\"#eeeeee\">\n";
	 
//    echo file_get_contents("./target_list.txt");
    clearstatcache();
    if (! file_exists('target_list.txt')) {
    	error('Show submission log', 'No jobs generated');
	exit;
    }
    
    // It would be better to output a number of submitted jobs
    // and just the last 20 or so as a (scrolling) list
    $log = fopen('target_list.txt', 'r');
    if ($log) {
	while (!feof($log)) {
	    // NOTE that here we take target+description together!!!
    	    $target_desc = trim(fgets($log), "\n");
	    if ($target_desc == '') continue;
	    echo "<tr><td>Preparing job to match <strong>$probe</strong> ".
	         "against <strong><font color=\"green\">$target_desc</font></strong> ".
	    	 "using <strong><font color=\"blue\">$docker</strong>...</td>".
		 "<td>done</td</tr>\n";
	}
	fclose ($log);
    }
    else
    	echo "No jobs have been prepared yet";
    
    echo "</tr>\n</table>\n</center>\n</body>\n</html>\n";
    return TRUE;
}

/**
 *  Display a page asking the user to wait while his jobs are being submitted
 *
 *  This function helps tell the user his job is being launched,
 * and that since it takes too long, s/he should wait for it to
 * start producing output. Once there is any output available,
 * status will change and a different page will be shown.
 *
 *  For this reason we need to auto-update ourselves and/or
 * tell the user to update the page manually or bookmark it.
 *
 *  @param string $id	The Job ID (to be used in the output report and to back-link
 *  	to ourselves for auto-updating)
 *  @param string $probe The name of the probe molecule submitted by the user
 *  @param string $pt	How to treat the probe molecule (ligand or receptor)
 *  @param string $db The name of the database being screened
 *  @param string $docker The program used to perform the match (used to process
 *  	job output)
 *  @param integer $from Top result at which display should start
 *  @param integer $to Top result at which display should end
 */
function show_submitting_page($id, $probe, $pt, $db, $docker, $from=1, $to=50) {
    global $www_app_dir;

    // show an auto-updated wait page
    // The layout is sort of
    //
    //	    	    GROCK
    //	IMG 	Reload this page
    //	    	    or
    //	    	Bookmark this page
    //
    //	      Your job is being submitted
    //	      Job (id) Status (Submitting)
    //	This pages reloads itself automatically
    //
    auto_update('Job being submitted, please wait', 20,
    	    	$id, $probe, $pt, $db, $docker, $from, $to);
    echo "<BODY BGCOLOR=\"white\" background=\"$www_app_dir/images/background.png\">\n" .
	"<CENTER><H1>GROCK results.</H1><BR /><BR />\n".
	"<TABLE BORDER=\"0\">\n<TR>\n  <TD><IMG SRC=\"$www_app_dir/images/Rodin_Penseur.jpg\"></TD>\n".
	"  <TD ALIGN=\"center\">\n".
	"  <H2>The docking jobs of $probe against all molecules in $db are now being submitted</H2>\n" .
    	"  <H3><A HREF=\"$www_app_dir/script/report.php?id=$id".
	"&probe=$probe&pt=$pt&db=$db&docker=$docker&from=$from&to=$to\" ".
	"    style=\"color:blue; text-decoration:none\">Reload</A> ".
	"    this page to update your job status</H3>\n".
	"  <H3>or</H3>\n" .
	"  <H3>Bookmark this page to access it later using [CTRL]+[D]</H3>\n" .
    	"  <TABLE BORDER=\"2\" ALIGN=\"center\">\n" .
	"    <TR><TD VALIGN=\"top\" ALIGN=\"center\" COLSPAN=\"4\">\n" .
	"      <b>Your GROCK Job for $probe vs. $db is being submitted for processing.</b>\n" .
	"    </TD></TR>\n" .
	"    <TR>\n" . 
	"      <TD BGCOLOR=\"#cccccc\" VALIGN=\"top\" ALIGN=\"center\">\n".
	"          <B>Gramm Job number: </B></TD>\n" .
	"      <TD BGCOLOR=\"LightBlue\" VALIGN=\"top\" ALIGN=\"center\">\n".
	"   	   <B> $id </B></TD>\n" .
	"      <TD BGCOLOR=\"#cccccc\" VALIGN=\"top\" ALIGN=\"center\">\n" .
	"   	   <B>Status: </B></TD>\n" .
	"      <TD BGCOLOR=\"Yellow\" VALIGN=\"top\" ALIGN=\"center\">\n".
	"          <B> Submitting </B></TD>\n" .
	"    </TR>\n" .
	"  </TABLE>\n" .
	"  </TD></TR>\n</TABLE>\n</CENTER>\n" .
	"<BR>\n<BR>\n<CENTER><P><STRONG>Note:</STRONG> ".
	"This page updates itself automatically every 20 seconds</P></CENTER>\n" .
	"<CENTER><P><STRONG>Note:</STRONG> ".
	"Please, take notice that once submitted a GROCK job may take in the order " .
	"of hours to days (or even weeks) to complete depending on the ".
	"options selected</P></CENTER>\n" .
	"</BODY></HTML>";

    return TRUE;
}

/**
 *  Display a page asking the user to wait for completion
 *
 *  This function helps tell the user his job is up and running,
 * and that since it takes too long, s/he should wait for it to
 * start producing output. Once there is any output available,
 * status will change and a different page will be shown.
 *
 *  For this reason we need to auto-update ourselves and/or
 * tell the user to update the page manually or bookmark it.
 *
 *  @param string $id	The Job ID (to be used in the output report and to back-link
 *  	to ourselves for auto-updating)
 *  @param string $probe The name of the probe molecule submitted by the user
 *  @param string $pt	How to treat the probe molecule (ligand or receptor)
 *  @param string $db The name of the database being screened
 *  @param string $docker The program used to perform the match (used to process
 *  	job output)
 *  @param integer $from Top result at which display should start
 *  @param integer $to Top result at which display should end
 */
function show_wait_page($id, $probe, $pt, $db, $docker, $from=1, $to=50) {
    global $www_app_dir;

    // show an auto-updated wait page
    // The layout is sort of
    //
    //	    	    GROCK
    //	IMG 	Reload this page
    //	    	    or
    //	    	Bookmark this page
    //
    //	      Your job is running
    //	      Job (id) Status (Running)
    //	This pages reloads itself automatically
    //
    auto_update('Job submitted, please wait', 20,
    	    	$id, $probe, $pt, $db, $docker, $from, $to);
    echo "<BODY BGCOLOR=\"white\" background=\"$www_app_dir/images/background.png\">\n" .
	"<CENTER><H1>GROCK results.</H1><BR /><BR />\n".
	"<TABLE BORDER=\"0\">\n<TR>\n  <TD><IMG SRC=\"$www_app_dir/images/Rodin_Penseur.jpg\"></TD>\n".
	"  <TD ALIGN=\"center\">\n".
	"  <H2>Your molecule $probe is now being docked against all molecules in $db</H2>\n" .
    	"  <H3><A HREF=\"$www_app_dir/script/report.php?id=$id".
	"&probe=$probe&pt=$pt&db=$db&docker=$docker&from=$from&to=$to\" ".
	"    style=\"color:blue; text-decoration:none\">Reload</A> ".
	"    this page to update your job status</H3>\n".
	"  <H3>or</H3>\n" .
	"  <H3>Bookmark this page to access it later using [CTRL]+[D]</H3>\n" .
    	"  <TABLE BORDER=\"2\" ALIGN=\"center\">\n" .
	"    <TR><TD VALIGN=\"top\" ALIGN=\"center\" COLSPAN=\"4\">\n" .
	"      <b>Your GROCK Job for $probe vs. $db is running.</b>\n" .
	"    </TD></TR>\n" .
	"    <TR>\n" . 
	"      <TD BGCOLOR=\"#cccccc\" VALIGN=\"top\" ALIGN=\"center\">\n".
	//"          <B>Gramm Job number: </B></TD>\n" .
	"          <B>Job number: </B></TD>\n" .
	"      <TD BGCOLOR=\"LightBlue\" VALIGN=\"top\" ALIGN=\"center\">\n".
	"   	   <B> $id </B></TD>\n" .
	"      <TD BGCOLOR=\"#cccccc\" VALIGN=\"top\" ALIGN=\"center\">\n" .
	"   	   <B>Status: </B></TD>\n" .
	"      <TD BGCOLOR=\"LightGreen\" VALIGN=\"top\" ALIGN=\"center\">\n".
	"          <B> Running </B></TD>\n" .
	"    </TR>\n" .
	"  </TABLE>\n" .
	"  </TD></TR>\n</TABLE>\n</CENTER>\n" .
	"<BR>\n<BR>\n<CENTER><P><STRONG>Note:</STRONG> ".
	"This page updates itself automatically every 20 seconds</P></CENTER>\n" .
	"<CENTER><P><STRONG>Note:</STRONG> ".
	"Please, take notice that a GROCK job may take in the order " .
	"of hours to days (or even weeks) to complete depending on the ".
	"options selected</P></CENTER>\n" .
	"</BODY></HTML>";

    return TRUE;
}

/**
 *  Display a page that allows the user to navigate through partial results.
 *
 *  @param string $id	The Job ID (to be used in the output report and to back-link
 *  	to ourselves for auto-updating)
 *  @param string $probe The name of the probe molecule submitted by the user
 *  @param string $pt	How to treat the probe molecule (ligand or receptor)
 *  @param string $db The name of the database being screened
 *  @param string $docker The program used to perform the match (used to process
 *  	job output)
 *  @param integer $from Top result at which display should start
 *  @param integer $to Top result at which display should end
 */
function show_partial_results($id, $probe, $pt, $db, $docker, $from=1, $to=50) {
    global $www_app_dir;
    
    // We need to tell the user that
    //	These are partial results of an incomplete search
    //	The page must be reloaded until the job is completed
    //	The results are whatever (allowing for result navigation
    //	as if the job were finished).
    auto_update('Partial results', 600,
    	    	$id, $probe, $pt, $db, $docker, $from, $to);
    echo "<body bgcolor=\"white\" ".
    	 "background=\"$www_app_dir/images/background.png\">\n";
    echo "<center><h1>Partial results report</h1></center>\n";
    echo "<center><table  border=\"0\" bgcolor=\"white\" ".
    	 "cellpadding=\"10\" width=\"80%\">\n<tr>\n".
    	 "<td><img src=\"$www_app_dir/images/Rodin_Penseur.jpg\"></td>\n".
	 "<td>".
	 "<p>Your GROCK job to match $probe against $db using $docker ".
         "has already been submitted for processing, but is not finished ".
	 "yet. However, if you are in a hurry, you may start looking ".
	 "at the partial results collected to date to get a feeling on ".
	 "how is your scan doing. Who knows? Maybe what you are looking ".
	 "has already popped-up. Or maybe you realize that there is ".
	 "some problem that needs intervention to fix.</p>\n";
/*
    echo "<center><h2>Job control and monitor</h2></center>\n".
    	 "<p>If you want to see how your scan is faring and check ".
	 "the progress of your GROCK jobs, you may open the ".
	 "<a href=\"job_monitor.php?id=$id&probe=$probe&db=$db".
	 "&docker=$docker&from=$from&to=$to\">GROCK monitor control ".
	 "panel</a> and follow the ".
	 "progress of GROCK comparison jobs one by one.</p>\n";
*/
    echo "<center><h2>Partial results navigation</h2></center>\n".
    	 "<p>You can navigate through the partial results available ".
	 "in the listing below. Beware that they are still partial, ".
	 "and therefore better matches may be found later in the run.</p>".
	 "</td></tr></table></center>";

    echo "<center><h3>This page will ".
    	 "<A HREF=\"$www_app_dir/script/report.php?id=$id".
	 "&probe=$probe&pt=$pt&db=$db&docker=$docker&from=$from&to=$to\" ".
	 "    style=\"color:blue; text-decoration:none\">update</a> itself periodically to ".
	 "keep you up to date.</h3></center>";
	 
    $target_list=fopen('target_list.txt', 'r');
    if (! $target_list) {
    	error("Partial report", "It seems that NO molecules have been screened!");
	echo "<center><h2>The list of screened molecules is not available</h2></center>\n".
	    "<p>This implies that either there has been an error processing ".
	    "your request, or you have attempted to check the wrong job, or ".
	    "you took too long to check your results and they have been purged ".
	    "from our server.</p>\n" .
	    "<p>In any case, there is little more that we can do at this stage. ".
	    "Perhaps you will want to <a href=\"$www_app_dir/interface/\">try again</a> or ".
	    "contact the <a href=\"mailto:$maintainer\">service maintainer</a> to ask for help.</p>\n";
    	echo "</body>\n</html>\n";
	// XXX JR XXX here we might try to recover by looping over the directory
	// contents... 
	exit;
    }
    
    // loop over the list of targets and see if any output
    // could be collected.
    clearstatcache();
    $finished_jobs = 0;
    while (!feof($target_list)) {
    	$target = strtok(trim(fgets($target_list)), ' ');
    	
	if (is_dir("$target/job_output"))
	    $finished_jobs++;
    }
    fclose($target_list);
    echo "<center><h2>$finished_jobs finished jobs</h2></center>";
    if ($finished_jobs == 0) {
    	echo "<center><h2>No jobs have finished yet</h2></center>\n".
	    "<p>Docking jobs take some time to complete. Although the".
	    "search has started, none of your jobs is finished yet. ".
	    "Please, be patient while results start to be collected.</p>\n".
	    "<h3>Note that this page will update itself periodically</h3>\n";
	return;
    }

    // Show navigation links.
    // First compute new addresses
    $prev_from = $from - 50;
    if ($prev_from < 1) $prev_from = 1;
    $prev_to = $prev_from + 50;
    if ($prev_to > $finished_jobs) $prev_to = $finished_jobs;
    
    $next_to = $to + 50;
    if ($next_to > $finished_jobs) $next_to = $finished_jobs;
    $next_from = $next_to - 50;
    if ($next_from < 1) $next_from = 1;
    
    show_nav_links($prev_from, $prev_to, $next_from, $next_to,
    	$id, $probe, $pt, $db, $docker);

    // OK, we may now procceed to show the results
    // for this we rely on 'scores.txt' and 'top_scores.txt'
    // We'll display only the top scores:
    //	Scores.txt contains a sorted list of ALL scores collected for ALL
    // molecules matched. We do not want such a HUGE listing, with 1000
    // results per molecule screened.
    //	Hence, we extract only the top score for each screened molecule.
    // Since scores are globally ranked, so are top-scores as well.
    //	To extract the top scores, we simply grep for "^   1" to generate
    // the tops listing
    echo "<center><h2>Listing of top scores ($from - $to)</h2></center>\n";
    
    // taken from dock.php as this is method-dependent
    dock_show_top_scores($docker, $id, $probe, $pt, $from, $to);

    // provide suitable navigation links (forward and backward) and savers.
    show_nav_links($prev_from, $prev_to, $next_from, $next_to,
    	$id, $probe, $pt, $db, $docker);
    
    echo "<center><h3>Note: this listing updates itself automatically ".
    	 "every 10 minutes</h3></center>\n";

    echo "</body></html>\n";
    return TRUE;
}

/**
 *  Display a page that allows the user to navigate the results
 *
 *  @param string $id	The Job ID (to be used in the output report and to back-link
 *  	to ourselves for auto-updating)
 *  @param string $probe The name of the probe molecule submitted by the user
 *  @param string $pt	How to treat the probe molecule (ligand or receptor)
 *  @param string $db The name of the database being screened
 *  @param string $docker The program used to perform the match (used to process
 *  	job output)
 *  @param integer $from Top result at which display should start
 *  @param integer $to Top result at which display should end
 */
function show_results($id, $probe, $pt, $db, $docker, $from=1, $to=50) {
    // Note that we are in state 'finished', but this
    // may well happen with some jobs unsuccessfully run!
    //	i.e. on getting output it one job ends abnormally,
    //	    it is restarted once. The second time that it
    //	    terminates abnormally, it is accepted as such.
    // It may also happen that the maximum grock duration
    // has been reached with unfinished jobs.
    // Hence there may be jobs with no output available,
    // which should be detected and notified to the user:
    //	    if processing is finished and no 'job_output'
    //	    	directory exists, the job aborted abnormally
    //	    	    report its status
    global $debug;
    global $maintainer;
    global $www_app_dir;
    
    // this time there is no need for auto-updating
    echo "<html>\n<head>\n".
         "  <TITLE>GROCK Results for $probe vs. $db ($id):Job completed</title>\n".
	 "</head>\n<body bgcolor=\"AntiqueWhite\" background=\"$www_app_dir/images/background.png\">\n";
    
    echo "<center><table width=\"70%\" border=\"2\" colspacing=\"1\" ".
    	 "background=\"$www_app_dir/images/background.png\" bgcolor=\"White\">\n";
    echo "<tr><td><center><h1>GROCK search complete</H1></center></tr></td>\n";
    echo "<tr><td><center><h2>Matching $probe against $db using $docker</h2>".
         "</center></td></tr>";
    echo "</table></center>\n";


    $target_list=fopen('target_list.txt', 'r');
    if (! $target_list) {
    	error("Report complete", "It seems that NO molecules have been screened!");
	echo "<center><h2>The list of screened molecules is not available</h2></center>\n".
	    "<p>This implies that either there has been an error processing ".
	    "your request, or you have attempted to check the wrong job, or ".
	    "you took too long to check your results and they have been purged ".
	    "from our server.</p>\n" .
	    "<p>Some molecules may have failed because there has been an ".
	    "error on the service, or they took too long to complete, or ".
	    "they simply can not be checked. It does not mean you did ".
	    "necessarily anything wrong.</p>\n".
	    "<p>In any case, there is little more that we can do at this stage. ".
	    "Perhaps you will want to <a href=\"$www_app_dir/interface/\">try again</a> or ".
	    "contact the <a href=\"mailto:$maintainer\">service maintainer</a> to ask for help.</p>\n";
    	echo "</body>\n</html>\n";
	// XXX JR XXX here we might try to recover by looping over the directory
	// contents... 
	exit;
    }
    
    // loop over the list of targets and see if their output
    // could be collected. Jobs with no output have been aborted,
    // so we must tell the user about them.
    clearstatcache();
    $finished_jobs = 0;
    $unfinished_jobs = 0;
    while (!feof($target_list)) {
    	$target = strtok(trim(fgets($target_list)), ' ');
    	if ($target == "") continue;

	if (! is_dir("$target/job_output"))
	    $unfinished_jobs++;
	else
	    $finished_jobs++;
    }
    fclose($target_list);
    
    if ($finished_jobs == 0) {
    	echo "<br><br><center><table width=\"70%\" border=\"1\" ".
	     "colspacing=\"1\" bgcolor=\"Coral\" ".
    	     "background=\"$www_app_dir/images/background.png\">\n";
	echo "<tr><td>\n".
	     "  <center><h2>There are <strong>NO</strong> results!!</h2></center>\n".
	     "</td></tr>\n<tr><td>\n".
	     "  <p>Something must have gone terribly wrong! Maybe there has been\n".
	     "  a network failure and your results could not be retrieved. Or maybe\n".
	     "  it is something else. It's difficult to tell. Your best option is\n".
	     "  to contact the <a href=\"mailto:$maintainer\">service maintainer</a>\n". 
	     "  indicating your session number (<strong>$id</strong>) and politely\n".
	     "  ask for help.\n".
	     "</td></tr>\n</table></center>\n";
    	echo "</body>\n</html>\n";
	exit;
    }
    
    echo "<br><center><h3>There are $finished_jobs finished jobs and ".
         "$unfinished_jobs unfinished jobs</h3></center>\n";
    
    if ($unfinished_jobs != 0) {
    	echo "<br><br><center><table width=\"70%\" border=\"1\" ".
	     "colspacing=\"1\" bgcolor=\"Coral\" ".
    	     "background=\"$www_app_dir/images/background.png\">\n";

    	echo "<tr><td><center><h2>Not all molecules could be successfully checked</h2></center></td></tr>\n".
	    "<tr><td><p>It seems that GROCK could not check your probe molecule $probe ".
	    "against all the molecules in the $db database. Some of the ".
	    "tests failed or could not be carried out</p>\n".
	    "<p><strong>The following results are incomplete</strong> but ".
	    "they may still provide enough information for you. Please, ".
	    "review the <a href=\"$www_app_dir/script/fishy.php?id=$id\">omitted molecules </a> and check if ".
	    "any of them may be relevant for your screening. If you think ".
	    "that they are possibly irrelevant or can be dismissed, then ".
	    "go ahead, but pick these results with a grain of salt. ".
	    "Otherwise you may want to reconsider resubmitting the missing ".
	    "checks, or -if they are too many- re-running GROCK again</p>\n".
	    "<center><h3><a href=\"$www_app_dir/script/fishy.php?id=$id\">See failed ".
	    "molecules</a></h3></center></td></tr>\n";

    	echo "</table></center>";
    }
    // OK, we may now procceed to show the results
    // for this we rely on 'scores.txt' and 'top_scores.txt'
    // We'll display only the top scores:
    //	Scores.txt contains a sorted list of ALL scores collected for ALL
    // molecules matched. We do not want such a HUGE listing, with 1000
    // results per molecule screened.
    //	Hence, we extract only the top score for each screened molecule.
    // Since scores are globally ranked, so are top-scores as well.
    //	To extract the top scores, we simply grep for "^   1" to generate
    // the tops listing
    echo "<center><h2>Listing of top scores ($from - $to)</h2></center>\n";
    
    // Show navigation links.
    // First compute new addresses
    $prev_from = $from - 50;
    if ($prev_from < 1) $prev_from = 1;
    $prev_to = $prev_from + 50;
    if ($prev_to > $finished_jobs) $prev_to = $finished_jobs;
    
    $next_to = $to + 50;
    if ($next_to > $finished_jobs) $next_to = $finished_jobs;
    $next_from = $next_to - 50;
    if ($next_from < 1) $next_from = 1;
    
    show_nav_links($prev_from, $prev_to, $next_from, $next_to,
    	$id, $probe, $pt, $db, $docker);

    // taken from dock.php as this is method-dependent
    dock_show_top_scores($docker, $id, $probe, $pt, $from, $to);

    // provide suitable navigation links (forward and backward) and savers.
    show_nav_links($prev_from, $prev_to, $next_from, $next_to,
    	$id, $probe, $pt, $db, $docker);

    echo "</body></html>\n";
    return TRUE;
}

/**
 *  Output a web page header with the title and an auto-update page
 * directive.
 *
 *  @param string $status A status message to include in the page title
 *  @param string $id	The Job ID (to be used in the output report and to back-link
 *  	to ourselves for auto-updating)
 *  @param string $probe The name of the probe molecule submitted by the user
 *  @param string $pt	How to treat the probe molecule (ligand or receptor)
 *  @param string $db The name of the database being screened
 *  @param string $docker The program used to perform the match (used to process
 *  	job output)
 *  @param integer $from Top result at which display should start
 *  @param integer $to   Top result at which display should end
 */
function auto_update($status, $when, $id, $probe,  $pt, $db, $docker, $from=1, $to=50)
{
    global $www_app_dir;
    
    echo "<html>\n<head>\n  <TITLE>GROCK Results for $probe vs. $db ".
    	 "using $docker ($id): $status</TITLE>\n";

    // make the page pre-expired
    //$date=date( "j F Y H:i", filemtime("$file") );
    //echo "<META http-equiv=\"Expires\" content=\"" . date("D, j M Y H:m:s T") . "\">";

    // make the page reload itself periodically
    echo "  <META HTTP-EQUIV=\"Refresh\" CONTENT=\"6000\" ".
         " URL=$www_app_dir/script/report.php?id=$id&probe=$probe&pt=$pt&db=$db".
	 "&docker=$docker&from=$from&to=$to\">\n";
    echo "</head>\n";
}

/**
 *  Show navigation links (backwards, save results, forwards)
 *
 *  We need to know the previous results to display, following results to
 * display and all additional paramaters required by the form.
 *
 *  NOTE: we might try to do it simpler with superglobals, i.e., simply
 * substituting 'from' and 'to' by the new values and using the same
 * $_GET with which we were invoked. Would this be better? Think of it.
 *
 *  @param integer $prev_from Top result at which previous display should start
 *  @param integer $prev_to Top result at which previous display should end
 *  @param integer $next_from Top result at which next display should start
 *  @param integer $next_to Top result at which next display should end
 *  @param string $id	The Job ID (to be used in the output report and to back-link
 *  	to ourselves for auto-updating)
 *  @param string $probe The name of the probe molecule submitted by the user
 *  @param string $pt	How to treat the probe molecule (ligand or receptor)
 *  @param string $db The name of the database being screened
 *  @param string $docker The program used to perform the match (used to process
 *  	job output)
 */
function show_nav_links($prev_from, $prev_to, $next_from, $next_to, 
    $id, $probe, $pt, $db, $docker)
{
    global $www_app_dir;
    global $www_tmp;

    echo "<center><table border=\"0\">\n<tr>\n".
    	 "<td><a href=\"$www_app_dir/script/report.php?".
	    "id=$id&probe=$probe&pt=$pt&db=$db&docker=$docker".
	    "&from=$prev_from&to=$prev_to\" style=\"color:white\">".
	    "<img src=\"$www_app_dir/images/back.gif\" alt=\"[Previous 50]\">".
	 "</a></td>".
    	 "<td><a href=\"".$www_tmp."/".basename(getcwd())."/top_scores.txt\" style=\"color:white\">".
	    "<img src=\"$www_app_dir/images/download.png\" alt=\"[Save top scores]\">".
	 "</a></td>".
    	 "<td><a href=\"$www_app_dir/script/report.php?".
	    "id=$id&probe=$probe&pt=$pG&db=$db&docker=$docker".
	    "&from=$next_from&to=$next_to\" style=\"color:white\">".
	    "<img src=\"$www_app_dir/images/forward.gif\" alt=\"[Next 50]\">".
	 "</a></td>".
    	 "</tr>\n</table></center>\n";
}

?>
