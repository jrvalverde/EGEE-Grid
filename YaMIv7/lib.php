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
 * @package 	YaMI
 * @author  	David Garcia <david@cnb.uam.es>
 * @author  	Jose R. Valverde <jrvalverde@es.embnet.org>
 * @copyright 	CSIC
 * @license 	../c/lgpl.txt
 * @version 	$Id$
 * @see     	util.php
 * @see     	gramm.php
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 0.0
 */


/**
 * include global configuration definitions
 */
require_once("config.php");

// ------------- COMMODITY ROUTINES ----------------

/**
 *  Get authentication data
 *
 *  Security may be done here or somewhere else.
 *  IFF we are to run the commands on the Grid using a server certificate
 *  (current situation) then all we need to do is ensure that the user
 *  calling us is valid, and this may as well be done using plain Web 
 *  authentication (via a .htaccess/.htpasswd system). Then we would
 *  rely on the web server to filter users for us.
 *
 *  IFF we are to run with a user ID, then we need to gather user ID and
 *  AUTH information and pass it on to the Grid back end. Then we would
 *  have to be installed with SSL/TLS (https) and get in the form additional
 *  data:
 *  	UI to use
 *  	username on the UI
 *  	password on the UI
 *  	grid passphrase
 *
 *  Alternatively we may gather that info on a previous auth form, and store
 *  it using sessions. Actually, it might make sense to store the info in a
 *  translucent mysql database. This info might then be that needed to run
 *  myproxy..
 *
 *  Finally we may have a separate form to run myproxy on behalf of the user,
 *  and then store only the user's UI-ID and UI-password in the session (hence
 *  protecting the Grid passphrase). This separate form might be a Java program
 *  communicating securely with a myproxy server with SSH.
 *
 *  TO BE ENHANCED.
 *
 *	For the time being use configuration data (i.e. use a server
 * certificate). 
 * It would be trivial to get data from the form using $_POST
 *	Ideally this would be an array of auth_token arrays, so we might
 * resort to using various alternate grid entry points as back-ends.
 */

function get_auth_data($auth)
{
    $auth['server'] = $grid_server;		// user@back-end
    $auth['user'] = $grid_user;		// user
    $auth['host'] = $grid_host;		// back-end
    $auth['password'] = $grid_password;	// user@back-end passwd
    $auth['passphrase'] = $grid_passphrase;	// user grid passphrase
}


/**
 * Start the display of a www page
 *
 * We have it as a function so we can customise all pages generated as
 * needed. This routine will open HTML, create the page header, and 
 * include any needed style sheets (if any) to provide a common 
 * look-and-feel for all pages generated.
 *
 *  @param integer $reload  number of seconds until next reload (0 = no reload)
 */
function set_header($reload)
{
    global $app_name, $app_dir;

    // Start HTML vx.xx output
    echo "<html>";
    // Print headers
    echo "<head>\n";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=ISO-8859-1\">\n";
    echo "<meta name=\"description\" content=\"a web interface to run modeller on the EGEE grid\">\n";
    echo "<meta name=\"author\" content=\"EMBnet/CNB\">\n";
    echo "<meta name=\"copyright\" content=\"(c) 2004-2006 by CSIC - Open Source Software\">\n";
    echo "<meta name=\"generator\" content=\"$app_name\">\n";
    if ($reload > NO_RELOAD)
    	echo "<meta http-equiv=\"Refresh\" content=\"$reload\" ".
	"url=\"".WWW_TMP_ROOT."/$session_id/\"2>\n";
    echo "<link rel=\"stylesheet\" href=\"$app_dir/style/style.css\" type=\"text/css\"/>\n";
    echo "<link rel=\"shortcut icon\" href=\"$app_dir/images/favicon.ico\"/>\n";
    echo "<title=\"$app_name\">\n";
    echo "</head>";
    // Prepare body
    echo "<body bgcolor=\"white\" background=\"$app_dir/images/6h2o-w-small.gif\" link=\"ffc600\" VLINK=\"#cc9900\" ALINK=\"#4682b4\">\n";
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
	// close body
	echo "</body><hr>";
	// footer
	echo "<center><table border=\"0\" width=\"90%\"><tr>";
	    // Copyright and author
	    echo "<td><a href=\"$app_dir/c/copyright.html\">&copy;</a>EMBnet/CNB</td>";
    	    // contact info
	    echo "<td align=\"right\"><a href=\"emailto:$maintainer\">$maintainer</a></td>";
	echo "</tr></table></center>";
	// Page
	echo "</html>";
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
    // TODO ECMAscript
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
	echo "ERROR - HORROR\n";
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
    	set_header(NO_RELOAD);
	error($where, $what);
	set_footer();
    	exit();
}

/**
 *  Output a log message
 */
function log_message($what)
{
    global $output, $app_name;
    
    fwrite($output, "$app_name: $what\n");
}

/**
 *  Output a log notice
 */
function log_notice($what)
{
    global $output, $app_name;
    
    fwrite($output, "$app_name NOTICE:  $what\n");
}

/**
 *  Output a plain-text warning to log file
 */
function log_warning($what)
{
    global $output, $app_name;
    
    fwrite($output, "$app_name WARNING: $what\n");
}

/**
 *  Output an error indicating where it took place and what happened
 */
function log_error($where, $what)
{
    global $output, $app_name;
    
    fwrite($output, "\n$app_name ERROR:  ($where) $what\n\n");
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
    
    $debug = TRUE;
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
    
    // we are still in YaMI's home directory
    copy("./modeller.sh", SYS_TMP_ROOT."/$session_id/modeller.sh");
    copy("./modeller.jdl", SYS_TMP_ROOT."/$session_id/modeller.jdl");
    copy("./show_results.php", SYS_TMP_ROOT."/$session_id/index.php");

    chdir("$local_tmp_path/$session_id");
    if ($debug) {
    	$cwd = getcwd();
	echo "<p>Working on $cwd (session is $session_id)</p>\n";
    }

    return $session_id;
    
}

/**
 *  Upload a file to the current directory
 *
 *  Get a user file submitted under a specific 'tag': for the user to
 * submit a file, it must be associated with a specific input field in
 * the submission form. This input field in turn will have an identifying
 * tag in the form. What we are actually doing here is to get the file
 * submitted under the input field associated to the tag provided.
 *
 *  @param  string tag	Tag identifying the file input box in the form.
 *
 *  @return string filename The original name of the file in the user's box.
 */
function upload_file($tag)
{
    global $debug;
    global $local_tmp_path;
    
    $dir = getcwd(); // bring it to the current directory
    
    if ($debug) echo "<p>Upload file</p>\n";

    $uploadfile = getcwd() . basename($_FILES[$tag]['name']); 
    // Use this instead if you want to use a predefined name
    // $uploadfile = $dir.'/'."$tag";
    
    $new_file = $_FILES[$tag]['tmp_name'];
    $new_filename = $_FILES[$tag]['name'];  // original user filename
    $new_filesize = $_FILES[$tag]['size'];

    if ($debug) echo "<blockquote>Temporary name: $new_file<br />\n".
    	    	     "File name: $new_filename<br />\n".
		     "File size: $new_filesize<br />\n".
		     "Saved as: $uploadfile</blockquote>\n";
    
    if ($new_file == 'none' || $new_file = '')
    {	
    	// letal prints an error and dies
    	letal("Upload file", "No file uploaded");
    }
    
    if ($new_filesize == 0)
    {
    	letal("Upload file", "Uploaded file $new_filename has zero length");
    }

    // seems OK, get it
    if (move_uploaded_file($new_file, $uploadfile)) 
    {
    	return basename($new_filename);	// original user file name
    } 
    else 
    {
    	letal("Upload file", "Could not upload your file $new_filename");
    }
}

/**
 *  Create a SEG file from a sequence and a set of known structures in PDB format
 *
 *  XXX REVIEW (jr) XXX
 */
function pdb2seg($sequence_pir, $segfile, $knowns)
{
	$pdb2seg = "/opt/structure/bin/pdb2seg $sequence_pir ".
	    	PDB_DIR.' '.PDB_PREFIX.' '.PDB_SUFFIX.
		" $segfile $knowns";
	exec("$pdb2seg");
}

/**
 *  Create MODELLER configuration file
 *
 *  This function creates a MODELLER configuration file (.top) using the
 * supplied user options and returns the name of the new file.
 *
 *  @param array options    User selected options
 *
 *  @return string filename Name of the config file generated.
 */
function make_config($opts)
{
    // Write the top file
    $topfile='./'.$opts['seq_name'].'.top';
    $fp = fopen( "$topfile", "w" );

    fwrite( $fp, 'INCLUDE\n'.
    	     "SET ATOM_FILES_DIRECTORY = '".ATOM_FILES_PATH."'\n".
    	     "SET PDB_EXT = '".PDB_SUFFIX."'\n" );

    if ( $md_level != 'default' )
	fwrite( $fp, "SET MD_LEVEL = '${opts['md_level']}'\n" );

    fwrite( $fp, "SET STARTING_MODEL = ${opts['model_first']}\n".
             "SET ENDING_MODEL = ${opts['model_last']}\n".
	     "SET DEVIATION = ${opts['deviation']}\n".
	     "SET KNOWNS = '${opts['knowns']}'\n".
	     "SET HETATM_IO = ${opts['hetatm_io']}\n".
	     "SET WATER_IO = ${opts['water_io']}\n".
	     "SET HYDROGEN_IO = ${opts['hydrogen_io']}\n".
	     "\n".
	     "SET ALIGNMENT_FORMAT = '${opts['alignment_format']}'\n".
	     "SET SEQUENCE = '$opts['seq_file']}'\n" );
	     
    if ( $opts['use_alig'] == 'yes' )
	fwrite( $fp, "SET ALNFILE = '${opts['alig_file']}'\n" );
    else
    	fwrite( $fp, "SET SEGFILE = '${opts['seg_file']}'\n" );
	
    fwrite( $fp, "CALL ROUTINE = '${opts['routine']}'\n" );
    fclose( $fp );
    
    return $topfile;
}

/**
 *  Build the model(s) as requested by the user
 *
 *  This function sets up the environment and calls MODELLER to build
 * a number of models after a configuration file built according to
 * user specs.
 *
 *  @param string $topfile  The name of the '.top' file containing the
 *  	    	    	    user selected options.
 */
function build_models($auth, $opts)
{
    global $debug;
    
    $topfile = make_config($opts);
    
    // Create a modeller run script (or copy it from our install dir)
    // run the driver script in the background
    //     The driver script:
    //		Sets-up environment
    //		Runs modeller directly with args
    //		touches "done"
    //		commits suicide (removes itself to clear things up)

    $modeller = "./modeller.sh $topfile";

    // Note:  If you start a program using this function ( exec ) and want to leave it
    // running in the background, you have to make sure that the output of 
    // that program is redirected to a file or some other output stream or 
    // else PHP will hang until the execution of the program ends.
    // Modeller creates his own .log file, so we redirect the output to a file
    // called "modeller.log" and after the program execution the modeller.sh
    // script delete this file.
    exec("$modeller");

    if ($debug > 0) 
        echo "<CENTER><H1>Done.</H1></CENTER>";
}

?>
