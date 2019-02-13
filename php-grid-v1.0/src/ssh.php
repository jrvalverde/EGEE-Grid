<?php
/**
 * Remote-Exec commands using SSH
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category	Net
 * @package	SExec
 * @author 	José R. Valverde <jrvalverde@acm.org>
 * @copyright 	José R. Valverde <jrvalverde@acm.org>
 * @license	doc/lic/lgpl.txt
 * @version	CVS: $Id$
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @see		ssh(1), scp(1)
 * @since	File available since Release 1.0
 */


/** 
 * Allow for remote execution of commands using SSH
 *
 *	The SExec class provides a number of facilities for remote
 * command execution using SSH.
 *
 *	The name SExec comes after "rexec" (the remote execution library)
 * and the "exec" facilities available under PHP. As a matter of fact,
 * we try to mimic to some extent the execution facilities offered by
 * PHP over SSH: thus you will find ssh_popen() akin to popen(), etc.
 *
 * <b>RATIONALE</b>
 *
 *	The reason for this class is to allow executing code on a remote
 * back-end avoiding MITM spoofs in your communications. This allows you
 * to provide a web front-end (possibly redundant) and call a remote
 * back-end to execute the job.
 *
 *	Furthermore, you may have fallback features where if execution
 * on a remote back-end fails you can restart the command on a fallback
 * remote host, increasing reliability.
 *
 * <b>DEPENDENCIES</b>
 *
 *	The class relies on an underlying installation of SSH. It has
 * been tested with OpenSSH on Linux, but should work on other systems
 * with OpenSSH as well.
 *
 * <b>DESIGN RATIONALE</b>
 *
 *	The reasons for the choices taken are simple: we might have
 * relied on an SSH library (like libSSH) and integrated it with PHP,
 * but then, any weakness/bug/change on said library would require a
 * recompilation of the library and PHP. This is a serious inconvenience.
 * More to that, it would require the maintenance of two simultaneous
 * SSH installations, viz. OpenSSH and the library, duplicating the work
 * of tracking security/bug issues.
 *
 *	By using the underlying SSH commands, we become independent of
 * them: if anything is discovered, you just have to update your system
 * SSH, and nothing else. Otherwise you would have a dependency on SSH
 * to remember, which is always forgotten. This way we avoid getting out
 * of sync with the system's SSH.
 *
 *	Better yet: this easies development, making this class a lot
 * simpler to write, understand, maintain and debug.
 *
 *	One more detail: some methods allow for interactive communication
 * with the remote end. We have simply used a terminal-less connection
 * for them, using regular pipes as the intermediate communication channels.
 *
 *  	Note that using pipes you may block on read and/or write, and so
 * may the other end. Since there may occur errors in the process, that
 * implies that getting into a deadlock is trivial. Just picture this
 * scenarios:
 *
 * 	You send a command -> the remote ends starts the command and
 * prompts for input on stdout, hangs reding on stdin -> you read the 
 * prompt and send the input -> the remote end wakes and processes it.
 *
 *	You send a command -> the remote end fails, logs an error on
 * stderr, gets back the system prompt and hangs on reading stdin -> you
 * notice the prompt and read stderr... since you can't predict the 
 * length of the error message you must empty the pipe... and when doing it
 * you hang after reading the last char... -> deadlock
 *
 *	You send a command -> the remote end fails, logs an error on stderr,
 * gets back the system prompt and hangs on reading stdin -> you don't read
 * stderr to avoid hanging, so submit a new command... this goes on and on
 * until the remote side's stderr buffer fills, then the remote side locks
 * waiting for you to read stderr -> you can't know it hang, so you try
 * to submit a new command, and hang on writing waiting for the other end
 * to read your command -> deadlock
 *
 * 	More scenarios are possible, and since you (or the other side)
 * can't predict what's going to happen, it is tricky to avoid them.
 * We have set up all pipes to be non-blocking and of size zero (to avoid
 * having to call flush), but depending on your OS, YMMV.
 *
 * <b>CUSTOMIZATION</b>
 *
 *	You <i>must</i> state to the class where your SSH executables (ssh and
 * scp) are located. This allows you to have them placed anywhere, but
 * also implies the responsability of using full pathnames to reduce
 * hacking dangers. It also allows you to use/test a new SSH implementation
 * installed in a non-standard place before switching to it, or even to
 * keep various SSH installations on the system (e.g. if the system's
 * SSH is not up-to-date, you may install one on your home and use it).
 *
 * <b>DEBUGGING</b>
 *
 *	The class comes with extensive debugging aids. To enable them,
 * just set a global variable called $debug to TRUE. This will output
 * abundant debugging information and leave copies of communication log
 * files for your reference.
 *
 * @package 	SExec
 *
 * @author 	José R. Valverde <jrvalverde@acm.org>
 *
 * @version    	Release: 1.0
 *
 * @see		ssh(1), scp(1)
 *
 * @since	Class available since Release 1.0
 *
 * @copyright 	José R. Valverde <jrvalverde@acm.org>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
 
class SExec {

    /**
     * The current version of the class
     *
     * @var string
     * @access public
     */
    var $version="1.0";

    /**
     * remote endpoint ([user@]host[:port])
     *
     * @var string
     * @access private
     */
    var $remote;

    /**
     * remote password 
     *
     * @var string
     * @access private
     */
    var $password;

    /**
     * location of ssh program
     *
     * @var string
     * @access private
     */
    var $ssh = "/usr/bin/ssh";
    
    /**
     * location of scp program
     *
     * @var string
     * @access private
     */
    var $scp = "/usr/bin/scp";
    
    /** 
     * Class constructor.
     *
     *	Generate a new instance of a remote execution environment.
     * The object returned allows you to invoke commands to be executed
     * remotely in a way similar to PHP exec commands (popen, proc_open...)
     * over SSH (so that your communications can be secure).
     *
     *	You may specify a remote endpoint and a password, a remote endpoint
     * alone, or nothing at all.
     *
     *	If you provide a remote endpoint and password they are used to drive
     * the communications and execute your commands.
     *
     *	If no password is provided, then a default of "xxyzzy" (the canonical
     * computer magic word) is used. Unless this is your password (not 
     * recommended), this means that the default password is useless unless
     * you are working in a trusted environment where it is not needed and
     * ignored. That may be the case if you enable trusting mechanisms with
     * .shosts/.rhosts or passphraseless RSA/DSA authentication. Not that
     * we endorse them either, but in these cases any password provided will
     * be ignored and it doesn't make sense to provide a real one: xxyzzy
     * can do as well as any other.
     *
     *	If no password and no remote end is provided, then "localhost" is
     * used as the remote end, assuming no password is required (as described
     * above). This is only useful if localhost is trusted, and you have reasons
     * to use SSH internally... Some people does.
     *
     *	Regarding the remote end specification, it can be any valid single-string
     * SSH remote end description: the basic format is
     *
     *	[username@]remote.host[:port]
     *
     *	- "username" is the remote user name to log in as. It is optional. If provided, 
     * 	  it must be separated from the remote host by an "@" sign. If it is not 
     *    provided, the remote username is assumed to be the same as the current local
     *    one.
     *
     *	- "remote.host" is a valid host specification, either a numeric IP address
     *    or a valid host name (which may require a full name or not depending on
     *    your settings).
     *
     * 	- "port" is the remote port where SSH is listening and which we want to
     *    connect to. It is optional, and if provided, must follow the remote host
     *    specification separated from it by a colon ":". If not provided, the
     *    default port (22) is used.
     *
     *	Examples of remote host specifications are "user@host.example.net:22",
     * "someone@host:22", "host.example.net:22", "host:22", 
     * "somebody@host.example.net", "user@host", "host.example.net", "host".
     *
     * Here is an example of how to use this constructor:
     * <code>
     *  require_once 'ssh.php';
     *
     *  $remote = "jruser@example.com";
     *  $password = "PASSWORD";
     *
     *  $rmt = new SExec($remote, $password);
     * </code>
     *
     *	@note IMPORTANT! as of this release these parameters are ignored
     *
     *  @param string remote   The remote end to run the command, in
     *  	    	    the form 'user@host:port' (you may
     *	    	    	    omit the 'user@' or ':port' parts
     *	    	    	    if the default values [i.e. same user
     *	    	    	    or standard port] are OK).
     *
     *  @param string password The remote password. Note that if direct
     *  	    	    RSA/DSA/.shosts/.rhosts login is enabled
     *  	    	    then the password will be ignored as
     *  	    	    SSH should not run the ASKPASS command).
     *
     *	@return SExec a new instance of a SExec object
     *
     *	@access public
     *  @since Method available since Release 1.0
     */
    function SExec($remote="localhost", $password="xxyzzy")
    {
    	$this->remote = $remote;
	$this->password = "$password";
	return $this
    }
    
    /**
     *	Set remote end of the communications
     *
     *	This method allows you to change the remote end for further 
     * communications using an existing SExec object.
     *
     *	At a minimum you must specify a remote end-point, omitting the
     * password if none is needed or if it is the same as the in the
     * currently active end-point.
     *
     *	If you provide a remote endpoint and password they are used to drive
     * further communications and execute your commands.
     *
     *	If no password is provided, then a default of "xxyzzy" (the canonical
     * computer magic word) is used. Unless this is your password (not 
     * recommended), this means that the default password is useless unless
     * you are working in a trusted environment where it is not needed and
     * ignored. That may be the case if you enable trusting mechanisms with
     * .shosts/.rhosts or passphraseless RSA/DSA authentication. Not that
     * we endorse them either, but in these cases any password provided will
     * be ignored and it doesn't make sense to provide a real one: xxyzzy
     * can do as well as any other.
     *
     *	Regarding the remote end specification, it can be any valid single-string
     * SSH remote end description: the basic format is
     *
     *	[username@]remote.host[:port]
     *
     *	- "username" is the remote user name to log in as. It is optional. If provided, 
     * 	  it must be separated from the remote host by an "@" sign. If it is not 
     *    provided, the remote username is assumed to be the same as the current local
     *    one.
     *
     *	- "remote.host" is a valid host specification, either a numeric IP address
     *    or a valid host name (which may require a full name or not depending on
     *    your settings).
     *
     * 	- "port" is the remote port where SSH is listening and which we want to
     *    connect to. It is optional, and if provided, must follow the remote host
     *    specification separated from it by a colon ":". If not provided, the
     *    default port (22) is used.
     *
     *	Examples of remote host specifications are "user@host.example.net:22",
     * "someone@host:22", "host.example.net:22", "host:22", 
     * "somebody@host.example.net", "user@host", "host.example.net", "host".
     *
     * Here is an example of how to use this method:
     * <code>
     *  require_once 'ssh.php';
     *
     *  $remote = "jruser@example.com";
     *  $password = "PASSWORD";
     *
     *  $rmt = new SExec();
     *
     *  $rmt->set_remote_end($remote, $password);
     * </code>
     *
     *	@note IMPORTANT! as of this release these parameters are ignored
     *
     *  @param string remote   The remote end to run the command, in
     *  	    	    the form 'user@host:port' (you may
     *	    	    	    omit the 'user@' or ':port' parts
     *	    	    	    if the default values [i.e. same user
     *	    	    	    or standard port] are OK).
     *
     *  @param string password The remote password. Note that if direct
     *  	    	    RSA/DSA/.shosts/.rhosts login is enabled
     *  	    	    then the password will be ignored as
     *  	    	    SSH should not run the ASKPASS command).
     *
     *	@return void
     *
     *	@access public
     *  @since Method available since Release 1.0
     */
    function set_remote_end($remote, $password="xxyzzy")
    {
    	$this->remote = $remote;
	$this->password = $password;
    }
    
    
    /**
     *	Execute a command remotely and display its output
     *
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
     * Here is an example of how to use this method:
     * <code>
     *  require_once 'ssh.php';
     *
     *  $remote = "jruser@example.com";
     *  $password = "PASSWORD";
     *
     *  $rmt = new SExec();
     *  
     *  echo "Using passthru to send ls...\n\n";
     *  $rmt->ssh_passthru($remote, $password, "ls", $status);
     *  echo "\nExit status was $status";
     * </code>
     *
     *  @param string   The remote end to run the command, in
     *  	    	    the form 'user@host:port' (you may
     *	    	    	    omit the 'user@' or ':port' parts
     *	    	    	    if the default values [i.e. same user
     *	    	    	    or standard port] are OK).
     *  @param string The remote password. Note that if direct
     *  	    	    RSA/DSA/.shosts/.rhosts login is enabled
     *  	    	    then the password should be ignored as
     *  	    	    SSH should not run the ASKPASS command).
     *  @param string  The command to execute on the remote end
     *  	    	    NOTE: if you want to use redirection, the
     *  	    	    entire remote command line should be 
     *  	    	    enclosed in additional quotes!
     *  @param integer   Optional, this will hold the termination
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
	if (isset($status))
	    passthru("$this->ssh -x -t -t $remote \"$command\"", $status);
	else
	    passthru("$this->ssh -x -t -t $remote \"$command\"");
    }
    
    
    /**
     *	Execute a remote command using SSH
     *
     *	This function sort of mimics rexec(3) using SSH as the transport
     * protocol. Not fully though: it doesn't return a bidirectional
     * socket.
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
     * Here is an example of how to use this method:
     * <code>
     *  require_once 'ssh.php';
     *
     *  $remote = "jruser@example.com";
     *  $password = "PASSWORD";
     *
     *  $rmt = new SExec();
     *
     *  $rmt->ssh_exec($remote, $password, "ls", $out);
     *  print_r($out);
     *  foreach ($out as $line)
     *      echo $line . "\n";
     * </code>
     *
     *  @param string   The remote end to run the command, in
     *  	    	    the form 'user@host:port' (you may
     *	    	    	    omit the 'user@' or ':port' parts
     *	    	    	    if the default values [i.e. same user
     *	    	    	    or standard port] are OK).
     *  @param string The remote password. Note that if direct
     *  	    	    RSA/DSA/.shosts/.rhosts login is enabled
     *  	    	    then the password should be ignored as
     *  	    	    SSH should not run the ASKPASS command).
     *  @param string  The command to execute on the remote end
     *  	    	    NOTE: if you want to use redirection, the
     *  	    	    entire remote command line should be 
     *  	    	    enclosed in additional quotes!
     *  @param string   Optional, the collated (stdout+stderr) output 
     *	    	    	    of the remote command.
     *  @return int  will hold the termination
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
	exec("$this->ssh -x -t -t $remote \"$command\"", $out, $retval);
	return $retval;

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
     *
     * Here is an example of how to use this method:
     * <code>
     *  require_once 'ssh.php';
     *
     *  $remote = "jruser@example.com";
     *  $password = "PASSWORD";
     *
     *  $rmt = new SExec();
     *
     *  echo "scp -pqrC ./SSH $remote:k/\n";
     *  $rmt->ssh_copy("./SSH", "$remote:k/", $password);
     * </code>
     *
     *	@param string	The origin path, of the form
     *	    	    	[user@][host][:port]path
     *	    	    	You may omit the optional sections if
     *	    	    	the default values (local username, local
     *	    	    	host, standard SSH port) are OK
     *
     *	@param string	The destination path, of the form
     *	    	    	[user@][host][:port:]path
     *	    	    	You may omit the optional sections if
     *	    	    	the default values (local username, local
     *	    	    	host, standard SSH port) are OK
     *
     *	@param string	The password to use to connect to the remote
     *	    	    	end of the copy (be it the origin or the
     *	    	    	destination, it's all the same). If connection
     *	    	    	is automatic by some means (.shosts or RSA/DSA
     *	    	    	authentication) then it should be ignored and
     *	    	    	any password should do.
     *
     *	@return int	TRUE if all went well, or FALSE on failure.
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
	exec("$this->scp -pqrC $origin $destination", $out, $status);
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
     * Here is an example of how to use this method:
     * <code>
     *  require_once 'ssh.php';
     *
     *  $remote = "jruser@example.com";
     *  $password = "PASSWORD";
     *
     *  $rmt = new SExec();
     *
     *  $p = $rmt->ssh_open_shell($remote, $password);
     *  echo "after open I got this\n";
     *  print_r($p);
     *  sleep(1);   	// give login time to run
     *  echo "[1] flushing stdout after login\n";
     *  flush();
     *  do {
     *      $line = fgets($p["std_out"], 1024);
     *      echo "> ".$line."\n";
     *  } while ((! ereg("example.com", $line)) && (strlen($line) != 0)); // look for prompt
     *  echo "\n[2]sending ls\n";
     *  fwrite($p['std_in'], "ls\n"); fflush($p['std_in']);
     *  sleep(1); flush();
     *  do {
     *      $line = fgets($p["std_out"], 1024);
     *      echo "> ".$line."\n";
     *  } while (! ereg("example.com", $line));
     *  $rmt->ssh_close();
     * </code>
     *
     *  @param string   The remote end to run the shell, in
     *  	    	    the form 'user@host:port' (you may
     *	    	    	    omit the 'user@' or ':port' parts
     *	    	    	    if the default values [i.e. same user
     *	    	    	    or standard port] are OK).
     *  @param string The remote password. Note that if direct
     *  	    	    RSA/DSA/.shosts/.rhosts login is enabled
     *  	    	    then the password should be ignored as
     *  	    	    SSH should not run the ASKPASS command).
     *	@return mixed|false A process control associative array or
     *	    	    	    FALSE on failure.
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
	//
	// TODO: it may be a good idea to use files instead of pipes for
	// the communication. Try it and see. There are two advantages and
	// one major disadvantage: files will leave a log trace on file of
	// the communications and they shouldn't block on read/write waiting
	// for the other end, but they will eat up disk space, which might
	// be a problem.

	// Prepare I/O
	$descriptorspec = array(
            0 => array("pipe", "r"),  // connect child's stdin to the read end of a pipe
            1 => array("pipe", "a"),  // connect child's stdout to the write end of a pipe
            2 => array("pipe", "a")   // stderr is a pipe to read from
	);

	// prepare password
	umask(0077);
	$tmpfname = tempnam("/tmp", "phpSsh");
	chmod($tmpfname, 0700);
    	if ($debug) echo $tmpfname . "\n";

	putenv("DISPLAY=none:0.");
	putenv("SSH_ASKPASS=$tmpfname");
	$fp = fopen($tmpfname, "w");
	fputs($fp, "#!/bin/sh\necho $password\n");
	fputs($fp, "rm $tmpfname\n");
	fclose($fp);

	if ($debug) echo "$this->ssh -x -t -t $remote<br />\n";
	$process = proc_open("$this->ssh -x -t -t $remote", 
	    		 $descriptorspec,
			 $pipes);
	
	// check status
	if ((!is_resource($process)) || ($process == FALSE)) 
	{
	    letal("SSH::connect", "cannot connect to the remote host");
	    return FALSE;
	}
	if ($debug) echo "proc_open done<br />\n";

	// $pipes now looks like this:
	//   0 => writeable handle connected to child stdin
	//   1 => readable handle connected to child stdout
	//   2 => readable handle connected to child stderr
	
	// Should we leave this to the user?
	// set to non-blocking and avoid having to call flush
	stream_set_blocking($pipes[0], FALSE);
	stream_set_blocking($pipes[1], FALSE);
	stream_set_blocking($pipes[2], FALSE);
	stream_set_write_buffer($pipes[0], 0);
	stream_set_write_buffer($pipes[1], 0);
	stream_set_write_buffer($pipes[2], 0);

	// We now have a connection to the remote SSH
	// Server which we may use to send commands/receive output
	$p = array('process' => $process
	    	    ,'std_in' => $pipes[0]
    	    	    ,'std_out' => $pipes[1]
		    ,'std_err' => $pipes[2] 
		   );
	if ($debug)  {
	    echo "process descriptor array is \n";
	    print_r($p);
	    /*
	    fwrite($p['std_in'], "\n");
	    fwrite($p['std_in'], "touch touche\n");
	    fwrite($p['std_in'], "logout\n");
	    fflush($p['std_in']);
	    fclose($p['std_in']); fclose($p['std_out']); fclose($p['std_err']);
	    echo "pipes closed\n";
	    proc_close($p['process']);
	    echo "process closed\n";
	} 
	if ($debug == "CHANGE ME") {
	    echo "process "; print_r($process); echo "\n";
	    echo "p->process "; print_r($p['process']); echo "\n";
	    fwrite($pipes[0], "touch touche\n");
	    fwrite($pipes[0], "logout\n");
	    fflush($pipes[0]);
	    fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
	    proc_close($process);
	    */
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
     *
     * Here is an example of how to use this method:
     * <code>
     *  require_once 'ssh.php';
     *
     *  $remote = "jruser@example.com";
     *  $password = "PASSWORD";
     *
     *  $rmt = new SExec();
     *
     *  $p = $rmt->ssh_open_command($remote, $password, "ls");
     *  echo "after open I got this\n";
     *  print_r($p);
     *  sleep(1);   	// give command time to run
     *  echo "flushing files\nstdout\n";
     *  flush();
     *  do {
     *      $line = fgets($p["std_out"], 1024);
     *      echo "> ".$line."\n";
     *  } while (! feof($p["std_out"]) );;
     *  echo "stderr\n";
     *  do {
     *      $line = fgets($p["std_err"], 1024);
     *      echo ">> ".$line."\n";
     *  } while (! feof($p['std_err']) && (strlen($line) != 0));;
     *
     *  $rmt->ssh_close($p);
     * </code>
     *
     *  @param string   The remote end to run the shell, in
     *  	    	    the form 'user@host:port' (you may
     *	    	    	    omit the 'user@' or ':port' parts
     *	    	    	    if the default values [i.e. same user
     *	    	    	    or standard port] are OK).
     *  @param string The remote password. Note that if direct
     *  	    	    RSA/DSA/.shosts/.rhosts login is enabled
     *  	    	    then the password should be ignored as
     *  	    	    SSH should not run the ASKPASS command).
     *  @param string  The command to execute on the remote end
     *  	    	    NOTE: if you want to use redirection, the
     *  	    	    entire remote command line should be 
     *  	    	    enclosed in additional quotes!
     *
     *	@return mixed|false A process control associative array or
     *	    	    	    FALSE on failure.
     */
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
	//
	// TODO: it may be a good idea to use files instead of pipes for
	// the communication. Try it and see. There are two advantages and
	// one major disadvantage: files will leave a log trace on file of
	// the communications and they shouldn't block on read/write waiting
	// for the other end, but they will eat up disk space, which might
	// be a problem.

	// Prepare I/O
	umask(0077);
	if ($debug) {
	    $child_stdout = tempnam("/tmp", "phpSsh-".getmypid()."-1-");
	    $child_stderr = tempnam("/tmp", "phpSsh-".getmypid()."-2-");
	} else {
	    $child_stdout = tempnam("/tmp", "phpSsh-");
	    $child_stderr = tempnam("/tmp", "phpSsh-");
	}
	$descriptorspec = array(
            0 => array("pipe", "r"),  // connect child's stdin to the read end of a pipe
            1 => array("pipe", "a"),  // connect child's stdout to the write end of a pipe
            2 => array("pipe", "a")   // stderr is a pipe to read from
	    #1 => array("file", $child_stdout, "a"),
	    #2 => array("file", $child_stderr, "a")
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

	if ($debug) echo "$this->ssh -x -t -t $remote $command<br />\n";
	$process = proc_open("$this->ssh -x -t -t $remote \"$command\"", 
	    		 $descriptorspec,
			 $pipes);
	
	// check status
	if ((!is_resource($process)) || ($process == FALSE)) 
	{
	    letal("SSH::connect", "cannot connect to the remote host");
	    return FALSE;
	}
	if ($debug) echo "proc_open done<br />\n";

	// $pipes now looks like this:
	//   0 => writeable handle connected to child stdin
	//   1 => readable handle connected to child stdout
	//   2 => readable handle connected to child stderr
	
	// Should we leave this to the user?
	// set to non-blocking and avoid having to call fflush
	stream_set_blocking($pipes[0], FALSE);
	stream_set_blocking($pipes[1], FALSE);
	#stream_set_blocking($pipes[2], FALSE);
	stream_set_write_buffer($pipes[0], 0);
	stream_set_write_buffer($pipes[1], 0);
	#stream_set_write_buffer($pipes[2], 0);

	// We now have a connection to the remote SSH
	// server which we may use to send commands/receive output
	$p = array('process' => $process
	    	    ,'std_in' => $pipes[0]
    	    	    ,'std_out' => $pipes[1]
		    ,'std_err' => $pipes[2] 
		   );
	if ($debug)  {
	    echo "process descriptor array is \n";
	    print_r($p);
	}
	return $p;
    }

    /**
     * Close an SSH interactive session
     *
     *	This method terminates a previously open interactive remote 
     * session. It will send a termination notification to the
     * remote end, close the connection with control and communication
     * streams, and terminate the local control process.
     *
     * Here is an example on how to use this method:
     * <code>
     *  require_once 'ssh.php';
     *
     *  $remote = "jruser@example.com";
     *  $password = "PASSWORD";
     *
     *  $rmt = new SExec();
     *
     *  $p = $rmt->ssh_open_command($remote, $password, "ls");
     *  echo "after open I got this\n";
     *  print_r($p);
     *  sleep(1);   	// give command time to run
     *  echo "flushing files\nstdout\n";
     *  flush();
     *  do {
     *      $line = fgets($p["std_out"], 1024);
     *      echo "> ".$line."\n";
     *  } while (! feof($p["std_out"]) );;
     *  echo "stderr\n";
     *  do {
     *      $line = fgets($p["std_err"], 1024);
     *      echo ">> ".$line."\n";
     *  } while (! feof($p['std_err']) && (strlen($line) != 0));;
     *
     *  $rmt->ssh_close($p);
     * </code>
     *
     *	@param mixed p an associative array with the description of the interactive
     *		session control process, obtained by a previous call to one
     *		of the interactive session creation methods ssh_open_shell()
     *		or ssh_open_command().
     *
     *	@return integer the exit status of the remote interactive session.
     *
     *	@access public
     *  @since Method available since Release 1.0
     */
    function ssh_close($p)
    {
    	global $debug;
	
	    fwrite($p['std_in'], "\n");
	    fwrite($p['std_in'], "logout\n");
	    fflush($p['std_in']);
	    fclose($p['std_in']); fclose($p['std_out']); fclose($p['std_err']);
	    if ($debug) echo "pipes closed\n";
	    return proc_close($p['process']);
	    if ($debug) echo "process closed\n";
    }
    
#    if ($php_version >= 5)
#    {
#	/**
#	 * send a signal to a running ssh_open_* process
#	 */
#	function ssh_signal($p, $signal)
#	{
#    	    return proc_terminate($p['process'], $signal);
#	}
#	/**
#	 * get info about a running ssh_open_* process
#	 */
#	function ssh_get_status($p)
#	{
#    	    return proc_get_status($p['process']);
#	}
#    }
    
    /**
     *	Execute a remote command and keep an unidirectional stream
     * contact with it.
     *
     *	This routine mimics 'popen()' but uses ssh to connect to
     * a remote host and run the requested command: in other words,
     * it opens a pipe to a remotely executed command. This pipe is
     * unidirectional, with the communications direction controlled
     * by a method parameter.
     *
     * Here is an example on how to use this method:
     * <code>
     *  require_once 'ssh.php';
     *
     *  $remote = "jruser@example.com";
     *  $password = "PASSWORD";
     *
     *  $rmt = new SExec();
     *
     *  $cin = $rmt->ssh_popen($remote, $password, "/usr/sbin/Mail -s test jruser", "w");
     *    fputs($cin, "\nTest");
     *    fputs($cin, ".\n");
     *  $rmt->ssh_pclose($cin);
     *  
     * </code>
     *
     *	@see popen() for more details.
     *
     *  @param string  The command to execute on the remote end
     *  	    	    NOTE: if you want to use redirection, the
     *  	    	    entire remote command line should be 
     *  	    	    enclosed in additional quotes!
     *
     *	@param string command is the command to execute on the remote end
     *
     *	@param string mode specifies the communications direction for the 
     *		pipe: if set to "r" (read), then we will be able to
     *		collect command output only; if set to "w" (write)
     *		then we may only send input to the remote command.
     *
     *	@return resource|false a handle to the unidirectional communication stream,
     *		similar to that returned by fopen(), or FALSE on
     *		failure. This handle must be closed with ssh_pclose().
     *
     *	@access public
     *  @since Method available since Release 1.0
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
	return popen("$this->ssh -x -t -t $remote \"$command\"", $mode);
    }
    
    /**
     * Close a piped remote execution command control pipe.
     *
     *	This routine accepts as input the handle for the control stream
     * of a remote command and closes it, terminating the command as well.
     * The handle must be valid and obtained through a call to ssh_popen().
     *
     * Here is an example on how to use this method:
     * <code>
     *  require_once 'ssh.php';
     *
     *  $remote = "jruser@example.com";
     *  $password = "PASSWORD";
     *
     *  $rmt = new SExec();
     *
     *  $cin = $rmt->ssh_popen($remote, $password, "/usr/sbin/Mail -s test jruser", "w");
     *    fputs($cin, "\nTest");
     *    fputs($cin, ".\n");
     *  $rmt->ssh_pclose($cin);
     *  
     * </code>
     *
     *	@param resource f is the file handle associated with the pipe control stream
     *
     *	@return integer the termination status of the command that was run.
     *
     *	@access public
     *  @since Method available since Release 1.0
     */
    function ssh_pclose($f)
    {
    	return pclose($f);
    }

}

?>
