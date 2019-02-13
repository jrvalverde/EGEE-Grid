<?

require_once("./grid.php");
require_once("./functions.php");

// SECURITY??? TO DO...

/**
 * Our session_id. Is the same for the LOCAL and the REMOTE machines.
 */  
$session_id=random_number();
$session_path="$local_tmp_path/$session_id";

/**
 * GRAMM docking resolution
 */
$resolution=$_POST["resolution"];

/**
 * GRAMM docking type
 */
$representation=$_POST["representation"];

/**
 * Generate the local working directory and enter it.
 */
generate_sandbox($session_path);
 
/**
 * Ligand upload validation
 *
 * Checks the ligand file upload. This function can be debugged.
 */ 
upload_user_data($session_path);
$ligand=basename($_FILES['upload']['name'][0]);  

/**
 * GROCK: GRid dOCKing
 * VARIABLES COMMENTS!!! VARIABLES COMMENTS!!! VARIABLES COMMENTS!!!
 */ 
do_grock($app_dir, $local_tmp_path, $session_path, $session_id, $resolution, $representation, $grid_server, $grid_password, $grid_passphrase, $UI_grid_path);

/**
 * The signal to notice the job is done is the "jobs_sended.txt" file
 */
$handle=fopen("$session_path/jobs_sended.txt", "w");
fclose($handle);

/**
 * the jobs_sended.txt file, needs to have the proper permissions
 */
chmod("$session_path/jobs_sended.txt", 0644);

echo "<center><h1>Please, click <a href=\"http://anarchy.cnb.uam.es/cgi-src/Grid/GROCK/script/results.php?id=$session_id&ligand=$ligand\">HERE</a> to see the GROCK results page</h1><center>";


?>
