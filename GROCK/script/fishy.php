<?
/**
 * Display failed jobs.
 *
 * And perhaps at some future step allow the user to relaunch them.
 *
 * (That would be 'repesca' in Spanish, hence the name).
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
 * @version 	$Id: fishy.php,v 1.1 2005/11/22 10:05:07 netadmin Exp $
 * @see     	util.php
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 0.0
 */

require 'config.php';
require 'util.php';

$id=$_GET['id'];

echo "<html>\n<head><title>GROCK results: failed jobs</title></head>\n";
echo "<body bgcolor=\"AntiqueWhite\" background=\"$www_app_dir/images/background.png\">\n";
echo "<center><h1>Targets that could not be checked</h1></center>\n";

if (! chdir("$local_tmp_path/$id")) {
    letal("GROCK results", "The session you specified does not exist!!!");
}

$target_list=fopen('target_list.txt', 'r');
if (! $target_list) {
    error("Failure report", "It seems that NO molecules have been screened!");
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

$unfinished_jobs = 0; 
$total_jobs = 0;
while (!feof($target_list)) {
    $line = fgets($target_list);
    if ($line == "") continue;
    $target = strtok(trim($line), ' ');
    $full_desc = strtok("\n");
    $total_jobs++;
    
    if (!is_dir("$target/job_output")) {
    	// this is a failed job
        $unfinished_jobs++;
        $failed[$target] = $full_desc;
    }
}
fclose($target_list);

if ($unfinished_jobs == 0) {
    echo "<p>All jobs finished correctly.</p>\n";
    echo "</body></html>\n";
    exit;
}

echo "<p>There are $unfinished_jobs unfinished jobs out of a total of \n".
     "$total_jobs jobs. These jobs represent molecules from the search \n".
     "database that could not be checked. These jobs may have failed for \n".
     "various reasons. The following listing shows which jobs have \n".
     "failed.</p>\n";

echo "<center><table>\n";
echo "<tr><th>Target</th><th>Description</th><th>Resubmitted</th></tr>\n";
foreach ($failed as $job => $description) {
    echo "<tr><td>$job</td><td>$description</td>";
    if (file_exists("$job/resubmitted")) {
    	echo "<td>".file_get_contents("$job/resubmitted")." times</td>";
    }
    else
    	echo "<td>No</td>";
    echo "</tr>\n";
}
echo "</table></center>\n";
echo "</body></html>\n";

?>
