<?
require_once("./config.php");
require_once("./grid.php");

/**
 * ######## processor.php. functions ########
 */
 
function do_grock($app_dir, $local_tmp_path, $session_path, $session_id, $resolution, $representation, $grid_server, $grid_password, $grid_passphrase, $UI_grid_path)
{   	

	/**
	 * We check if the PDB list file is available
	 */
    	$fp = fopen("$app_dir/pdb_nr/pdb40.lst", "r");
    	if (!$fp)
    	{
    	    letal("Receptors list", "Cannot read the PDB list file.");
    	}
	
	/**
	 * COMMENTS
	 */
	//$cmd = "/opt/globus/bin/grid-proxy-init -valid 72:00 -pwstdin";
        //remote_cmd($cmd, $grid_password, $grid_passphrase);
	activate_grid_proxy($grid_server, $grid_password, $grid_passphrase);
	  
	/*
	 * Start a loop to send the gramm jobs over the grid.
	 * There is a job for each ligand-receptor pair.
	 */
	$i = 0;
	while (!feof($fp)) 
	{ 
	    /*
	     * Read the PDB list file.
	     */
    	    $line = fgets( $fp );
	    $i++;
	    echo $i."\t:[".$line . "]<br>\n";
	    if ($line=="")
	    {
	     	continue;
	    }
	    
	    /*
	     * Process line by line the file with the receptors list, to get the receptors file.
	     * Some receptor/protein names have the last character empty
	     */
	    $aux = substr("$line", 5, 6);
	    $aux = trim($aux);
    	    $chain = substr("$aux", 5, 1);
	    
	    /**
	     * To send a GRAMM job over the grid we need:
	     * - A JDL("Job Description Language") file
	     * - The GRAMM program itself!!!
	     */
	    create_gramm_job($app_dir, $session_path, $aux, $resolution, $representation);
	}
    	
	/**
	 * the pdb_list.txt file, generated by the receptor_file function, called by create_gramm_job
	 * needs to have the proper permissions
	 */
	//chmod("$session_path/pdb_list.txt", 0644);
	
	/**
	 * LCG tools installation in the remote machine - UI.
	 */
	$local_dir = "$app_dir/script/lcg-tools";
	$remote_dir="/home/david/lcg-tools";
	install_lcg_tools($local_dir, $remote_dir, $grid_server, $grid_password);	
    
	/**
	 * Copy the session directory with all the job directories to the remote machine, the UI.
	 * Then, we execute the LCG submitter tool to send all session jobs.
	 */
    	$local_dir = "$local_tmp_path/$session_id";
	$remote_dir="$UI_grid_path/$session_id";
	send_session_jobs($local_dir, $remote_dir, $grid_server, $grid_password);
	
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

function generate_sandbox($session_path)
{
    mkdir("$session_path");
    chdir("$session_path");	
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

function activate_grid_proxy($grid_server, $grid_password, $grid_passphrase)
{
    // TO CHANGE
    /**
     *
     */
    $pos = strpos($grid_server, "@");
    $ru = substr($grid_server, 0, $pos);
    $rh = substr($grid_server, $pos+1, strlen($grid_server));
    
    $password = $grid_password;
    $psp = $grid_passphrase;

    echo "<pre>\n";

    $debug_sexec = TRUE;
    $debug_grid = TRUE;

    $eg = new Grid;
    if ($eg == FALSE) {
     	echo "Cannot get a new Grid!\n";
    	exit;
    }
    $eg->set_host($rh);
    $eg->set_user($ru);
    $eg->set_password($password);
    $eg->set_passphrase($psp);
    //$eg->set_work_dir("./tmp");
    $eg->set_error_log("/data/www/EMBnet/tmp/grid/test/error-output.txt");
    $eg->connect();
    $eg->initialize();
    echo $eg->get_init_status();
    $eg->destroy();
    $eg->destruct();
    echo "</pre>\n";
}

function install_lcg_tools($local_dir, $remote_dir, $grid_server, $grid_password)
{
    $remote = $grid_server;
    $password = $grid_password;

    $rmt = new SExec($remote, $password);
    echo "<pre>";
    $rmt->ssh_copy_to($local_dir, $remote_dir, $out);
    echo "</pre>";
    //echo $out;
    
    $rmt->destruct();
}

function send_session_jobs($local_dir, $remote_dir, $grid_server, $grid_password)
{
    $remote = $grid_server;
    $password = $grid_password;
    echo "<pre>";
    $rmt = new SExec($remote, $password);
    
    /**
     * Copy the whole session directory with all the job files required
     */
    $rmt->ssh_copy_to($local_dir, $remote_dir, $out);
    //echo $out;
    
    /**
     * There are two files that we don't need in the remote directory, ligand.pdb and 
     */
    $cmd = "rm -f $remote_dir/ligand.pdb $remote_dir/pdb_list.txt";
    $rmt->ssh_passthru("$cmd", $status=0);
    
    /**
     * The submiter tool throw all jobs.
     */
    //TO CHANGE!!! SEE "INSTALL LCG-TOOLS.
    $cmd = "/usr/bin/perl /home/david/lcg-tools/lcg-submitter-biomed-beta11.pl -session $remote_dir";
    $rmt->ssh_passthru("$cmd", $status=0);
    //echo $status;
    
    echo "</pre>";
    $rmt->destruct();
}

function create_gramm_job($app_dir, $session_path, $aux, $resolution, $representation)
{
    $local_job_directory="$session_path/$aux";	
    /*
     * LOCAL working directory. The Grid class copies the whole directoy
     * to the REMOTE machine, the USER INTERFACE.
     */
    exec("mkdir -p $local_job_directory");
    //chmod($local_job_directory, 0644);
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
     * GRAMM program files. The rpar.gr is generated under the user instructions in the submit form.
     */     
    write_rpar_gr($local_job_directory, $resolution, $representation);
    
    exec("cp $app_dir/script/gramm/rmol.gr $local_job_directory/rmol.gr");
    exec("cp $app_dir/script/gramm/wlist.gr $local_job_directory/wlist.gr");
    exec("cp $app_dir/script/gramm/gramm $local_job_directory/gramm");
    exec("cp -r $app_dir/script/gramm/*.dat $local_job_directory/");
    exec("cp $app_dir/script/gramm/gramm.sh $local_job_directory/gramm.sh");
    
    /**
     * We generate the gramm-go-tar.gz package
     */
    exec("tar -cf gramm-go.tar *.dat *.gr gramm");
    exec("gzip gramm-go.tar");
    exec("rm -f *dat *gr gramm");
    
    /**
     * JDL file, mandatory to send a job over the grid
     */
    exec("cp $app_dir/jdl/session.jdl $local_job_directory");
    
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

function write_rpar_gr($local_job_directory, $resolution, $representation)
{
    $rpar="./rpar.gr";
    $matching = "generic"; //$matching = "helix";
    
    if ($representation=="hydrophobic")
    {
        $projection="blackwhite";
    }else
    {
        $projection="gray";
    }

    $fp = fopen( "$rpar", "w" );

        if ( $resolution=="low")
        {
            fwrite( $fp, "Matching mode (generic/helix) ....................... mmode= $matching\n" );
            fwrite( $fp, "Grid step ............................................. eta= 6.8\n" );
            fwrite( $fp, "Repulsion (attraction is always -1) .................... ro= 6.5.\n" );
            fwrite( $fp, "Attraction double range (fraction of single range) ..... fr= 0.\n" );
            fwrite( $fp, "Potential range type (atom_radius, grid_step) ....... crang= grid_step\n" );
            fwrite( $fp, "Projection (blackwhite, gray) ................ ....... ccti= $projection\n" );
            fwrite( $fp, "Representation (all, hydrophobic) .................... crep= $representation\n" );
            fwrite( $fp, "Number of matches to output .......................... maxm= 1000\n" );
            fwrite( $fp, "Angle for rotations, deg (10,12,15,18,20,30, 0-no rot.)  ai= 20\n" );
        }else
        {
            fwrite( $fp, "Matching mode (generic/helix) ....................... mmode= $matching\n" );
            fwrite( $fp, "Grid step ............................................. eta= 1.7\n" );
            fwrite( $fp, "Repulsion (attraction is always -1) .................... ro= 30.\n" );
            fwrite( $fp, "Attraction double range (fraction of single range) ..... fr= 0.\n" );
            fwrite( $fp, "Potential range type (atom_radius, grid_step) ....... crang= atom_radius\n" );
            fwrite( $fp, "Projection (blackwhite, gray) ................ ....... ccti= $projection\n" );
            fwrite( $fp, "Representation (all, hydrophobic) .................... crep= $representation\n" );
            fwrite( $fp, "Number of matches to output .......................... maxm= 1000\n" );
            fwrite( $fp, "Angle for rotations, deg (10,12,15,18,20,30, 0-no rot.)  ai= 10\n" );
        }

    fclose($fp);
}


/**
 * ######## result.php functions ########
 */
 
function remote_session_jobs($remote_wd, $grid_server, $grid_password)
{
    $remote = $grid_server;
    $password = $grid_password;
    echo "<pre>";
    $rmt = new SExec($remote, $password);
    
    /**
     * To check the results a grid proxy is mandatory
     */
    $cmd = "/usr/bin/perl /home/david/lcg-tools/get_output.pl -session $remote_wd";
    
    $rmt->ssh_passthru("$cmd", $status=0);
    //echo $status;
    echo "</pre>";
    $rmt->destruct();
}

function get_session_jobs($local_wd, $remote_wd, $grid_server, $grid_password, $line)
{
    $remote = $grid_server;
    $password = $grid_password;
    echo "<pre>";
    $rmt = new SExec($remote, $password);
    
    /**
     * Copy the job output
     */
    $rmt->ssh_copy_from("$remote_wd/$line/OUTPUT/test_x.outputdir", "$local_wd/$line/OUTPUT/test_x.outputdir", $out);
    
    echo "</pre>";
    $rmt->destruct();
}

function get_grid_results($local_wd, $remote_wd, $session_id, $app_dir, $grid_server, $grid_password, $grid_passphrase, $UI_grid_path)
{   	    
	
	/**
	 * COMMENTS
	 */
	activate_grid_proxy($grid_server, $grid_password, $grid_passphrase);
	remote_session_jobs($remote_wd,  $grid_server, $grid_password);

	/**
	 * Copy the grid output to the local machine
	 */
    	$fp = fopen("$local_wd/pdb_list.txt", "r");
    	if (!$fp)
    	{
    	    letal("Receptors list", "Cannot read the receptors list file in the local machine.");
    	}
	
	$i = 0;
	while (!feof($fp)) 
	{ 
	    /*
	     * Read the PDB list file.
	     */
    	    $line = trim(fgets( $fp ));
	    $i++;
	    //echo $i."\t:["."$remote_wd/$line" . "]<br>\n";
	    if ($line=="")
	    {
	     	continue;
	    }
	    
	    /**
	     * We get the job output
	     */
	    //TO CHANGE
	    get_session_jobs($local_wd, $remote_wd, $grid_server, $grid_password, $line);
	}
	
	/**
	 * We close the receptors list
	 */
    	rewind ($fp );
    	fclose($fp);
}

function check_results($local_wd)
{ 
    if ($handle = opendir("$local_wd")) 
    {
    	 /**
	  * We read the local session directory. Inside are all jobs directories, with 
	  * the receptor name. 'check_results' searches inside each job directory if the
	  * results are available. If all the results are avaliable, returns TRUE.
	  * Otherwise, returns FALSE. 
	  */
	  
	 $check=TRUE;
	 
    	 while (false !== ($file = readdir($handle))) 
	 {  
	     	/**
	     	 * We only want to check the directories with the receptor name.
	     	 */
             	if ($file != "." && $file != ".." && is_dir("$local_wd/$file")) 
	     	{
		    echo "<h1>$local_wd/$file/OUTPUT</h1>";
		    chdir("$local_wd/$file/OUTPUT");
		    /**
		     * If at least one job is not done this functions returns FALSE
		     */
		    if (!file_exists("./test_x.outputdir")) 
    	    	    {
    	    	    	$check=FALSE;
    	     	    } 
	    	}
	 }	 
    }
    closedir($handle); 
    
    return $check;
}

function score_file($local_wd, $app_dir, $session_ligand)
{
    /**
     * The score.txt file is generated comparing all receptor-ligand.res files.
     * SEE 
     */
     
    chdir($local_wd);
    $fp_score = fopen("./score.txt", "w");
    //chmod("./score.txt", 0644);
    
    if (!$fp_score)
    {
    	   letal("Score file", "Cannot write the score file.");
    }
    
    fwrite($fp_score, "We show in this file the GROCK score results, using the\n"); 
    fwrite($fp_score, "$session_ligand ligand and multiple receptors [R]\n\r\r");
    
    fwrite($fp_score, "___________________________________________________________\n");
    fwrite($fp_score, "  No. Energy\tRotation\t   Translation\t       [R]\n");
    fwrite($fp_score, "       (-)\n");
    fwrite($fp_score, "___________________________________________________________\n");
    fwrite($fp_score, "[match]\n");
    
    fclose($fp_score);
    echo "ls | xargs -i -t $app_dir/script/get-results.sh {} | sort -r -n -k 2 >> score.txt";
    exec("ls | xargs -i -t $app_dir/script/get-results.sh {} | sort -r -n -k 2 >> ./score.txt"); 
}

function grock_results($local_wd)
{ 
 
  chdir("$local_wd");
  if ($handle = opendir(".")) 
  {
    echo "<center><table border=\"1\">";
    echo "<tr><td><b>Receptors</b></td></tr>";
    /* This is the correct way to loop over the directory. */
    while (false !== ($file = readdir($handle))) 
    {    
    	if ( "$file" !== "." && "$file" !== ".." && is_dir($file) )
	{            
	    $receptor="$local_wd/$file";
            echo "<tr><td><a href=\"browse-results.php?receptor=$receptor\" \">$file</a></td></tr>\n";
	}
	clearstatcache();
    }
    echo "</table></center>";
    closedir($handle);
 } 
    	
}

?>
