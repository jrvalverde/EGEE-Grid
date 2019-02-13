<?php

class Analyze extends TinkerApp {

// Who we are

var $app_name	    ="analyze";

// Generate names for all files we'll use

var $fn="tinker";	    // base name for all generated files

var $inuri  	    ="$httptmp/$dir/$fn.inp";	// name relative to DocumentRoot
var $infile 	    ="$serverpath/$inuri";	// absolute file name

var $brkuri 	    = "$httptmp/$dir/$fn.brk";
var $brkfile 	    = "$serverpath/$brkuri";

var $xyzuri 	    = "$httptmp/$dir/$fn.xyz";
var $xyzfile 	    = "$serverpath/$xyzuri";

var $loguri         = "$httptmp/$dir/$fn.log";
var $logfile        = "$serverpath/$loguri";

var $resuri         = "$httptmp/$dir/$fn.xyz_2";
var $resfile        = "$serverpath/$resuri";

var $pdburi         = "$httptmp/$dir/$fn.pdb";
var $pdbfile        = "$serverpath/$pdburi";

var $sequri         = "$httptmp/$dir/$fn.seq";
var $seqfile        = "$serverpath/$sequri";

// options we need to receive from user form

var $input_file;    	// uploaded a local file
var $input_data;    	// filled in data text box
var $iformatopts;
var $force_field;
var $energy;
var $eatom;
var $elarge;
var $details;
var $inertia;
var $moment;
var $ffpar;
var $email;
var $wapmail;
var $job;
var $key;

function upload_user_data($workdir)
{
    $this->input_file  = $_FILES['upload']['tmp_name'][0];
    $file_name = $_FILES['upload']['name'][0];
    $file_size = $_FILES['upload']['size'][0];
    

    $this->iformatopts = $_POST["informat"];
    $this->force_field = $_POST["forcefield"];
    $this->energy      = $_POST["energy"];
    $this->eatom       = $_POST["eatom"];
    $this->elarge      = $_POST["elarge"];
    $this->details     = $_POST["details"];
    $this->inertia     = $_POST["inertia"];
    $this->moment      = $_POST["moment"];
    $this->ffpar       = $_POST["ffpar"];
    $this->email       = $_POST["email"];
    $this->wapmail	 = $_POST["wapmail"];
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


    // check_mail_address($this->email);
    if ($this->email == "") {
    	badaddress(); 	// in functions.php
	exit();
    }
    // test correct email
    // verify it is of the form .*@.*\..*
    ...
    $remotehost = $this->email minus .*@
    if (gethostbyname($remotehost)) {
    	badaddress();
	exit();
    }
    
    if ($this->wapmail != "") {
    	check_mail_address($this->wapmail);
    }
}

function print_options()
{
}

function go_to_work()
{
}

function run_application()
{
}
