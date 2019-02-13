<?php

class SExec {

    var $version="1.0";
    var $remote;
    var $password;

    function SExec($remote="localhost", $password="xxyzzy")
    {
    	$this->remote = $remote;
	$this->password = "$password";
    }
    
    function set_remote_end($remote, $password="xxyzzy")
    {
    	$this->remote = $remote;
	$this->password = $password;
    }
    
    /**
     *  Execute a single command remotely using ssh and 
     * display its output, optionally returning its exit 
     * status (like passthru)
     *
     *	This function is intended to be used as a one-time
     * all-at-once non-interactive execution mechanism which
     * will run the command remotely and display its output.
     *
     *	If you try to issue an interactive command using this
     * function, all you will get is unneccessary trouble. So
     * don't!
     *
     *  This might be done as well using a pipe on /tmp and
     * making the command 'cat' the pipe: when ssh runs, it
     * runs the command 'cat' on the pipe and hangs on read.
     *  Then we just need a thread to open the pipe, put the
     * password and close the pipe.
     *  This other way the password is never wirtten down.
     * But, OTOH, the file life is so ephemeral that most
     * of the time it will only exist in the internal system
     * cache, so this approach is not that bad either.
     *
     *  @param remote   The remote end to run the command, in
     *  	    	    the form 'user@host:port' (you may
     *	    	    	    omit the 'user@' or ':port' parts
     *	    	    	    if the default values [i.e. same user
     *	    	    	    or standard port] are OK).
     *  @param password The remote password. Note that if direct
     *  	    	    RSA/DSA/.shosts/.rhosts login is enabled
     *  	    	    then the password should be ignored as
     *  	    	    SSH should not run the ASKPASS command).
     *  @param command  The command to execute on the remote end
     *  	    	    NOTE: if you want to use redirection, the
     *  	    	    entire remote command line should be 
     *  	    	    enclosed in additional quotes!
     *  @param status   Optional, this will hold the termination
     *  	    	    status of SSH after invocation, which
     *  	    	    should be the exit status of the remote
     *  	    	    command or 255 if an error occurred
     *  @return void
     */
    function ssh_passthru($remote, $password, $command, &$status)
    {
    	global $debug;

    	// Setup environment
	umask(0077);
	$tmpfname = tempnam('/tmp', 'phpSsh');
	chmod($tmpfname, 0700);
    	if ($debug) echo $tmpfname."\n";
	
	putenv("DISPLAY=none:0.");
	putenv("SSH_ASKPASS=$tmpfname");

	// make askpass command
	$fp = fopen($tmpfname, "w");
	fputs($fp, "#!/bin/sh\necho $password\n");
	fputs($fp, "rm -f $tmpfname\n");
	fclose($fp);
	// go
	if (isset($status))
	    passthru("ssh -x -t -t $remote $command", $status);
	else
	    passthru("ssh -x -t -t $remote $command");
    }
    
    /**
     *	Execute a remote command using SSH
     *
     *	This function sort of mimics rexec(3) using SSH as the transport
     * protocol.
     *
     *	The function returns the exit status of the remote command, and
     * appends the remote job output to an optional argument.
     *
     *	This function is intended to be used as a one-time
     * all-at-once non-interactive execution mechanism which
     * will run the command remotely and return its output.
     *
     *	If you try to issue an interactive command using this
     * function, all you will get is unneccessary trouble. So
     * don't!
     *
     *  @param remote   The remote end to run the command, in
     *  	    	    the form 'user@host:port' (you may
     *	    	    	    omit the 'user@' or ':port' parts
     *	    	    	    if the default values [i.e. same user
     *	    	    	    or standard port] are OK).
     *  @param password The remote password. Note that if direct
     *  	    	    RSA/DSA/.shosts/.rhosts login is enabled
     *  	    	    then the password should be ignored as
     *  	    	    SSH should not run the ASKPASS command).
     *  @param command  The command to execute on the remote end
     *  	    	    NOTE: if you want to use redirection, the
     *  	    	    entire remote command line should be 
     *  	    	    enclosed in additional quotes!
     *  @param output   Optional, the collated (stdout+stderr) output 
     *	    	    	    of the remote command.
     *  @return status  will hold the termination
     *  	    	    status of SSH after invocation, which
     *  	    	    should be the exit status of the remote
     *  	    	    command or 255 if an error occurred
     */
    function ssh_exec($remote, $password, $command, &$out)
    {
    	global $debug;

	umask(0077);
	$tmpfname = tempnam('/tmp', 'phpSsh');
	chmod($tmpfname, 0700);
	if ($debug) echo $tmpfname . "\n";

	putenv('DISPLAY=none:0.');
	putenv("SSH_ASKPASS=$tmpfname");
	$fp = fopen($tmpfname, "w");
	fputs($fp, "#!/bin/sh\necho $password\n");
	fputs($fp, "rm -f $tmpfname\n");
	fclose($fp);
	exec("ssh -x -t -t $remote $command", $out, $retval);

    }
    
    /**
     *  Copy a file or directory from one source to a destination
     *
     *  This function copies source to dest, where one of them is a
     * local filespec and the other a remote filespec of the form
     * [user@]host:path
     *
     *  If the original source is a directory, it will be copied
     * recursively to destination (hence easing file transfers).
     *
     *  The function returns TRUE on success or FALSE on failure.
     *
     *	@param origin	The origin path, of the form
     *	    	    	[user@][host][:port]path
     *	    	    	You may omit the optional sections if
     *	    	    	the default values (local username, local
     *	    	    	host, standard SSH port) are OK
     *
     *	@param destination	The destination path, of the form
     *	    	    	[user@][host][:port]path
     *	    	    	You may omit the optional sections if
     *	    	    	the default values (local username, local
     *	    	    	host, standard SSH port) are OK
     *
     *	@parm password	The password to use to connect to the remote
     *	    	    	end of the copy (be it the origin or the
     *	    	    	destination, it's all the same). If connection
     *	    	    	is automatic by some means (.shosts or RSA/DSA
     *	    	    	authentication) then it should be ignored and
     *	    	    	any password should do.
     *
     *	@return status	TRUE if all went well, or FALSE on failure.
     */
    function ssh_copy($origin, $destination, $password)
    {
    	global $debug;

	umask(0077);
	$tmpfname = tempnam("/tmp", "phpSsh");
	chmod($tmpfname, 0700);
	putenv("DISPLAY=none:0.");
	putenv("SSH_ASKPASS=$tmpfname");
	$fp = fopen($tmpfname, "w");
	fputs($fp, "#!/bin/sh\necho $password\n");
	fputs($fp, "rm $tmpfname\n");
	fclose($fp);
	system("scp -pqrC $origin $destination", $status);
	if ($status == 0)
	    return TRUE;
	else
	    return FALSE;
    }

    /**
     *	Open an SSH connection to a remote site with a shell to run 
     * interactive commands
     *
     *	Connects to a remote host and opens an interactive shell session
     * with NO controlling terminal.
     *
     *	Returns a process_control array which contains the process resource
     * ID and an the standard file descriptors which the caller may use to
     * interact with the remote shell.
     *
     *  @param remote   The remote end to run the shell, in
     *  	    	    the form 'user@host:port' (you may
     *	    	    	    omit the 'user@' or ':port' parts
     *	    	    	    if the default values [i.e. same user
     *	    	    	    or standard port] are OK).
     *  @param password The remote password. Note that if direct
     *  	    	    RSA/DSA/.shosts/.rhosts login is enabled
     *  	    	    then the password should be ignored as
     *  	    	    SSH should not run the ASKPASS command).
     */
    function ssh_open_shell($remote, $password)
    {	
	global $debug;

	// Open a child process with the 'proc_open' function. 
	//
	// Some tricks: we must open the connection using '-x' to disable
	// X11 forwarding, and use '-t -t' to avoid SSH generating an error
	// because we are not connected to any terminal.
	//
	// NOTE: if the web server is trusted remotely (i.e. it's SSH public 
	// key is accepted in ~user@host:.ssh/authorized_keys) then any 
	// password will do.

	// Prepare I/O
	umask(0077);
	// NOTE, here we try leaving log files behind, but it is not
	// neccessary
	if ($debug) {
	    $child_stdout = tempnam("/tmp", "phpSsh-".getmypid()."-1-");
	    $child_stderr = tempnam("/tmp", "phpSsh-".getmypid()."-2-");
	} else {
	    $child_stdout = tempnam("/tmp", "phpSsh-");
	    $child_stderr = tempnam("/tmp", "phpSsh-");
	}
	$descriptorspec = array(
            0 => array("pipe", "r"),  // connect child's stdin to the read end of a pipe
	    1 => array("file", $child_stdout, "a"),
	    2 => array("file", $child_stderr, "a")
	);

	// prepare password
	umask(0077);
	$tmpfname = tempnam("/tmp", "phpSsh-");
	chmod($tmpfname, 0700);
    	if ($debug) echo $tmpfname . "\n";

	putenv("DISPLAY=none:0.");
	putenv("SSH_ASKPASS=$tmpfname");
	$fp = fopen($tmpfname, "w");
	fputs($fp, "#!/bin/sh\necho $password\n");
	fputs($fp, "rm $tmpfname\n");
	fclose($fp);

	if ($debug) echo "ssh -x -t -t $remote<br />\n";
	$process = proc_open("ssh -x -t -t $remote", 
	    		 $descriptorspec,
			 $pipes);
	
	// check status
	if (!is_resource($process)) 
	{
	    letal("SSH::connect", "cannot connect to the remote host");
	    return;
	}
	if ($debug) echo "proc_open done<br />\n";

	// $pipes now looks like this:
	//   0 => writeable handle connected to child stdin
	
	// Open child's stdin and stdout
	$pipes[1] = fopen($child_stdout, "r");
	$pipes[2] = fopen($child_stderr, "r");
	
	// Should we leave this to the user?
	// set to non-blocking and avoid having to call fflush
	#stream_set_blocking($pipes[0], FALSE);
	#stream_set_blocking($pipes[1], FALSE);
	#stream_set_blocking($pipes[2], FALSE);
	#stream_set_write_buffer($pipes[0], 0);
	#stream_set_write_buffer($pipes[1], 0);
	#stream_set_write_buffer($pipes[2], 0);

	// We now have a connection to the remote SSH
	// Server which we may use to send commands/receive output
	$p = array('process' => $process
	    	    ,'std_in' => $pipes[0]
    	    	    ,'std_out' => $pipes[1]
		    ,'std_err' => $pipes[2] 
		    ,'stdout_file' => $child_stdout
		    ,'stderr_file' => $child_stderr
		   );
	if ($debug)  {
	    echo "process descriptor array is \n";
	    print_r($p);
	}
	return $p;
    }
    
    /**
     *	Open an SSH connection to run an interactive command on a remote
     * site
     *
     *	Connects to a remote host and runs an interactive command
     * with NO controlling terminal.
     *
     *	Returns a process_control array which contains the process resource
     * ID and an the standard file descriptors which the caller may use to
     * interact with the remote shell.
     */
    function ssh_open_command($remote, $password, $command)
    {	
	global $debug;

	// Open a child process with the 'proc_open' function. 
	//
	// Some tricks: we must open the connection using '-x' to disable
	// X11 forwarding, and use '-t -t' to avoid SSH generating an error
	// because we are not connected to any terminal.
	//
	// NOTE: if the web server is trusted remotely (i.e. it's SSH public 
	// key is accepted in ~user@host:.ssh/authorized_keys) then any 
	// password will do.

	// Prepare I/O
	umask(0077);
	// NOTE, here we try leaving log files behind, but it is not
	// neccessary
	if ($debug) {
	    $child_stdout = tempnam("/tmp", "phpSsh-".getmypid()."-1-");
	    $child_stderr = tempnam("/tmp", "phpSsh-".getmypid()."-2-");
	} else {
	    $child_stdout = tempnam("/tmp", "phpSsh-");
	    $child_stderr = tempnam("/tmp", "phpSsh-");
	}
	$descriptorspec = array(
            0 => array("pipe", "r"),  // connect child's stdin to the read end of a pipe
	    1 => array("file", $child_stdout, "a"),
	    2 => array("file", $child_stderr, "a")
	);

	// prepare password
	umask(0077);
	$tmpfname = tempnam("/tmp", "phpSsh-");
	chmod($tmpfname, 0700);
    	if ($debug) echo $tmpfname . "\n";

	putenv("DISPLAY=none:0.");
	putenv("SSH_ASKPASS=$tmpfname");
	$fp = fopen($tmpfname, "w");
	fputs($fp, "#!/bin/sh\necho $password\n");
	fputs($fp, "rm $tmpfname\n");
	fclose($fp);

	if ($debug) echo "ssh -x -t -t $remote $command<br />\n";
	$process = proc_open("ssh -x -t -t $remote $command", 
	    		 $descriptorspec,
			 $pipes);
	
	// check status
	if (!is_resource($process)) 
	{
	    letal("SSH::connect", "cannot connect to the remote host");
	    return;
	}
	if ($debug) echo "proc_open done<br />\n";

	// $pipes now looks like this:
	//   0 => writeable handle connected to child stdin
	
	// Open child's stdin and stdout
	$pipes[1] = fopen($child_stdout, "r");
	$pipes[2] = fopen($child_stderr, "r");
	
	// Should we leave this to the user?
	// set to non-blocking and avoid having to call fflush
	#stream_set_blocking($pipes[0], FALSE);
	#stream_set_blocking($pipes[1], FALSE);
	#stream_set_blocking($pipes[2], FALSE);
	#stream_set_write_buffer($pipes[0], 0);
	#stream_set_write_buffer($pipes[1], 0);
	#stream_set_write_buffer($pipes[2], 0);

	// We now have a connection to the remote SSH
	// Server which we may use to send commands/receive output
	$p = array('process' => $process
	    	    ,'std_in' => $pipes[0]
    	    	    ,'std_out' => $pipes[1]
		    ,'std_err' => $pipes[2] 
		    ,'stdout_file' => $child_stdout
		    ,'stderr_file' => $child_stderr
		   );
	if ($debug)  {
	    echo "process descriptor array is \n";
	    print_r($p);
	}
	return $p;
    }

    /**
     * Close an SSH interactive session
     */
    function ssh_close($p)
    {
    	global $debug;
	
	    fwrite($p['std_in'], "\n");
	    fwrite($p['std_in'], "logout\n");
	    fflush($p['std_in']);
	    fclose($p['std_in']); fclose($p['std_out']); fclose($p['std_err']);
	    if ($debug) echo "pipes closed\n";
	    proc_close($p['process']);
	    if ($debug) echo "process closed\n";
    }
    
    function ssh_close_bck($p)
    {
    	global $debug;
    	if ($debug) {
	    echo "ssh_close\n";
	    print_r($p);
	}

    	fwrite($p['std_in'], 'logout\n'); 
	fflush($p['std_in']);
	fclose($p['std_in']);
	fclose($p['std_out']);
	fclose($p['std_err']);
	if ($debug)
	    echo "pipes closed\n";
	return proc_close($p['process']);
    }


    /**
     *	Execute a remore command and keep an unidirectional stream
     * contact with it.
     *
     *	This routine mimics 'popen()' but uses ssh to connect to
     * a remote host and run the requested command.
     */
    function ssh_popen($remote, $password, $command, $mode)
    {
    	global $debug;

    	// Setup environment
	umask(0077);
	$tmpfname = tempnam('/tmp', 'phpSsh-');
	chmod($tmpfname, 0700);
    	if ($debug) echo $tmpfname."\n";
	
	putenv("DISPLAY=none:0.");
	putenv("SSH_ASKPASS=$tmpfname");

	// make askpass command
	$fp = fopen($tmpfname, "w");
	fputs($fp, "#!/bin/sh\necho $password\n");
	fputs($fp, "rm -f $tmpfname\n");
	fclose($fp);
	// go
	return popen("ssh -x -t -t $remote $command", $mode);
    }
    
    function ssh_pclose($f)
    {
    	return pclose($f);
    }

}

?>
