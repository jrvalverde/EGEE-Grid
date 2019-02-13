<?
umask(0077);
$tmpfname = tempnam('/tmp', 'phpSsh');
chmod($tmpfname, 0700);

$debug = TRUE;

if ($debug) echo $tmpfname . "\n";

putenv('DISPLAY=none:0.');
putenv("SSH_ASKPASS=$tmpfname");

$fp = fopen($tmpfname, "w");
fputs($fp, "#!/bin/sh\necho miseria2004\n");
fputs($fp, "rm -f $tmpfname\n");
fclose($fp);

$remote = "david@villon.cnb.uam.es";
//$command = "ls";
$command = "/opt/globus/bin/grid-proxy-init -pwstdin";
$pass="o";

if ($pass=="")
{
    //system("ssh -x -t -t $remote \"$command\"");
} else
{   
    // Prepare I/O
    $descriptorspec = array(
    	0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
    	1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
    	2 => array("file", "/data/www/EMBnet/tmp/grid/test/error-output.txt", "a") // stderr is a file to write to
    );
    
    $process = proc_open("ssh -x -t -t $remote \"$command\"", $descriptorspec, $pipes);
    fflush($pipes[0]);
    fwrite($pipes[0], "kndlaria\n");
    fwrite($pipes[0], "exit\n");

    fclose($pipes[0]);

    
    while (!feof($pipes[1]))
    {
    	echo "<pre>";
	echo fgets($pipes[1], 1024)."<br />";
	echo "</pre>";
    }
	
    fclose($pipes[1]);
	
    echo "\ncommand returned $return_value\n";
    
}


?>
