<?

	$descriptorspec = array(
        	0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
        	1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        	2 => array("file", "./error-output.txt", "a") // stderr is a file to write to
	);
	
	// Open the child process with 'proc_open' function. SSH connection with
	//  -x parameter, we need to disable the X11 forwarding.
	// $process = proc_open("ssh -t -t $server", $descriptorspec, $pipes);
	// Is required to us twice the parameter -t, otherwise we get the error:
	// "Pseudo-terminal will not be allocated because stdin is not a terminal".
	
	$process = proc_open("ssh -x -t -t david@villon", $descriptorspec, $pipes);
	
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
	
?>
