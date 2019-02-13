<?php

    // generate scores file
    $target_list=fopen('target_list.txt', 'r');
    while (!feof($target_list)) {
    	$line = fgets($target_list);
    	if (trim($line, "\n") == '') continue;
	$target = strtok($line, ' ');    // ENTR_C (entry_chain)
    	$description = strtok("\n");   	// description
	echo "$target -- $description\n";
	if (file_exists("$target/job_output/gramm-come.tar.gz")) {
	    exec("tar -zxOf $target/job_output/gramm-come.tar.gz ".
	    	 "     receptor-ligand.res | ".
    	    	 "tail -n +32 | ".
    	    	 "sed -e 's#\$#  $target $description#g' >> sctmp");  
	}
	exec("echo \"\" >> sctmp");
    }
    exec('sort -r -n -k 2 sctmp > nscores.txt');
    unlink('sctmp');
?>
