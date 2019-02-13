<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
	<title>Results</title>
</head>

<?
$username=$_POST["username"];
$password=$_POST["password"];
$life=$_POST["life"];

//escapeshellcmd() escapes any characters in a string that might 
//be used to trick a shell command into executing arbitrary commands.
$username= escapeshellcmd($username);
$password= escapeshellcmd($password);
$life= escapeshellcmd($life);

//MyProxy installation program
$program  = "/opt/globus/bin/myproxy/bin/myproxy-get-delegation";
 
//Outfile
$outfile = "/data/www/EMBnet/tmp/grid/$username.cred";

//Program arguments
$args     = "-s localhost -l $username -t $life -o $outfile";

echo "EOOOO $username $password $life";

//The fields are required
if (($username=="") || ($password=="") || ($life=="")) 
{
    required_fields();
}

//Zap everything past first nonword character
$username = ltrim($username);
$findme  = ' ';
$pos = strpos($username, $findme);
if (is_numeric($pos) == true) 
{
	$username=substr($username, 0, $pos);
	
} 

//We check the password length
$len = strlen($password);
if (($len < 5) || ($len > 10)) 
{
    passwordtoolong();
}

if (is_numeric($lifetime) == false) 
{
  invalidlifetime();
}

function required_fields
{
   
    echo "<h1>Error. All fields are required.</h1></body></html>";
    exit;
}

function passwordtoolong
{
   
    echo "<h1>The password must be between 5 and 10 characters.</h1></body></html>";
    exit;
}

/*
Perl Translation!!!

# use expect to run the command
my $cmd_filehandle = Expect->spawn("$program $args");

# this looks for the string "Pass Phrase:" for 20 seconds
# and failing that, does the "error" subroutine.
unless ($cmd_filehandle->expect(20, "Pass Phrase:")) 
{
  printerror();
}

print $cmd_filehandle "$password\n";

# gather the output into the array
@output = <$cmd_filehandle>;

# close the filehandle to the command
$cmd_filehandle->soft_close();

# now you have an array called @outputmsg which has the rest of the output... 
# get rid of output[0], since it contains the password

$outputmsg = join(" ", $output[1]);
if ($cmd_filehandle->exitstatus() != 0) {
    $outputmsg =~ s/(.*):\s//;
    &printerror($outputmsg);
} else {
    &printsuccess;
}



sub printerror
{
    my $errmsg = $_[0];
    print header;
    print "<BODY BGCOLOR=#efefef>"; 
    print "<TITLE>Error!</TITLE>";
    print "<H1><FONT FACE=Arial COLOR=Red><STRONG>";
    print "Error executing myproxy-get-delegation!\n";
    print "</STRONG></FONT></H1>";
    print "$errmsg";
    exit;
}

sub printsuccess
{
    print header;
    print "<BODY BGCOLOR=#efefef>"; 
    print  "<TITLE>Error!</TITLE>";
    print "<H1><FONT FACE=Arial COLOR=Blue><STRONG>";
    print "Received a delegated proxy for $username good for $lifetime hours.";
    print "</STRONG></FONT></H1>";
    exit;
}

*/	
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
