<?php
/**
 * Submit jobs to the Grid through a UI-node
 *
 * @author José R. Valverde <jrvalverde@acm.org>
 * @version 2.6
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
 * @license	../doc/c/lgpl.txt
 * @version	CVS: $Id: grid.php,v 1.4 2005/10/06 09:35:59 netadmin Exp $
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @see		ssh.php
 * @see     	../test/grid_test.php
 * @since	File available since Release 1.0
 */

/** definition of default values */
include_once("grid_config.php");
/** SExec class */
require_once("ssh.php");
/** utility routines */
include_once("util.php");

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
 *  <b>PERSISTENT vs. DISCONNECTED MODE</b>
 *
 *  As of release 2.0, this distinction is no longer meaningful. We now
 * use a new SExec class that multiplexes disconnected commands over a
 * single sahred channel, thus providing all the advantages of both,
 * connected and disconnected modes. We now provide a single set of
 * methods.
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
 *  Further to it, this has been designed to make it easy to deploy
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
 *  *session_new() routines to ensure all your jobs will be kept isolated
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
 * @version 2.1
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
    /*
     * Notes: preparing for 3.0
     *
     *	Change entry_point, etc... to array of alternate UI nodes.
     *	These should be provided by the user on the Web form.
     *
     *	Load balance among user interfaces (start with one and round-robin)
     *
     *	Detect connection failures to a UI and remove it from the list of
     *	active UI (put it on a list of inactive UI just in case we have to
     *  resort to retrying).
     *
     *	Recover failed jobs:
     *	Difficult -- we need some way to detect them (perhaps spawn a monitor
     *  process) or be notified of their status, and restart as needed. Most
     *  probably we will need to spawn a monitor subprocess, and have this
     *  poll a listing of jobs on the grid and check their status... If so,
     *  then we might as well cache results for finished jobs locally...
     *
     *	Mmmmph...
     */

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
     * @var array
     */
    var $sessions;
    /**
     *  The secure transfer communications line to use
     *
     * @access private
     * @var SExec
     */
    var $sx;
    /**
     *	Submit timeout
     *	XXX JR XXX HACK
     *	This variable used for a hack (?) to ensure job submission does
     * not hang. Currently job submission using edg-job-submit may hang
     * occassinally. This is rare, maybe one out of 4-5 thousand jobs,
     * but for such large datasets it becomes an issue.
     *
     *	My impression is that it hangs on copying the job to the remote
     * resource broker. This may happen if the RB becomes unavaliable:
     * by TCP semantics the line remains open until connectivity is
     * restored, but if this takes too long, or if some error conditions
     * are met your connection may stall.
     *
     *	This is very difficult to spot unless it is done at a very low
     * level in the copy connection protocol. Higher up, one can not know
     * if the copy takes too long because of connection problems or if it
     * simply is a huge dataset or a very slow connection.
     *
     *	For the same reason, edg-job-submit has no way to know if the
     * connection is stalled unless it has some way to measure the amount
     * of data transferred (which is done lower in the protocol stack). So
     * from edg-job-submit point of view, most probably it is not a bug,
     * but a feature: it aims for a most optimistic situation and a most
     * safe transfer, i.e. keep trying to use the connection as long as it
     * is not broken, and try to ensure the data gets fully transferred
     * in the presence of trouble for as long as the connection may be
     * recovered, and do not make any assumption about the size of the
     * dataset or the speed of the line.
     *
     *	Now, for an automated submission system with deadline (proxy
     * validity) this is a potential disaster: I have witnessed job submissions
     * hang for as long as a couple of days (and then I killed them manually).
     *
     *  So, what can we do?
     *
     *	If you know the bounds of your job sizes (exec + datasets) then you
     * may have an estimate of how long you want to allow a transfer to 
     * take, and kill any submission taking longer. Then with appropriate
     * code the submission may be retaken (in my experience, hang submissions
     * usually succeed after bein manually killed and retried) or the job
     * resubmitted at a later time if you have recovery code on your app.
     *
     *	This variable holds the maximum time in seconds you guesstimate a 
     * job submission should be allowed to take. If this timeout is 
     * exceeded the submission will be unpiteously killed. Since this is
     * a rare condition you may be generous.
     *
     * @access private
     * @var integer
     */
    var $submit_timeout;
    
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
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::Grid()\n";

    	$this->entry_point = $GLOBALS['grid_server'];
	$this->username    = $GLOBALS['grid_user'];
	$this->hostname    = $GLOBALS['grid_host'];
	$this->password    = $GLOBALS['grid_password'];
	$this->passphrase  = $GLOBALS['grid_passphrase'];
	$this->work_dir    = $GLOBALS['grid_wd_path'];
	$this->error_log   = $GLOBALS['grid_error_log'];
	$this->connected   = FALSE;
	$this->sessions['default'] = 'default';
	return $this;
    }
    
    function destruct() {
    	global $debug_grid;
	
    	if ($debug_grid) echo "Grid::destruct()\n";
	if ($debug_grid) print_r($this);
    	// remove remote files (should we?)
	// $this->destroy_all_sessions();
    	// free sessions array
    	// unset($this->sessions);
	// remove local files if any (should we?)
	
    	// with a shared connection we now must ensure some
	// cleanup is done...
	// THIS SHOULD BE AUTOMATIC ON CLASS SExec! REVIEW
	$this->sx->destruct();
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
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::set_user($user)\n";

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
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::set_host($host)\n";

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
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::set_password($pass)\n";

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
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::set_passphrase($pass)\n";

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
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::set_work_dir($wd)\n";

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
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::set_error_log($errlog)\n";

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
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::get_connection_status()\n";

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
     * $eg->initialize();
     * if ($eg->get_init_status() == FALSE)
     *     echo "Couldn't initialize the Grid!\n";
     * </code>
     *
     * @return bool TRUE if the grid has been initialized, FALSE
     *	    	    otherwise.
     */
    function get_init_status()
    {
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::get_init_status()\n";

    	return $this->initialized;
    }


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
     *  username (i.e. all jobs will be run under said username).
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

    function connect()
    {	
 	global $debug_grid;
	
	if ($debug_grid) echo "\nGrid::connect()\n";
	
	// Using the new SExec class (Rel2) instantiating an SExec
	// object creates a master channel to the remote end point
	// The master channel will be hitchhicked by all other
	// SExec commands.
	
	$this->sx = new SExec($this->entry_point, $this->password);
	
	if ($this->sx == FALSE)
	    // couldn't stablish connection with the remote end
	    return FALSE;
	$this->connected = TRUE;
	return TRUE;
    }


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
     *	Grid opening time is specified in hours+minutes. If the number of
     *	minutes is negative, the specified minutes are substracted from
     *	the specified hours (e.g: 1, -15 is fifteen minutes to one hour,
     *	i.e. 45 minutes). If the total time specified is negative then
     *	the default of 12:00 is used.
     *
     *	Grid::initialize() enables the grid for a specified amount of time
     * (by default 12:00h). This means that during the validity period, 
     * the user on the Grid-UI host may access the grid, in the same or
     * different logins. The validity period SURVIVES after we close 
     * all communications with the remote grid entry point for as long
     * as we have specified (so our jobs may continue running).
     *
     *	If Grid access was already available (by a previous call to 
     * Grid::initialize()) when we issue the call, then it is reused
     * and extended to acommodate the newly requested validity period.
     * In other words, the Grid access is shared among all logins
     * during its lifetime.
     *
     *	This also means that if a valid certificate has been issued and
     * not expired yet (another call to Grid::initialize() is still
     * valid), then we may submit jobs to the Grid without any need
     * to call Grid::initialize() ourselves.
     *
     *	E.g., say you have a web-based service that runs a long job
     * and you want to have the grid enabled 12h (the default). You
     * just call Grid::initialize() and then submit the job.
     *
     *	Now say that before it expires, someone logs in on your account 
     * but shouldn't have access to the Grid (i.e. they don't know
     * the Grid-activation passphrase). Since the grid is already
     * activated, they CAN submit jobs on your behalf even if they
     * should not.
     *
     *	Therefore, <b>DO NOT SHARE YOUR ACCOUNT ON THE GRID-UI
     * WITH ANYBODY</b>. Protect it as dearly as your Grid certificate.
     *
     *	It also means that debugging may be somewhat convoluted, as
     * a call to Grid::initialize() may fail and jobs could still
     * be accepted if another call from some other process is still
     * valid. While debugging, it is better if you review the 
     * command output and make sure it shows how the call fared.
     *
     *	So, what happens if we destroy a session? See Grid::destroy()
     * for more details.
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
     *     $eg->connect();
     *     if (!$eg->initialize())
     *     	echo "error: couldn't init the grid\n";
     *     else
     *     	echo "OK\n";
     *     $eg->destroy();
     *     $eg->disconnect();
     * </code>
     *
     *	@note the output of the grid initialization command will go to
     *	      <b>our</b> standard output (i.e. the web server)!
     *
     *  @bug: XXX JR XXX grid-proxy-init will use the time from the last
     *        issued command. To avoid setting a time shorter than one
     *	      already existing we should first issue a grid-proxy-info,
     *	      check if there is a running proxy, its time left, see if
     *        it is longer than what we want to set and if it is, then
     *	      do NOTHING to avoid setting it shorter.
     *
     *	@param	integer	    Estimated duration in hours of the session
     *
     *	@param	integer     Estimated duration in minutes of the session
     *
     *	@return bool TRUE on success, FALSE otherwise
     */
     
    function initialize($hours=12, $minutes=0)
    {
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::initialize($hours, $minutes)\n";
	
	// connect if not connected
 	if ($this->connected == FALSE)
	    if ($this->connect() == FALSE)
		return FALSE;
	
	// validate and compute activation time to use
    	$total_minutes = ($hours * 60) + ($minutes);
	if ($total_minutes < 0) {
	    //use default
	    $hours = 12;
	    $minutes = 0;
	} else {
	    $hours = floor($total_minutes / 60);
	    $minutes = $total_minutes % 60;
	}
	
	$out = "";
	// Create remote working dir
	$status = $this->sx->ssh_exec(
	    "mkdir -p $this->work_dir/default",
	    $out);
	if ($status != 0) {
	    // something went airy
	    if ($debug_grid) {
	    	echo "--> mkdir -p $this->work_dir/default\n";
	    	echo "--> exit: $status\n";
	    	echo "--> output:\n".$out . "\n\n";
	    }
	    return FALSE;
	}
	$this->sessions[] = 'default';	    // enter on sessions list
	
	// NOTE: XXX JR XXX
	//  Using popen here has one major drawback: the command output
	//  will go directly to the web page!
	//  We should consider instead getting stdin/stdout handles or
	//  dismissing remote command output.
    	$rin = $this->sx->ssh_popen(
	    ". /etc/bashrc ; " .
	    "/opt/globus/bin/grid-proxy-init -pwstdin -valid $hours:$minutes", 
# XXX JR XXX test this	    "/opt/globus/bin/grid-proxy-init -pwstdin -valid $hours:$minutes 2>&1/dev/null", 
	    "w"
	    );
	sleep(1);   // leave some time for the prompt (and preceding flush)
	if ($debug_grid) echo "Remote Input = $rin\n";
	fputs($rin, $this->passphrase."\n"); fflush($rin);
	$status = $this->sx->ssh_pclose($rin);
	
	if ($status == 0) {
	    return $this->initialized = TRUE;
	} else {
	    if ($debug_grid) echo "PCLOSE STATUS = $status\n";
	    return $this->initialized = FALSE;
	}
	
    }

    /**
     *	Destroy remote grid identity
     *
     *	Destroy the certification we initialized so that no more jobs
     *	can be launched under our identity.
     *
     *	This may be called even if we haven't called 'Grid::initialize()'
     * because there may exits a previous activation that is still 
     * valid and we want to destroy it.
     *
     *	To make things clear:
     *
     *	Grid::initialize() "opens" the "door" to the Grid for <i>the user</i>
     * during a given time. New calls from the same user in this or any
     * other login session, from this or any other site, while the "door" 
     * is open, share the same "door" and simply extend its validity
     * period.
     *
     *	Grid::destroy() "closes" the currently open door. If the door
     * was being shared by more login sessions, it is closed for <i>all</i>
     * of them, not just the caller, and hence nor the caller, nor
     * <i>any process</i> under the same user will be able to use the
     * grid any longer unless Grid::initialize() is called again to
     * open the door again (issue a new certificate).
     *
     *	In other words, you don't close <i>a</i> Grid "door", you close 
     * <i>the</i> Grid "door", and if it casually is being shared with other 
     * work sessions, then ALL of them will be destroyed (meaning that 
     * other active work sessions will fail).
     *
     *	Thus: be careful when using this method. Be <b>very careful</b>.
     *
     *	Sessions should be initiated using a validity length that you
     * guesstimate will be comfortably enough for running all your work
     * and left to expire by themselves.
     *
     *	Grid::destroy() should only be called when you are sure that
     * you don't want <i>any</i> work on the Grid to be accepted on
     * your behalf, neither from this not other work sessions.
     *
     *	So, in general, it is better to make good estimations of the
     * time needed by your jobs and specify it to Grid::initialize()
     * and not use Grid::destroy() unless there are good reasons.
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
     *	@return bool TRUE on success, FALSE on failure
     */
    function destroy()
    {
    	global $debug_grid;
	
    	if ($debug_grid) echo "Grid::destroy()\n";
	
	if (! $this->connected)
    	    if ($this->connect() == FALSE)
		return FALSE;

    	$out = "";
    	$ret = $this->sx->ssh_exec(
	    ". /etc/bashrc ; grid-proxy-destroy",
	    $out);
	if ($ret == 0)
	    return TRUE;    // OK
	else {
	    if ($debug_grid) {
	    	echo "--> exit: $ret\n";
	    	echo "--> output:\n".$out . "\n\n";
	    }
	    return FALSE;
	}
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
     * created on the remote grid access point on your behalf. The routine
     * will return a unique session identifier which you later use to 
     * tag jobs that are to be run within that session's sandbox.
     *
     * Sample usage:
     * <code>
     *	$eg = new Grid;
     *	$eg->set_user($user);
     *	$eg->set_host($host);
     *	$eg->set_password($passwd);
     *	$eg->set_passphrase($passphrase);
     *	$eg->set_work_dir("/tmp/grid/debug");
     *	$eg->set_error_log("/tmp/grid/debug/connection.err");
     *	echo "initializing grid... ";
     *	if (!$eg->initialize()) {
     *	    echo "error: couldn't init the grid\n";
     *	    exit;
     *  }
     *	else
     *	    echo "OK\n";
     *	echo "Submitting tst-job to default session...\n";
     *	$out = array("");
     *	if (! $eg->job_submit("tst-job", $out)) {
     *	    echo "error: coudn't start the job\n";
     *	    exit;
     *	}
     *	else 
     *	   echo "OK\n";
     *	print_r($out);
     *	echo "Submitting tst-job to a new session...\n";
     *	$sess = $eg->session_new();
     *	echo "sess = "; print_r($sess); echo "\n";
     *	if ($sess != FALSE) {
     *	    $out = array("");
     *	    if (! $eg->job_submit("tst-job", $out, $sess))
     *	       echo "error: coudn't start the job\n";
     *	    else {
     *	       echo "OK\n";
     *	    }
     *	    $eg->session_destroy($sess);
     *	}
     *	$eg->destruct();
     * </code>
     *
     * @param string $hint an optional string to be used for the session name
     *
     * @return string|false session ID of the newly generated session.
     */
    function session_new($hint="sess")
    {
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::session_new()\n";
	
	// we need a valid connection, no need for initialization.
	if (! $this->connected)
	    if (! $this->connect())
	    	return FALSE;
	
	$out=array("");
	$status = $this->sx->ssh_exec( 
            "mktemp -d $this->work_dir/$hint.XXXXXX",
            $out);
    	if ($status == 0) {
	    if ($debug_grid) print_r($out);
	    // find out the generated session directory name
	    $sess = basename($out[1]);
    	    //if we got no suggested session name
	    if (strcmp($hint, 'sess') == 0)
	    	// use the generated one
	    	$hint = $sess;
	    // keep a mapping of session names to directories
	    $this->sessions[$hint] = $sess;
	    return $hint;
	} else {
	    if ($debug_grid) print_r($out);
	    return FALSE;
	}

/* Alternate implementation:	
    	list($usec, $sec) = explode(' ', microtime());
    	$seed = (float) $sec + ((float) $usec * 100000);
    	srand($seed);
    	$rand1 = rand(); $rand2 = rand();
	$session = "session-$rand1-$rand2";
	if ($debug_grid) echo "--> creating session $session\n";
	if ($debug_grid) echo "--> mkdir -p ".$this->work_dir."/".$session."\n";
	$out = array("");
	$status = $this->sx->ssh_exec( 
	    "mkdir -p ".$this->work_dir."/".$session,
	    $out);
	if ($status == 0) {
	    if ($debug_grid) print_r($out);
	    $this->sessions[] = $session;
	    return $session;
	} else {
	    if ($debug_grid) print_r($out);
	    return FALSE;
	}
*/
    }
    
    /**
     *  Define an already existing session
     *
     *  This is useful if the session already exists and
     * we want to access it: we already know its
     * directory name, and just want to associate a
     * new name with the existing directory name.
     *
     *  Use e.g. when you are to submit a job from
     * a WWW page and access the results from a different
     * one: as the new page has no access to the status of
     * the previous one, we need to rebuild it ourselves.
     *
     *  @param string session name
     *  @param string subdirectory of $work_dir to be associated to that name
     */
    function session_define($session, $directory)
    {
	$this->sessions[$session] = $directory;
    }

    /**
     * check if supplied argument is a valid (existing and active) 
     * session.
     *
     * @param string a session name
     * @return bool TRUE if the session could be found among the list of
     *	    	    valid sessions, FALSE otherwise
     *
     * @access private
     */
    function session_is_valid($session='default')
    {
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::is_valid_session($session)\n";
    	foreach ($this->sessions as $i => $value)
	    if (strcmp($i, $session) == 0)
	    	return TRUE;
	return FALSE;
    }

    /**
     * return the directory associated to a session
     *
     *	DOES NO ERROR CHECKING FOR VALID SESSION
     *
     *	@param string a session name
     *	@return string the associated directory name
     *
     *  @access private
     */
    function session_directory($session='default')
    {
    	global $debug_grid;
	return $this->sessions[$session];
    }

    /**
     * list all existing sessions
     * (debugging only)
     *
     * @access private
     */
    function session_list_all()
    {
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::list_sessions()\n";
     	print_r($this->sessions);
    	foreach ($this->sessions as $i => $value)
	    echo "Session $i is stored on $value\n";
    }
    
    /**
     * destroy the specified session
     *
     *	This method destroys all data associated with the specified
     * session. Currently it does not kill its associated jobs, but
     * deleted all their underlying data nevertheless.
     *
     * Warning: passing an empty string or no argument will destroy
     * the default session.
     *
     * return bool TRUE if success, FALSE otherwise
     */
    function session_destroy($session='default')
    {
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::session_destroy($session)\n";
	
	if (! $this->session_is_valid($session))
	    return FALSE;
	
	// we need at least a valid remote connection
	if (! $this->connected)
	    if (! $this->connect())
	    	return FAIL;
	
    	// XXX JR XXX we should loop first over every job and kill it.
	// delete all data for this session
	$sess_dir = $this->sessions[$session];
    	$status = $this->sx->ssh_exec(
	    "rm -rf $this->work_dir/$sess_dir",
	    $out);
	if ($status == 0) {
	    unset($this->sessions[$session]);
	    return TRUE;
	}
	else
	    return FALSE;
    }
    
    /**
     *	Destroy all existing sessions
     *
     *	@access private
     */
    function session_destroy_all()
    {
    	global $debug_grid;
	
	if ($debug_grid) echo "Grid::destroy_all_sessions()\n";
	
	// we need at least a valid remote connection
	if (! $this->connected)
	    if (! $this->connect())
	    	return FAIL;
	
	if ($debug_grid) print_r($this->sessions);
	foreach ($this->sessions as $i => $value) {
    	    // XXX JR XXX we should loop first over every job and kill it.
	    // delete all data for this session
    	    $status = $this->sx->ssh_exec(
	    	"rm -rf $this->work_dir/$value",
	    	$out);
	    if ($status == 0)
	    	unset($this->sessions[$i]);
	    else
	    	return FALSE;
    	}
	return TRUE;
    }
    
    /**
     *	Set maximum (guesstimated) allowed time for a job submission to
     * succeed.
     *
     *	This value is application and dataset dependent, will be of
     * relevance in rare occasions (1/4000) and hence may as well be
     * generous.
     *
     *	The default is 0 seconds (no timeout). You should make measures
     * to ensure it is reasonable. If set to 0, no timeout will be used.
     * 
     *	Unless set to 0, you will need to code some resubmission policy
     * in your application.
     *
     *	@param integer $seconds timeout in seconds for job submission
     *	    (defaults to 0, no timeout).
     */
    function job_submit_set_timeout($seconds=0)
    {
    	$this->submit_timeout=$seconds;
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
     *	@param array  	A variable to hold any messages spitted by the submission
     *	    	    	procedure. Messages will be stored as an array of
     *	    	    	strings (one per line) without ending newlines.
     *	@param	string Optional name of the session to which this job belongs
     *	    	    	(obtained from a previous call to session_new).
     *  @return bool TRUE if success, FALSE otherwise
     */
    function job_submit($job, &$out, $session='default')
    {
    	global $debug_grid;
	
	if ($debug_grid) {
	    echo "Grid::job_submit($job, $out, $session)\n";
	    echo "Copying job $job to remote end ".
	    	"$this->entry_point:$this->work_dir/$session\n";
	}
	
	// check this first as it is cheaper
	if (! $this->session_is_valid($session))
	    return FALSE;
	$sd = $this->sessions[$session];
	
	// we need an initialized grid
	if (! $this->initialized)
	    if (! $this->initialize())
	    	return FALSE;
	
	// We should check for $job validity (i.e., $job/job.jdl exists)
	
    	// Copy job details over to remote end
	$status = $this->sx->ssh_copy_to(
	    $job, "$this->work_dir/$sd", $out);
	if ($status == FALSE) {
	    // copy failed
	    if ($debug_grid) {
	    	echo "--> copy: failed\n";
		print_r($out);
	    }
	    return FALSE;
	}
	// XXX JR XXX
	// Remove any existing 'identifier.txt' file
	//  This is 'cos otherwise, if we are re-invoked to re-submit
	// the job, the new ID will be appended and job_get_id will
	// return the old, failed one.
	//  Ideally we should have a job_resubmit() routine that takes
	// care of it AND keeps track of the number of resubmissions...
	$this->sx->ssh_exec("rm -f $this->work_dir/$sd/$job/identifier.txt",
	    $out);
	unset($out);	// we ignore output and exit code!
	
	if ($debug_grid)
	    echo "Submitting with a timeout of $this->submit_timeout:\n\t"
	    . (($this->submit_timeout != 0)? 
	         "(sleep 10; pkill -P \$\$ ) & " :
		 " ")
	    . "/opt/edg/bin/edg-job-submit"
	    . "  -o $this->work_dir/$sd/$job/identifier.txt"
	    . "  $this->work_dir/$sd/$job/job.jdl"
	    . "  | tee $this->work_dir/$sd/$job/submit.txt\n";

    	// NOTE: with direct execution we don't go through the login
	// process, hence needed environment variables must be set.
    	// XXX JR XXX
	//  	There is an alternate solution: use the remote
	//  	command "sh --login -c $command"
	//  	This tells sh to behave as a login shell, and read
	//  	commands from the string $command.
	$status = $this->sx->ssh_exec(
	    "("
	    . ". /etc/bashrc ;"
	    . "export EDG_WL_LOCATION=/opt/edg ;"
	    . "export EDG_WL_LIBRARY_PATH=/opt/globus/lib:/opt/edg/lib ;"
	    . "cd $this->work_dir/$sd/$job; "
	    . (($this->submit_timeout != 0) ?
	         "(sleep $this->submit_timeout; pkill -P \$\$ ) & " :
		 " ")
    	    . "/opt/edg/bin/edg-job-submit"
	    . "  -o $this->work_dir/$sd/$job/identifier.txt"
	    . "  $this->work_dir/$sd/$job/job.jdl"
	    . "  | tee $this->work_dir/$sd/$job/submit.txt"
	    . ")",
	    $out);
	if ($debug_grid) echo "$session::$job ($sd/$job) submit status was $status\n";
	if ($status == 0) {
	    // at this point we may as well copy identifier.txt and submit.txt
	    // to the local directory and have them cached here.
	    $this->sx->ssh_copy_from("$this->work_dir/$sd/$job/submit.txt",
	    	$job, $out);
	    // note that identifier.txt may not exist (it SHOULD though!)
	    $this->sx->ssh_copy_from("$this->work_dir/$sd/$job/identifier.txt",
	    	$job, $out);
	    return TRUE;
	}
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
     *	@param  string Optionally identifies the session to which the job
     *	    	    	belongs (if it was submitted within one).
     *
     *	@return array|false with the known job-id's submitted from this
     *	    	'job'. That looks as an oxymoron: there should only be
     *	    	one. Ergo, you may use it to detect job name clashes or
     *	    	job re-submissions.
     *
     *	@note this is nasty and should be enhanced on a future version.
     */
    function job_get_id($job, $session='default')
    {
    	global $debug_grid;
	$debug_grid = TRUE;
	
    	if ($debug_grid) echo "Grid::job_get_id($job, $session)\n";
	    
	// check this first as it is cheaper
	if (! $this->session_is_valid($session))
	{	
	    return FALSE;
	}
	    
	$sd = $this->sessions[$session];
	
    	// if we have been called successfully previously, then
	//  we have a cached copy of the 'identifier.txt' file locally.
	//  Use it instead and save bandwidth.
	// identifier.txt is of the form
	//  	###Submitted Job Ids###
    	//  	https://rb1.egee.fr.cgg.com:9000/entEU8ZO4LlP6ZMVcpryog
	if (file_exists("$job/identifier.txt"))
	    return preg_grep("/^https:/", file("$job/identifier.txt"));
	
	// identifier.txt is not cached locally, grab it from remote entry point
	// as it should have been created during submission
	// we just need a valid connection
	if (! $this->connected)
	    if (! $this->connect())
	    {	
	    	   return FALSE;
	    }
    	// we may have received a long pathname, but only care about
	// its last component remotely
	$remote_dir = basename($job);
	
	/* 
	    	$tatus is all the times != 0
		CHANGE THIS
		
    	// check if the remote file exists
	$status = $this->sx->ssh_exec(
	    "test -f $this->work_dir/$sd/$remote_dir/identifier.txt 2>&1", $out);
	    
	if ($status != 0) {
	    // destination does not exists or is not a file
	    if ($debug_grid) echo "*** $sd/$job/identifier.txt does not exist\n";
	    warning("Get job ID: remote grid job ID does not exist");
    	    return FALSE;
    	}
	*/
	
	$this->sx->ssh_exec("test -f $this->work_dir/$sd/$remote_dir/identifier.txt 2>&1", $out);
	
    	$status = $this->sx->ssh_copy_from("$this->work_dir/$sd/$remote_dir/identifier.txt", $job, $out);
	    
    	if ($status == FALSE) {
	    warning("Get job ID: could not retrieve grid job ID");
	    return FALSE;
	}
	else {
	    //$id = preg_grep("/^https:/", file("$job/identifier.txt");
	    // return $id;
	    return preg_grep("/^https:/", file("$job/identifier.txt"));
	}
	// NOTE: this returns an array of \n terminated strings, which is
	// highly inconvenient. REVIEW
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
     *	@param	string 	Output of the status request program. Useful for
     *	    	    	debugging.
     *	@param  string Optionally identifies the session to which the job
     *	    	    	belongs (if it was submitted within one).
     *
     *	@return array|false an array containing the job status or FALSE on
     *	    	    	failure.
     */
    function job_status($job, &$out, $session='default')
    {
    	global $debug_grid;
	$debug_grid = TRUE;
	
	if ($debug_grid) echo "Grid::job_status($job, $out, $session)\n";
	
	// check this first as it is cheaper
	if (! $this->session_is_valid($session))
	{
	    return FALSE;
	}
	$sd = $this->sessions[$session];
	    
	// we need an initialized grid
	if (! $this->initialized)
	    if (! $this->initialize())
	    {
    	    	 return FALSE;
	    }
	
    	$rjob = basename($job);
    	$job_id = $this->job_get_id($rjob, $session);
	
	if ($job_id == FALSE)
    	    return FALSE;
	
	if ($debug_grid)
	    echo "Sending\n\t" .
	    	"(export EDG_WL_LOCATION=/opt/edg ; " .
		"/opt/edg/bin/edg-job-status ". $job_id .")";
	/*	
    	$status = $this->sx->ssh_exec(
	    "(" .
    	    ". /etc/bashrc ; " .
	    "export EDG_WL_LOCATION=/opt/edg ; " .
	    "/opt/edg/bin/edg-job-status ". $job_id[1] . 
	    //"/opt/edg/bin/edg-job-status ". $job_id .
	    ")",
	    $out);
	    
	if ($status != 0)
    	    return FALSE;
	else
	    return $out;
	*/
	
	$this->sx->ssh_exec(
	    "(" .
    	    ". /etc/bashrc ; " .
	    "export EDG_WL_LOCATION=/opt/edg ; " .
	    "/opt/edg/bin/edg-job-status ". $job_id[1] . 
	    ")",
	    $out);
	return $out;
	
	/* 
	    NOTE: Format of the output array:
    	    [0]
    	    [1]
    	    [2]*************************************************************
    	    [3]BOOKKEEPING INFORMATION:
    	    [4]
    	    [5]Status info for the Job : https://rb1.egee.fr.cgg.com:9000/entEU8ZO4LlP6ZMVcpryog
    	    [6]Current Status:     Scheduled 
    	    [7]Status Reason:      Job successfully submitted to Globus
    	    [8]Destination:        ce00.inta.es:2119/jobmanager-lcgpbs-biomed
    	    [9]reached on:         Mon Aug 29 12:37:05 2005
    	    [10]*************************************************************
    	    [11]
    	*/
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
     *	All results will be stored remotely on the job directory, under a
     * subdirectory with a unique name of the form $grid_user_name_XXXXX...
     * where the X's mean a random string. Locally you will see them as
     *	$job/job_output so that they have an easy name to identify them.
     *
     *	To access your job output, just open this $job/output directory
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
     *     if (! $eg->job_get_output("tst-job", $out))
     *     	echo "error: couldn't get job output\n";
     *     else
     *     	echo "OK\n";
     *     print_r($out);
     * </code>
     *
     *	@param	string 	The name of the job you submitted to the grid
     *	@param  string Optionally identifies the session to which the job
     *	    	    	belongs (if it was submitted within one). If this
     *	    	    	is not specified, then the default session is used.
     *	@return bool TRUE on success, FALSE on failure.
     */
    function job_get_output($job, &$out, $session='default')
    {
    	global $debug_grid;
    	
    	if ($debug_grid) echo "Grid::job_get_output($job, $out, $session)\n";
	
	// check this first as it is cheaper
	if (! $this->session_is_valid($session))
	    return FALSE;
	$sd = $this->sessions[$session];
	    
	// we need an initialized grid
	if (! $this->initialized)
	    if (! $this->initialize())
	    	return FALSE;
	
    	$rjob = basename($job);
	$job_id = $this->job_get_id($rjob, $session);
	
	if ($debug_grid) print_r($job_id);

    	// check if output has already been retrieved from the grid
	$status = $this->sx->ssh_exec(
	    "test -d ".$this->work_dir."/$sd/$rjob/".$this->username."_* 2>&1", $out);
	unset($out);
	if ($status != 0) {
	    // results have not been recovered yet, retrieve them
	    $status = $this->sx->ssh_exec(
		    "(" .
		    " . /etc/bashrc ; " .
	    	    "export EDG_WL_LOCATION=/opt/edg ; " .
	    	    "export PATH=/opt/globus/bin:/opt/edg/bin:\$PATH ;" .
	    	    "/opt/edg/bin/edg-job-get-output " .
	    	    "--dir ".$this->work_dir."/$sd/$rjob ".
		    $job_id[1]. 
		    //$job_id
		    ")",
	    	    $out);
	    if ($debug_grid) {
		print_r($out);
	    }
	    if ($status != 0)
		return FALSE;
    	}

    	// copy over to local 'output' directory
	//  We want to provide a well known name
	$status = $this->sx->ssh_copy_from(
	    "$this->work_dir/$sd/$rjob/".
	      $this->username."_*",
	    "$job/job_output", $out);
	return $status;

/* Alternate implementation for jobs submitted many times	
    	$all_done = TRUE;
	foreach ($job_id as $i => $id) {
	    if ($debug_grid) echo "--> retrieving job #$i ($id)\n";
	    $status = $this->sx->ssh_exec(
	    	"(export EDG_WL_LOCATION=/opt/edg ; " .
	    	" export PATH=/opt/globus/bin:/opt/edg/bin:\$PATH ;" .
	    	"/opt/edg/bin/edg-job-get-output " .
	    	"--dir $this->work_dir/$session/$rjob/ $id)",
	    	$out);
	    if ($status == 0)
	    	$status = $this->sx->ssh_copy_from(
	    	    "'$this->work_dir/$this->session/$session/$rjob/".
		      $this->username."_*'",
	    	    $job);
		if ($status = FALSE)
		    $all_done = FALSE;
	    else
	    	$all_done = FALSE;
	}
	return $all_done;
*/
    }
    
    /**
     *	Cancel a job previously submitted to the grid
     *
     * Note: If the job has not reached the CE yet (i.e.: its status is WAITING 
     * or READY states), the cancellation request may be ignored, and the job 
     * may continue running, although a message of successful cancellation is 
     * returned to the user. In such cases, just cancel the job again when its 
     * status is SCHEDULED or RUNNING
     *
     *	@param	string 	The name of the job you submitted to the grid
     *	@param  string Optionally identifies the session to which the job
     *	    	    	belongs (if it was submitted within one). If this
     *	    	    	is not specified, then the default session is used.
     *	@return bool TRUE on success, FALSE on failure.
     */
    function job_cancel($job, &$out, $session='default')
    {
    	global $debug_grid;

    	if ($debug_grid) echo "Grid::job_cancel($job, $out, $session)\n";
	
	// check this first as it is cheaper
	if (! $this->session_is_valid($session))
	    return FALSE;
	$sd = $this->sessions[$session];
	    
	// we need an initialized grid
	if (! $this->initialized)
	    if (! $this->initialize())
	    	return FALSE;
	
    	$rjob = basename($job);
	$job_id = $this->job_get_id($rjob, $session);
	$out = array("");
	return $this->sx->ssh_exec(
	    "(".
	    ". /etc/bashrc ;" .
	    "export EDG_WL_LOCATION=/opt/edg ; " .
	    "export PATH=/opt/globus/bin:/opt/edg/bin:\$PATH ;" .
	    "yes | /opt/edg/bin/edg-job-cancel ".
	    $job_id[1].
	    ")",
    	    $out);
    }
}
?>
