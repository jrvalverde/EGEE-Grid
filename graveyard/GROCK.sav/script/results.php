<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >
	<title>GROCK Results</title>
</head>
<body>
<?
require_once("./grid.php");
require_once("./functions.php");

/**
 * We retrieve the session identifier and the ligand name
 */ 
$session_id=$_GET["id"];
$session_ligand=$_GET["ligand"];

/**
 * Working directories
 */
$local_wd = "$local_tmp_path/$session_id";
$remote_wd="$UI_grid_path/$session_id";

/**
 * Files to control the script execution
 */
$process_control = "$local_wd/jobs_sended.txt";
$output_control = "$local_wd/jobs_output.txt";

/**
 * The session_id is mandatory to show the results.
 */
if ($session_id=="") 
{
    letal("GROCK results", "You need a session_id to see the results!!!");  
}

/**
 * file_exists -- Checks whether a file or directory exists
 * the results of "file_exists" are cached
 * clearstatcache -- Clears file status cache
 */
clearstatcache(); 

if (!file_exists($process_control)) 
{  
    letal("GROCK results", "GROCK results are not ready, or may be you have a invalid session identifier.");  
}

if (file_exists($output_control)) 
{
    echo "<center>";
    echo "<h1>GROCK RESULTS</h1><br>";
    echo "<p>View the file with the <a href=\"$httptmp/$session_id/score.txt\" target=\"_blank\">SCORES</a></p>";
    echo "</center>";
    
    /**
     * COMMENTS
     */
    grock_results($local_wd);
    
}  else 
{   
   /**
    * We connect to the GRID to check each job sended. If the job is finished
    * we copy the output to the receptor local directory.
    */
   echo "<h1>Retrieving jobs output.</h1><br>";
   // REMEMBER... TO CHANGE!!!
   get_grid_results($local_wd, $remote_wd, $session_id, $app_dir, $grid_server, $grid_password, $grid_passphrase, $UI_grid_path);
   
   /**
    * We check now in the local directories if ALL the jobs output are availabale,
    * i.e., ALL the pair receptor-ligand jobs.
    */
   $check= check_results($local_wd);

   if ($check==TRUE)
   {
    	echo "<h1>end $check es igual a TRUE</h1><br>";
	echo "<h1>PROCESO FINALIZADO<br>";
    	/**
    	 * The signal to notice all jobs are done is the "jobs_sended.txt" file
         */
    	$handle=fopen("$output_control", "w");
    	fclose($handle);
	
   } else
   {	
    	/**
         * For debugging
	 */
    	echo "<h3>end $check es igual a FALSE</h3><br>";
   }
   
   /**
    * With the get-results.sh script score.txt is generated.
    */
    chdir("$local_wd");
    exec("ls | xargs -i -t /data/www/EMBnet/cgi-src/Grid/GROCK/script/get-results.sh {} | sort -r -n -k 2 > ./score.txt");
    echo "ls | xargs -i -t /data/www/EMBnet/cgi-src/Grid/GROCK/script/get-results.sh {} | sort -r -n -k 2 > ./score.txt";
    // TO CHANGE???? THIS COMMAND RUNS IN BACKGROUND!!!
    echo "<center>";
    echo "<h3>Temporal GROCK results. All the job results are not available</h3><br>";
    echo "View the file with the <a href=\"$httptmp/$session_id/score.txt\" target=\"_blank\">SCORES</a>";
    echo "<h1><a href=\"./results.php?id=$session_id&ligand=$session_ligand\">RELOAD</a></h1>";
    echo "</center>";
}


?>
</body>
</html>
