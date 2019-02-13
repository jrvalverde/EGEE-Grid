<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
<title>
	GridGramm (a docking Interface)
</title>
</head>

<body>
<?
/**
 *	PHP-MIDDLEWARE INTERACTION
 *
 *	This php script runs in the local machine as user 'apache', is the 'father' process. 
 *	In a remote machine (our grid User Interface) we open a 'child' process, and it's runs as  
 *	the user validated before. That user must connect via ssh to the User Interface without password
 *	See http://www.cs.umd.edu/~arun/misc/ssh.html and http://www.openssh.com/
 *	Remember that the User Interface has mounted a directory in the local machine
 *	
 *	DOCKING PROGRAM
 *
 *	Gramm - Global Range Molecular Matching
 *
 *	Protein-Protein Docking and Protein-Ligand Docking
 *	{@link http://reco3.ams.sunysb.edu/gramm/ Home site}
 *
 *	@package GridGRAMM
 *	@author David Garcia Aristegui <david@cnb.uam.es>
 *	@copyright CSIC - GPL
 *	@version 1.0	
 */

/**
 * General utility functions
 */
require("./functions.php");


// MANAGING GRAMM AND GRID MIDDLEWARE REQUIRED FILES
// *************************************************

// Step One
// Directory in the local machine 
create_working_directory($grid_path);

// Step Two
// We  put with the user form the Receptor and Ligand files in the gramm directory inside the w.d.
upload_user_data($grid_path, $random_value);

// Step Three
// We generate in the gramm directory inside the w.d.the three gr files requiered to run gramm: 
// rmol.gr, rpar.gr and wlist.gr; the resolution determine the rpar.gr 
/**
 * Resolution to use (HR or LR)
 *
 * @global string $resolution
 */
$resolution=$_POST["resolution"];

create_gr_files($grid_path, $random_value, $resolution);

// Step Four
// We put requiered files in the gramm directory inside the w.d., the .dat files, the gramm executable file and
// a small shell script to run gramm with the requiered environment variable and parameters...
install_gramm($grid_path, $random_value);

// Step five
// We generate the JDL file,required to send the job with gramm over the EGEE testebed
create_jdl_file($grid_path, $random_value);

// Step Six
// We go to the gramm directory, inside the w.d.
chdir("$grid_path/$random_value/gramm");

// Step seven
// We compress all the gramm files: gramm executable, .dat files, the uploaded receptor and ligand and the .gr files.
exec("tar -cf gramm-go.tar gramm *.dat *.pdb *gr; gzip gramm-go.tar");

//*************************************************
//*************************************************

if( file_exists("$grid_path/$random_value/gramm/file.jdl") )
{	
	// We submit the gramm job over the grid
	fwrite($pipes[0], "edg-job-submit -o $grid_path/$random_value/gramm/identifier.txt $grid_path/$random_value/gramm/file.jdl > $grid_path/$random_value/gramm/submit.txt\n");
} else
{
	"<h1>Error, there is no JDL file!!!</h1><br /></body></html>\n";
	exit;
}

fwrite($pipes[0], "grid-proxy-destroy\n");

close_connection($process, $descriptorspec, $pipes);

// display background running notice
echo "<center><h1>Your Gramm job has been started.</h1></center>";
echo "<center><h2>Please don't reload this page.</h2><br />";
echo "<h2>When the results are ready they will be available in the<br />";
echo "following link: <br><br><a href=\"http://anarchy.cnb.uam.es/tmp/grid/$random_value/\">$random_value</a></h2></center>";

?>
<center>
<table>
<tr>
	<td align=center><img src="../interface/egee.jpg" alt="[EGEE]"></a>
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
