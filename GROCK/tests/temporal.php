<?

require_once("./grid.php");


// SECURITY??? TO DO...

/**
 * Our session_id. Is the same for the LOCAL and the REMOTE machines.
 */  
$session_id=random_number();
$session_path="$local_tmp_path/$session_id";

/**
 * We generate the local working directory
 */
local_directory($session_path);
 
/**
 * Ligand upload validation
 *
 * Checks the ligand file upload. This function can be debugged.
 */    
upload_user_data($session_path);


/**
 *  GROCK: GRid dOCKing
 */ 
do_grock($app_dir, $gramm_dir, $jdl_dir, "pdb40.lst", $local_tmp_path, $session_path, $session_id);

/**
 * The signal to notice the job is done is the "jobs_sent.txt" file
 */
$handle=fopen("$session_path/jobs_sent.txt", "w");
fclose($handle);

echo "<center><h1>Please, click <a href=\"http://bakunin.cnb.uam.es/cgi-src/Grid/GROCK/script/results.php?id=$session_id\">HERE</a> to see the GROCK results page</h1><center>";

function do_grock($app_dir, $gramm_dir, $jdl_dir, $database, $local_tmp_path, $session_path, $session_id)
{   	
    global $db_dir;
    	/**
	 * We check if the PDB list file is available
	 */
    	$fp = fopen("$db_dir/$database", "r");
    	if (!$fp)
    	{
    	    letal("Receptors list", "Cannot read the PDB list file.");
    	}
	
	
	/**
	 * We use the Grid class to establish a connection 
	 * with the remote user interface, and then enter the Grid
	 */
        $grid = new Grid;
        $grid->connect($app_dir);
	$grid->new_session($session_id);
	$grid->initialize();
	
	/*
	 * We start a loop to send the gramm jobs over the grid.
	 * There is a job for each ligand-receptor pair.
	 */
	do{ 
	    /*
	     * We read the PDB list file.
	     */
    	    $line= fgets( $fp );
	    if ($line=="")
	    {
	     	break;
	    }
	    
	    /*
	     * Comments here!!!
	     */
	    $aux=substr("$line", 5, 6);
	    /* 
	     * Some protein names have the last character empty
	     */
	    $aux=trim($aux);
    	    $chain=substr("$aux", 5, 1);
	    
	    /**
	     * To send a GRAMM job over the grid we need:
	     * - A JDL("Job Description Language") file
	     * - The GRAMM program itself!!!
	     */
	    install_grid_files($app_dir, $gramm_dir, $jdl_dir, $session_path, $aux);
	    
	    /*
	     * We send the grid job here
	     */
	    $grid->job_submit($local_tmp_path, $session_id, $aux);

	    
	
	} while( !feof( $fp ) );
    	
	//$grid->destroy();   
        $grid->disconnect();
	
	/**
	 * We close the PDB list
	 */
    	rewind ( $fp  );
    	fclose( $fp );
	
	}

function random_number()
{
    /**
     * Random values generation
     * srand -- Seed the random number generator
     * rand -- Generate a random integer
     */
     srand((double)microtime()*10000);
     $random_value = rand();
     return $random_value;
}

function local_directory($session_path)
{
    mkdir("$session_path");	
}

function upload_user_data($session_path)
{

    /**
     * In PHP versions earlier than 4.1.0, $HTTP_POST_FILES should be used instead
     * of $_FILES.
     */ 
    $uploaddir = "$session_path/";
    //$uploadfile = $uploaddir . basename($_FILES['upload']['name'][0]);
    $uploadfile = $uploaddir . "ligand.pdb";

    /*
     * The uploaded file will we moved later by the "install_grid_files" function
     * to a directory inside the session directory, named with the receptor file.
     */
    if (move_uploaded_file($_FILES['upload']['tmp_name'][0], $uploadfile)) 
    {
    	echo "<center>Ligand file is valid, and was successfully uploaded.</center>\n";
    } else 
    {
    	letal("File Upload", "Cannot upload ligand file");
    }

    /**
     * Upload information
     *
     */
    
    	//echo 'Here is some more debugging info:';
    	//print_r($_FILES);

}

function install_grid_files($app_dir, $gramm_dir, $jdl_dir, $session_path, $aux)
{
    $local_job_directory="$session_path/$aux";	
    /*
     * LOCAL working directory. The Grid class copies the whole directoy
     * to the REMOTE machine, the USER INTERFACE.
     */
    exec("mkdir -p $local_job_directory");
    /*
     * We move the ligand file, uploaded by the user with the GROCK form
     */
    exec("cp $session_path/ligand.pdb $local_job_directory/");
    
    /*
     * Now we extract the receptor file from the PDB database.
     */
     receptor_file($session_path, $local_job_directory, $aux);
     
    /*
     * GRAMM program and JDL file
     */ 
    exec("cp -r $gramm_dir/gramm-go.tar.gz $local_job_directory/gramm-go.tar.gz");
    exec("cp -r $gramm_dir/gramm.sh $local_job_directory/gramm.sh");
    exec("cp -r $jdl_dir $local_job_directory");
    
}


function receptor_file($session_path, $local_job_directory, $aux)
{
      // PDB Database
      $local_pdb_path="/data/gen/pdb";
      
      // Path to convpdb.pl script
      $mmtsb_tools="/opt/structure/mmtsb_tools/perl/";
      
      /**
       * We extract the last character of the pdb file name
       */
      $chain=substr($aux, 5, 6);
      if ($chain=="")
    	     { 
	    	$entry="pdb".strtolower( substr("$aux", 0, 4) ).".ent";
	    	exec("echo $aux >> $session_path/pdb_list.txt");
		exec("cp $local_pdb_path/$entry $local_job_directory/receptor.pdb");
    	     } else
    	     { 
	    	$mmtsb_tools="/opt/structure/mmtsb_tools/perl/";
	    	$entry="pdb".strtolower( substr("$aux", 0, 4) ).".ent";
	        exec("echo $aux >> $session_path/pdb_list.txt");
		exec("$mmtsb_tools/convpdb.pl -chain $chain $local_pdb_path/$entry > $local_job_directory/$entry");
		exec("cp $local_pdb_path/$entry $local_job_directory/receptor.pdb");
    	     }
}

?>
