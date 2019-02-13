<?
/**
 *	test routines
 *
 * To test on the command line, use
 *  ssh -x -T localhost "(cd `pwd` ; php grid_test.php)"
 */
require_once './grid_config.php';
require_once '../../php-ssh/ssh.php';
require_once './grid.php';

$debug=TRUE;

#$user="user";
#$host="host";
#$passwd="pwd";
#$passphrase="psp";

/**
 * test grid::pconnect()/pdisconnect()
 *
 * Grid::connect() opens a connection to a Grid UI server (a server that
 * allows one to submit and monitor jobs in the grid). It is much like
 * 'rsh' allowing one to send commands to the UI node.
 *
 + This routine tests Grid::connect() by opening a connection to a user
 * interface node, executing a simple command (ls -l) on it (on the
 * UI node itself) and disconnecting.
 *
 */
function test_grid_pconnect()
{
    global $user, $host, $passwd, $passphrase;
    
    $eg = new Grid;
    $eg->set_user($user);	// does nothing
    $eg->set_host($host);	// does nothing
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/test");
    $eg->set_error_log("/tmp/grid/test/connection.err");
    $eg->pconnect();
    fwrite($eg->std_in, "ls -l\nlogout\n");
    fflush($eg->std_in);
    while (!feof($eg->std_out)) {
	$line = fgets($eg->std_out, 1024);
	if (strlen($line) == 0) break;
	echo $line."<br />\n";
    }
    $ret = $eg->pdisconnect();
    print_r($ret);
    echo "\n<br />\n<hr><br />\n";
}

/**
 * test Grid::pinitialize()/pdestroy()
 *
 * Grid::pinitialize() allows us to identify ourselves to the Grid.
 * Identification must be made from a UI node, hence we need to connect()
 * first to a UI node.
 *
 * In later versions, pinitialize() detects if we are already connected
 * and if not, opens the connection implicitly for us. Both scenarios
 * should be checked.
 */
function test_grid_pinit()
{
    global $user, $host, $passwd, $passphrase;

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/test");
    $eg->set_error_log("/tmp/grid/test/connection.err");
// try both, with an existing connection and without
//	$eg->pconnect();
//	if not connected, initialize() will open an implicit connection
    $eg->pinitialize();
    $eg->pdestroy();
    $eg->pdisconnect();
}

/**
 * test Grid::initialize()/destroy()
 *
 * Grid::initialize() allows us to identify ourselves to the Grid.
 * Identification must be made from a UI node, hence we need to connect
 * first to a UI node.
 *
 */
function test_grid_init()
{
    global $user, $host, $passwd, $passphrase;

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/test/cless");
    $eg->set_error_log("/tmp/grid/test/cless/connection.err");
    if (!$eg->initialize())
    	echo "error: couldn't init the grid\n";
    else
    	echo "OK";
    $eg->destroy();
}

function test_pjob_submit()
{
    global $user, $host, $passwd, $passphrase;

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/test");
    $eg->set_error_log("/tmp/grid/test/connection.err");
    $eg->pconnect();
    $eg->pinitialize();
    $eg->pjob_submit("tst-job");
    $eg->pdisconnect();
}

function test_job_submit()
{
    global $user, $host, $passwd, $passphrase;

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/test/cless");
    $eg->set_error_log("/tmp/grid/test/cless/connection.err");
    echo "initializing grid... ";
    if (!$eg->initialize())
    	echo "error: couldn't init the grid\n";
    else
    	echo "OK\n";
    echo "Submitting tst-job... ";
    if (! $eg->job_submit("tst-job", $out))
    	echo "error: coudn't start the job\n";
    else 
    	echo "OK\n";
    print_r($out);
    #$eg->destroy();
}

function test_pjob_get_id()
{
    global $user, $host, $passwd, $passphrase;

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/test");
    $eg->set_error_log("/tmp/grid/test/connection.err");
    $eg->pconnect();
    $eg->pinitialize();
    $eg->pjob_submit("tst-job");
    print_r( $eg->pjob_get_id("tst-job") );
    $eg->pdisconnect();
}

function test_job_get_id()
{
    global $user, $host, $passwd, $passphrase;

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/test/cless");
    $eg->set_error_log("/tmp/grid/test/cless/connection.err");
    echo "initializing grid... ";
    if (!$eg->initialize())
    	echo "error: couldn't init the grid\n";
    else
    	echo "OK\n";
    echo "Submitting tst-job... ";
    if (! $eg->job_submit("tst-job", $out))
    	echo "error: coudn't start the job\n";
    else 
    	echo "OK\n";
    print_r($out);
    print_r($eg->job_get_id("tst-job"));
}

function test_pjob_status()
{
    global $user, $host, $passwd, $passphrase;

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/test");
    $eg->set_error_log("/tmp/grid/test/connection.err");
    $eg->pconnect();
    $eg->pinitialize();
    $eg->pjob_submit("tst-job");
    print_r($eg->pjob_status("tst-job"));
    $eg->pdisconnect();
}

function test_job_status()
{
    global $user, $host, $passwd, $passphrase;

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/test/cless");
    $eg->set_error_log("/tmp/grid/test/cless/connection.err");
    echo "initializing grid... ";
    if (!$eg->initialize())
    	echo "error: couldn't init the grid\n";
    else
    	echo "OK\n";
    echo "Submitting tst-job... ";
    if (! $eg->job_submit("tst-job", $out))
    	echo "error: coudn't start the job\n";
    else 
    	echo "OK\n";
    print_r($out);
    echo "\nGetting job ID... \n";
    print_r($eg->job_get_id("tst-job"));
    echo "\nGetting job status... \n";
    print_r($eg->job_status("tst-job", $out));
}

function test_pjob_output()
{
    global $user, $host, $passwd, $passphrase;

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/test");
    $eg->set_error_log("/tmp/grid/test/connection.err");
    $eg->pconnect();
    $eg->pinitialize();
    $eg->pjob_submit("tst-job");
    print_r($eg->pjob_status("tst-job"));
    sleep(5);	// give time to run
    $eg->pjob_output("tst-job");
    $eg->pdisconnect();
}

function test_job_output()
{
    global $user, $host, $passwd, $passphrase;

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/test/cless");
    $eg->set_error_log("/tmp/grid/test/cless/connection.err");
    echo "initializing grid... ";
    if (!$eg->initialize())
    	echo "error: couldn't init the grid\n";
    else
    	echo "OK\n";
    echo "Submitting tst-job... ";
    if (! $eg->job_submit("tst-job", $out))
       echo "error: coudn't start the job\n";
    else 
       echo "OK\n";
    print_r($out);
    echo "\nGetting job ID... \n";
    print_r($eg->job_get_id("tst-job"));
    echo "\nGetting job status... \n";
    print_r($eg->job_status("tst-job", $out));
    echo "\nGetting job output... ";
    if (! $eg->job_output("tst-job", $out))
    	echo "error: couldn't get job output\n";
    else
    	echo "OK\n";
    print_r($out);
}


$debug = TRUE;
$debug_persistent = FALSE;

#rm -rf tst-job
#mkdir tst-job
#cat > tst-job/job.jdl<<END
#Type = "Job";
#JobType = "normal";
#VirtualOrganisation= "biomed";
#Executable = "/bin/hostname";
#StdOutput = "salida.out";
#StdError = "error.err";
#OutputSandbox = {"error.err","salida.out"};
#END

echo "<html><body>\n";
echo "<PRE>\n";

if ($debug_persistent) {
    #test_grid_pconnect();	// OK
    #test_grid_pinit();   	// OK
    #test_pjob_submit();     	// OK
    #test_pjob_get_id();     	// OK
    #test_pjob_status();     	// OK
    test_pjob_output();     	// OK
} else {
    test_grid_init();	    	// OK
    #test_job_submit();	    	// OK
    #test_job_get_id();	    	// OK
    #test_job_status();	    	// OK
    #test_job_output();	    	// OK
}

echo "</PRE>";
echo "</body></html>";
?>
