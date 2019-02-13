<?
$host="david@villon";

//Local directories
$local_pdb_path="/data/gen/pdb";
$local_tmp_directory="/data/www/EMBnet/tmp/grid/grock/new";

//User Interface directory
$UI_data_path="/tmp/grock/data/new";
$control_file = "$local_tmp_directory/control.txt";

echo "\nEmpezamos<br>";

clearstatcache();
/*
* You should also note that PHP doesn't cache information about non-existent files. 
* So, if you call file_exists() on a file that doesn't exist, it will return FALSE 
* until you create the file. If you create the file, it will return TRUE even if you 
* then delete the file.
*/
if (file_exists($control_file)) 
{   
    results($local_tmp_directory, $UI_data_path);
} else 
{   
    //Grock: GRid dOCKing
    //$pipes
    echo "\nLlamo a la funcion grock<br>";
    grock($local_pdb_path, $UI_data_path, $local_tmp_directory, $host);
    echo "\n<h1>process finished?</h1>";
    exec("touch $control_file");
}

function connect($UI_data_path)
{

    $error_log="/data/www/EMBnet/tmp/grid";

    $descriptorspec = array(
        	0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
        	1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        	2 => array("file", "$error_log/error-output.txt", "a") // stderr is a file to write to
	    	);

    $process = proc_open("ssh -x -t -t david@villon", $descriptorspec, $pipes);

    $debug = TRUE;
    if ($debug) {
    	echo "\n\n-----------------------------------------\n\n";    
    	echo date("dS of F h:i:s") . " [R] connect + grid-proxy-init\n";
    }
    
    fwrite($pipes[0], "grid-proxy-init -pwstdin\n");
    fflush($pipes[0]);
    //sleep(5);     # Probado. NO NECESARIO CON EL FFLUSH
    fwrite($pipes[0], "kndlaria\n");
    fflush($pipes[0]);
    //sleep(5);     # Probado. NO NECESARIO CON EL FFLUSH
    
    $conn_values = array($descriptorspec, $process, $pipes);
    return $conn_values;
}

//$pipes
function grock($local_pdb_path, $UI_data_path, $local_tmp_directory, $host)
{
    //TO CHANGE!!!
    $fp = fopen("./pdb40.lst", "r");
    
    if (!$fp)
    {
    	echo "\nPDB file unavailable";
    	exit;
    }
    
    do{ 
    	$line= fgets( $fp );
    	
	if ($line=="")
	{
	    break;
	}
	
    	$aux=substr("$line", 5, 6);
    	$chain=substr("$aux", 5, 1);
    	
	//Starts the UI connection
	//  We just want to run GridProxyInit
    	$conn_values=connect($UI_data_path); 

	$debug = TRUE;
    	if ($debug == TRUE) {
	    //echo "\nConn_values[0] (descriptorspec)\n";
	    //print_r($conn_values[0]);
	    //echo "\nConn_values[1] (process)\n";
    	    //print_r($conn_values[1]);
	    //echo "\nConn_values[2] (pipes)\n";
    	    //print_r($conn_values[2]);
	    //echo "\n";
    	}	
	
	$descriptorspec=$conn_values[0];
    	$process=$conn_values[1];
    	$pipes=$conn_values[2];
	//print_r($pipes);
	
    	if ($chain==" ")
    	{ 
    	    $directory=substr("$line", 5, 4);
	    $local_directory="$local_tmp_directory/$directory";
	    $UI_directory="$UI_data_path/$directory";
	    
	    if ($debug)
	    	echo date("dS of F h:i:s") . " [L] mkdir $local_directory\n";
	    exec("mkdir $local_directory");
    	    if ($debug) {
	    	echo date("dS of F h:i:s") . " [R] mkdir -p $UI_directory\n";
	    }
	    //fwrite($pipes[0], "pwd\nls -l\nmkdir -p $UI_directory\n");
	    //fflush($pipes[0]);
	    remote_mkdir($pipes, $UI_directory);
	    
    	    $entry="pdb".strtolower( substr("$line", 5, 4) ).".ent";
	    exec("echo $directory >> $local_tmp_directory/pdb_list.txt");
    	    if ($debug)
	    	echo date("dS of F h:i:s") . " [L] scp $local_pdb_path/$entry $host:$UI_directory/receptor.pdb\n";
	    exec("scp $local_pdb_path/$entry $host:$UI_directory/receptor.pdb");
	    
    	} else
    	{ 
    	    $directory=$aux;
	    $local_directory="$local_tmp_directory/$directory";
	    $UI_directory="$UI_data_path/$directory";
	    $mmtsb_tools="/opt/structure/mmtsb_tools/perl/";
	    
	    if ($debug) {
	    	echo  date("dS of F h:i:s") . " [L] mkdir $local_directory\n";
	    }
	    exec("mkdir $local_directory");
	    
	    remote_mkdir($pipes, $UI_directory);
	    
    	    $entry="pdb".strtolower( substr("$line", 5, 4) ).".ent";
    	    if ($debug)
	    	echo  date("dS of F h:i:s") . " [L] echo $directory >> $local_tmp_directory/pdb_list.txt\n";
	    exec("echo $directory >> $local_tmp_directory/pdb_list.txt");
    	    if ($debug)
	    	echo  date("dS of F h:i:s") . " [L] $mmtsb_tools/convpdb.pl -chain $chain $local_pdb_path/$entry > $local_directory/$entry\n";
    	    exec("$mmtsb_tools/convpdb.pl -chain $chain $local_pdb_path/$entry > $local_directory/$entry");
    	    if ($debug)
	    	echo  date("dS of F h:i:s") . " [L] scp $local_pdb_path/$entry $host:$UI_directory/receptor.pdb\n";
	    exec("scp $local_directory/$entry $host:$UI_directory/receptor.pdb"); 
    	}
        
    	install_gramm($local_directory, $UI_directory, $host);
	
	grid_files($UI_directory, $host);
	
    	send_grid_jobs($UI_directory, $pipes);
	
	//Ends the connection
	disconnect($descriptorspec, $process, $pipes);    
	//disconnect($conn_values);
		
    } while( !feof( $fp ) );

    rewind ( $fp  );
    fclose( $fp );
}

function remote_mkdir($pipes, $UI_directory)
{
    	    $debug = TRUE;
    	    if ($debug) {
	    	echo  date("dS of F h:i:s") . " [R] mkdir -p $UI_directory\n";
	    }
	    //fwrite($pipes[0], "ls\nmkdir -p $UI_directory\n");
	    //fflush($pipes[0]);
    	    echo passthru("ssh -x -t -t david@villon mkdir -p $UI_directory");
}

function disconnect($descriptorspec, $process, $pipes)
{      
    $debug = TRUE;
    // DON'T: this may step in the way of other simultaneous jobs
    //if ($debug)
    //	echo  date("dS of F h:i:s") . " [R] grid-proxy-destroy\n";
    //fwrite($pipes[0], "grid-proxy-destroy\n");
    //fflush($pipes[0]);
    
    if ($debug)
    	echo  date("dS of F h:i:s") . " [R] exit\n";
    fwrite($pipes[0], "exit\n");
    fflush($pipes[0]);
   
    fclose($pipes[0]);
	
	    while (!feof($pipes[1]))
	    {
		echo fgets($pipes[1], 1024)."<br />";
	    }
	
    	    fclose($pipes[1]);
	
    $return_value = proc_close($process);
	
    echo "\ncommand returned $return_value\n";	

}

function install_gramm($local_directory, $UI_directory, $host)
{
    $debug = TRUE;
    
    if ($debug)
    	echo  date("dS of F h:i:s") . " [L] scp ../aspirin.pdb $host:$UI_directory/ligand.pdb\n";
    //This will be changed, the ligand part!!!
    exec("scp ../aspirin.pdb $host:$UI_directory/ligand.pdb");

    if ($debug)
    	echo  date("dS of F h:i:s") . " [L] generating gramm-go.tgz\n";
    exec("tar -cf  $local_directory/gramm-go.tar *.gr gramm/*");
    // if the file exists, this would lock for user input
    exec("gzip -f $local_directory/gramm-go.tar");
    if ($debug)
    	echo  date("dS of F h:i:s") . " [L] scp $local_directory/gramm-go.tar.gz $host:$UI_directory/gramm-go.tar.gz\n";
    exec("scp $local_directory/gramm-go.tar.gz $host:$UI_directory/gramm-go.tar.gz");
}

function grid_files($UI_directory, $host)
{
    $debug = TRUE;
    
    $grid_files_path="/data/www/EMBnet/cgi-src/Grid/GGG/script/pdb_nr/KKKK";
    if ($debug)
    	echo date("dS of F h:i:s") . " [L] scp $grid_files_path/grock.sh $host:$UI_directory/grock.sh\n";
    exec("scp $grid_files_path/grock.sh $host:$UI_directory/grock.sh");
    if ($debug)
    	echo date("dS of F h:i:s") . " [L] scp $grid_files_path/grock.jdl $host:$UI_directory/grock.jdl\n";
    exec("scp $grid_files_path/grock.jdl $host:$UI_directory/grock.jdl");
}

function send_grid_jobs($UI_directory, $pipes)
{
    $debug = TRUE;
    
    if ($debug)
    	   echo date("dS of F h:i:s") . " [R] cd $UI_directory\n";
    //fwrite($pipes[0], "cd $UI_directory\n");
    //fflush($pipes[0]);
    
    if ($debug)
    	echo date("dS of F h:i:s") . " [R] edg-job-submit  -o $UI_directory/identifier.txt $UI_directory/grock.jdl > submit.txt &\n";
//    fwrite($pipes[0], "edg-job-submit  -o $UI_directory/identifier.txt $UI_directory/grock.jdl > submit.txt &\n");
    fflush($pipes[0]);
    echo passthru("ssh -x -t -t david@villon \"edg-job-submit  -o $UI_directory/identifier.txt $UI_directory/grock.jdl > $UI_directory/submit.txt &\"\n");
    if ($debug)
    	echo date("dS of F h:i:s") . " [R] grid-proxy-info > $UI_directory/grid-proxy-info.txt &\n";
    //fwrite($pipes[0], "grid-proxy-info > grid-proxy-info.txt &\n");
    //fflush($pipes[0]);
    echo passthru("ssh -x -t -t david@villon \"/opt/globus/bin/grid-proxy-info > $UI_directory/grid-proxy-info.txt \"\n");

    // needs to return the JOB-ID from edg-job-submit output
}

function results($local_tmp_directory, $UI_data_path)
{   
    $debug = TRUE;

    $fp_list = fopen("$local_tmp_directory/pdb_list.txt", "r");
    
    if (!$fp_list)
    {
    	echo "\nList file unavailable";
    	exit;
    }
    
    do{ 
    	$ID= trim( fgets( $fp_list ) );
    	
	if ($ID=="")
	{
	    break;
	}
    	
	connect($UI_data_path);
	    //We retrieve the results
	    if ($debug)
	    	echo date("dS of F h:i:s") . " [R] cd $UI_data_path/$ID\n";
    	    //fwrite($pipes[0], "cd $UI_data_path/$ID\n");
    	    //fflush($pipes[0]);
	    if ($debug)
	    	echo date("dS of F h:i:s") . " [R] edg-job-get-output  --dir . --input ./identifier.txt  > results.txt &\n";
    	    //fwrite($pipes[0], "edg-job-get-output  --dir . --input ./identifier.txt  > results.txt &\n");
    	    //fflush($pipes[0]);
	    echo passthru("ssh -x -t -t david@villon edg-job-get-output  --dir $UI_data_path/$ID --input $UI_data_path/$ID/identifier.txt  > $UI_data_path/$ID/results.txt &\n");
	    
	disconnect($descriptorspec, $pipes, $process); 
	
    } while( !feof( $fp_list ) );
    
}
?>
