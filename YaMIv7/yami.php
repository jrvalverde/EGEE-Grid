<?
/**
 * Yet Another Modeller Interface (YaMI)
 *
 *  This service will run Modeller to generate an homology model given
 * a sequence and a number of template structures providing limited
 * modelling options.
 *
 *  Modeller is a complex program allowing for a lot of flexibility.
 * We don't want to support all of it as most users will not be
 * profficient enough on Modeller to correctly use all its options.
 * For this reason we prefer to give a very limited set of options and
 * append reasonable defaults.
 *
 *  @package 	YaMI
 *  @author  	David Garcia <david@cnb.uam.es>
 *  @author  	Jose R. Valverde <jrvalverde@es.embnet.org>
 *  @copyright  CSIC
 *  @license	./c/gpl.txt
 *  @version	7
 *  @since  	File available since first release
 */

require_once('config.php');
require_once('lib.php');
require_once('grid.php');

function get_md_level($md_level)
{
    $md = $_POST["md"];
    switch ($md) 
    {
	case 'None':
            $md_level = 'nothing';
            break;
	case 'Thorough MD':
            $md_level = 'refine1';
            break;
	case 'Fast MD':
            $md_level = 'refine2';
            break;
	default:
            $md_level = 'default';
    }
}

// Create with pdb2seg the .seg file if requiered
function call_pdb2seg($sequence_pir, $pdb_dir, $pdb_prefix, $pdb_extension, $segfile, $knowns, $random_str)
{
	$pdb2seg = "/opt/structure/bin/pdb2seg $sequence_pir $pdb_dir $pdb_prefix $pdb_extension $segfile $knowns";
	exec("$pdb2seg");
}

function write_top_file($opts, $topfile)
{
    $fp = fopen( "$topfile", "w" );
    fwrite( $fp, 'INCLUDE\n'.
    	    	 "SET ATOM_FILES_DIRECTORY = '$atom_files_directory'\n".
    	    	 "SET PDB_EXT = '$pdb_extension'\n" );

    if ( $md_level!="default" )
    {
	    fwrite( $fp, "SET MD_LEVEL = '$md_level'\n" );
    }

    fwrite( $fp, "SET STARTING_MODEL = $starting_model\n".
    	    	 "SET ENDING_MODEL = $ending_model\n".
    	    	 "SET DEVIATION = $deviation\n".
    	    	 "SET KNOWNS = '$knowns'\n".
    	    	 "SET HETATM_IO = $hetatm_io\n".
    	    	 "SET WATER_IO = $water_io\n".
		 "SET HYDROGEN_IO = $hydrogen_io\n".
		 "\n".
    	    	 "SET ALIGNMENT_FORMAT = '$alignment_format'\n".
    	    	 "SET SEQUENCE = '$sequence'\n" );

    if ( $alig=="yes" )
    {
	    fwrite( $fp, "SET ALNFILE = '$alnfile'\n" );
    } else
    {
	    //seg file generation
	    call_pdb2seg($sequence_pir, $pdb_dir, $pdb_prefix, $pdb_extension, $segfile, $knowns, $random_str);
	    fwrite( $fp, "SET SEGFILE = '$segfile'\n" );
    }
    fwrite( $fp, "CALL ROUTINE = '$routine'\n" );
    fclose( $fp );
    return $topfile;
}

// Set the environment variables and run modeller
function run_modeller($auth, $topfile)
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
	exec("$modeller");

	if ($debug > 0) 
	{
            echo "<CENTER><H1>Done.</H1></CENTER>";
	}
}

function build_model($auth, $opts)
{
    $sess = activate_new_sandbox();
    write_top_file($opts, 'modeller.top');
    run_modeller($auth, 'modeller.top');
}

/*
 ******************************************************************
 *       A C T U A L   P R O G R A M   S T A R T S   H E R E
 ******************************************************************
 */

// Our session_id will be the same for the LOCAL and the UI servers.
// Generate the local working directory and make it current.
$session_id = activate_new_sandbox();

// Collect user data
$auth = array();
get_auth_data($auth);

// Get user-selected options
$opts = array();

// Get input sequence:
$opts['seq_file'] = upload_file( 'sequence' );
$opts['seq_name'] = strtok($opts['seq_file'], '.');

// Set modeller parameters

// Find selected MD Level
get_md_level($md_level);

// Number of models to generate
$opts['model_first'] = 1;

$ending_model = trim($_POST['ending_model']);
//  	sanity checks
if ( $ending_model >= MAXMODELS )
	letal('YaMI',"The maximum number of models allowed is ".MAXMODELS);
if ( $ending_model == '' )
	letal('YaMI', 'You must specify the number of models');
$opts['model_last'] = $ending_model;

// Deviation depends on the number of models
if ($ending_model == 1) 
    $opts['deviation'] = '0';
else 
    $opts['deviation'] = '4.0';

// Template PDB Codes
$opts['knowns'] = $_POST['knowns'];
if ( $knowns == '' )
	letal('YaMI', 'You must provide one or mode template PDB codes');

// Check if we'll use a guiding alignment and if so get it.
$opts['alignment_format'] = PIR;    	// for now this is not user-selectable
$opts['use_alig'] = $_POST['use_alig'];
if ( $opts['use_alig'] == 'yes' )
{
    $opts['alig_file'] = upload_file( 'alignment' );
    $opts['routine'] = 'model';
}
else
{
    $opts['seg_file'] = $opts['seq_name'] . '.seg';
    $routine = 'full_homol';
    // generate SEG file in cwd
    pdb2seg($opts['seq_file'], $opts['seg_file'], $opts['knowns']);
}


// Advanced Options
$opts['hetatm_io'] = $_POST['hetatm_io'];
$opts['water_io'] = $_POST['water_io'];
$opts['hydrogen_io'] = $_POST['hydrogen_io'];

// run Modeller in the background
#build_models($auth, $opts);


set_header(UPD_TIME);

// tell the user his job has been started and where to look for output
echo "<CENTER><H1>Your MODELLER job has been started</H1>\n<BR />\n<BR />\n";
echo "<H2>When the results are ready they will be available in the \n";
// next url should be 'show_results.php?id=$session_id' instead.
echo "following link: <br><br><A HREF=\"".WWW_TMP_ROOT."/$session_id/\">$session_id</A></CENTER></H2>\n";
echo "<br /><br /><h3>This page will update itself after ".UPD_TIME." seconds</h3>\n</center>";

set_footer();

?>
