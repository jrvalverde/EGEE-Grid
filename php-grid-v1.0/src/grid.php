<?
/**
 * Submit jobs to the Grid through a UI-node
 *
 * @author José R. Valverde <jrvalverde@acm.org>
 * @version 1.0
 * @copyright José R. Valverde <jrvalverde@acm.org>
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
 * @package	Grid
 * @author 	José R. Valverde <jrvalverde@acm.org>
 * @copyright 	José R. Valverde <jrvalverde@acm.org>
 * @license	doc/lic/lgpl.txt
 * @version	CVS: $Id$
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @see		ssh.php
 * @see     	../test/grid_test.php
 * @since	File available since Release 1.0
 */

/** definition of default values */
include_once("./grid_config.php");
/** SExec class */
require_once("./ssh.php");
/** utility routines */
include_once("./util.php");

/**
 *  Grid access class
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
 *  <b>PERSISTENT CONNECTIONS</b>
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
 *  <b>DISCONNECTED MODE</b>
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
 *  <b>JOB MANAGEMENT</b>
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
 *  <b>PREPARING JOBS FOR THE GRID</b>
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
 *  <b>SUBMITTING JOBS TO THE GRID</b>
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
 * @package Grid
 *
 * @pre ssh.php
 *
 * @see ssh.php (PHP SSH class)
 *
 * @author José R. Valverde <jrvalverde@acm.org>
 * @version 1.0
 * @copyright José R. Valverde <jrvalverde@acm.org>
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
 */
 
class Grid {

    /**#@+
     * @access private
     * @var string 
     */
    /** the grid entry point, should not be needed */
    var $entry_point; 	
    /** user name to use to connect to the grid */
    var $username;
    /** name of host that provides access to the grid */
    var $hostname;	
    /** password to login on the UI node */
    var $password;	
    /** key to unlock the grid access certificate */
    var $passphrase;
    /** a GrUI directory where we can work */
    var $work_dir;	
    /** a local file to store the error log */
    var $error_log;	
    /**#@-*/
    
    /**#@+
     * @access private
     * @var resource
     */
    /** Standard input of the grid entry */
    var $std_in;	
    /** Standard output of the grid entry */
    var $std_out;	
    /** Standard error of the grid entry */
    var $std_err;	
    // !!! IMPORTANT !!! either:
    //	a. set files to not hang on wait or
    //	b. remember to set them to no-hang when needed
    /**#@-*/

    /**
     * Have we already connected?
     *
     * @access private
     * @var bool
     */
    var $connected;
    /**
     * Have we already identified ourselves?
     *
     * @access private
     * @var bool
     */
    var $initialized;
    /**
     *  A unique identifier for this session
     *
     * @access private
     * @var string
     */
    var $session;
    /**
     *  The secure transfer communications line to use
     *
     * @access private
     * @var SExec
     */
    var $sx;
    
    /**
     * Constructor for the class
     *
     * Set the values for the class variables using defaults provided in
     * 'config.php'
     *
     * These defaults can be overridden using the functions provided below.
     *
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     * 
     * $eg = new Grid;
     * if ($eg == FALSE)
     *     echo "Couldn't instantiate a new Grid!\n";
     * </code>
     *
     * @return Grid a new instance of a Grid class object
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
	$this->session	   = "";
	$this->sx          = new SExec($this->entry_point, $this->password);
	return $this;
    }
    
    // A C C E S S    V A R I A B L E    V A L U E S
    // Note: do we also need "get_XX" routines?

    /**
     * set the Grid user name
     *
     *	In order to connect to the Grid and be able to submit
     * jobs we need a tuple (host/user/password/passphrase),
     * i.e. an entry point to log in, and a passphrase to unlock
     * the grid certificate.
     *
     *	This method allows us to define the username which will
     * be used to log in on the grid, i.e. how do we identify 
     * ourselves to the Grid UI host.
     *
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     *
     * $user="user";
     *
     * $eg = new Grid;
     * if ($eg == FALSE)
     *     echo "Couldn't instantiate a new Grid!\n";
     * $eg->set_user($user);
     * </code>
     *
     * @param string user identity to use in the Grid UI host
     */
    function set_user($user)
    {
    	$this->username = $user;
	$this->entry_point=$user."@".$this->hostname;
    }
    
    /**
     * set the name of the Grid access host
     *
     *	In order to connect to the Grid and be able to submit
     * jobs we need a tuple (host/user/password/passphrase),
     * i.e. an entry point to log in, and a passphrase to unlock
     * the grid certificate.
     *
     *	This method allows us to define the entry point to use
     * to gain access to the Grid.
     *
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     *
     * $host="gridui.example.com";
     *
     * $eg = new Grid;
     * if ($eg == FALSE)
     *     echo "Couldn't instantiate a new Grid!\n";
     * $eg->set_host($host);
     * </code>
     *
     * @param string host name of the remote UI host
     */
    function set_host($host)
    {
    	$this->hostname = $host;
	$this->entry_point = $this->username."@".$host;
    }
    
    /**
     * set the password for the remote grid user/server
     *
     *	In order to connect to the Grid and be able to submit
     * jobs we need a tuple (host/user/password/passphrase),
     * i.e. an entry point to log in, and a passphrase to unlock
     * the grid certificate.
     *
     *	This method allows us to specify the password to
     * clear access to the Grid UI host.
     *
     *	Note that this is specific to the remote UI server 
     * selected.
     *
     *	Further note that gaining access to a user account on
     * a given host does not give us rights to submit jobs:
     * we still need to unlock our ID certificate with the
     * appropriate passphrase. Anybody with root access to an
     * UI host can add accounts. Further, the UI host might
     * have other roles and host other accounts for different
     * purposes which should not access the grid. Bottomline is
     * that we can not trust an account on a user-controlled
     * host to identify Grid users. For this we need to recurse
     * to a central authority to grant final access.
     *
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     *
     * $password="password";
     *
     * $eg = new Grid;
     * if ($eg == FALSE)
     *     echo "Couldn't instantiate a new Grid!\n";
     * $eg->set_password($password);
     * </code>
     *
     * @param string password needed to login on to the grid UI server
     */
    function set_password($pass)
    {
    	$this->password = $pass;
    }

    /**
     * set the passphrase for the remote grid user
     *
     *
     *	In order to connect to the Grid and be able to submit
     * jobs we need a tuple (host/user/password/passphrase),
     * i.e. an entry point to log in, and a passphrase to unlock
     * the grid certificate.
     *
     *	After we gain access to the UI host, we must unlock our
     * certificate which identifies ourselves as 'bona-fide'
     * grid users.
     *
     *	People might have an account on any UI node for a variety
     * of reasons, but that does not qualify them to use Grid
     * resources. Only a central Grid authority can grant this
     * kind of access and this is done by issuing a Certificate.
     *
     *	Users then must store this certificate in their account
     * on a UI host and protect it with a suitably long passphrase.
     * This last one is the value we provide here.
     *
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     *
     * $passphrase="pass phrase to unlock certificate";
     *
     * $eg = new Grid;
     * if ($eg == FALSE)
     *     echo "Couldn't instantiate a new Grid!\n";
     * $eg->set_passphrase($passphrase);
     * </code>
     *
     * @param string passphrase needed to unlock the grid certificate
     */
    function set_passphrase($pass)
    {
    	$this->passphrase = $pass;
    }
    
    /**
     * set working directory on the Grid server
     *
     * This is a directory located on the grid server where all jobs
     * and job related information will be created. It may be a path
     * local to the user home or a global path (usually on /tmp or 
     * /var/tmp).
     *
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     *
     * $eg = new Grid;
     * if ($eg == FALSE)
     *     echo "Couldn't instantiate a new Grid!\n";
     * $eg->set_work_dir("./grid-services");
     * </code>
     *
     * @param string the remote path of the working directory
     */
    function set_work_dir($wd)
    {
    	$this->work_dir=$wd;
    }
    
    /**
     * set error log
     *
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     *
     * $eg = new Grid;
     * if ($eg == FALSE)
     *     echo "Couldn't instantiate a new Grid!\n";
     * $eg->set_error_log("./grid-services/error.log");
     * </code>
     *
     * @param string path to a local file where we will store the error log
     *		     (i.e. stderr of the grid connection)
     */
    function set_error_log($errlog)
    {
    	$this->error_log = $errlog;
    }
    
    /**
     * get grid connection status
     *
     * This method allows you to know if the connection with the remote
     * grid entry point has been successfully established or not. Note
     * that this does not mean you may launch jobs to the grid: you
     * still need to initialize the grid first.
     *
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     * 
     * $eg = new Grid;
     * 
     * $eg->pconnect();
     * if ($eg->get_connection_status() == FALSE)
     *     echo "Couldn't connect to the Grid entry point!\n";
     * </code>
     *
     * @return bool TRUE if the connection has been established, FALSE
     *	    	    otherwise.
     */
    function get_connection_status()
    {
    	return $this->connected;
    }

    /**
     * get grid initialization status
     *
     * This method allows you to learn whether the Grid has been
     * successfully initialized and is ready to accept jobs. This
     * entails both, login in as a specific user on the Grid connection
     * point, and activating the proxy with your passphrase.
     *
     * The reason for the two step process is that in order to activate
     * the grid you need to identify yourself using a grid certificate
     * emitted by a CA. But to activate it you need an account on a grid
     * access machine, which is open by any local administrator. Since
     * this account is not under the central CA control, we can't trust 
     * it to submit jobs and require a proxy-initialization with an
     * appropriate passphrase.
     *
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     * 
     * $eg = new Grid;
     * 
     * $eg->pinitialize();
     * if ($eg->get_init_status() == FALSE)
     *     echo "Couldn't initialize the Grid!\n";
     * </code>
     *
     * @return bool TRUE if the grid has been initialized, FALSE
     *	    	    otherwise.
     */
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
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     * 
     * $eg = new Grid;
     * 
     * $eg->pconnect();
     * if ($eg->get_connection_status() == FALSE)
     *     echo "Couldn't instantiate a new Grid!\n";
     * </code>
     *
     *	@note	Use files instead of pipes and open them after securing
     * thew connection: this should do with deadlocks and leave a trace
     * log.
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
	    if ($debug) echo "SSH/SSH.sh $this->entry_point\n";
	    // check status
	    if ((!is_resource($this->process)) || ($this->process == FALSE))
	    {
		letal("Grid::connect", "cannot connect to the Grid");
		return $this->connected = FALSE;
	    }
	    if ($debug) echo "proc_open\n";
	    // give SSH it's due password
	    // XXX - this might be better..
	    fwrite($pipes[0], "$this->password\n");
	    if ($debug) echo "pwd sent: $this->password\n";
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
	    if ((!is_resource($this->process)) || ($this->process == FALSE))
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
	    if ($debug) echo $dummy."\n";
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
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     * 
     * $eg = new Grid;
     * 
     * $eg->pconnect();
     * if ($eg->get_connection_status() == FALSE)
     *     echo "Couldn't instantiate a new Grid!\n";
     * $eg->pdisconnect();
     * </code>
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
		echo $line."\n";
	    } while (!feof($this->std_out) && (strlen($line) != 0));

	fclose($this->std_out);

    	if ($debug == TRUE)
    	    do {
    	    
		$line = fgets($this->std_err, 1024);
		echo $line."\n";
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
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     * 
     * $eg = new Grid;
     * 
     * $eg->pinitialize();
     * if ($eg->get_init_status() == FALSE)
     *     echo "Couldn't initialize the Grid!\n";
     * </code>
     *
     *	@param	integer	    Estimated duration in hours of the session
     *
     *	@param	integer     Estimated duration in minutes of the session
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
	    $minutes = $total_minutes % 60;
	}

 	if ($this->connected == FALSE)
		$this->pconnect();
	
	// Remote EGEE middleware interaction
	// We run the Globus and Middleware commands in the User Interface, but 
	// the output goes to the remote machine in the current directory
	fwrite($this->std_in, "grid-proxy-init  -pwstdin -valid $hours:$minutes\n");
	fflush($this->std_in);
	if ($debug) {
	    echo fgets($this->std_out, 1024)."\n";	// last (should have been eaten
						    // by connect() )
	    echo fgets($this->std_out, 1024)."\n";	// command
	    echo fgets($this->std_out, 1024)."\n"; // whoami
	} else {
	    fgets($this->std_out, 1024);	// this one should not be needed
	    fgets($this->std_out, 1024);    	// XXX JR XXX review this.
	    fgets($this->std_out, 1024);    	// for now, works 'as is'
	}
	fwrite($this->std_in, "$this->passphrase\n");
	fflush($this->std_in);
	if ($debug) {
		echo"<p><hr><p>";
		echo fgets($this->std_out, 1024)."\n"; // password
		echo fgets($this->std_out, 1024)."\n"; // creating...
		echo fgets($this->std_out, 1024)."\n"; // validity
	} else {
	    fgets($this->std_out, 1024);    // remove passphrase from std_out
//	    fgets($this->std_out, 1024);
//	    fgets($this->std_out, 1024);
	}
    	// The output from grid-proxy-init will go to the session log
	// in std_out
	// Make sure it is read or the child may block on writing.
	
	// Create the working directory if it doesn't exist
	fwrite($this->std_in, "mkdir -p $this->work_dir\n");
	fflush($this->std_in);
    }

    /**
     *	Destroy remote grid identity
     *
     *	Destroy the certification we initialized so that no more jobs
     *	can be launched under our identity.
     *
     *	Note that once you call this, no more work will be accepted on
     * the Grid on your behalf... BEWARE of race conditions!!!
     *
     *	As an example, suppose you have two concurrent works, A and B:
     * <pre>
     *	    A connects to the Grid
     *      A submits job to the Grid
     *	    	    	    	    	B connects to the Grid
     *	    A destroys proxy
     *	    	    	    	    	B submits job to the Grid <-- FAILS!!!
     *	    	    	    	    	B destroys proxy
     * </pre>
     *
     *	In general, you should not use this function, but rely instead on
     * the proxy's lifetime to do the control. Use this function when you
     * really want to make sure NO MORE WORK is performed at all. This may
     * be the case if you want to cancel all outstanding jobs.
     *
     *	@note no error checking is performed. You are supposed to check
     * the standard output to verify success yourself (just as in all 
     * other persistent commands).
     *
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     * 
     * $eg = new Grid;
     * 
     * $eg->pconnect();
     * $eg->pinitialize();
     * if ($eg->get_init_status() == FALSE)
     *     echo "Couldn't initialize the Grid!\n";
     * $eg->pdestroy();
     * $eg->pdisconnect();
     * </code>
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
    
    /**
     *	Create a new session
     *
     *	We take control of the session system: in a previous incarnation
     * we got the session name from the user. This is dangerous since we
     * can not avoid clashes even with random session names provided by
     * a user: a randomly generated name may be guaranteed unique within
     * the user's local host namespace, but should various calls come from
     * different hosts (e.g. HA front-ends in a cluster), it is conceivable
     * (although improbable) that both come up with the same random number
     * and generate a clash on the shared remote grid access point. Hence 
     * the new approach: to be true, users do not need to generate a session 
     * name themselves: they just need a way to refer to them.
     *
     *	This routine will generate a new session: under a given session,
     * we guarantee that processes will be run under an isolated sandbox
     * where non name clashes from other concurrent users will occur.
     * Name clashes within a given session induced by the user are still
     * the responsability of the user.
     *
     * SESSIONS
     *
     *	The basic idea is as follows: if you are developing a service that
     * may be called concurrently by various users, your problem at the GrUI
     * is the same as on your local server: avoiding name clashes for jobs.
     * As long as you manage that locally using unique names, the same will
     * work on the UI server.
     *
     *	This however may be inconvenient at times. It is usually the case
     * when your service is not composed by a single job, but by many
     * independent jobs that may be run concurrently as well. You still 
     * need to generate unique names for each sub-job, but if they are
     * produced within a local uniquely named service-instance, then you
     * may reuse the sub-job names for each instance.
     *
     *	This comes handy in the case of many sub-jobs: as long as the main
     * service instance is uniquely identified, contained sub-jobs may have
     * significant names that are easier to identify than randomly-generated
     * ones: e.g.
     * <pre>
     *    user A -> service-instance-A -+-> sub-job-1
     *                                  |
     *                                  +-> sub-job-2
     *                                  |
     *                                  etc...
     *
     *    user B -> service-instance-B -+-> sub-job-1
     *                                  |
     *                                  +-> sub-job-2
     *                                  |
     *                                  etc...
     * </pre>
     *	In this case, since the service-instances have unique names (A, B)
     * we may use the same naming strategy in both cases for naming sub-jobs
     * (1, 2,...) which makes bookkeeping a lot easier.
     *
     *	To reproduce a similar behaviour remotely we provide 'sessions'.
     *
     *	Basically, what you are doing in the local case is isolating all the
     * equally named jobs of each service-instance within an unique sandbox.
     * In the grid you get the same result using 'sessions': whenever you
     * want to submit a series of non-randomly-named jobs (or even a single
     * one) you first allocate a session, and then attach those jobs to the
     * session. Job names within a session are guaranteed not to clash with
     * equal job names from another session.
     *
     *	Note the 'non-randomly-named' tag above: you want to use sessions
     * ALWAYS that you use any non-random job name unless you can guarantee
     * it will be the only job with that name ON THE GRID.
     *
     *	This is an important notice: your job may have a non-random, but
     * guaranteed unique name on your local host. And as long as the job
     * will only be submitted from your local host it is OK. But if you
     * are going to share your tools with other fellows, then they will
     * install them locally and submit a similarly named job as well. The
     * job name will be unique within each one's local machine, but when
     * jobs are collected at the GrUI, they will all have the same name,
     * which is a no-no.
     *
     * USING SESSIONS
     *
     *	Once you see the need for using sessions, using them is quite 
     * simple: you call this function asking for a new session to be
     * created on the remote grid access point on your behalf. The routine
     * will return a unique session identifier which you later use to 
     * tag jobs that are to be run within that session's sandbox.
     *
     * @return string session ID of the newly generated session.
     *
     * @note untested. As all connected mode functions in this release,
     *	    you should check the grid stdout to test for errors.
     */
    function pnew_session()
    {
    	list($usec, $sec) = explode(' ', microtime());
    	$seed = (float) $sec + ((float) $usec * 100000);
    	srand($seed);
    	$rand1 = rand(); $rand2 = rand();
	$session = "session-$rand1-$rand2";
	fwrite($this->std_in, "mkdir -p ".$this->work_dir."/".$session."\n");
	return $session;
    }

    /**
     * just a placeholder. Untested.
     */
    function pdestroy_session($session)
    {
    	// we should loop first over every job and kill it.
    	// delete everything
    	fwrite($this->std_in, "rm -rf $this->work_dir/$session\n");
	$this->session = "";
    }

    /**
     * Submit a job to the Grid
     *
     *	This procedure submits a job to the Grid, optionally tagging it
     * as part of a specific session.
     *
     *	A job must be stored in a single directory (whose name you
     * provide in the call to this function). The directory must contain
     * any executables, libraries, configuration files/scripts, and input
     * data needed to run your job.
     *
     *	In addition, there must be a JDL file called 'job.jdl' and describing
     * the job to the grid using the JDL language.
     *
     *	Please note that only the job-directory name is used. If you provide
     * a longer path, only the last component (the job directory name) will
     * be used to identify your job remotely.
     *
     * Sample usage:
     * <code>
     * require_once './grid_config.php';
     * require_once './ssh.php';
     * require_once './grid.php';
     * 
     * $eg = new Grid;
     * 
     * $eg->pconnect();
     * $eg->pinitialize();
     * if ($eg->get_init_status() == FALSE)
     *     echo "Couldn't initialize the Grid!\n";
     * $eg->pjob_submit("tst-job"); 	// tst-job is a directory holding the job
     * $eg->pdiconnect();
     * </code>
     *
     *	@note in future instances we may provide routines to generate the 
     *	    JDL, possibly within a GridJob class of its own.
     *
     *	@param	string	The name of the job (same as the subdirectory it is in)
     *	@param	string Optional name of the session to which this job belongs
     *	    	    	(obtained from a previous call to pnew_session).
     * 
     */
    function pjob_submit($job, $session="")
    {
	global $debug;
	
    	// Take a job package and submit it to the Grid
	//  Job package:
	//  	everything needed to run the job
	//
	// 1. copy the job package to the grid server
	
	if ($debug) {
	    echo "Copying job $job to remote end ".
	    	"$this->entry_point:$this->work_dir/$session" .
		"with $this->password\n\n";
	}
	$status = $this->sx->ssh_copy(
	    	$job, 
	    	"$this->entry_point:$this->work_dir/$session", 
		$this->password);
	if ($status == FALSE)
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
    
    /**
     *	Get Grid ID of a submitted job.
     *
     *	You should not need this function normally. The job and session
     * names you already have should actually be enough for all your needs.
     * The function is needed internally by the class, but otherwise it should
     * be of little interest.
     *
     *	Nevertheless, you may want to have access to this knowledge, either
     * out of curiosity or for other reasons (e.g. re-routing access to a
     * job through other access points after a crash of the original access
     * point you used to submit it).
     *
     *	Indeed, this will come handy for newer releases of this class when
     * disaster recovery is added. Meanwhile, as already said, it is of little
     * use.
     *
     *	Sample usage:
     * <code>
     *     $eg = new Grid;
     *     $eg->set_user($user);
     *     $eg->set_host($host);
     *     $eg->set_password($passwd);
     *     $eg->set_passphrase($passphrase);
     *     $eg->set_work_dir("/tmp/grid/test");
     *     $eg->set_error_log("/tmp/grid/test/connection.err");
     *     $eg->pconnect();
     *     $eg->pinitialize();
     *     $eg->pjob_submit("tst-job");
     *     print_r( $eg->pjob_get_id("tst-job") );
     *     $eg->pdisconnect();
     * </code>
     *
     *	@param	string 	The name of the job you submitted to the grid
     *	@param  string Optionally identified the session to which the job
     *	    	    	belongs (if it was submitted within one).
     *
     *	@return array with the known job-id's submitted from this
     *	    	'job'. That looks as an oxymoron: there should only be
     *	    	one. Ergo, you may use it to detect job name clashes.
     *
     *	@note this is nasty and should be enhanced on a future version.
     */
    function pjob_get_id($job, $session="")
    {
    	global $debug;
	
    	// XXX first we check that job.id exists locally
	// (from a previous call) and use it. This will save
	// time and bandwidth.
	if (file_exists("$job/job.id"))
	    return preg_grep("/^https:/", file("$job/job.id"));

    	// we may have received a long pathname, but only care about
	// its last component remotely
	$remote_dir = basename($job);
    	if ($debug) {
	    echo "Retrieving ". 
	    "$this->entry_point:$this->work_dir/$session/$remote_dir/job.id" .
	    " into $job using $this->password\n\n";
	}
	$status = $this->sx->ssh_copy(
	    "$this->entry_point:$this->work_dir/$session/$remote_dir/job.id",
	    $job,
	    $this->password);
    	if ($status == FALSE) {
    	    // this is a warning since job-submit may not have finished yet
	    warning("Get job ID: could not retrieve grid job ID\n\n");
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
     *	This routine retrieves the job status report from the remote
     * grid entry point into your local job directory, and returns the
     * status of your specified job/session.
     *
     * Sample usage:
     * <code>
     *     $eg = new Grid;
     *     $eg->set_user($user);
     *     $eg->set_host($host);
     *     $eg->set_password($passwd);
     *     $eg->set_passphrase($passphrase);
     *     $eg->set_work_dir("/tmp/grid/test");
     *     $eg->set_error_log("/tmp/grid/test/connection.err");
     *     $eg->pconnect();
     *     $eg->pinitialize();
     *     $eg->pjob_submit("tst-job");
     *     print_r($eg->pjob_status("tst-job"));
     *     $eg->pdisconnect();
     * </code>
     * 
     *	@param	string 	The name of the job you submitted to the grid
     *	@param  string Optionally identified the session to which the job
     *	    	    	belongs (if it was submitted within one).
     *
     *	@return an array containing the job status
     */
    function pjob_status($job, $session="")
    {
    	global $debug;
	
    	$rjob = basename($job);
    	$job_id = $this->pjob_get_id($rjob);
	if ($debug) print_r($job_id);
    	fwrite($this->std_in, "edg-job-status ".$job_id[1]." > $this->work_dir/$session/$rjob/job.stat\n");
	fflush($this->std_out);
// wait for completion and copy locally as above and return.
// XXX JR XXX nasty, nasty, nasty. We should do out with sleeps..
//  and will on a newer release using files instead of pipes!
	sleep(1);
	if ($debug) {
	    echo "Retrieving " .
	    	"$this->entry_point:$this->work_dir/$session/$rjob/job.stat" .
		" into $job using $this->password\n\n";
	}
 	$status = $this->sx->ssh_copy(
	    "$this->entry_point:$this->work_dir/$session/$rjob/job.stat",
	    $job,
	    $this->password);
    	if ($status == FALSE) {
    	    // this is a warning since job-submit may not have finished yet
	    warning("Get job status: could not retrieve grid job status\n\n");
	    return;
	}
	else {
	    return preg_grep("/^Current Status:/", file("$job/job.stat"));
	}
	return; // empty
   	
    }
    
    /**
     * retrieve results
     *
     *	Retrieve the results of a job from the Grid. This function will
     * attempt to retrieve the results of a job. This relies on the results
     * being already available, i.e. you better check the job status first
     * and make sure it has completed.
     *
     *	If you don't, and the job hasn't completed yet, don't worry: nothing
     * will be retrieved. So, no harm done. But you should check stdout to
     * verify the condition.
     *
     *	All results will be stored locally on the job directory, under a
     * subdirectory with a unique name of the form $grid_user_name_XXXXX...
     * where the X's mean a random string.
     *
     *	To access your job output, just open this $grid_user_name* directory
     * and look inside.
     *
     *	The rationale for this is to avoid overwriting your job information
     * with its output. If that was intended, nothing is lost, just pick-up
     * the newly generated file. This way you always have continued access 
     * to your old, submitted data for checking.
     *
     * Sample usage:
     * <code>
     *     $eg = new Grid;
     *     $eg->set_user($user);
     *     $eg->set_host($host);
     *     $eg->set_password($passwd);
     *     $eg->set_passphrase($passphrase);
     *     $eg->set_work_dir("/tmp/grid/test");
     *     $eg->set_error_log("/tmp/grid/test/connection.err");
     *     $eg->pconnect();
     *     $eg->pinitialize();
     *     $eg->pjob_submit("tst-job");
     *     print_r($eg->pjob_status("tst-job"));
     *     sleep(5);	// give it time to run
     *     $eg->pjob_output("tst-job");
     *     $eg->pdisconnect();
     * </code>
     *
     *	@param	string 	The name of the job you submitted to the grid
     *	@param  string Optionally identified the session to which the job
     *	    	    	belongs (if it was submitted within one).
     *	@return bool TRUE on success, FALSE on failure.
     */
    function pjob_output($job, $session="")
    {
    	global $debug;
	
    	$rjob = basename($job);
	$job_id = $this->pjob_get_id($rjob);
	// get output pack from the grid to UI... 
    	if ($debug) {
	    print_r($job_id);
	    echo "sending edg-job-get-output " .
	    "--dir $this->work_dir/$session/$rjob/ " . 
	    $job_id[1] . "> job.out\n";
	}
	fwrite($this->std_in, "edg-job-get-output " .
	    "--dir $this->work_dir/$session/$rjob/ " . 
	    $job_id[1] . "> job.out\n");
	sleep(5);   // XXX JR XXX should check stdout instead of sleeping!
	if ($debug)
	    echo "getting $this->entry_point:$this->work_dir/$session/$rjob/"
	    .$this->username."_* into $job\n\n";
	$status = $this->sx->ssh_copy(
	    "$this->entry_point:$this->work_dir/$session/$rjob/".$this->username."_*",
	    $job,
	    $this->password);
	return $status;
    }



//------ C O N N E C T I O N L E S S   G R I D   A C C E S S   F U N C T I O N S ------

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
     * Sample usage:
     * <code>
     *     $eg = new Grid;
     *     $eg->set_user($user);
     *     $eg->set_host($host);
     *     $eg->set_password($passwd);
     *     $eg->set_passphrase($passphrase);
     *     $eg->set_work_dir("/tmp/grid/test/cless");
     *     $eg->set_error_log("/tmp/grid/test/cless/connection.err");
     *     if (!$eg->initialize())
     *     	echo "error: couldn't init the grid\n";
     *     else
     *     	echo "OK";
     *     $eg->destroy();
     *     $eg->disconnect();
     * </code>
     *
     *	@param	integer	    Estimated duration in hours of the session
     *
     *	@param	integer     Estimated duration in minutes of the session
     *
     *	@return bool TRUE on success, FALSE otherwise
     */
     
    function initialize($hours=12, $minutes=0)
    {
    	global $debug;
	
    	$total_minutes = ($hours * 60) + ($minutes);
	if ($total_minutes < 0) {
	    //use default
	    $hours = 12;
	    $minutes = 0;
	} else {
	    $hours = floor($total_minutes / 60);
	    $minutes = $total_minutes % 60;
	}
    	$rin = $this->sx->ssh_popen(
	    $this->entry_point,
	    $this->password,
	    "/opt/globus/bin/grid-proxy-init -pwstdin -valid $hours:$minutes", 
	    "w"
	    );
	fputs($rin, $this->passphrase."\n");
	$status = $this->sx->ssh_pclose($rin);
	if ($status != 0)
	    // something went airy
	    return FALSE;
	
	// Create working dir
	$status = $this->sx->ssh_exec(
	    $this->entry_point,
	    $this->password,
	    "mkdir -p $this->work_dir",
	    $out);
	    
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
     * Sample usage:
     * <code>
     *     $eg = new Grid;
     *     $eg->set_user($user);
     *     $eg->set_host($host);
     *     $eg->set_password($passwd);
     *     $eg->set_passphrase($passphrase);
     *     $eg->set_work_dir("/tmp/grid/test/cless");
     *     $eg->set_error_log("/tmp/grid/test/cless/connection.err");
     *     if (!$eg->initialize())
     *     	echo "error: couldn't init the grid\n";
     *     else
     *     	echo "OK";
     *     $eg->destroy();
     *     $eg->disconnect();
     * </code>
     *
     *	@note	Be careful when using this function: as it destroys our
     *	    	Grid-ID, no more work will be able to be executed on the
     *	    	grid on our behalf. In other words, please, make sure there
     *	    	is no work pending and that all your work has terminated
     *	    	before destroying your Grid-ID.
     *
     *	@return integer exit status of the destroy command.
     */
    function destroy()
    {
    	return $this->sx->ssh_exec(
	    $this->entry_point,
	    $this->password,
	    "grid-proxy-destroy",
	    $out);
    }

    /**
     *	Create a new session
     *
     *	We take control of the session system: in a previous incarnation
     * we got the session name from the user. This is dangerous since we
     * can not avoid clashes even with random session names provided by
     * a user: a randomly generated name may be guaranteed unique within
     * the user's local host namespace, but should various calls come from
     * different hosts (e.g. HA front-ends in a cluster), it is conceivable
     * (although improbable) that both come up with the same random number
     * and generate a clash on the shared remote grid access point. Hence 
     * the new approach: to be true, users do not need to generate a session 
     * name themselves: they just need a way to refer to them.
     *
     *	This routine will generate a new session: under a given session,
     * we guarantee that processes will be run under an isolated sandbox
     * where non name clashes from other concurrent users will occur.
     * Name clashes within a given session induced by the user are still
     * the responsability of the user.
     *
     * SESSIONS
     *
     *	The basic idea is as follows: if you are developing a service that
     * may be called concurrently by various users, your problem at the GrUI
     * is the same as on your local server: avoiding name clashes for jobs.
     * As long as you manage that locally using unique names, the same will
     * work on the UI server.
     *
     *	This however may be inconvenient at times. It is usually the case
     * when your service is not composed by a single job, but by many
     * independent jobs that may be run concurrently as well. You still 
     * need to generate unique names for each sub-job, but if they are
     * produced within a local uniquely named service-instance, then you
     * may reuse the sub-job names for each instance.
     *
     *	This comes handy in the case of many sub-jobs: as long as the main
     * service instance is uniquely identified, contained sub-jobs may have
     * significant names that are easier to identify than randomly-generated
     * ones: e.g.
     * <pre>
     *    user A -> service-instance-A -+-> sub-job-1
     *                                  |
     *                                  +-> sub-job-2
     *                                  |
     *                                  etc...
     *
     *    user B -> service-instance-B -+-> sub-job-1
     *                                  |
     *                                  +-> sub-job-2
     *                                  |
     *                                  etc...
     * </pre>
     *	In this case, since the service-instances have unique names (A, B)
     * we may use the same naming strategy in both cases for naming sub-jobs
     * (1, 2,...) which makes bookkeeping a lot easier.
     *
     *	To reproduce a similar behaviour remotely we provide 'sessions'.
     *
     *	Basically, what you are doing in the local case is isolating all the
     * equally named jobs of each service-instance within an unique sandbox.
     * In the grid you get the same result using 'sessions': whenever you
     * want to submit a series of non-randomly-named jobs (or even a single
     * one) you first allocate a session, and then attach those jobs to the
     * session. Job names within a session are guaranteed not to clash with
     * equal job names from another session.
     *
     *	Note the 'non-randomly-named' tag above: you want to use sessions
     * ALWAYS that you use any non-random job name unless you can guarantee
     * it will be the only job with that name ON THE GRID.
     *
     *	This is an important notice: your job may have a non-random, but
     * guaranteed unique name on your local host. And as long as the job
     * will only be submitted from your local host it is OK. But if you
     * are going to share your tools with other fellows, then they will
     * install them locally and submit a similarly named job as well. The
     * job name will be unique within each one's local machine, but when
     * jobs are collected at the GrUI, they will all have the same name,
     * which is a no-no.
     *
     * USING SESSIONS
     *
     *	Once you see the need for using sessions, using them is quite 
     * simple: you call this function asking for a new session to be
     * created on the remote grid access point on your behalf. From now
     * on all jobs submitted will be part of the current session.
     *
     * @return bool TRUE on success, FALSE on failure
     *
     * @note untested. As all connected mode functions in this release,
     *	    you should check the grid stdout to test for errors.
     */
    function new_session()
    {
    	$status = $this->sx->ssh_exec( 
	    $this->entry_point,
	    $this->password,
	    "mktemp -d -p $this->work_dir sess_XXXXXXXXXXXXXXXX",
	    $out);
	if ($status == 0) {
	    $this->work_dir = $out;
	    return TRUE;
	} else
	    return FALSE;
    }

    /**
     * destroy the current session
     *
     * untested!
     */
    function destroy_session()
    {
    	// we should loop first over every job and kill it.
	// delete everything
    	$status = $this->sx->ssh_exec(
	    $this->entry_point,
	    $this->password,
	    "rm -rf $this->work_dir",
	    $out);
	if ($status == 0) {
	    // strip session part
	    $this->work_dir = dirname($this->work_dir);
	    return TRUE;
	}
	else
	    return FALSE;
    }

    /**
     * submit a job to the grid
     *
     *	This procedure submits a job to the Grid, optionally tagging it
     * as part of a specific session.
     *
     *	A job must be stored in a single directory (whose name you
     * provide in the call to this function). The directory must contain
     * any executables, libraries, configuration files/scripts, and input
     * data needed to run your job.
     *
     *	In addition, there must be a JDL file called 'job.jdl' and describing
     * the job to the grid using the JDL language.
     *
     *	Please note that only the job-directory name is used. If you provide
     * a longer path, only the last component (the job directory name) will
     * be used to identify your job remotely.
     *
     * Sample usage:
     * <code>
     *     $eg = new Grid;
     *     $eg->set_user($user);
     *     $eg->set_host($host);
     *     $eg->set_password($passwd);
     *     $eg->set_passphrase($passphrase);
     *     $eg->set_work_dir("/tmp/grid/test/cless");
     *     $eg->set_error_log("/tmp/grid/test/cless/connection.err");
     *     echo "initializing grid... ";
     *     if (!$eg->initialize())
     *     	echo "error: couldn't init the grid\n";
     *     else
     *     	echo "OK\n";
     *     echo "Submitting tst-job... ";
     *     if (! $eg->job_submit("tst-job", $out))
     *     	echo "error: coudn't start the job\n";
     *     else 
     *     	echo "OK\n";
     *     print_r($out);
     *     $eg->destroy();
     *     $eg->disconnect();
     * </code>
     *
     *	@note in future instances we may provide routines to generate the 
     *	    JDL, possibly within a GridJob class of its own.
     *
     *	@param	string	The name of the job (same as the subdirectory it is in)
     *	@param	string Optional name of the session to which this job belongs
     *	    	    	(obtained from a previous call to pnew_session).
     *	@param string  	A variable to hold any messages spitted by the submission
     *	    	    	procedure.
     * 
     */
    function job_submit($job, &$out)
    {
    	global $debug;
	
	if ($debug) {
	    echo "Copying job $job to remote end ".
	    	"$this->entry_point:$this->work_dir" .
		" with $this->password\n\n";
	}
    	// Copy job details over to remote end
	$status = $this->sx->ssh_copy(
	    $job, 
	    "$this->entry_point:$this->work_dir",
	    $this->password);
	if ($status == FALSE)
	    // copy failed
	    return FALSE;
	if ($debug)
	    echo "Submitting "
	    . "/opt/edg/bin/edg-job-submit"
	    . "  -o $this->work_dir/$job/job.id"
	    . "  $this->work_dir/$job/job.jdl"
	    . "  | tee $this->work_dir/$job/job.info.out";

    	// NOTE: with direct execution we don't go through the login
	// process, hence needed environment variables must be set.
	$status = $this->sx->ssh_exec(
	    $this->entry_point,
	    $this->password,
	    "(export EDG_WL_LOCATION=/opt/edg ;"
    	    . " /opt/edg/bin/edg-job-submit"
	    . "  -o $this->work_dir/$job/job.id"
	    . "  $this->work_dir/$job/job.jdl"
	    . "  | tee $this->work_dir/$job/job.info.out"
	    . ")",
	    $out);
	// note: perhaps we should use 'tee' and get the job.info.out?
	if ($debug) echo "submit status was $status\n";
	if ($status == 0)
	    return TRUE;
	else
	    return FALSE;
    }
    
    /**
     *	Get Grid ID of a submitted job.
     *
     *	You should not need this function normally. The job and session
     * names you already have should actually be enough for all your needs.
     * The function is needed internally by the class, but otherwise it should
     * be of little interest.
     *
     *	Nevertheless, you may want to have access to this knowledge, either
     * out of curiosity or for other reasons (e.g. re-routing access to a
     * job through other access points after a crash of the original access
     * point you used to submit it).
     *
     *	Indeed, this will come handy for newer releases of this class when
     * disaster recovery is added. Meanwhile, as already said, it is of little
     * use.
     *
     * Sample usage:
     * <code>
     *     $eg = new Grid;
     *     $eg->set_user($user);
     *     $eg->set_host($host);
     *     $eg->set_password($passwd);
     *     $eg->set_passphrase($passphrase);
     *     $eg->set_work_dir("/tmp/grid/test/cless");
     *     $eg->set_error_log("/tmp/grid/test/cless/connection.err");
     *     echo "initializing grid... ";
     *     if (!$eg->initialize())
     *     	echo "error: couldn't init the grid\n";
     *     else
     *     	echo "OK\n";
     *     echo "Submitting tst-job... ";
     *     if (! $eg->job_submit("tst-job", $out))
     *     	echo "error: coudn't start the job\n";
     *     else 
     *     	echo "OK\n";
     *     print_r($out);
     *     print_r($eg->job_get_id("tst-job"));
     * </code>
     *
     *	@param	string 	The name of the job you submitted to the grid
     *	@param  string Optionally identified the session to which the job
     *	    	    	belongs (if it was submitted within one).
     *
     *	@return array with the known job-id's submitted from this
     *	    	'job'. That looks as an oxymoron: there should only be
     *	    	one. Ergo, you may use it to detect job name clashes.
     *
     *	@note this is nasty and should be enhanced on a future version.
     */
    function job_get_id($job)
    {
    	// if we have been called successfully previously, then
	//  we have a cached copy of the 'job.id' file locally.
	//  Use it instead and save bandwidth.
	if (file_exists("$job/job.id"))
	    return preg_grep("/^https:/", file("$job/job.id"));
	
    	// we may have received a long pathname, but only care about
	// its last component remotely
	$remote_dir = basename($job);
    	$status = $this->sx->ssh_copy(
	    "$this->entry_point:$this->work_dir/$remote_dir/job.id",
	    $job, 
	    $this->password);
    	if ($status == FALSE) {
    	    // this is a warning since job-submit might not have finished yet
	    warning("Get job ID: could not retrieve grid job ID");
	    return "";
	}
	else {
	    return preg_grep("/^https:/", file("$job/job.id"));
	}
	// NOTE: this returns an array of \n terminated strings, which is
	// highly inconvenient. CORRECT
	return;
    }

    /**
     * check job status
     *
     *	This routine retrieves the job status report from the remote
     * grid entry point into your local job directory, and returns the
     * status of your specified job/session.
     *
     * Sample usage:
     * <code>
     *     $eg = new Grid;
     *     $eg->set_user($user);
     *     $eg->set_host($host);
     *     $eg->set_password($passwd);
     *     $eg->set_passphrase($passphrase);
     *     $eg->set_work_dir("/tmp/grid/test/cless");
     *     $eg->set_error_log("/tmp/grid/test/cless/connection.err");
     *     echo "initializing grid... ";
     *     if (!$eg->initialize())
     *     	echo "error: couldn't init the grid\n";
     *     else
     *     	echo "OK\n";
     *     echo "Submitting tst-job... ";
     *     if (! $eg->job_submit("tst-job", $out))
     *     	echo "error: coudn't start the job\n";
     *     else 
     *     	echo "OK\n";
     *     print_r($out);
     *     echo "\nGetting job ID... \n";
     *     print_r($eg->job_get_id("tst-job"));
     *     echo "\nGetting job status... \n";
     *     print_r($eg->job_status("tst-job", $out));
     * </code>
     * 
     *	@param	string 	The name of the job you submitted to the grid
     *	@param  string Optionally identified the session to which the job
     *	    	    	belongs (if it was submitted within one).
     *	@param	string 	Output of the status request program. Useful for
     *	    	    	debugging.
     *
     *	@return an array containing the job status
     */
    function job_status($job, &$out)
    {
    	global $debug;
	
    	$rjob = basename($job);
    	$job_id = $this->job_get_id($rjob);
	if ($debug)
	    echo "Sending\n\t" .
	    	"(export EDG_WL_LOCATION=/opt/edg ; " .
		"/opt/edg/bin/edg-job-status ". $job_id[1] .")",
    	$status = $this->sx->ssh_exec(
	    $this->entry_point,
	    $this->password,
	    "(export EDG_WL_LOCATION=/opt/edg ; " .
	    "/opt/edg/bin/edg-job-status ". $job_id[1] .")",
	    $out);
	if ($status != 0)
	    return "";
	else
	    return $out;	
    }
    
    /**
     * retrieve results
     *
     *	Retrieve the results of a job from the Grid. This function will
     * attempt to retrieve the results of a job. This relies on the results
     * being already available, i.e. you better check the job status first
     * and make sure it has completed.
     *
     *	If you don't, and the job hasn't completed yet, don't worry: nothing
     * will be retrieved. So, no harm done. But you should check stdout to
     * verify the condition.
     *
     *	All results will be stored locally on the job directory, under a
     * subdirectory with a unique name of the form $grid_user_name_XXXXX...
     * where the X's mean a random string.
     *
     *	To access your job output, just open this $grid_user_name* directory
     * and look inside.
     *
     *	The rationale for this is to avoid overwriting your job information
     * with its output. If that was intended, nothing is lost, just pick-up
     * the newly generated file. This way you always have continued access 
     * to your old, submitted data for checking.
     *
     * Sample usage:
     * <code>
     *     $eg = new Grid;
     *     $eg->set_user($user);
     *     $eg->set_host($host);
     *     $eg->set_password($passwd);
     *     $eg->set_passphrase($passphrase);
     *     $eg->set_work_dir("/tmp/grid/test/cless");
     *     $eg->set_error_log("/tmp/grid/test/cless/connection.err");
     *     echo "initializing grid... ";
     *     if (!$eg->initialize())
     *     	echo "error: couldn't init the grid\n";
     *     else
     *     	echo "OK\n";
     *     echo "Submitting tst-job... ";
     *     if (! $eg->job_submit("tst-job", $out))
     *        echo "error: coudn't start the job\n";
     *     else 
     *        echo "OK\n";
     *     print_r($out);
     *     echo "\nGetting job ID... \n";
     *     print_r($eg->job_get_id("tst-job"));
     *     echo "\nGetting job status... \n";
     *     print_r($eg->job_status("tst-job", $out));
     *     echo "\nGetting job output... ";
     *     if (! $eg->job_output("tst-job", $out))
     *     	echo "error: couldn't get job output\n";
     *     else
     *     	echo "OK\n";
     *     print_r($out);
     * </code>
     *
     *	@param	string 	The name of the job you submitted to the grid
     *	@param  string Optionally identified the session to which the job
     *	    	    	belongs (if it was submitted within one).
     *	@return bool TRUE on success, FALSE on failure.
     */
    function job_output($job, &$out)
    {
    	global $debug;
	
    	$rjob = basename($job);
	$job_id = $this->pjob_get_id($rjob);
	$status = $this->sx->ssh_exec(
	    $this->entry_point,
	    $this->password,
	    "(export EDG_WL_LOCATION=/opt/edg ; " .
	    " export PATH=/opt/globus/bin:/opt/edg/bin:\$PATH ;" .
	    "/opt/edg/bin/edg-job-get-output " .
	    "--dir $this->work_dir/$rjob/ " .
	    $job_id[1].")",
	    $out);
	if ($status != 0)
	    return FALSE;
	$status = $this->sx->ssh_copy(
	    "$this->entry_point:$this->work_dir/$session/$rjob/".$this->username."_*",
	    $job,
	    $this->password);
	return $status;
    }
}
?>

