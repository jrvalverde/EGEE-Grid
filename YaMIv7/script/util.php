<?

/**
 * General utility functions
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
 *
 */


/**
 * include global configuration definitions
 */
require_once('config.php');

// ------------- COMMODITY ROUTINES ----------------

/**
 * Start the display of a www page
 *
 * We have it as a function so we can customise all pages generated as
 * needed. This routine will open HTML, create the page header, and 
 * include any needed style sheets (if any) to provide a common 
 * look-and-feel for all pages generated.
 */
function set_header()
{
    global $app_name, $app_dir;
    // Start HTML 
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">";
    echo "<html>";
    // Print headers
    echo "<head>";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=ISO-8859-1\">";
    echo "<meta name=\"description\" content=\"a web interface to run MODELLER on the EGEE grid\">";
    echo "<meta name=\"author\" content=\"EMBnet/CNB\">";
    echo "<meta name=\"copyright\" content=\"(c) 2004-5 by CSIC - Open Source Software\">";
    echo "<meta name=\"generator\" content=\"$app_name\">";
    //echo "<link rel=\"stylesheet\" href=\"$app_dir/style/style.css\" type=\"text/css\"/>";
    //echo "<link rel=\"shortcut icon\" href=\"$app_dir/images/favicon.ico\"/>";
    echo "<title=\"$app_name\">";
    echo "</head>";
    
    // Prepare body
   // echo "<body bgcolor=\"white\" background=\"$app_dir/images/6h2o-w-small.gif\" link=\"ffc600\" VLINK=\"#cc9900\" ALINK=\"#4682b4\">";
}

/**
 * close a web page
 *
 * Make sure we end the page with all the appropriate formulisms:
 * close the body, include copyright notice, state creator and
 * any needed details, and close the page.
 */
function set_footer()
{
	global $maintainer, $app_dir;
	
	// footer
	echo "<hr>";

	echo "<center><table border=\"0\" width=\"90%\"><tr>";
	    // Copyright and author
	    echo "<td><a href=\"$app_dir/c/copyright.html\">&copy;</a>EMBnet/CNB</td>";
    	    // contact info
	    echo "<td align=\"right\"><a href=\"emailto:$maintainer\">$maintainer</a></td>";
	echo "</tr></table></center>";
	
	// close body and page
	echo "</body></html>";
}

/**
 * print a warning
 *
 * Prints a warning in a separate pop-up window.
 * A warning is issued when a non-critical problem has been detected.
 * Execution can be resumed using some defaults, but the user should
 * be notified. In order to not disrupt the web page we are displaying
 * we use a JavaScript pop-up alert to notify the user.
 *
 * @param msg the warning message to send the user
 */
function warning($msg)
{
    //TODO ECMAscript
    echo "<script language=\"JavaScript\">";
    echo "alert(\"WARNING:\n $msg\");";
    echo "</script>";
}

/**
 * print an error message and exit
 *
 * Whenever we detect something wrong, we must tell the user. This function
 * will take an error message as its argument, format it suitably and
 * spit it out.
 *
 * @note This might look nicer using javascript to pop up a nice window with
 * the error message. Style sheets would be nice too.
 *
 * @param where the name of the caller routine or the process where the
 * 		error occurred
 * @param what  a description of the abnormal condition that triggered
 *  		the error
 */

function error($where, $what)
{
	// format the message
	echo "<p></p><center><table border=\"2\">\n";
	echo "<tr><td><center><font color=\"red\"><strong>\n";

	// output the message
	echo "ERROR - HORROR\n";

	// close format
	echo "</strong></font></center></td></tr>\n";
	echo "<tr><td><center><b>$where</b></center></td></tr>\n";
	echo "<tr><td><center>$what</center></td></tr>\n";
	echo "</table></center><p></p>\n";
}

/**
 * print a letal error message and die
 *
 * This function is called whenever a letal error (one that prevents
 * further processing) is detected. The function will spit out an
 * error message, close the page and exit the program.
 * It should seldomly be used, since it may potentially disrupt the
 * page layout (e.g. amid a table) by not closing open tags of which
 * it is unaware.
 * Actually it is a wrapper for error + terminate.
 *
 * @param where location (physical or logical) where the error was
 * detected: a physical location (routine name/line number) may be
 * helpful for debugging, a logical location (during which part of
 * the processing it happened) will be more helful to the user.
 *
 * @param where the name of the caller routine or the process where the
 * 		error occurred
 * @param what  a description of the abnormal condition that triggered
 *  		the error
 */
function letal($where, $what)
{
	error($where, $what);
	set_footer();
    	exit;
}

/**
 *  Output a log message
 */
function log_message($what)
{
    global $output;
    
    fwrite($output, "YaMIv7: $what\n");
}

/**
 *  Output a log notice
 */
function log_notice($what)
{
    global $output;
    
    fwrite($output, "YaMIv7 NOTICE:  $what\n");
}

/**
 *  Output a plain-text warning to log file
 */
function log_warning($what)
{
    global $output;
    
    fwrite($output, "YaMIv7 WARNING: $what\n");
}

/**
 *  Output an error indicating where it took place and what happened
 */
function log_error($where, $what)
{
    global $output;
    
    fwrite($output, "\nYaMIv7 ERROR:  ($where) $what\n\n");
}

/**
 *  Generate a random number using srand()/rand()
 *
 *  @return integer random number
 */
function random_number()
{
    /**
     * Random values generation
     * srand -- Seed the random number generator
     * rand -- Generate a random integer
     */
     srand((double)microtime()*10000);
     $random_value = rand();
     return $random_value;
}

/**
 *  Generate a session directory to be used for doing all our work
 *
 *  We will create a session directory on the system scratch space
 * to perform all our subsequent work.
 *
 *  We need to know the systemwide scratch/tmp directory (which is a
 * global defined in the 'config.php' file).
 *
 *  We do all our work on the system scratch space, but to avoid clashes
 * between simultaneous instances of this same service, we generate a
 * unique session_ID and use it to ensure that we are using a namespace that
 * is not being used by anyone else.
 *
 *  To generate the unique name, we use the fact that mkdir(2) should be
 * atomic and return an error (EEXIST) if the specified pathname already
 * exists.
 *
 *  To avoid an infinite loop in the event of an error, we allow a maximum
 * number of tries.
 *
 *  @return string a unique name for the sandbox. This name should be
 *  	    	unique or we risk colliding with other simultaneous
 *  	    	runs.
 */
function activate_new_sandbox()
{
    
    global $local_tmp_path;
    global $debug;
    
    //$debug = TRUE;
    if ($debug)
    	echo "<h3>activating a new sandbox</h3>";

    $i = 0;
    do {
    	if ($i > 10) 
	    letal("Activate a new sandbox", 
	    	"Could not create a sandbox on $local_tmp_path after 10 tries\n");
		
    	$i++;
        $session_id = random_number();
	echo "<p>$i: Trying $session_id</p>\n";
    } while (!mkdir("$local_tmp_path/$session_id", 0700));
    
    chdir("$local_tmp_path/$session_id");
    
    if ($debug) 
    {
    	$cwd = getcwd();
	echo "<p>Working on $cwd (session is $session_id)</p>\n";
    }

    return $session_id;
}

/**
 *  Upload the probe molecule specified by the user to the session directory
 * as 'probe.pdb'.
 *
 *  This function accesses the global variable $_FILES with the known name
 * for the user file option to upload the user data. If the user data name
 * is changed in the form, it should be also changed here.
 *
 *  To locate the session directory we need a global variable to tell us
 * which is the default work directory, and a session_id to identify a
 * unique session directory within it to store safely the file without
 * colliding with other simultaneous instances.
 *
 *  @return string containing the original name of the file uploaded if all goes well,
 *  	otherwise die after printing an error message.
 *
 */
 
 // TO CHANGE!!!!!!!!!!!!!!!!
function upload_probe_data()
{
    global $debug;
    global $local_tmp_path;
    
    $dir = getcwd(); // use to bring it to the current directory
    
    if ($debug) echo "<p>Uploading user data to ".$dir."/probe.pdb</p>\n";

    // We want to know the name of the file uploaded. For commodity (to us)
    //	we'll use a supplied name. Would probably be better to use something
    //	else (the original name) and return it to the caller.
    //$uploadfile = getcwd() . basename($_FILES['upload']['name'][0]); 
    $uploadfile = $dir.'/probe.pdb';

    $probe_file = $_FILES['upload']['tmp_name'][0];
    $probe_filename = $_FILES['upload']['name'][0];
    $probe_filesize = $_FILES['upload']['size'][0];
    
    if ($probe_file == 'none' || $probe_file = '')
    {	
    	// letal prints an error and dies
    	letal("Get_probe", "No file uploaded");
    }
    
    if ($probe_filesize == 0)
    {
    	letal("Get_probe", "Uploaded file has zero length");
    }

    // TO CHANGE
    if (move_uploaded_file($_FILES['upload']['tmp_name'][0], $uploadfile)) 
    {
    	return basename($_FILES['upload']['name'][0]);	// original user file name
    } 
    else 
    {
    	letal("Get probe", "Could not upload your probe molecule");
    }
}

?>
