<?
$host="david@villon";

//User Interface directory
$UI_data_path="/tmp/grock/data";

connect($UI_data_path);

results($UI_data_path, $pipes, $host);

disconnect($descriptorspec, $pipes, $process);

function connect($UI_data_path)
{

    global $descriptorspec;
    global $process;
    global $pipes;

    $error_log="/data/www/EMBnet/tmp/grid";

    $descriptorspec = array(
        	0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
        	1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        	2 => array("file", "$error_log/error-output.txt", "a") // stderr is a file to write to
	    	);

    $process = proc_open("ssh -x -t -t david@villon", $descriptorspec, $pipes);
    
    fwrite($pipes[0], "grid-proxy-init  -pwstdin\n");
    sleep(5);     
    fwrite($pipes[0], "kndlaria\n");
    sleep(5);

}

function disconnect($descriptorspec, $pipes, $process)
{
    fwrite($pipes[0], "exit\n");

    fclose($pipes[0]);
	
	    while (!feof($pipes[1]))
	    {
		echo fgets($pipes[1], 1024)."<br />";
	    }
	
    	    fclose($pipes[1]);
	
    $return_value = proc_close($process);
	
    echo "\ncommand returned $return_value\n";	

}

function results($UI_data_path, $pipes)
{   
    fwrite($pipes[0], "cd $UI_directory\n");
    
    //Loop
    fwrite($pipes[0], "edg-job-submit  -o $UI_directory/identifier.txt $UI_directory/grock.jdl > submit.txt &\n");
}

?>
