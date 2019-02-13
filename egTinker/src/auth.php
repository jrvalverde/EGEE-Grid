<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
	<title>Results</title>
</head>

<?
/**
 * General utility functions
 */
require("./functions.php");

$username=$_POST["username"];
$password=$_POST["password"];
$passphrase=$_POST["passphrase"];

//escapeshellcmd() escapes any characters in a string that might 
//be used to trick a shell command into executing arbitrary commands.
$username= escapeshellcmd($username);
$password= escapeshellcmd($password);
$passphrase= escapeshellcmd($passphrase);

echo "EOOOO $username $password $passphrase<br>";

//The fields are required
if ( ($username=="") || ($password=="")|| ($passphrase=="") ) 
{
    //required_fields();
}
/*
//Zap everything past first nonword character
$username = ltrim($username);
$findme  = ' ';
$pos = strpos($username, $findme);

if ( is_numeric($pos) == true ) 
{
	$username=substr($username, 0, $pos);
} 
*/
//////////////////////////////
$descriptorspec = array(
        	0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
        	1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        	2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
	);
	
	echo "OOOOOOOOO<br>";
	$process = proc_open("bash", $descriptorspec, $pipes);
	
	fwrite($pipes[0], "whoami\n");
	fwrite($pipes[0], "pwd\n");
	fwrite($pipes[0], "echo cambiamos\n");

	
	fwrite($pipes[0], "sudo -u david ls -la /home/david\n");
	sleep(5);     
	fwrite($pipes[0], "\n");
	
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
	
	echo "\ncommand returned $return_value\n";

function required_fields()
{
   
    echo "<h1>Error. Username, password and passphrase are required.</h1></body></html>";
    //exit;
}


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
