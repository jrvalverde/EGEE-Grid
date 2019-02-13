<?
/**
 *	test routines
 *
 * To test on the command line, use
 *  ssh -x -T localhost "(cd `pwd` ; php grid_test.php)"
 */
require_once './grid_config.php';
require_once './ssh.php';
require_once './grid.php';

#$user="user";
#$host="host";
#$passwd="pwd";
#$passphrase="psp";

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
    
    echo "----------------------------------------------\n";
    echo "            Testing Grid::init()\n";
    echo "----------------------------------------------\n";

    $eg = new Grid;
    if ($eg == FALSE) {
    	echo "error: couldn't create grid object\n";
	return;
    }
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/debug");
    $eg->set_error_log("/tmp/grid/debug/connection.err");
    if (!$eg->initialize())
    	echo "error: couldn't init the grid\n";
    else
    	echo "OK\n";
    $eg->destroy();
    $eg->sx->destruct();    // shouldn't be needed. REVIEW SExec.
}

function test_job_submit()
{
    global $user, $host, $passwd, $passphrase;
    
    echo "----------------------------------------------\n";
    echo "         Testing Grid::job_submit()\n";
    echo "----------------------------------------------\n";

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/debug");
    $eg->set_error_log("/tmp/grid/debug/connection.err");
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
    $eg->sx->destruct();    // shouldn't be needed. REVIEW SExec.
}

function test_job_get_id()
{
    global $user, $host, $passwd, $passphrase;
    
    echo "----------------------------------------------\n";
    echo "          Testing Grid::job_get_id()\n";
    echo "----------------------------------------------\n";

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/debug");
    $eg->set_error_log("/tmp/grid/debug/connection.err");
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
    $eg->sx->destruct();    // shouldn't be needed. REVIEW SExec.
}

function test_job_status()
{
    global $user, $host, $passwd, $passphrase;
    
    echo "----------------------------------------------\n";
    echo "         Testing Grid::job_status()\n";
    echo "----------------------------------------------\n";

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/debug");
    $eg->set_error_log("/tmp/grid/debug/connection.err");
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
    $eg->sx->destruct();    // shouldn't be needed. REVIEW SExec.
}

function test_job_get_output()
{
    global $user, $host, $passwd, $passphrase;
    
    echo "----------------------------------------------\n";
    echo "         Testing Grid::job_get_output()\n";
    echo "----------------------------------------------\n";

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/debug");
    $eg->set_error_log("/tmp/grid/debug/connection.err");
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
    echo "\nWaiting 20s for job\n";
    sleep(20);
    echo "\nGetting job output... ";
    if (! $eg->job_get_output("tst-job", $out))
    	echo "error: couldn't get job output\n";
    else
    	echo "OK\n";
    print_r($out);
    $eg->sx->destruct();    // shouldn't be needed. REVIEW SExec.
}

function test_sessions()
{
    global $user, $host, $passwd, $passphrase;
    
    echo "----------------------------------------------\n";
    echo "         Testing Grid::job_get_output()\n";
    echo "----------------------------------------------\n";

    $eg = new Grid;
    $eg->set_user($user);
    $eg->set_host($host);
    $eg->set_password($passwd);
    $eg->set_passphrase($passphrase);
    $eg->set_work_dir("/tmp/grid/debug");
    $eg->set_error_log("/tmp/grid/debug/connection.err");
    echo "initializing grid... ";
    if (!$eg->initialize())
    	echo "error: couldn't init the grid\n";
    else
    	echo "OK\n";
    echo "Submitting tst-job to default session...\n";
    $out = array("");
    if (! $eg->job_submit("tst-job", $out))
       echo "error: coudn't start the job\n";
    else 
       echo "OK\n";
    print_r($out);
    echo "cancelling job\n";
    if (! $eg->job_cancel("tst-job", $out)) 
    	echo "FAILED:\n";
    else 
    	echo "OK:\n";
    print_r($out);
    $eg->session_list_all();
    echo "Submitting tst-job to a new session...\n";
    $sess = $eg->new_session();
    echo "sess = "; print_r($sess); echo "\n";
    if ($sess != FALSE) {
	$out = array("");
	if (! $eg->job_submit("tst-job", $out, $sess))
	   echo "error: coudn't start the job\n";
	else {
	   echo "OK\n";
	    print_r($out);
    	    echo "\nGetting job ID... \n";
    	    print_r($eg->job_get_id("tst-job", $sess));
    	    echo "\nGetting job status... \n";
    	    print_r($eg->job_status("tst-job", $out, $sess));
    	    echo "\nWaiting 20s for job\n";
    	    sleep(20);
    	    echo "\nGetting job output... \n";
    	    if (! $eg->job_get_output("tst-job", $out, $sess))
    	    	echo "error: couldn't get job output\n";
    	    else
    	    	echo "OK\n";
    	    print_r($out);
	}
    	$eg->session_list_all();
    	$eg->session_destroy($sess);
	
    }
    $eg->session_list_all();
    $eg->destruct();    // shouldn't be needed. REVIEW
}

# create test job as follows:
#----------------------------
# rm -rf tst-job
# mkdir tst-job
# cat > tst-job/job.jdl<<END
# Type = "Job";
# JobType = "normal";
# VirtualOrganisation= "biomed";
# Executable = "/bin/hostname";
# StdOutput = "output.out";
# StdError = "error.err";
# OutputSandbox = {"error.err","output.out"};
# END

$debug_grid = TRUE;
$debug_sexec= TRUE;

echo "<html><body>\n";
echo "<PRE>\n";

if ($debug_grid) {

    test_grid_init();	    	// OK
    #test_job_submit();	    	// OK
    #test_job_get_id();	    	// OK
    #test_job_status();	    	// OK
    #test_job_get_output();	    	// OK
    #test_sessions();
}

echo "</PRE>\n";
echo "</body></html>\n";
?>
