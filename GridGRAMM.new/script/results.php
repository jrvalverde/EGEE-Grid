<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
	<meta http-equiv="Refresh" content="15; url=./index.php">
	<title>Results</title>
</head>

<?
/**
 * Display results
 *
 *	Present the results to the user and allow him/her to browse,
 * navigate and visualize them in a number of ways.
 *
 * @package GridGRAMM
 * @author David Garcia Aristegui <david@cnb.uam.es>
 * @copyright CSIC - GPL
 * @version 1.0
 */

/**
 * General utility functions
 */
require("./functions.php");

/**
 * Current directory
 *
 * The 'child' process needs to know were is the working directory, to generate
 * there the middleware output files
 *
 * @global string $current_directory
 */
$current_directory=getcwd();

// We check the job status...
// This is a particular case, the job is done, we only show the results, otherwise we have to connect...

if( check_status()=="Current Status:     Cleared")
{	
	show_results($current_directory);
	echo "<center><table>";
	echo "<tr><td align=\"center\"><img src=\"egee.jpg\" alt=\"[EGEE]\"></a></td></tr>";
	echo "</table></center>";
	echo "<p><a href=\"http://validator.w3.org/check?uri=referer\"><img border=\"0\" ";
	echo "src=\"http://www.w3.org/Icons/valid-html401\"alt=\"Valid HTML 4.01!\" height=\"31\" width=\"88\"></a> ";
        echo "</p></body></html>";
	exit;
}


// If the job is not 'Cleared', we continue. First we check the grid job identifier
if( file_exists("./gramm/identifier.txt") )
{	
	// This function opens the 'child' process, is a ssh connection with the User Interface
	open_connection($server, $grid_path, $process, $descriptorspec, $pipes);

	// Enter the Grid!!!
	enter_grid($pipes, $grid_pass);
	
	// We need the job identifier to run retrieve all the job information	
	job_identifier();
	
	// Job status output
	fwrite($pipes[0], "edg-job-status $identifier > $current_directory/gramm/status.txt\n");
	
	// We wait until the file is available
	while (!file_exists("./gramm/status.txt"))
	{
		sleep(1);	
	}
	
	// We check again the job status...
	$status=check_status();
	
} else
{
	echo "<h1>Error, there is no job identifier file!!!</h1><br />\n";
	exit; 
}

// We have a new status
if($status=="Current Status:     Done (Success)")
{	
	// We control when we get the job output with the output.txt file
	// If output.txt don't exists we have to execute 'edg-job-get-output'
	if (!file_exists("./output.txt"))
	{	
		// Get the job output
		fwrite($pipes[0], "edg-job-get-output --dir $current_directory $identifier > $current_directory/output.txt\n");
		
		// We wait until the file is available
		while (!file_exists("./output.txt"))
		{
			sleep(1);	
		}
		
		echo "<pre>\n";
				//We read the middleware output
				readfile("./output.txt");
		echo "</pre>\n";
	}
	
	// We can show the files if they are unpacked only!!!
	if (unpack_results()=="OK")
	{
		show_results();
	} else
	{
		echo "<center><h2>Please reload this page to show results. GridGramm is unpacking the results.</h2></center>";
	}
} else
{	
	echo "<center><table border=2>";
	echo "<tr><td>"; 
		echo "<pre>\n";
			//We read the middleware output
			readfile("./gramm/submit.txt");
		echo "</pre>\n";
	echo "</td></tr>";
	echo "</table></center>";
	echo "<hr>";
	echo "<center><h2>GridGramm results are not available. $status</h2></center>";
	echo "<center><h1>Please, bookmark this page. This page is updated every 15 seconds.</h1></center>";
}

fwrite($pipes[0], "grid-proxy-destroy\n");

// Closes the ssh connection 
close_connection($process, $descriptorspec, $pipes);	
?>
<center>
<table>
<tr>
	<td align=center><img src="egee.jpg" alt="[EGEE]"></a>
	</td>
</tr>
</table>
<p>
	<a href="http://validator.w3.org/check?uri=referer">
	<img border="0" src="http://www.w3.org/Icons/valid-html401"alt="Valid HTML 4.01!" height="31" width="88"></a>
</p>
</center>

</body>
</html>
