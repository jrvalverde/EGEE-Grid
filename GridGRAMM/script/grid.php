<?

require_once("./config.php");
require_once("./util.php");

/**
 *  Grid access class
 *
 *  CAUTION: THIS CLASS IS UNDERGOING A GENERAL OVERHAUL. DO NOT USE
 *  NOW.
 *
 *  This class allows you to connect to a remote Grid UI server and
 *  launch and monitor jobs on it.
 *
 *  The reason for this class is mostly one of resilience: if you 
 *  put all your services directly on a GrUI host, then whenever 
 *  that host if offline, your services will be as well.
 *
 *  An alternative is to replicate the services on various GrUI nodes.
 *
 *  Or better yet: use this class and set up your services wherever
 *  you want. You may put them on an HA system and ensure this way 
 *  their continuous availability. Your service will be always up
 *  and running, ready to accept jobs.
 *
 *  Your next problem is dealing with the Grid UI. You still need
 *  to log-in on a Grid access point to submit your jobs. But once
 *  you have detached from a specific GrUI node, you are free to
 *  attempt a connection to a given remote access point, and if it
 *  is available (and while it is) submit jobs as needed. If at any
 *  time it is not available, nothing is lost: just look for another
 *  one and use this to continue working. You may thus enter the
 *  grid through any door.
 *
 *  There is a side advantage too: with your services on a GrUI node
 *  you can only launch jobs from it. With your services detached from
 *  any given Grid door, you may use _any_ AND _as many_ as you want:
 *  this means you may launch jobs using various GrUI nodes simultaneously
 *  if you so wish. And even split the jobs througho various, separate 
 *  Grids if you feel like it, hence potentially increasing your 
 *  throughput and computing power, harnessing even more resources.
 *
 *  Detaching your jobs from the GrUI has one serious drawback though:
 *  all your job information must travel from your service user interface
 *  to the grid user interface through the Internet, which may be
 *  potentially dangerous. We deal with this security issue using SSH
 *  to handle all communications and provide encryption. As long as 
 *  you use strong passwords you may feel secure. Actually, it is as
 *  weak (or strong) as working directly on the GrUI node: access to
 *  it is still managed by standard password mechanisms and subject to
 *  the same types of attacks. However, remember you now have an extra
 *  system (the front-end) to maintain and secure. Please, be always
 *  cautious with any server you use.
 *
 *  Sounds convincing? Then read ahead to learn how to use this class.
 *
 *  Using this class you may access the Grid in two different ways:
 *  connected or disconnected mode (or if you prefer, using persistent
 *  connections or disconnected mode).
 *
 *  It is important to understand the differences among them:
 *
 *  PERSISTENT CONNECTIONS
 *
 *  When working with persistent connections, you first issue a
 *  Grid::connect() call to stablish a connection to the GrUI. This will
 *  be kept open during all the time until you call Grid::disconnect().
 *  All commands issued will travel over the same wire, and hence 
 *  communications will be more efficient.
 *
 *  Output of all your commands is collected on a single I/O channel
 *  that persists during the whole session.
 *
 *  On the minus side, persistent connections drive all communications
 *  between your front-end and the grid access point through pipes, and
 *  are subject to serious deadlock problems. You must make sure you
 *  avoid them by clearing periodically the communication buffers. In
 *  addition, error checking is relayed to YOU: all information will 
 *  travel through the persistent I/O channels, and it will be YOUR
 *  responsability to detect errors and act accordingly. Debugging is
 *  thus more difficult.
 *
 *  DISCONNECTED MODE
 *
 *  When working in disconnected mode, the connections are open and
 *  closed for each command you issue. This imposes a heavier tax
 *  on your communications, which may become serious for submitting
 *  huge numbers of jobs.
 *
 *  Each command will return its output separately since it uses a
 *  new I/O channel every time.
 *
 *  On the plus side, you will retrieve job completion status inmediately
 *  and won't risk deadlocks while running remote jobs. Development and
 *  debugging will be a lot easier.
 *
 *  JOB MANAGEMENT
 *
 *  In order to submit your jobs to the grid you need to understand how
 *  job management has been defined for this class. On the command line
 *  you would have a lot more versatility, but to make this class more
 *  useful some compromises had to be reached. We have defined a strict
 *  protocol to generate/prepare your jobs before submitting them to
 *  the grid, and you must stick to it if you want to avoid problems.
 *
 *  To understand why this has been designed the way it is, you should
 *  keep in mind that you will be preparing your jobs on one (or many)
 *  front-end and submitting them from it to one (or many) GrUI nodes.
 *  Further to it, this has been designhed to make it easy to deploy
 *  web-based services for users. Therefore many similar jobs might
 *  be launched simultaneously and we need some way to avoid collusion
 *  among them. To avoid one job stepping over other we must isolate
 *  every one from each other. This means providing an isolated environment
 *  for every job.
 *
 *  The easiest way to achieve our goal is to have every job submitted
 *  placed on an independent directory (which we pair to the job name). 
 *  For single jobs, this means that you should make sure that no two 
 *  potentially simultaneous/overlapping jobs have the same name (i.e. 
 *  are stored in the same directory so there is no risk one overwrites
 *  files of the other).
 *
 *  Sometimes this may result inconvenient to you. E.g. if your whole job
 *  is submitted split into many separate sub-jobs (which each is a separate
 *  job from the point of view of the grid) you may want to follow some
 *  naming convention for your sub-jobs that makes it easier to identify
 *  and keep track of them. In this case, if you had two simultaneous
 *  runs, then the names would collide.
 *
 *  For example, let's say you are rendering frames of a movie and want
 *  to identify each job by frame number: 0000, 0001, 0002, 0003... If
 *  you now try to generate a second movie while the first is being
 *  processed, then the frames of the second movie (named as well 0000,
 *  0001, etc...) would overwrite the frames of the first one.
 *  Generating random names for each frame would be an option, but too
 *  cumbersome and expensive as you would need to keep track of the
 *  association of the random names with the actual frames.
 *
 *  To deal with this scenario we define 'sessions'. A session is identified
 *  by a unique identifier, and guarantees that all jobs belonging to this
 *  session are kept separate from similarly named jobs from other sessions.
 *
 *  Actually when you create a session, what we actually do is create
 *  a subdirectory in the GrUI and direct all further jobs to this 
 *  subdirectory. This way, jobs of two sessions may have the same name
 *  and not step into each other.
 *
 *  PREPARING JOBS FOR THE GRID
 *
 *  To prepare a job for the grid you must assign it a name. The same
 *  preacutions that apply to any local job hold for your grid work too:
 *  if various simultaneous jobs of the same kind may be run, then each
 *  must be kept separate from the others by using a different name.
 *
 *  Once you have decided the name, you must create a directory locally
 *  with the same name as your job. In this directory you must install
 *  everything needed to run your job: executables, libraries, input
 *  data and a JDL file.
 *
 *  The JDL file defines the work that we will ask the grid to carry
 *  out. Since each single job gets its own directory, you will only
 *  submit one JDL file for each, and to make processing easier, we
 *  request that this JDL file have a fixed name: "job.jdl".
 *
 *  The grid processing will generate various auxiliary files, for internal
 *  housekeeping. Again, for simplicity, we have chosen to call each of
 *  them 'job.*', i.e. 'job.' something. This means that other than 'job.jdl'
 *  you should NOT create any file named job.anything on your job directory
 *  to avoid collusion with possible temporary files.
 *
 *  In brief, to prepare a job:
 *  - select a name
 *  - create a directory named after the job
 *  - populate this directory with all files needed to run your job
 *  - generate the file 'job.jdl' with the description of the work to
 *    be carried out by the grid
 *  - avoid having files named 'job.*' (starting with 'job.')
 *
 *  SUBMITTING JOBS TO THE GRID
 *
 *  First consider whether you will be using unique job names or if
 *  you will follow a convenient naming convention that may cause name
 *  collisions with other jobs.
 *
 *  If you feel pretty safe that the job name is unique (e.g. has been
 *  generated using one or more random strings), then simply call the
 *  appropriate *job_submit() function.
 *
 *  If you are using names that have low entropy or reusing names for
 *  similar jobs then it is advisable that you first call one of the
 *  *new_session() routines to ensure all your jobs will be kept isolated
 *  from other similarly named jobs, and then use the *job_submit()
 *  routines to send your jobs.
 *
 *  For the curious: when you submit your job, the directory and all
 *  of its contents will be sent to the remote Grid UI selected and
 *  then the 'job.jdl' will be submitted to the grid. In the process,
 *  several files will be generated holding information about your
 *  job identity in the grid context that will be kept for housekeeping
 *  and future reference.
 *
 *  The above is to be kept in mind when submitting light or numerous
 *  jobs: the transfer time may become sensibly relevant. Please do 
 *  take it into consideration in your equations when designing jobs
 *  for the grid using this class. You may find it interesting to first
 *  store all or some of your job data/execs on the grid and keep them
 *  already there instead of having to copy them.
 *
 *  Grid file management routines are not included yet, but are intended
 *  for a future release of this class.
 *
 */
class Grid {

    var $entry_point; 	/**< the grid entry point, should not be needed */
    var $username;	/**< user name to use to connect to the grid */
    var $hostname;	/**< name of host that provides access to the grid */
    var $password;	/**< password to login on the UI node */
    var $passphrase;	/**< key to unlock the grid access certificate */
    var $work_dir;	/**< a GrUI directory where we can work */
    var $error_log;	/**< a local file to store the error log */
    
    var $std_in;	/**< Internal. Standard input of the grid entry */
    var $std_out;	/**< Internal. Standard output of the grid entry */
    var $std_err;	/**< Internal. Standard error of the grid entry */
			// !!! IMPORTANT !!! either:
			//	a. set files to not hang on wait or
			//	b. remember to set them to no-hang when needed

    var $connected;	/**< Internal: Have we already connected? */
    var $initialized;	/**< Internal: Have we already identified ourselves? */
    
    /**
     * Constructor for the class
     *
     * Set the values for the class variables using defaults provided in
     * 'config.php'
     *
     * These defaults can be overridden using the functions provided below.
     */
    function Grid() {
    	$this->entry_point = $GLOBALS['grid_server'];
	$this->username    = $GLOBALS['grid_user'];
	$this->hostname    = $GLOBALS['grid_host'];
	$this->password    = $GLOBALS['grid_password'];
	$this->passphrase  = $GLOBALS['grid_passphrase'];
	$this->work_dir    = $GLOBALS['grid_wd_path'];
	$this->error_log   = $GLOBALS['grid_error_log'];
	$this->connected   = FALSE;
    }
    
    // A C C E S S    V A R I A B L E    V A L U E S
    // Note: do we also need "get_XX" routines?

    /**
     * set the Grid user name
     *
     * @param user identity to use in the Grid UI host
     */
    function set_user($user)
    {
    	$this->username = $user;
	$this->entry_point=$user."@".$this->hostname;
    }
    
    /**
     * set the name of the Grid access host
     *
     * @param host name of the remote UI host
     */
    function set_host($host)
    {
    	$this->hostname = $host;
	$this->entry_point = $this->username."@".$host;
    }
    
    /**
     * set the password for the remote grid user/server
     *
     *	This is specific to the remote UI server selected.
     *
     * @param pass password needed to login on to the grid UI server
     */
    function set_password($pass)
    {
    	$this->password = $pass;
    }

    /**
     * set the passphrase for the remote grid user
     *
     *	This is grid-wise and independent of the UI-node used.
     *
     * @param pass passphrase needed to unlock the grid certificate
     */
    function set_passphrase($pass)
    {
    	$this->passphrase = $pass;
    }
    
    /**
     * set working directory on the Grid server
     *
     * @param work_dir the remote path of the working directory
     */
    function set_work_dir($wd)
    {
    	$this->work_dir=$wd;
    }
    
    /**
     * set error log
     *
     * @param errlog path to a local file where we will store the error log
     *		     (i.e. stderr of the grid connection)
     */
    function set_error_log($errlog)
    {
    	$this->error_log = $errlog;
    }
    
    function get_connection_status()
    {
    	return $this->connected;
    }

    function get_init_status()
    {
    	return $this->initialized;
    }

//------ P E R S I S T E N T   G R I D   A C C E S S   F U N C T I O N S ------

    //
    // C O N N E C T    T O    T H E    U S E R    I N T E R F A C E   H O S T
    //
    
    /**
     * open a persistent connection to the Grid UI server
     * 
     * 	The Grid User Interface Server is the entry point to the Grid
     *  for users and user applications. This is where jobs are launched from.
     * 
     * 	This package has been designed to be able to be installed in any
     *  host, independent of whether it is an UI or not. Thus, to be able to
     *  submit jobs to the Grid, the server hosting the Web UI must connect to
     *  a Grid UI host to do the work.
     * 
     * 	This routine opens a connection to a Grid UI host using an specified
     *  username (i.e. all jobs will be run under said username). To allow the
     *  caller to communicate with the remote end, it creates two pipes/pty, one for
     *  input and one for output, and redirects error messages to a file.
     *
     *  We need to redirect stderr to a file. This is so to avoid blocking on
     *  reading to check for errors and to avoid (if we use PTYs) interleave
     *	of error messages and normal I/O.
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
     *	This allows for better resilience: should a GridUI host be 
     * unavailable, we can detect the error condition and try another 
     * one. If the GridUI runs the front-end, then we have a single
     * point of failure, which is a no-no.
     *
     *	@note	Use of persistent connections is greatly DISCOURAGED:
     * all I/O will go through pipes, and any end of the line may hang
     * waiting on read (if there is nothing to be read at the other end)
     * or write (if there is noone to retrieve the data at the other
     * end).
     *
     *	Just picture this: you open a connection to a GridUI and the
     * remote host issues an unusually large 'motd' that fills the
     * pipe. The remote shell will hang waiting for someone to read and
     * empty the pipe before continuing. Now, on the local side we do
     * issue a remote command (without checking the output): we hang 
     * waiting for the other end to read it, but the other end is hung.
     * Sadly a 'motd' may contain anything, and we can't predict what
     * the remote prompt will look like...
     *
     *	Be careful. Be _very_ careful.
     *
     *	AND always consider setting the pipe ends to non-blocking status.
     * This is actually the default, but has its tricks too. Be careful.
     * Be _very_ careful.
     *
     *	@note	There is no easy way to know the exit status of a remote
     * command while using persisten connections. Your only chance is to
     * review the output log and check it yourself for error messages.
     *
     *	We could be doing better here, but for now we will leave this for
     * later. After all, the point here is to give the user maximum
     * efficiency.
     *
     *	@return TRUE on success, FALSE otherwise.
     */

    function pconnect()
    {	
    	global $php_version;
	global $debug;
	
	// Open a child process with the 'proc_open' function. 
	//
	// Some tricks: we must open the connection using '-x' to disable
	// X11 forwarding, and use '-t -t' to avoid SSH generating an error
	// because we are not connected to any terminal.
    	// NOTE:
	//   We require users to have an account and password on
	//   the UI and provide their user/password through the web or
	//   otherwise (e.g. using myproxy)
    	//
	// NOTE: if the web server is trusted remotely (i.e. it's SSH public 
	// key is accepted in ~user@host:.ssh/authorized_keys) then any 
	// password will do.
	if ($php_version < 5) {
	    $descriptorspec = array(
        	0 => array("pipe", "r"),  // connect child's stdin to the read end of a pipe
        	1 => array("pipe", "w"),  // connect child's stdout to the write end of a pipe
        	2 => array("file", $this->error_log, "a") // stderr is a file to write to
	    );
	    $this->process = proc_open("SSH/SSH.sh $this->entry_point", 
	    		     $descriptorspec,
			     $pipes);
	    if ($debug) echo "SSH/SSH.sh $this->entry_point<br />\n";
	    // check status
	    if (!is_resource($this->process)) 
	    {
		letal("Grid::connect", "cannot connect to the Grid");
		return $this->connected = FALSE;
	    }
	    if ($debug) echo "proc_open<br />\n";
	    // give SSH it's due password
	    // XXX - this might be better..
	    fwrite($pipes[0], "$this->password\n");
	    if ($debug) echo "$this->password<br />\n";
	    fflush($pipes[0]);
	 
	}
	else { 	/* php5 -- we can use PTYs */
		/* XXX -- untested -- probably unneeded */
	    $descriptorspec = array(
		0 => array("pty"),
		1 => array("pty"),
		2 => array("file", $this->error_log, "a")
	    );
	    $this->process = proc_open("ssh -x -t -t $this->entry_point", 
	    	    	     $descriptorspec,
			     $pipes);
		
	    // check status
	    if (!is_resource($this->process)) 
	    {
		letal("Grid::connect", "cannot connect to the Grid");
		return $this->connected = FALSE;
	    }

	    $status = proc_get_status($this->process);
	    if ($status->running == FALSE) {
		fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
		proc_close($this->process);
		letal("Grid::connect", "connection exited ".$status->exitcode);
		return $this->connected = FALSE;
	    }
	    if ($status->signaled) {
		fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
		proc_close($this->process);
		letal("Grid::connect", "connection terminated by ".$status->termsig);
		return $this->connected = FALSE;
	    }
	    if ($status->stopped) {
		// Tell the user and hope for the best
		warning("Grid::connect stopped by ".$status->stopsig.
		" it may still have a chance though");
	    }

	    // Give ssh it's due password
	    $dummy = fgets($pipes[1],1024);	/* wait for prompt */
	    if ($debug) echo $dummy."<br />\n";
	    fwrite($pipes[0], "$this->password\n");
	    fflush($pipes[0]);

	}
	
	// $pipes now looks like this:
	//   0 => writeable handle connected to child stdin
	//   1 => readable handle connected to child stdout
	// Any error output will be appended to $wd_path/error-output.txt
    	$this->std_in = $pipes[0];
	$this->std_out = $pipes[1];
	$this->std_err = fopen($this->error_log, "r");

	// We now have a connection to the remote Grid User Interface
	// Server which we may use to send commands/receive output
	return $this->connected = TRUE;
    }

    /**
     * Close the connection with the remote Grid access point (UI node)
     *
     * 	What we are going to do is close the communication pipes
     *  and kill the child process that actually handles communication
     *  with the remote Grid UI host (ssh).
     *  This function returns the exit status of the connection (i.e. of
     *	the intermediate program that handles the connection --in this
     *  case SSH).
     *
     *  @return integer exit status of the connection (or the handling program)
     */
    function pdisconnect()
    {
	global $debug;
	if ($this->connected == FALSE)
		return $ret = 0;

	// We need to close the 'child' process, otherwise the call to
	// the 'proc_close()' function hangs!!!
	fwrite($this->std_in, "logout\n");
	fflush($this->std_in);

	fclose($this->std_in);

	// The output from the commands
	//  Note that this may
	//  	a) disrupt normal output
	//  	b) get into an infinite loop if the child is in one
	//  But it is useful for debugging, hence here.
    	if ($debug == TRUE)
	    do {
    	    
		$line = fgets($this->std_out, 1024);
		echo $line."<br />\n";
	    } while (!feof($this->std_out) && (strlen($line) != 0));

	fclose($this->std_out);

    	if ($debug == TRUE)
    	    do {
    	    
		$line = fgets($this->std_err, 1024);
		echo $line."<br />\n";
	    } while (!feof($this->std_err) && (strlen($line) != 0));

	fclose($this->std_err);

	// It's important that we close any open files before calling
	// proc_close in order to avoid a deadlock

	$return_value = proc_close($this->process);
	
	$this->connected = FALSE;

	return $return_value;	
    }
    
    // I D E N T I F Y    T O    T H E    G R I D
    
    /**
     * Start the Grid services
     *
     *	This function starts the grid services on the remote UI server host.
     *	This is done by unlocking the certificate that we are going to use 
     *  to run our jobs on the grid using the passphrase provided.
     *
     *	We need to have a connection open with the remote grid server as the 
     *  user with whose identity we want to run the jobs. This connection is
     *	created by Grid::pconnect().
     *
     *	To ease things up, we check if we are already connected, and if we
     *	aren't, we try to connect ourselves. That is, there is no need to
     *	call Grid::Connect() first unless you want to do something else
     *	on the Grid-UI before initializing the Grid.
     *
     *	Grid services have a lifetime of their own. By default they are
     *	available for 12:00 hours (that's the default value of
     *	grid-proxy-init itself), but their duration may be fine tuned
     *	if we have some knowledge about the time required to run our
     *	job.
     *
     *	Session duration is specified in hours+minutes. If the number of
     *	minutes is negative, the specified minutes are substracted from
     *	the specified hours (e.g: 1, -15 is fifteen minutes to one hour,
     *	i.e. 45 minutes). If the total time specified is negative then
     *	the default of 12:00 is used.
     *
     *	@param	hours	    Estimated duration in hours of the session
     *
     *	@param	minutes     Estimated duration in minutes of the session
     *
     */
    function pinitialize($hours=12, $minutes=0)
    {
	global $debug;

    	$total_minutes = ($hours * 60) + ($minutes);
	if ($total_minutes < 0) {
	    //use default
	    $hours = 12;
	    $minutes = 0;
	} else {
	    $hours = floor($total_minutes / 60);
	    $minutes = $total_minutes % 60);
	}

 	if ($this->connected == FALSE)
		$this->connect();
	
	// Remote EGEE middleware interaction
	// We run the Globus and Middleware commands in the User Interface, but 
	// the output goes to the remote machine in the current directory
	fwrite($this->std_in, "grid-proxy-init  -pwstdin -valid $hours:$minutes\n");
	fflush($this->std_in);
	if ($debug) {
	    echo fgets($this->std_out, 1024)."<p>\n";	// last (should have been eaten
						    // by connect() )
	    echo fgets($this->std_out, 1024)."<p>\n";	// command
	    echo fgets($this->std_out, 1024)."<p>\n"; // whoami
	} else {
	    fgets($this->std_out, 1024);	// this one should not be needed
	    fgets($this->std_out, 1024);
	    fgets($this->std_out, 1024);
	}
	fwrite($this->std_in, "$this->passphrase\n");
	fflush($this->std_in);
	if ($debug) {
		echo"<p><hr><p>";
		echo fgets($this->std_out, 1024)."<p>"; // password
		echo fgets($this->std_out, 1024)."<p>"; // creating...
		echo fgets($this->std_out, 1024)."<p>"; // validity
	} else {
	    fgets($this->std_out, 1024);    // remove passphrase from std_out
//	    fgets($this->std_out, 1024);
//	    fgets($this->std_out, 1024);
	}
    	// The output from grid-proxy-init will go to the session log
	// in std_out
	// Make sure it is read or the child may block on writing.
	
    }

    /**
     *	Destroy remote grid identity
     *
     *	We destroy the certification we initialized so that no more jobs
     *	can be launched under our identity.
     *
     *	@param pipes	The set of pipes to communicate (stdin/stdout) with
     *	    	    	the remote grid server entry point
     */
    function pdestroy()
    {
	global $debug;
    	fwrite($this->std_in, "grid-proxy-destroy\n");
	fflush($this->std_in);
    	
	// The output from grid-proxy-destroy will go to the session log
	// in std_out. There should be none.
	// Make sure it is read or the child may block on writing.

    }

    // J O B    M A N I P U L A T I O N
    // For the moment these are just stubs to be filled in
    //
    
    /**
     * Submit a job to the Grid
     *
     * @note STUB. DO NOT USE YET
     */
    function pjob_submit($job)
    {
	global $debug;
	
    	// Take a job package and submit it to the Grid
	//  Job package:
	//  	everything needed to run the job
	//
	// 1. copy the job package to the grid server
	
	fwrite($this->std_in, "mkdir -p $this->work_dir\n");
	fflush($this->std_in);
	// Note: the following will prompt for a password, needs refining!
	passthru("scp -rpqC $job $this->entry_point:$this->work_dir\n", $status);
	if ($status > 0)
	    letal("Job submission", "Can't copy job to Grid server");
	
	// set real job name:
	//  we might have got a long pathname for the job
	//  scp will only copy the last element of the path, which is OK
	//  but we must be sure we use that portion as well for the
	//  remote directory name
	$job = basename($job);
	
    	// 2. submit job to the grid
	// we may need to issue a fwrite($this->std_in, "cd $this->work_dir\n");
	fwrite($this->std_in, "edg-job-submit"
	    	    	    . "  -o $this->work_dir/$job/job.id"
	    	    	    . "  $this->work_dir/$job/job.jdl"
	    	    	    . "  > $this->work_dir/$job/job-info.out\n");
    	fflush($this->std_in);
	
	/* This might be used for local files, not remote ones
	return preg_grep("/^https:/", file("$this->work_dir/$job/job.id"));
	* Alternately, we might test for completion of edg-job-submit and
	* then scp those files back to user's home host and then use the
	* local code above.
    	*/
	return;
    }
    

    function pjob_get_id($job)
    {
    	// XXX first we should check that job.id exists
	// there are two possible scenarios:
	//  mistake - we are called without first calling job_submit
	//  correct - we are called after job_submit: then
	//  	- job_submit has completed -- job.id exists
	//  	- job_submit has NOT completed -- job.id does'n exist yet
	//  	  but job-info.out does
	
	// check submit.out exists: if not, then warn user
	
	// check job.id exists: if not wait/warn user

    	// we may have received a long pathname, but only care about
	// its last component remotely
	$remote_dir = basename($job);
    	passthru("scp -C $this->work_dir/$remote_dir/job.id $job", $status);
    	if ($status > 0) {
    	    // this is a warning since job-submit may not have finished yet
	    warning("Get job ID: could not retrieve grid job ID");
	    return;
	}
	else {
	    return preg_grep("/^https:/", file("$job/job.id"));
	}
	return; // empty
    }

    /**
     * check job status
     *
     * @note STUB. DO NOT USE YET
     */
    function pjob_status($job)
    {
    	$rjob = basename($job);
    	$job_id = $this->job_get_id($rjob);
    	fwrite($this->std_in, "edg-job-status $job_id > $this->work_dir/$rjob/job.stat\n");
	fflush($this->std_out);
//...
    }
    
    /**
     * retrieve results
     *
     * @note STUB. DO NOT USE YET
     */
    function pjob_retrieve($job)
    {
    	// get output pack ... 
	passthru("scp $this->entry_point:$work_dir/$job/output.tgz .", $status);
	passthru("tar -zxvf output.tgz", $status);
    }
}


//------ S T A N D A L O N E   G R I D   A C C E S S   F U N C T I O N S ------

    /**
     * Start the Grid services
     *
     *	This function starts the grid services on the remote UI server host.
     *	This is done by unlocking the certificate that we are going to use 
     *  to run our jobs on the grid using the passphrase provided.
     *
     *	Grid services have a lifetime of their own. By default they are
     *	available for 12:00 hours (that's the default value of
     *	grid-proxy-init itself), but their duration may be fine tuned
     *	if we have some knowledge about the time required to run our
     *	job.
     *
     *	Session duration is specified in hours+minutes. If the number of
     *	minutes is negative, the specified minutes are substracted from
     *	the specified hours (e.g: 1, -15 is fifteen minutes to one hour,
     *	i.e. 45 minutes). If the total time specified is negative then
     *	the default of 12:00 is used.
     *
     *	@param	hours	    Estimated duration in hours of the session
     *
     *	@param	minutes     Estimated duration in minutes of the session
     *
     *	@return TRUE on success, FALSE otherwise
     */
     
    function initialize($hours=12, $minutes=0)
    {
    	$total_minutes = ($hours * 60) + ($minutes);
	if ($total_minutes < 0) {
	    //use default
	    $hours = 12;
	    $minutes = 0;
	} else {
	    $hours = floor($total_minutes / 60);
	    $minutes = $total_minutes % 60);
	}
	
    	$remote = SExec::ssh_popen("grid-proxy-init -pwstdin -valid $hours:$minutes", "w");
	fputs($remote, $this->passphrase);
	$status SExec::ssh_close($remote);
	if ($status == 0) {
	    return $this->initialized = TRUE;
	} else {
	    return $this->initialized = FALSE;
	}
    }

    /**
     *	Destroy remote grid identity
     *
     *	We destroy the certification we initialized so that no more jobs
     *	can be launched under our identity.
     *
     *	@note	Be careful when using this function: as it destroys our
     *	    	Grid-ID, no more work will be able to be executed on the
     *	    	grid on our behalf. In other words, please, make sure there
     *	    	is no work pending and that all your work has terminated
     *	    	before destroying your Grid-ID.
     *
     *	@param pipes	The set of pipes to communicate (stdin/stdout) with
     *	    	    	the remote grid server entry point
     *
     *	@return exit status of the destroy command.
     */
    function destroy()
    {
    	return SExec::ssh_exec("grid-proxy-destroy");
    }

    /**
     * submit a job to the grid
     */
    function job_submit($job, &$out)
    {
    	if (isset($out)) {
    	    // we can report back job execution in detail
	    $status = SExec::ssh_exec("mkdir -p $this->work_dir", $output);
	    $out .= $output;
	    if ($status != 0) {
	    	// something went wrong
		return FALSE; 
	    }
	    $status = SExec::ssh_copy($job, $this->entry_point:$this->work_dir)
    }

?>

