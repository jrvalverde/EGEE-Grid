<?
/**
 *	test routines
 *
 */
require_once './grid_config.php';
require_once '../../php-ssh/ssh.php';
require_once './grid.php';

$debug=TRUE;

$user="user";
$host="host.example.com";
$passwd="password";
$passphrase="passphrase";

function test_job_submit()
{
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

echo "<PRE>\n";

test_job_submit();

echo "</PRE>";

?>
