<?

require_once("./grid.php");

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
 * You should also note that PHP doesn't cache information about non-existent files. 
 * So, if you call file_exists() on a file that doesn't exist, it will return FALSE 
 * until you create the file. If you create the file, it will return TRUE even if you 
 * then delete the file.
 */
clearstatcache();

//TO CHANGEEEEEEE!!!
if (file_exists($control_file)) 
{   
    //If we retrieve all the job outputs, we don't hace to connect again
    echo "<h1>A CURRAR</h1>";
    exit;
    //retrieve_grock_results
} else 
{   
    /**
     *  Grock: GRid dOCKing
     *
     */
     do_grock($gramm_dir, $jdl_dir, "pdb40.lst", $local_tmp_path, $session_path, $session_id);
}

echo "<h1>EGEE</h1>";

function do_grock($gramm_dir, $jdl_dir, $database, $local_tmp_path, $session_path, $session_id)
{   	
    global $db_dir;
    
    	/**
	 * We check if the PDB list file is available
	 */
    	$fp = fopen("$db_dir/$database", "r");
    	if (!$fp)
    	{
    	    letal("DB", "Cannot read DB list file $database");
    	}
	
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
	   
	    /**
	     * We use the Grid class to establish a connection 
	     * with the remote user interface, and then enter the Grid
	     */
            $grid = new Grid;
            $grid->connect();
	    $grid->new_session($session_id);
	    $grid->initialize(); 
	    
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
	    install_grid_files($gramm_dir, $jdl_dir, $session_path, $aux);
	    
	    /*
	     * We send the grid job here
	     */
	    $grid->job_submit($local_tmp_path, $session_id, $aux);

	    //$grid->destroy();   
            $grid->disconnect();
	
	} while( !feof( $fp ) );
    	
	/*
	 * We close the PDB list
	 */
    	rewind ( $fp  );
    	fclose( $fp );
}

function grock_results()
{
        $grid = new Grid;
        $grid->connect();
	//$grid->job_results();
        $grid->disconnect();
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
    	echo "Ligand file is valid, and was successfully uploaded.\n";
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

function install_grid_files($gramm_dir, $jdl_dir, $session_path, $aux)
{
    $local_job_directory="$session_path/$aux";	
    /*
     * LOCAL working directory. The Grid class copies the whole directoy
     * to the REMOTE machine, the USER INTERFACE.
     */
    exec("mkdir -p $local_job_directory");
    chdir("$local_job_directory");
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
    exec("cp -r $gramm_dir/* $local_job_directory/");
    exec("cp -r $jdl_dir $local_job_directory");
    
    /*
     * We generate the gramm-go.tar.gz 
     * gramm-go.tar.gz is the INPUT for the GRID
     */
     echo "<h1>tar -cf $local_job_directory/gramm-go.tar $local_job_directory/*.dat</h1><br>";
     exec("tar --remove-files -cf gramm-go.tar *.dat *.gr gramm");
     exec("gzip -f gramm-go.tar");
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
