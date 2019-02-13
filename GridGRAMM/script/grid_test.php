<?
/**
 *	test routines
 *
 */
require_once 'grid.php';

$debug=TRUE;

/**
 * test grid::connect()/disconnect()
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
function test_grid_connect()
{
	$eg = new Grid;
	$eg->set_user("user");	// does nothing
	$eg->set_host("grid-ui-host");	// does nothing
	$eg->set_password("password");
	$eg->set_passphrase("XXXXXXpassphraseXXXXXX");
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
 * test Grid::initialize()
 *
 * Grid::initialize() allows us to identify ourselves to the Grid.
 * Identification must be made from a UI node, hence we need to connect
 * first to a UI node.
 *
 * In later versions, initialize() detects if we are already connected
 * and if not, opens the connection implicitly for us. Both scenarios
 * should be checked.
 */
function test_grid_init()
{
	$eg = new Grid;
	$eg->set_user("user");
	$eg->set_host("grid-ui-host");
	$eg->set_password("password");
	$eg->set_passphrase("XXXXXXpassphraseXXXXXX");
	$eg->set_work_dir("/tmp/grid/test");
	$eg->set_error_log("/tmp/grid/test/connection.err");
// try both, with an existing connection and without
//	$eg->pconnect();
//	if not connected, initialize() will open an implicit connection
	$eg->pinitialize();
	$eg->pdestroy();
	$eg->pdisconnect();
}

test_grid_connect();
//test_grid_init();


?>
