<?
//It's mandatory to access GROCK session variables
session_start();

/**
 * process a GROCK query
 *
 *  This file retrieves the form parameters, check them for validity
 * and then calls do_grock() to do the actual work.
 *
 *  REARRANGED - v1.1 - 22-aug-2005 (j)
 *  DEBUGGED   - v1.2 - 26-aug-2005 (j)
 *
 * @package 	grock
 * @author  	David Garcia <david@cnb.uam.es>
 * @author  	Jose R. Valverde <jrvalverde@es.embnet.org>
 * @copyright 	CSIC
 * @license 	../c/gpl.txt
 * @version 	$Id$
 * @see     	config.php
 * @see     	util.php
 * @see     	grock_lib.php
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 0.0
 */

require_once('config.php');
require_once('util.php');
require_once('grock_lib.php');

$debug = TRUE;
if ($debug)
    echo "<H1>GROCK: High Throughput docking on the Grid</H1>\n";


// SECURITY
//
//  Security may be done here or somewhere else.
//  IFF we are to run the commands on the Grid using a server certificate
//  (simplest scenario) then all we need to do is ensure that the user
//  calling us is valid, and this may as well be done using plain Web 
//  authentication (via a .htaccess/.htpasswd system). Then we would
//  rely on the web server to filter users for us.
//
//  Grid authentication would then be done using default values provided
//  in 'config.php' or 'config.inc'
//
function set_auth_defaults(&$auth)
{
    global $grid_server, $grid_user, $grid_host;
    global $grid_password, $grid_passphrase;

    // Set default values
    $auth['server'] = $grid_server;		// user@back-end
    $auth['user'] = $grid_user;		// user
    $auth['host'] = $grid_host;		// back-end
    $auth['password'] = $grid_password;	// user@back-end passwd
    $auth['passphrase'] = $grid_passphrase;	// user grid passphrase

}

// SECURITY
//
//  IFF we are to run with a user ID, then we need to gather user ID and
//  AUTH information and pass it on to the Grid back end. Then we would
//  have to be installed with SSL/TLS (https) and get in the form additional
//  data:
//  	UI to use
//  	username on the UI
//  	password on the UI
//  	grid passphrase
//
//  Alternatively we may gather that info on a previous auth form, and store
//  it using sessions. Actually, it might make sense to store the info in a
//  translucent mysql database. This info might then be that needed to run
//  myproxy..
//
//  Finally we may have a separate form to run myproxy on behalf of the user,
//  and then store only the user's UI-ID and UI-password in the session (hence
//  protecting the Grid passphrase). This separate form might be a Java program
//  communicating securely with a myproxy server with SSH.
//
//  TO BE ENHANCED.
//
//	Ideally this would be an array of auth_token arrays, so we might
// resort to using various alternate grid entry points as back-ends. It would
// also make life for the end user a lot more cumbersome unless we could use
// server certificates and hide all authentication details from the user.
//
function get_user_auth(&$auth)
{

    if ($_POST['user_login'] != '')
    	$auth['user'] = $_POST['user_login'];
    else {
    	error("create dock job", "define a user name");
    	return FALSE;
    }

    // NOTE that we are not asking the user which UI to use!!!
    //	Meaning: that you better set it correctly on 'grid_config.php' !!!
    $auth['server'] = $auth['user'].'@'.$auth['host'];	// user@back-end

    if ($_POST['user_password'] != '')
    	$auth['password'] = $_POST['user_password'];	// user@back-end passwd
    else {
    	error("create dock job", "define a user password");
    	return FALSE;
    }

    if ($_POST['passphrase'] != '')
    	$auth['passphrase'] = $_POST['passphrase'];
    else {
    	error("create dock job", "define a passphrase");
    	return FALSE;
    }

    return TRUE;
}

function get_gramm_options(&$options)
{
    // GRAMM docking resolution
    $options['resolution'] = $_POST["resolution"];
    // GRAMM docking type
    $options['representation'] = $_POST["representation"];
}

//
//  T H E R E   W E   G O ! ! !
//

// Set default values
$auth=array();
set_auth_defaults($auth);   	// define default authentication values

get_user_auth($auth);	    	// retrieve user authentication data

//
// Retrieve user selected options:
// method to use and options (includes probe file)
//

$options = array();

// Selected database
$options['database'] = $_POST["database"];

// if the target database is PDB we'll consider the probe as a ligand
if (strcmp(substr($options['database'], 0, 3), 'pdb') == 0)
    $options['probe_type'] = 'ligand';	    // ligand
else
    $options['probe_type'] = 'receptor';	    // receptor



// Selected docker
$options['docker'] = $_POST["docker"];
$i = $options['docker'];
switch ($i) {
    case "gramm":
    	get_gramm_options($options);
	break;
    case "ftdock":
	// FTDock options?
	break;
    default:
	error("create dock job", "unknown method ".$options['docker']);
    	return FALSE;
}

// Get the probe molecule and save its original name
//  	This is 'cos we'll use pre-agreed names to work with it,
//  	not the original one.
$options['probe'] = $_FILES['upload']['name'][0];

// Our session_id. It is the same for the LOCAL and the REMOTE machines.
// Generate the local working directory and make it current.

$session_id = activate_new_sandbox();

upload_probe_data();

if ($debug) {
    echo "<h2>FORM</H2>\n<pre>\n";
    print_r($_POST);
    echo "</pre>\n";
    echo "<h2>Options</H2>\n<pre>\n";
    print_r($options);
    echo "</pre>\n";
    //echo "<h2>Authentication</H2>\n<pre>\n";
    //print_r($auth);
    echo "</pre>\n";
    echo "<h2>Session is $session_id</h2\n";
    flush();
}

// GROCK: GRid dOCKing
// Pass the session id, user options and auth details to connect to the Grid.

// The following is so we may redirect the user to the results page
// ASAP without having to wait for grock submission to finish:
ob_end_flush();

grock_set_status('starting');

// let the user go forward
// This should be done ASAP and let the submission work in the background...
// So that tracking the submission, results, etc... is done directly from 
// the results page...
echo "<center><h1>You may now procceed to ".
      "<a href=\"$www_app_dir/script/report.php?".
      "id=$session_id&probe=${options['probe']}&pt=${options['probe_type']}".
      "&db=${options['database']}&docker=${options['docker']}".
      "&from=1&to=50\">the GROCK results page</a></h1></center>";
echo "</body></html>";
flush();

// try to cover our ass
umask(077);

// save options on a file to pass them on to GROCK
$grockopt = fopen("options", "w+");
foreach ($auth as $idx => $value) fwrite($grockopt, "$idx=$value\n");
fwrite($grockopt, "\n");
foreach ($options as $idx => $value) fwrite($grockopt, "$idx=$value\n");
fclose($grockopt);

exec("(/usr/bin/php $app_dir/script/grock.php > grock_error 2>&1 )&");


?>
