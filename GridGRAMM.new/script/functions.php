<?
/**
 * General utility functions
 *
 * This file contains convenience functions used throughout the package. 
 *
 * @package GridGRAMM
 * @author David Garcia Aristegui <david@cnb.uam.es>
 * @version 1.0
 * @copyright CSIC - GPL
 */

/**
 * include global configuration definitions
 */
require("./variables.php");

/**
 * open a connection to the Grid UI server
 * 
 * 	The Grid User Interface server is the entry point to the Grid
 *  for users and user applications. This is where jobs are launched from.
 * 
 * 	GridGRAMM has been designed to be able to be installed in any
 *  host, independent of whether it is an UI or not. Thus, to be able to
 *  submit jobs to the Grid, the server hosting GridGRAMM must connect to
 *  a Grid UI host to do the work.
 * 
 * 	This routine opens a connection to a Grid UI host using a predefined
 *  username (i.e. all jobs will be run under said username). To allow the
 *  caller to communicate with the remote end, it creates two pipes, one for
 *  input and one for output, and redirect error messages to a file.
 * 
 * 	These pipes lead to a child process that actually manages the 
 *  communication. Using a child process has several advantages: it simplifies
 *  the communication process by offloading the comm. logic to a separate,
 *  convenience tool, and by being able to use SSH as the child, we can increase
 *  security taking advantage of its encryption capabilities.
 * 
 * 	The panorama therefore will look like this:
 * 
 * 	HTML front-end --> processor.php <--> SSH <--> remote host <--> Grid
 * 
 * 	@param server	A username@remove.grid.ui.host pair to use a command
 * 			processor
 * 	@param grid_path Path to working directory in the local machine
 * 	@param process	A handle to the child process that manages communication
 * 			with the Grid access point
 * 	@param descriptorspec
 * 	@param pipes	An array of file descriptors to be used to communicate
 * 			withe the Grid UI through the child process.
 */
function open_connection($server, $grid_path, $process, $descriptorspec, $pipes)
{
	// The ssh connection is a 'child' process, in the User Interface 
	// opened wit 'proc_open'
	
	global $process, $descriptorspec, $pipes;
	$descriptorspec = array(
        	0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
        	1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        	2 => array("file", "$grid_path/error-output.txt", "a") // stderr is a file to write to
	);
	
	// Open the child process with 'proc_open' function. SSH connection with
	//  -x parameter, we need to disable the X11 forwarding.
	// $process = proc_open("ssh -t -t $server", $descriptorspec, $pipes);
	// Is required to us twice the parameter -t, otherwise we get the error:
	// "Pseudo-terminal will not be allocated because stdin is not a terminal".
	$process = proc_open("ssh -x -t -t $server", $descriptorspec, $pipes);
	
	// $pipes now looks like this:
	//   0 => writeable handle connected to child stdin
	//   1 => readable handle connected to child stdout
	// Any error output will be appended to $grid_path/error-output.txt


	if (!is_resource($process)) 
	{
		echo "connection error!!!<br \></body></html>\n";
		exit;
	}

}


/**
 * Close the connection with the remote Grid access point (UI node)
 *
 * 	What we are going to do is to close the communication pipes
 *  and kill the child processes that actually handles communication
 *  with the remote Grid UI host (ssh).
 * 
 *  @note CLOSE_CONNECTION IS USEFUL TO DEBUG!!!
 *
 *  @param $process
 *  @param $descriptorspec
 *  @param $pipes
 */
function close_connection($process, $descriptorspec, $pipes)
{
	
	// Required to close the 'child' process, otherwise 'proc_close' function hangs!!!
	fwrite($pipes[0], "exit\n");

	fclose($pipes[0]);

	// The output from the commands
	// Uncomment to debug the 'child' process!!!
	
	while (!feof($pipes[1]))
	{
		echo fgets($pipes[1], 1024)."<br />";
	}
	
	fclose($pipes[1]);

	// It's important that you close any pipes before calling
	// proc_close in order to avoid a deadlock
	$return_value = proc_close($process);
	
	// echo "\ncommand returned $return_value\n";	
}

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
 *	@param grid_path	local directory devoted to Grid work
 *	@param random_value	a random value to generate a unique
 *				name for the working directory.
 */
function install_gramm($grid_path, $random_value)
{	
	// We copy the results.php to the working directory
	if (!copy( "./results.php", "$grid_path/$random_value/index.php" ))	
	{
		echo "Error, can't copy results.php to the working directory<br /></body></html>\n";
		exit;	
	} 
	
	// We copy the functions.php to the working directory
	if (!copy( "./functions.php", "$grid_path/$random_value/functions.php" ))	
	{
		echo "Error, can't copy functions.php to the working directory<br /></body></html>\n";
		exit;	
	} 
	
	// We copy the variables.php to the working directory
	if (!copy( "./variables.php", "$grid_path/$random_value/variables.php" ))	
	{
		echo "Error, can't copy variables.php to the working directory<br /></body></html>\n";
		exit;	
	} 
	
	// We copy the egee logo
	if (!copy( "../interface/egee.jpg", "$grid_path/$random_value/egee.jpg" ))	
	{
		echo "Error, can't copy  egee.jpg to the working directory<br /></body></html>\n";
		exit;	
	} 
	// We move the gramm files to the working directory
	// XXX JR XXX -- this should suffice
        exec("cp -f gramm/* $grid_path/$random_value/gramm/");
        // otherwise use
        // - in variables.php
        // - $my_location="/data/www/EMBnet/cgi-src/Grid/GridGRAMM";
        // exec("cp -f $my_location/script/gramm/* $grid_path/$random_value/gramm/");
}

/**
 * Start the Grid services
 */
function enter_grid($pipes, $username, $grid_pass)
{
	fwrite($pipes[0], "su  -pwstdin\n");
	sleep(2);     
	fwrite($pipes[0], "$grid_pass\n");
	
	// Remote EGEE middleware interaction
	// We run the Globus and Middelware commands in the User Interface, but the output goes to the local machine 
	// in the mounted directory
	fwrite($pipes[0], "grid-proxy-init  -pwstdin\n");
	sleep(2);     
	fwrite($pipes[0], "$grid_pass\n");
}

/**
 * Create working directory
 */
function create_working_directory($grid_path)
{
	// Random value for the directory
	global $random_value;
	srand((double)microtime()*10000);
	$random_value = rand();
	
	// Working directory in the local machine
	if (!mkdir("$grid_path/$random_value", 0777))
	{
		echo "Error, can't generate the working directory<br /></body></html>\n";
		exit;	
	}
	// Mkdir don't work properly with the permissions
	chmod( "$grid_path/$random_value", 0777 );
	
	// Gramm directory in the local machine
	if (!mkdir ("$grid_path/$random_value/gramm", 0777))
	{
		echo "Error, can't generate the gramm directory<br /></body></html>\n";
		exit;	
	}
	// Mkdir don't work properly with the permissions
	chmod( "$grid_path/$random_value/gramm", 0777 );
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
	if( file_exists("./gramm/status.txt") )
	{	
		$lines=file("./gramm/status.txt");
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
function upload_user_data($grid_path, $random_value)
{
// Two files to upload: Receptor file and Ligand file, for the gramm program
	// This loop is dirty trick to get Receptor and Ligand files
	for ($i=0; $i<2; $i++) 
	{
   
		if($i==0)
		{
			$file_str="Receptor"; 
			$upfile="$grid_path/$random_value/gramm/receptor.pdb";
		}else
		{
			$file_str="Ligand";
			$upfile="$grid_path/$random_value/gramm/ligand.pdb";
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
function create_gr_files($grid_path, $random_value, $resolution)
{
// We generate rmol.gr, rpar.gr and wlist.gr, requiered to run gramm

// Writing rmol.gr

$rmol="$grid_path/$random_value/gramm/rmol.gr";
$receptor = "receptor";
$ligand = "ligand";

$fp = fopen( "$rmol", "w" );

	fwrite( $fp, "# Filename  Fragment  ID      Filename  Fragment  ID     [paral/anti  max.ang]\n");
	fwrite( $fp, "# ----------------------------------------------------------------------------\n");
	fwrite( $fp, "\n");
	fwrite( $fp, "$receptor.pdb     *    receptor  $ligand.pdb       *     ligand\n");

fclose( $fp );

// Writing rpar.gr
$rpar="$grid_path/$random_value/gramm/rpar.gr";

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
$wlist="$grid_path/$random_value/gramm/wlist.gr";

$fp2 = fopen( "$wlist", "w" );

	fwrite( $fp2, "# File_of_predictions   First_match   Last_match   separate/joint  +init_lig\n" );
	fwrite( $fp2, "# ----------------------------------------------------------------------------\n" );
	fwrite( $fp2, "$receptor-$ligand.res	1   10	separ	no\n" );	

fclose( $fp2 );
}

/**
 * Create JDL description of the job to be submitted to the Grid
 */
function create_jdl_file($grid_path, $random_value)
{
// JDL file to run the middleware command edg-job-submit
$new_jdl="$grid_path/$random_value/gramm/file.jdl";

$fp = fopen( "$new_jdl", "w" );

	fwrite( $fp, "Type = \"Job\";\n");
	fwrite( $fp, "JobType = \"normal\";\n");
	fwrite( $fp, "VirtualOrganisation= \"biomed\";\n");
	fwrite( $fp, "Executable = \"gramm.sh\";\n");
	fwrite( $fp, "StdOutput = \"gramm.out\";\n");
	fwrite( $fp, "StdError = \"gramm.err\";\n");
	fwrite( $fp, "InputSandbox = {\"$grid_path/$random_value/gramm/gramm.sh\",\"$grid_path/$random_value/gramm/gramm-go.tar.gz\"};\n");
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
