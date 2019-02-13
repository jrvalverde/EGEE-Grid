<?php

// Do a remote ssh connection with local password
function do_ssh($host, $user, $password, $command)
{
	umask(0077);
	$tmpfname = tempnam("/tmp", "egTinker");
	chmod($tmpfname, 0700);
	$fp = fopen($tmpfname, "w");
	fputs($fp, "#!/bin/sh\necho $password\n");
	fclose($fp);
	putenv("DISPLAY=none:0.");
	putenv("SSH_ASKPASS=$tmpfname");
	system("ssh -x -t -t $host -l$user $command");
	unlink($tmpfname);
}

// Do a remote scp connection with local password
function do_scp($origin, $destination, $password)
{
	umask(0077);
	$tmpfname = tempnam("/tmp", "egTinker");
	chmod($tmpfname, 0700);
	$fp = fopen($tmpfname, "w");
	fputs($fp, "#!/bin/sh\necho $password\n");
	fclose($fp);
	putenv("DISPLAY=none:0.");
	putenv("SSH_ASKPASS=$tmpfname");
	system("scp -pqrC $origin $destination &");
	unlink($tmpfname);
}

do_ssh("cnb.uam.es", "jr", "1/1K way", "ls");

do_scp("SSH", "jr@cnb.uam.es:.", "1/1K way");

do_ssh("cnb.uam.es", "jr", "1/1K way", "ls");
do_ssh("cnb.uam.es", "jr", "1/1K way", "ls SSH");


?>
