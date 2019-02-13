<?php
/**
 *  GROCK - GRid dOCK
 *
 *  A web service to perform high-thoughput docking on the Grid.
 *
 *  The goal of this service is to provide a convenient way to generate
 * docking searches of a probe molecule against a database of target
 * molecules.
 *
 *  The probe may be a protein or a drug or small compound. The target
 * database to be searched may be a set of protein or small compound
 * structures.
 *
 * LICENSE
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package 	grock
 * @author  	David Garcia <david@cnb.uam.es>
 * @author  	Jose R. Valverde <david@cnb.uam.es>
 * @copyright 	CSIC
 * @license 	../c/gpl.txt
 * @version 	$Id$
 * @see     	config.php
 * @see     	grock_lib.php
 * @see     	dock.php
 * @see     	processor.php
 * @link	http://savannah.cern.ch/projects/GridGRAMM
 * @since   	File available since release 0.0
 */

// add our install dir to the include path
$path=dirname($argv[0]);
set_include_path($path . PATH_SEPARATOR . get_include_path());

require_once('config.php');
require_once('grock_lib.php');
require_once('dock.php');
require_once('lib/nusoap.php');

$debug=TRUE;
$debug_grid=TRUE;
$debug_ssh=TRUE;

/*
 * Generate WS server
 */
$server = new soap_server();

// initialize WSDL support
$server->configureWSDL('grock', 'urn:grock');

/*
 * Register the data structures used by the service
 */

//      string bibliographyList[]
$server->wsdl->addComplexType(
         'bibliographyList',
         'complexType',
         'array',
         '',
         'SOAP-ENC:Array',
         array(),
         array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'string[]')),
         'xsd:string'
);

//	struct auth_token {
//		string user;
//		string host;
//		string password
//		string passphrase
//	}
$server->wsdl->addComplexType(
    'auth_token',  	    	    // name
    'complexType',  	    	    // kind of type
    'struct',	    	    	    // type (e.g. struct, array)
    'all',
    '',     	    	    	    // encoding (e.g. SOAP-ENC:Array)
    array(  	    	    	    // components
	 'user' => array('name' => 'user', 'type' => 'xsd:string'),
	 'host' => array('name' => 'host', 'type' => 'xsd:string'),
	 'password' => array('name' => 'password', 'type' => 'xsd:string'),
	 'passphrase' => array('name' => 'passphrase', 'type' => 'xsd:string'),
    )
);

//	struct grock_options {
//		string probe3D;
//		string probe_type;
//		string database;
//		string docker;
//		string resolution;
//		string representation;
//	}
$server->wsdl->addComplexType(
    'grock_options',  	    	    // name
    'complexType',  	    	    // kind of type
    'struct',	    	    	    // type (e.g. struct, array)
    'all',
    '',     	    	    	    // encoding (e.g. SOAP-ENC:Array)
    array(  	    	    	    // components
	 'probe3D' => array('name' => 'probe3D', 'type' => 'xsd:string'),
	 'probe_type' => array('name' => 'probe_type', 'type' => 'xsd:string'),
	 'database' => array('name' => 'database', 'type' => 'xsd:string'),
	 'docker' => array('name' => 'docker', 'type' => 'xsd:string'),
	 'resolution' => array('name' => 'resolution', 'type' => 'xsd:string'),
	 'representation' => array('name' => 'representation', 'type' => 'xsd:string')
    )
);

//	string failedJobs[]
//	UNUSED (How do I include it in a struct?) Find out XXX JR XXX
#$server->wsdl->addComplexType(
#	'failedJobs',
#	'complexType',
#	'array',
#	'',
#	'SOAP-ENC:Array',
#	array(),
#	array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'string[]')),
#	'xsd:string'
#);

//	struct output {
//		string status;
//		string output;
//		string error;
//		string report;
//		string failedJobs;
//	}
$server->wsdl->addComplexType(
    'grock_output',  	    	    // name
    'complexType',  	    	    // kind of type
    'struct',	    	    	    // type (e.g. struct, array)
    'all',
    '',     	    	    	    // encoding (e.g. SOAP-ENC:Array)
    array(  	    	    	    // components
	 'status' => array('name' => 'status', 'type' => 'xsd:string'),
	 'output' => array('name' => 'output', 'type' => 'xsd:string'),
	 'error' => array('name' => 'error', 'type' => 'xsd:string'),
	 'report' => array('name' => 'report', 'type' => 'xsd:string'),
	 'failedJobs' => array('name' => 'failedJobs', 'type' => 'xsd:string')
   )
);

/*
 *	Register methods
 */
$server->register('source_code',    	    	    // method name
	array(),    	    	    	    	    // input parameters
	array('return' => 'xsd:string'),    	    // output parameters
	'urn:grock',	    	    	    	    // namespace
	'urn:grock#source_code',    	    	    // soapaction
	'rpc',	    	    	    	    	    // invocation style
	'encoded',  	    	    	    	    // use
	'Returns the source code for this server'   // documentation
);

$server->register('bibliography',
	array(),
	array('return' => 'tns:bibliographyList'),
	'urn:grock',
	'urn:grock#bibliography',
	'rpc',
	'encoded', 
	'Return a list of bibliographic references to include in derived works'
);

$server->register('usage',
	array(),
	array('return' => 'xsd:string'),
	'urn:grock',
	'urn:grock#usage',
	'rpc',
	'encoded',
	'Report usage tips on this service'
);

$server->register('grock_it',
	array('auth_token' => 'tns:auth_tooken',
	      'grock_options' => 'tns:grock_options'),
	array('return' => 'xsd:string'),
	'urn:grock',
	'urn:grock#grock_it',
	'rpc',
	'encoded',
	'Perform a docking search of a probe structure against a database of structures'
);

$server->register('get_output',
	array('output_handle' => 'xsd:string'),
	array('return' => 'tns:grock_output'),
	'urn:grock',
	'urn:grock#get_output',
	'rpc',
	'encoded', 
	'Get status and output of a running GROCK job'
);


//////////////////////// OFFER SERVICE /////////////////////////

// Call the service method to initiate the transaction and send the response
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);

if(isset($log) and $log != ''){
        harness('grock',$server->headers['User-Agent'],$server->methodname,$server->request,$server->response,$server->result);
}

/////////////////////// IMPLEMENTATION /////////////////////////

/**
 *  return source code for this script
 *
 *  This method allows interested parties to get the source code for
 * this script. Since this is open source, we want to make it easy
 * for users to get/produce source code.
 *
 *  @return 	string containing the source code (with embedded newlines)
 */
function source_code() {
	$source = file_get_contents("./grock-ws.php");
	return $source;
}

/**
 *  Return bibliographic references needed when using this server
 *
 *  We want users to have an easy way to determine which references they
 * must include in publications using this server. This method provides an
 * easy way to collect them... and to add to them by higher-level software.
 *
 *  Clients may then call this method, get the references as an array of
 * strings, and add additional strings to this array to append *their* own
 * references to the list.
 *
 *  @return an array of strings containing bibliographic references
 */
function bibliography()
{
	$biblio = array(
		'<A HREF=\"\">Ref1</A>',
		'<A HREF=\"\">Ref2</A>');
	return $biblio;
}

/**
 *  Return usage of this service
 *
 *  @return 	HTML string containing the usage (with embedded newlines)
 */
function usage()
{
    return "<CENTER><H1>GROCK</H1></CENTER>" .
    "<P><STRONG>GRid dOCK</STRONG>: perform a high-throughout docking search using the Grid</P>" .
    "<P><STRONG>Usage:</STRONG><PRE>\n" .
    "string grock->source_code()	// return source code of this server\n" .
    "string grock->usage()		// return usage instructions (this message)\n" .
    "string grock->bibliography()	// return bibliographic references\n" .
    "string grock->grock_it(auth_token, grock_options) // Run GROCK as given user with given params\n" .
    "struct grock_output grock->get_output(output_handle) // return GROCK output\n" .
    "\n" .
    "struct auth_token {\n" .
    "    string user;\n" .
    "    string host;\n" .
    "    string password;\n" .
    "    string passphrase;\n" .
    "}\n\n" .
    "struct grock_options {\n" .
    "    string probe3D;\n" .
    "    string probe_type;\n" .
    "    string database;\n" .
    "    string docker;\n" .
    "    string resolution;\n" .
    "    string representation;\n" .
    "}\n\n" .
    "struct grock_output {\n" .
    "    string status;\n" .
    "    string output;\n" .
    "	 string error\n" .
    "    string report;\n" .
    "    string failedJobs[];\n" .
    "}\n\n" .
    "</PRE>";
}

function grock_it($auth_token, $grock_options)
{
	$auth = array();
	$auth['server'] = $auth_token['user'].'@'.$auth_token['host'];         // user@back-end
	$auth['user'] = $auth_token['user'];             // user
	$auth['host'] = $auth_token['host'];             // back-end
	$auth['password'] = $auth_token['password'];     // user@back-end passwd
	$auth['passphrase'] = $auth_token['passphrase']; // user grid passphrase

	$options = array();
	$options['docker'] = $grock_options['docker'];
	$options['database'] = $grock_options['database'];
	$options['probe_type'] = $grock_options['probe_type'];
	
	$options['resolution'] = $grock_options['resolution'];
	$options['representation'] = $grock_options['representation'];

	// generate session and go to it
	$session_id =activate_new_sandbox();
	
	// save probe as a file
	file_put_contents('probe.pdb', $grock_options['probe3D']);

	ob_end_flush();
	grock_set_status('starting');
	flush();
	
	umask(077);
	$grockopt = fopen("options", "w+");
	foreach ($auth as $idx => $value) fwrite($grockopt, "$idx=$value\n");
	fwrite($grockopt, "\n");
	foreach ($options as $idx => $value) fwrite($grockopt, "$idx=$value\n");
	fclose($grockopt);
	exec("(/usr/bin/php $app_dir/script/grock.php > grock_error 2>&1 )&");

	return new soapval('return', 'string', $session_id);
}

function get_output($output_handle)
{
	// go to $output_handle directory
	if (! chdir("$local_tmp_path/$output_handle")) {
	    return new soap_fault('SERVER', '',
	    	"Specified job $output_handle does not exist", '');
	}

	$status = grock_get_status();
	if ($status == FALSE) {
	    // raise an exception
	    return new soap_fault('SERVER', '', 
		"Cannot find status of your job $output_handle", '');
	}
	if ($status == 'aborted') {
	    // raise an exception
	    return new soap_fault('SERVER', '', 
		"Job $output_handle has been aborted", '');
	}
	$status = trim($status);
	$output = file_get_contents("./grock_output");
	$error = file_get_contents("./grock_error");

	// have some default values for not-common vars
	$report = '';
	$failedJobs = array();
	
	if ($status == 'submitting') {
	    $report = file_get_contents('./target_list.txt');
	}

	if ($status = 'partial') {
	    $report = file_get_contents('./top_scores.txt');
	}
	
	if ($status = 'finished') {
	    $report = file_get_contents('./top_scores.txt');
	    // get list of failed jobs
	    $unfinished_jobs = 0; 
	    $total_jobs = 0;
	    while (!feof($target_list)) {
	        $line = fgets($target_list);
	        if ($line == "") continue;
	        $target = strtok(trim($line), ' ');
	        $full_desc = strtok("\n");
		$total_jobs++;

		if (!is_dir("$target/job_output")) {
        	    // this is a failed job
        	    $unfinished_jobs++;
#        	    $failedJobs[$target] = $full_desc;
		    $failedJobs .= $line;
		}
	    }
	}
	return array(
	    'status' => $status,
	    'output' => $output,
	    'error' => $error,
	    'report' => $report,
	    'failedJobs' => $failedJobs
	);
}

?>
