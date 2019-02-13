<?
/**
 * General utility functions
 *
 * This file contains convenience functions used throughout the package. 
 *
 * @note should be split into several class files
 *
 * @package egTINKER
 * @author José R. Valverde <jrvalverde@cnb.uam.es>
 * @version 1.0
 * @copyright CSIC - GPL
 */

/**
 * include global configuration definitions
 */
require_once("./config.php");
require_once("./util.php");

// --------------- JOB CONFIGURATION ----------------------

/**
 * Install the program and scripts needed to run it on a suitable place
 *
 *	This function installs the docking program GRAMM and the scripts
 * needed to run it on a suitable local directory.
 *
 *	Since we may potentially be called many times in a short period
 * we need a way to make sure their jobs don't clash with one another,
 * most specially, that they don't overwrite each others' workspace.
 *
 *	For this reason, we use a separate working directory for each
 * request. This function will install GRAMM on the specified working
 * directory.
 *
 *	@param wd_path	local directory devoted to Grid work
 *	@param random_value	a random value to generate a unique
 *				name for the working directory.
 */
function install_gramm($wd_path, $random_value)
{	
	// We copy the results.php to the working directory
	if (!copy( "./results.php", "$wd_path/$random_value/index.php" ))	
	{
		echo "Error, can't copy results.php to the working directory<br /></body></html>\n";
		exit;	
	} 
	
	// We copy the functions.php to the working directory
	if (!copy( "./functions.php", "$wd_path/$random_value/functions.php" ))	
	{
		echo "Error, can't copy functions.php to the working directory<br /></body></html>\n";
		exit;	
	} 
	
	// We copy the variables.php to the working directory
	if (!copy( "./variables.php", "$wd_path/$random_value/variables.php" ))	
	{
		echo "Error, can't copy variables.php to the working directory<br /></body></html>\n";
		exit;	
	} 
	
	// We copy the egee logo
	if (!copy( "../interface/egee.jpg", "$wd_path/$random_value/egee.jpg" ))	
	{
		echo "Error, can't copy  egee.jpg to the working directory<br /></body></html>\n";
		exit;	
	} 
	// We move the gramm files to the working directory
	// XXX JR XXX -- this should suffice
        exec("cp -f gramm/* $wd_path/$random_value/gramm/");
        // otherwise use
        // - in variables.php
        // - $my_location="/data/www/EMBnet/cgi-src/Grid/GridGRAMM";
        // exec("cp -f $my_location/script/gramm/* $wd_path/$random_value/gramm/");
}

/**
 * Create working directory and move to it
 *
 *  The goal is to go to the working directory. If it does not
 * exist, we create it (it shouldn't) and move inside.
 *
 *  Ideally we would also create an .htaccess file and a .htpasswd
 * with a random password to return to the user. Should that be done
 * here?
 *
 * @note The working directory should not exist!
 *
 * @param $user_wd_path the _absolute_ path to the local directory where 
 *  	    	we will be storing user data.
 */
function go_to_work($user_wd_path, $options)
{
	
	// Working directory in the local machine
	if (!mkdir("$user_wd_path", 0777))
	{
		echo "ERROR, HORROR: cannot generate a working directory<br /></body></html>\n";
		exit;
	}
	// Mkdir seems to not handle properly the permissions
	chmod( "$user_wd_path", 0777 );

    	chdir("$user_wd_path");	
}

/**
 * Get job identifier
 */
function job_identifier()
{
	// Each grid job has a unique identifier
	global $identifier;
	$identifier="";
	$lines=file("./gramm/identifier.txt");
	// We retrieve the identifier
	$identifier=$lines[1];
	$identifier=rtrim("$identifier");
	
}

/**
 * Check job status
 */
function check_status()
{
        // array with all the text lines
	if( file_exists("./tinker/status.txt") )
	{	
		$lines=file("./tinker/status.txt");
		//We retrieve the information
        	$value=$lines[6];
		//Last character 
        	$value=rtrim("$value");
		return $value;
	}
	
}	

/**
 * Upload user data
 */
function get_user_data($user_wd_path, $options)
{
    	// We need to upload the user file
	// and get all options (these should probably go into a struct)
	// This loop is dirty trick to get Receptor and Ligand files
	for ($i=0; $i<2; $i++) 
	{
   
		if($i==0)
		{
			$file_str="Receptor"; 
			$upfile="$user_wd_path/tinker/receptor.pdb";
		}else
		{
			$file_str="Ligand";
			$upfile="$user_wd_path/tinker/ligand.pdb";
		}

		
		$userfile = $_FILES['upload']['tmp_name'][$i];
		$userfile_name = $_FILES['upload']['name'][$i];
		$userfile_size = $_FILES['upload']['size'][$i];

		// We check if the files upload are correct  
  		if ($_FILES['upload']['tmp_name'][$i]=="none" || $_FILES['upload']['tmp_name'][$i]=="")
  		{
    			echo "<h1>Problem: no $file_str file uploaded</h1></body></html>";
    			exit;
  		}
  
  		if ($_FILES['upload']['size'][$i]==0)
  		{
    			echo "<h1>Problem: uploaded $file_str file is zero length</h1></body></html>";
    			exit;
  		}

  		// $upfile = "./$userfile_name";
		
		// move_uploaded_file this function checks to ensure that the file designated by filename is a valid upload file 
		// (meaning that it was uploaded via PHP's HTTP POST upload mechanism). If the file is valid, it will be moved to the 
		// filename given by destination.If filename is not a valid upload file, then no action will occur, and move_uploaded_file() 
		// will return FALSE. 
  		if (!move_uploaded_file($userfile, $upfile)) 
  		{
    			echo "<h1>$userfile y $upfile Problem: Could not move file into directory</h1></body></html>"; 
    			exit;
  		}


	}
	
}

/**
 * Create configuration files needed to run GRAMM
 */
function create_gr_files($user_wd_path, $resolution)
{
// We generate rmol.gr, rpar.gr and wlist.gr, requiered to run gramm

// Writing rmol.gr

$rmol="$user_wd_path/tinker/rmol.gr";
$receptor = "receptor";
$ligand = "ligand";

$fp = fopen( "$rmol", "w" );

	fwrite( $fp, "# Filename  Fragment  ID      Filename  Fragment  ID     [paral/anti  max.ang]\n");
	fwrite( $fp, "# ----------------------------------------------------------------------------\n");
	fwrite( $fp, "\n");
	fwrite( $fp, "$receptor.pdb     *    receptor  $ligand.pdb       *     ligand\n");

fclose( $fp );

// Writing rpar.gr
$rpar="$user_wd_path/tinker/rpar.gr";

$fp1 = fopen( "$rpar", "w" );

	if ($resolution=="low")
    	{	
		fwrite( $fp1, "Matching mode (generic/helix) ....................... mmode= generic\n" );
		fwrite( $fp1, "Grid step ............................................. eta= 6.8\n" );
		fwrite( $fp1, "Repulsion (attraction is always -1) .................... ro= 6.5.\n" );
		fwrite( $fp1, "Attraction double range (fraction of single range) ..... fr= 0.\n" );
		fwrite( $fp1, "Potential range type (atom_radius, grid_step) ....... crang= grid_step\n" );
		fwrite( $fp1, "Projection (blackwhite, gray) ................ ....... ccti= gray\n" );
		fwrite( $fp1, "Representation (all, hydrophobic) .................... crep= all\n" );
		fwrite( $fp1, "Number of matches to output .......................... maxm= 1000\n" );
		fwrite( $fp1, "Angle for rotations, deg (10,12,15,18,20,30, 0-no rot.)  ai= 20\n" );	
    	}else
    	{
    		fwrite( $fp1, "Matching mode (generic/helix) ....................... mmode= generic\n" );
    		fwrite( $fp1, "Grid step ............................................. eta= 1.7\n" );
    		fwrite( $fp1, "Repulsion (attraction is always -1) .................... ro= 30.\n" );
    		fwrite( $fp1, "Attraction double range (fraction of single range) ..... fr= 0.\n" );
    		fwrite( $fp1, "Potential range type (atom_radius, grid_step) ....... crang= atom_radius\n" );
     		fwrite( $fp1, "Projection (blackwhite, gray) ................ ....... ccti= gray\n" );
     		fwrite( $fp1, "Representation (all, hydrophobic) .................... crep= all\n" );
    		fwrite( $fp1, "Number of matches to output .......................... maxm= 1000\n" );
    		fwrite( $fp1, "Angle for rotations, deg (10,12,15,18,20,30, 0-no rot.)  ai= 10\n" );
   	 }

fclose( $fp1 );

// Writing wlist.gr
$wlist="$user_wd_path/tinker/wlist.gr";

$fp2 = fopen( "$wlist", "w" );

	fwrite( $fp2, "# File_of_predictions   First_match   Last_match   separate/joint  +init_lig\n" );
	fwrite( $fp2, "# ----------------------------------------------------------------------------\n" );
	fwrite( $fp2, "$receptor-$ligand.res	1   10	separ	no\n" );	

fclose( $fp2 );
}

/**
 * Create JDL description of the job to be submitted to the Grid
 */
function create_jdl_file($user_wd_path)
{
// JDL file to run the middleware command edg-job-submit
$new_jdl="$user_wd_path/tinker/file.jdl";

$fp = fopen( "$new_jdl", "w" );

	fwrite( $fp, "Type = \"Job\";\n");
	fwrite( $fp, "JobType = \"normal\";\n");
	fwrite( $fp, "VirtualOrganisation= \"biomed\";\n");
	fwrite( $fp, "Executable = \"gramm.sh\";\n");
	fwrite( $fp, "StdOutput = \"gramm.out\";\n");
	fwrite( $fp, "StdError = \"gramm.err\";\n");
	fwrite( $fp, "InputSandbox = {\"$user_wd_path/tinker/gramm.sh\",\"$user_wd_path/tinker/gramm-go.tar.gz\"};\n");
	fwrite( $fp, "OutputSandbox = {\"gridgramm.out\",\"gridgramm.err\",\"gramm-come.tar.gz\"};\n");

fclose( $fp );

}

/**
 * Extract results gathered from the Grid
 */
function unpack_results()
{
// We search the directory with the user results!!!

// The results directory generated by the grid middleware starts with the grid user name
$dir = dir(".");	
$dir->rewind();
	while ($file = $dir->read())
	{	
		// To change: $user
		if ( substr($file, 0, 5)=="$server_user" )
		{
			// We extract the gramm results
			exec("tar -zxvf $file/gramm-come.tar.gz -C .");
			$aux="OK";
			return $aux;
		}
	}
}

/**
 * Show the results to the user
 */
function show_results()
{
echo "<center><h1>GridGramm results:<br></h1><center>";
	echo "<hr>";

	echo "<center><table border=2>";
	
  	$dir = dir(".");
	
	$dir->rewind();
	while ($file = $dir->read())
  	{	
		if ( $file=="receptor.pdb" )
		{
			echo "<tr>";
			echo "<td>Structure of your receptor molecule.</td>"; 
			echo "<td><a href=./$file>$file</a></td>";
			echo "</tr>";	
		}
	}
	
	$dir->rewind();
	while ($file = $dir->read())
  	{	
		if ( $file=="ligand.pdb" )
		{
			echo "<tr>";
			echo "<td>Structure of your ligand molecule.</td>"; 
			echo "<td><a href=./$file>$file</a></td>";
			echo "</tr>";	
		}
	}
	

	$dir->rewind();
	while ($file = $dir->read())
  	{
		// Regular expression to show the receptor-ligand files
		if ( ereg("^receptor-ligand+_[0-9]+.pdb$", $file))
		{
			$filename = basename($file, ".pdb");
			echo "<tr>";
			echo "<td>Structure of the receptor-ligand complex</td>"; 
			echo "<td><a href=\"$filename.pdb\">$filename</a></td>";
			echo "</tr>";	
		}
	}
	
	$dir->rewind();
	while ($file = $dir->read())
  	{
		if ( $file=="receptor-ligand.res" )
		{
			echo "<tr>";
			echo "<td>Listing of the 1000 best scoring docks</td>"; 
			echo "<td colspan=\"4\"><a href=./$file>$file</a></td>";
			echo "</tr>";	
		}
	}
	
	$dir->rewind();
	while ($file = $dir->read())
  	{
		if ( $file=="gramm.log" )
			{
				echo "<tr>";
				echo "<td>Log output produced by GRAMM</td>"; 
				echo "<td colspan=\"4\"><a href=./$file>$file</a></td>";
				echo "</tr>";	
			}
	}
	
	$dir->rewind();
	while ($file = $dir->read())
  	{
		if ( $file=="rpar.gr" )
			{
				echo "<tr>";
				echo "<td>Parameters used for the docking procedure</td>"; 
				echo "<td colspan=\"4\"><a href=./$file>$file</a></td>";
				echo "</tr>";	
			}
	}
	
	$dir->rewind();
	while ($file = $dir->read())
  	{
		if ( $file=="rmol.gr" )
			{
				echo "<tr>";
				echo "<td>Description of the molecules.</td>"; 
				echo "<td colspan=\"4\"><a href=./$file>$file</a></td>";
				echo "</tr>";	
			}
	}
	
	$dir->rewind();
	while ($file = $dir->read())
  	{
		if ( $file=="wlist.gr" )
			{
				echo "<tr>";
				echo "<td>Config file to set the results</td>"; 
				echo "<td colspan=\"4\"><a href=./$file>$file</a></td>";
				echo "</tr>";	
			}
	}
	
	$dir->close();
	echo "</table></center>";	
	echo "<hr>";
}

?>
