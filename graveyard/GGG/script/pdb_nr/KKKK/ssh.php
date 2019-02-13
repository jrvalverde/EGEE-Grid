<?
$data_path="/data/www/EMBnet/tmp/grid/grock/data";

system("scp ./*xto.* david@villon:");

echo "OOOO";

/*
$descriptorspec = array(
        	0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
        	1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        	2 => array("file", "$data_path/error-output.txt", "a") // stderr is a file to write to
	    	);
	
	
	
//$process = proc_open("ssh -x -t -t david@villon", $descriptorspec, $pipes);
$process = proc_open("scp -r /data/www/EMBnet/tmp/grid/grock/data david@villon:data/", $descriptorspec, $pipes);
	

echo "OOOO";
//fwrite($pipes[0], "ls\n");
//fwrite($pipes[0], "exit\n");

fclose($pipes[0]);

	
	while (!feof($pipes[1]))
	{
		echo fgets($pipes[1], 1024)."<br />";
	}
	
fclose($pipes[1]);

	
$return_value = proc_close($process);
	
echo "\ncommand returned $return_value\n";	
*/

?>
