<?
//
// HYML tags
//
echo "<HTML><HEAD></HEAD><BODY>";

//
// Customization section:
//	Set these values appropriately for your environment
//

// Location of the user data and result files:
//	they should be WWW accessible, therefore we need
//	to specify their location relative to the system /
//	and to the www root (for the URLs):
$system_tmproot="/data/www/EMBnet/tmp";
$http_tmproot="/tmp";

// PDB configuration
//	Where your PDB database files are (uncompressed)
$pdb_dir="/data/gen/pdb/";

//	The user refers to PDB entries by their code, but they
//	are actually stored as independent files. To build the
//	filename corresponding to an entry we need to know if
//	the filename has anything before the entry code and anything
//	afterwards, e.g.
//		if entry 1ENG is in file "pdb1eng.ent", then it is
//		preceded by prefix "pdb" and folowed by ".ent"
$pdb_prefix="pdb";
$pdb_extension=".ent";

// set to 0 for no debug output, or select a debug level
$debug=0;

//
// End of configuration section
//

//
// Start processing
//

if ($debug > 0) 
{
	$system_tmproot="/data/www/EMBnet/tmp/yami";
	$http_tmproot="/tmp/yami";
	echo "<CENTER><H1>MODELLER DRIVER</H1></CENTER>";
}

// Set modeller parameters
$starting_model=1;
$alignment_format="PIR";
$atom_files_directory="./:$pdb_dir";

// Get multiple alignment file
$alig=$_POST["alig"];

// select a random name for the tmp dir and cd to it
$random_str = rand(1000000, 9999999);
set_tmp_dir( $random_str, $system_tmproot );

//PIR and ALIGNMENT uploads
upload_processor( $alig );

//$sequence PIR is a global variable from the upload_processor function
//We need the name of the sequence without the ".pir" extension
$pos=strpos( $sequence_pir, ".");
if ($pos === false) 
{
	$sequence=$sequence_pir;
} else
{
	$sequence=substr($sequence_pir, 0, $pos); 
}

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

//Models
$ending_model=$_POST["ending_model"];
if ( $ending_model >= 6 )
{
	echo "<h1>Problem: the maximum number of models allowed is 6.</h1>";
    	exit;
}

//Template PDB Codes
$knowns=$_POST["knowns"];

//Some validations
if ( $ending_model == "" )
{
	echo "<h1>Problem: number of models is a required field.</h1>";
    	exit;
}
if ( $knowns == "" )
{
	echo "<h1>Problem: template pdb codes is a required field.</h1>";
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

//Writing the top file

$topfile="./$sequence.top";
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
	call_pdb2seg($sequence_pir, $pdb_dir, $pdb_prefix, $pdb_extension, $segfile, $knowns, $random_str);
	fwrite( $fp, "SET SEGFILE = '$segfile'\n" );
}
fwrite( $fp, "CALL ROUTINE = '$routine'\n" );
fclose( $fp );

//run Modeller in the background
call_modeller($topfile, $debug);

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
//Two files to upload: PIR file and SEG file

	for ($i=0; $i<2; $i++) 
	{
   
		if($i==0)
		{
			$file_str="PIR"; 
		}else
		{
			$file_str="ALIGNMENT";
		}

		if ( $i==0 || ( $alig=="yes" && $i==1 ) )
		{
			$userfile = $_FILES['yamifile']['tmp_name'][$i];
			$userfile_name = $_FILES['yamifile']['name'][$i];
			$userfile_size = $_FILES['yamifile']['size'][$i];


  			if ($_FILES['yamifile']['tmp_name'][$i]=="none" || $_FILES['yamifile']['tmp_name'][$i]=="")
  			{
    				echo "<h1>Problem: no $file_str file uploaded</h1>";
    				exit;
  			}
  
  			if ($_FILES['yamifile']['size'][$i]==0)
  			{
    				echo "<h1>Problem: uploaded $file_str file is zero length</h1>";
    				exit;
  			}

  			$upfile = "./$userfile_name";
  
  			if ( !move_uploaded_file($userfile, $upfile)) 
  			{
    				echo "<h1>$userfile y $upfile Problem: Could not move file into directory</h1>"; 
    				exit;
  			}
		}

	}
	//The first upload is the PIR file
 	global $sequence_pir;
	$sequence_pir = $_FILES['yamifile']['name'][0];

	//The second ( optional ) upload is the ALN file
	if ( $alig=="yes" )
	{
		global $alnfile;
		$alnfile = $_FILES['yamifile']['name'][1];
	}
}

//Creating with pdb2seg the .seg file if requiered
function call_pdb2seg($sequence_pir, $pdb_dir, $pdb_prefix, $pdb_extension, $segfile, $knowns, $random_str)
{
	$pdb2seg = "/opt/structure/bin/pdb2seg $sequence_pir $pdb_dir $pdb_prefix $pdb_extension $segfile $knowns";
	exec("$pdb2seg");
}


//Setting the environment variables and runnig modeller
function call_modeller($topfile, $debug)
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

//
//HTML tags
//
echo "</BODY></HTML>";


?>
