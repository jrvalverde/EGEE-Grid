#!/usr/bin/php
<?
// add our install dir to the include path
$path=dirname($argv[0]);
set_include_path($path . PATH_SEPARATOR . get_include_path());

require_once('config.php');
require_once('yami_lib.php');

$debug=TRUE;
$debug_grid=TRUE;
$debug_ssh=TRUE;

// we are intended to be run in the background
//  all our output will be to a special file
$output = fopen('yami_output', 'w+');
if (! $output)
    yami_letal("YAMI", "Can not open output file");

yami();

fclose($output);

// That's all, folks!

function yami()
{
    global $debug;
    global $output;
    global $app_dir;
    
    // work on the current directory: it will become the session
    // directory by default
    $session_id = basename(getcwd());
    $auth = array();
     
    if ($debug) {
    	fwrite($output, "\nsession is $session_id\n");
	fwrite($output, "called from ". basename(getcwd()) . "\n");
   }
    
    // submit job
    //yami_submit($session_id, $options, $auth);
    
    yami_submit($session_id, $auth);
    /*
    //for ($i = 0; $i < 960; $i++) {
    //TO CHANGE???
    for ($i = 0; $i < 430; $i++) {
    	//sleep(3600); 	// 3600: wait 1 hour before checking results again
	sleep(600);
	if ($debug) fwrite($output, "Checking job output availability\n");
    	$done = yami_get_output($session_id, $auth);

    	// generate modeller result file(s)
    	//dock_generate_scores($options['docker']); TO CHANGE
	
	if ($done)
	    break;
	yami_set_status('partial');
    }
    yami_set_status('finished');
   */
    exit;
}


?>