<?php

class TinkerApp {

    // Who we are
    var $app_name	    ="egTinker";

    // Generate names for all files we'll use

    var $fn="tinker-$appname";	    // base name for all generated files

    // input file in any format
    var $inuri  	    ="$httptmp/$dir/$fn.inp";	// name relative to DocumentRoot
    var $infile 	    ="$serverpath/$inuri";	// absolute file name

    // input file after conversion to Brookheaven format
    var $brkuri 	    = "$httptmp/$dir/$fn.brk";
    var $brkfile 	    = "$serverpath/$brkuri";

    // input file converted to XYZ format
    var $xyzuri 	    = "$httptmp/$dir/$fn.xyz";
    var $xyzfile 	    = "$serverpath/$xyzuri";

    // log file
    var $loguri             = "$httptmp/$dir/$fn.log";
    var $logfile            = "$serverpath/$loguri";

    // results file
    var $resuri             = "$httptmp/$dir/$fn.xyz_2";
    var $resfile            = "$serverpath/$resuri";

    // PDB file
    var $pdburi             = "$httptmp/$dir/$fn.pdb";
    var $pdbfile            = "$serverpath/$pdburi";

    // Sequence file
    var $sequri             = "$httptmp/$dir/$fn.seq";
    var $seqfile            = "$serverpath/$sequri";

    // options we need to receive from user form
    var $input_file;    	// uploaded a local file
    var $input_data;    	// filled in data text box
    var $iformatopts;		// format the input is in

    var $force_field;
    var $ffpar;
    var $email;
    var $wapmail;
    var $job;
    var $key;

    function get_user_data($workdir)
    {
    	// sample skeleton for derives classes
	
    	// Upload all user files
	$this->input_file  = $_FILES['upload']['tmp_name'][0];
	$file_name = $_FILES['upload']['name'][0];
	$file_size = $_FILES['upload']['size'][0];


	$this->iformatopts = $_POST["informat"];
	$this->force_field = $_POST["forcefield"];
	$this->ffpar       = $_POST["ffpar"];
	$this->email       = $_POST["email"];
	$this->wapmail	   = $_POST["wapmail"];
	$this->job         = $_POST["job"];

	// Check if the file uploaded is correct  
	if ($_FILES['upload']['tmp_name'][0]=="none" || $_FILES['upload']['tmp_name'][0]=="")
    	    haveinput=0;
	if ($_FILES['upload']['size'][0]==0)
    	    haveinput=0;

	// if haveinput == 0 check whether there is data in the text box	

	// move file to destination working directory.
	if (!move_file($this->input_file, $infile))
	{
    	    echo "<h1>$this->input_file and $infile problem: can not move</H1>";
	    exit();
	}


	check_mail_address($this->email);
	
	if ($this->wapmail != "") {
    	    check_mail_address($this->wapmail);
	}
    }

    function print_user_data()
    {
    	return;
    }
    
    
    function run() {
    	return;
    }
}
?>
