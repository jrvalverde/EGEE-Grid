<?
/**
 * General utility functions
 *
 * This file contains convenience functions used throughout the package. 
 *
 * @version 1.0
 * @copyright CSIC - GPL
 */

/**
 * include global configuration definitions
 */
require_once("./config.php");

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
    // Start HTML vx.xx output
    echo "<html>";
    // Print headers
    echo "<head>";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=ISO-8859-1\">";
    echo "<meta name=\"description\" content=\"a web interface to run TINKER molecular modelling on the EGEE grid\">";
    echo "<meta name=\"author\" content=\"EMBnet/CNB\">";
    echo "<meta name=\"copyright\" content=\"(c) 2004-5 by CSIC - Open Source Software\">";
    echo "<meta name=\"generator\" content=\"$app_name\">";
    echo "<link rel=\"stylesheet\" href=\"$app_dir/style/style.css\" type=\"text/css\"/>";
    echo "<link rel=\"shortcut icon\" href=\"$app_dir/images/favicon.ico\"/>";
    echo "<title=\"$app_name\">";
    echo "</head>";
    // Prepare body
    echo "<body bgcolor=\"white\" background=\"$app_dir/images/6h2o-w-small.gif\" link=\"ffc600\" VLINK=\"#cc9900\" ALINK=\"#4682b4\">";
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
 * @param what a descrition of the abnormality detected.
 */
function letal($what, $where)
{
	error($what, $where);
	set_footer();
    	exit();
}
?>
