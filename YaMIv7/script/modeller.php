<?
require_once('util.php');

/**
 *  Create a Modeller job
 *  MORE HERE
 *
 *  @return boolean TRUE is all went well, FALSE otherwise.
 *
 */

function abc()
{
	return TRUE;
}

function modeller_create_job($sequence, $aligment, $topfile) 
{
    global $debug;
    global $app_dir;	    // where exec and config files are located
    global $output;
    
    //Validations, FILES???
    
    return TRUE;

    /*
    if (strcmp($options['docker'], 'ftdock') != 0) {
    	log_error( "create 3D-dock job", "method selected is not 3D-dock");
    	return FALSE;
    }
    if ($debug) fwrite($output, "<h3>Creating a FTDOCK job for $probe+$target</h3>\n");
    */
    
    // To launch a job to the grid it must be fully self-contained
    // within its own subdirectory.
    // Create job directory
    // We use $target as the name to identify the job.
    /*
    $job_directory='./'.$target;
    if (is_dir($job_directory)) {
    	// it already exists from a previous run: ignore
	log_message("$job_directory already exists: ignoring\n");
 	return TRUE;
    }
    */
}
?>