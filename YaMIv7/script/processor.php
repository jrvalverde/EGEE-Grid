<?
// session_start(); ?????????

/**
 * process a YAMI query
 *
*/

require_once('config.php');
require_once('util.php');
require_once('yami_lib.php');
//
//  T H E R E   W E   G O ! ! !
//

// DEBUG
$debug = "YES";

// Set default values
$auth=array();

// HTML tags
set_header();

// define default authentication values
/*
if (set_auth_defaults($auth) == FALSE)   	
{
	// not mandatory at this point, check the function!!!
	error("Authetication", "Define a user, password and passphrase values");
	set_footer();
	exit;
}

// retrieve user authentication data
if (get_user_auth($auth) == FALSE)	    	
{
	set_footer();
	exit;
}
*/

//
// Retrieve user selected options:
// method to use and options 
//
$options = array();

// Get multiple alignment file, values: yes/no
$alig=$_POST["alig"];

//PIR and ALIGNMENT uploads
upload_processor( $alig );

//$sequence PIR is a global variable from the upload_processor function
//We need the name of the sequence without the ".pir" extension
$sequence_pir = $_FILES['pir-file']['name'];
$sequence = process_pir_name($sequence_pir);

// Our session_id. It is the same for the LOCAL and the REMOTE machines.
// Generate the local working directory and make it current.
$session_id = activate_new_sandbox();

if ($debug) {
    echo "<h2>FORM</h2>\n<pre>\n";
    print_r($_POST);
    echo "</pre>\n";
    //echo "<h2>Authentication</H2>\n<pre>\n";
    //print_r($auth);
    echo "</pre>\n";
    echo "<h2>Session is $session_id</h2>\n";
    flush();
}


$topfile = "./modeller.top";
if (!generate_top_file($pdb_path, $pdb_extension, $alig, $sequence, $session_id, $topfile))
{
	echo "<center><h1>Problem: Unable to generate top file</h1></center>";
	set_footer();
	exit;
}

// The following is so we may redirect the user to the results page
// ASAP without having to wait for yami submission to finish:
ob_end_flush();

if (!yami_set_status('starting'))
{
	echo "<center><h1>Problem: Unable to generate/update status file</h1></center>";
	set_footer();
	exit;
} 

// let the user go forward
// This should be done ASAP and let the submission work in the background...
// So that tracking the submission, results, etc... is done directly from 
// the results page...
echo "<center><h1>You may now procceed to "."<a href=\"$www_app_dir/script/report.php?id=$session_id\">the YaiM results page</a></h1></center>";
      
//echo "</body></html>";

flush();

// try to cover our ass (JR dixit!!!)
umask(077);

exec("(/usr/bin/php $app_dir/script/yami.php > yami_error 2>&1 )&");
exit;

//run Modeller in the background
//call_yami($topfile, $debug);

set_footer();    
exit;



// display background running notice
// TEST THIS FIRST
echo "<CENTER><H1>Your MODELLER job has been started</H1><BR><BR>";
echo "<H2>When the results are ready they will be available in the ";
echo "following link: <br><br><A HREF=\"$http_tmproot/yami$random_str/\">$random_str</A></CENTER></H2>";

// Above approach is somewhat cumbersome. There's a better one, but we
// will try that first.
// Once it is working, we may try the advanced method:
//	In the <HEAD> section include an automatic redirection directive to 
//      the results page with a delay of 5-10 seconds, so the user may see
//      this advice.
// This implies we copy the show_results.php file and spawn modeller before
// returning the correct result page to the user (if there are errors before
// reaching here, we write the error message and die.



/////////////////////////////// SUBROUTINES ////////////////////////////////

//
//Creating the tmp directory
//
function set_tmp_dir( $random_str, $system_tmproot )
{
	mkdir ("$system_tmproot/yami$random_str", 0755);
	copy("./modeller.sh", "$system_tmproot/yami$random_str/modeller.sh");
	copy("./show_results.php", "$system_tmproot/yami$random_str/index.php");
	chdir( "$system_tmproot/yami$random_str" ); 
}

//
// Manage uploads
//
function upload_processor( $alig )
{
	// TO UNDERSTAND FILE VALIDATION TRY
	/*
	echo "Alig: $alig";
	echo "<pre>";
		print_r($_FILES);
	echo "</pre>";
	*/
	
	// PIR file validation
	if ($_FILES['pir-file']['tmp_name']=="none" || $_FILES['pir-file']['tmp_name']=="")
	{
		echo "<center><h1>Problem: no PIR file uploaded</h1></center>";
		set_footer();
		exit;
	}
  
	if ($_FILES['pir-file']['size']==0)
	{
		echo "<center><h1>Problem: uploaded PIR file is zero length</h1></center>";
		set_footer();
		exit;
	}
	
	// If "yes", the second file must be validated
	if ($alig == "yes")
	{
		// Alignment file validation
		if ($_FILES['alignment-file']['tmp_name']=="none" || $_FILES['alignment-file']['tmp_name']=="")
		{
			echo "<center><h1>Problem: no ALIGNMENT file uploaded</h1></center>";
			set_footer();
			exit;
		}
  
		if ($_FILES['alignment-file']['size']==0)
		{
			echo "<center><h1>Problem: uploaded ALIGNMENT file is zero length</h1></center>";
			set_footer();
			exit;
		}
	
	}
	
}

// The sequence name must be without extensions
function process_pir_name($sequence_pir)
{
	$pos=strpos( $sequence_pir, ".");
	if ($pos === false) 
	{
		$sequence=$sequence_pir;
	} else
	{
		$sequence=substr($sequence_pir, 0, $pos); 
	}
	
	return $sequence;
}


//Creating with pdb2seg the .seg file if requiered
function call_pdb2seg($sequence_pir, $pdb_dir, $pdb_prefix, $pdb_extension, $segfile, $knowns, $random_str)
{
	$pdb2seg = "/opt/structure/bin/pdb2seg $sequence_pir $pdb_dir $pdb_prefix $pdb_extension $segfile $knowns";
	exec("$pdb2seg");
}


//Setting the environment variables and runnig modeller
function call_yami($topfile, $debug)
{	
	// Create a modeller run script (or copy it from our install dir)
	// run the driver script in the background
	//     The driver script:
	//		Sets-up environment
	//		Runs modeller directly with args
	//		touches "done"
	//		suicides (removes itself to clear things up)

	$modeller = "./modeller.sh $topfile";
    	
	// Note:  If you start a program using this function ( exec ) and want to leave it
	// running in the background, you have to make sure that the output of 
	// that program is redirected to a file or some other output stream or 
	// else PHP will hang until the execution of the program ends.
	// Modeller creates his own .log file, so we redirect the output to a file
	// called "modeller.log" and after the program execution the modeller.sh
	// script delete this file.
	//exec("$modeller");

	if ($debug == "yes") 
	{
            echo "<center><h1>Done.</h1></center>";
	}
}

//
//HTML tags
//

// NEW CODE ***
// NEW CODE ***
// NEW CODE ***

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
    
    return TRUE;
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

function get_user_auth(&$auth)
{

    if ($_POST['user_login'] != '')
    {
    	$auth['user'] = $_POST['user_login'];
    }
    else 
    {
    	error("create Modeller job", "define a user name");
    	return FALSE;
    }

    // NOTE that we are not asking the user which UI to use!!!
    //	Meaning: that you better set it correctly on 'grid_config.php' !!!
    $auth['server'] = $auth['user'].'@'.$auth['host'];	// user@back-end

    if ($_POST['user_password'] != '')
    {
    	$auth['password'] = $_POST['user_password'];	// user@back-end passwd
    }
    else 
    {
    	error("create Modeller job", "define a user password");
    	return FALSE;
    }

    if ($_POST['passphrase'] != '')
    {
    	$auth['passphrase'] = $_POST['passphrase'];
    }
    else 
    {
    	error("create Modeller job", "define a passphrase");
    	return FALSE;
    }

    return TRUE;
}

function generate_top_file($pdb_path, $pdb_extension, $alig, $sequence, $session_id, $topfile)
{
// Set modeller parameters
$starting_model=1;
$alignment_format="PIR";
$atom_files_directory="./:"."$pdb_path"; 


	//Choosing the routine and the alignment file
	if ( $alig=="yes" )
	{
		$routine="model";	
	}else
	{
		$segfile=$sequence.".seg";
		$routine="full_homol";
	}

	//MD Level
	$md=$_POST["md"];

	switch ($md) 
	{
		case 'None':
		$md_level="nothing";
		break;
	case 'Thorough MD':
		$md_level="refine1";
		break;
	case 'Fast MD':
		$md_level="refine2";
		break;
	default:
		$md_level="default";
	}
	
	//Template PDB Codes
	$knowns=$_POST["knowns"];
	
	//Template
	if ( $knowns == "" )
	{
		echo "<center><h1>Problem: template pdb codes is a required field.</h1></center>";
		set_footer();
		exit;
	}
	
	//Models, SWITCH...CASE???
	$ending_model=$_POST["ending_model"];
	if ( $ending_model >= 6 )
	{
		echo "<center><h1>Problem: the maximum number of models allowed is 6.</h1></center>";
		set_footer();
		exit;
	}

	//Some validations
	if ( $ending_model == "" )
	{
		echo "<center><h1>Problem: number of models is a required field.</h1></center>";
		set_footer();
		exit;
	}

	//Deviation
	if (trim($ending_model) == 1) 
	{
		$deviation="0";
	} else 
	{
		$deviation="4.0";
	}

	//Advanced Options
	$hetatm_io=$_POST["hetatm_io"];
	$water_io=$_POST["water_io"];
	$hydrogen_io=$_POST["hydrogen_io"];

	//Writing the  file
	$fp = fopen( "$topfile", "w" );
	fwrite( $fp, "INCLUDE\n" );

	fwrite( $fp, "SET ATOM_FILES_DIRECTORY = '$atom_files_directory'\n" );
	fwrite( $fp, "SET PDB_EXT = '$pdb_extension'\n" );

	if ( $md_level!="default" )
	{
		fwrite( $fp, "SET MD_LEVEL = '$md_level'\n" );
	}

	fwrite( $fp, "SET STARTING_MODEL = $starting_model\n" );
	fwrite( $fp, "SET ENDING_MODEL = $ending_model\n" );
	
	fwrite( $fp, "SET DEVIATION = $deviation\n" );
	
	fwrite( $fp, "SET KNOWNS = '$knowns'\n" );  

	fwrite( $fp, "SET HETATM_IO = $hetatm_io\n" );
	fwrite( $fp, "SET WATER_IO = $water_io\n" );
	fwrite( $fp, "SET HYDROGEN_IO = $hydrogen_io\n" );
	fwrite( $fp, "\n" ); 
	fwrite( $fp, "SET ALIGNMENT_FORMAT = '$alignment_format'\n" );
	fwrite( $fp, "SET SEQUENCE = '$sequence'\n" );

	if ( $alig=="yes" )
	{
		fwrite( $fp, "SET ALNFILE = '$alnfile'\n" );
	}else
	{
	//seg file generation
	//call_pdb2seg($sequence_pir, $pdb_dir, $pdb_prefix, $pdb_extension, $segfile, $knowns, $random_str);
	fwrite( $fp, "SET SEGFILE = '$segfile'\n" );
	}
	fwrite( $fp, "CALL ROUTINE = '$routine'\n" );
	fclose( $fp );
	
	return TRUE;
}

?>
