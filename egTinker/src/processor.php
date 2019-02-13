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
require("./variables.php");
require("./functions.php");

// Step one:
//  	Get name of application to run
$application=$_POST["application"];

//  	and load its app-specific module
require("./$application.php");

// Step two:
// This one is application-dependent. There is a number of common options
// that can be handled by a routine in "functions.php", but the others
// are app. specific.
// $options is globally defined in $application.php
upload_user_data($workdir, $options);

// Step three:
//  	Go to local working directory
//  	We generate a random name for the directory in order to
//  	a) avoid clashes with other user jobs
//  	b) gain a bit of security through obscurity
//  	Yeah. That's it. We should in addition create an .htaccess/.htpasswd
// file to enhance security... for the next release...
// Generate a random value to name the user directory
srand((double)microtime()*10000);

$r1=rand(); $r2=rand(); 
$dir="$application.$r1.$r2";
$workdir=$serverpath/$httptmp/$dir;   // name of working directory

go_to_work($workdir);


// Step four:
// Run the application
run_application($workdir);

// Step five:
// Notify the user. Send back the options s/he submitted and tell
// them where can they monitor the run and get the result.
print_options();
echo "";
echo "<center><h1>Your Gramm job has been started.</h1></center>";
echo "<center><h2>Please don't reload this page.</h2><br />";
echo "<h2>You can monitor the status of your job and see the results<br />";
echo "once they are available on the following link: <br><br><a href=\"$httptmp/$dir\">CONTINUE</a></h2></center>";

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
