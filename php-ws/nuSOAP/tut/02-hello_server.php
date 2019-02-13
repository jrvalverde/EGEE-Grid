<?php
/*
 * There were two aspects of the XML payload in the SOAP response that could 
 * be improved. First, the response element should have a namespace. Second, 
 * the first child of the response should be given a meaningful name, rather 
 * than just accepting the default name soapVal.
 *
 * One would think that the namespace of the response element could be set 
 * with the $namespace parameter to the soap_server->register method. 
 * Likewise, it would seem possible to set the name of the return element by 
 * specifying it in the output parameter array. However, the code with these 
 * changes, shown below, returns the same SOAP response as the original 
 * helloworld.php sample. Interestingly, although a namespace and SOAPAction 
 * are specified, the client need not provide these in its SOAP request.
 *
 */
// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the server instance
$server = new soap_server;

// Register the method to expose
// Note: with NuSOAP 0.6.3, only method name is used w/o WSDL
$server->register(
	'hello',			    // method name
	array('name' => 'xsd:string'),      // input parameters
    	array('return' => 'xsd:string'),    // output parameters
    	'uri:helloworld',                   // namespace
    	'uri:helloworld/hello',             // SOAPAction
    	'rpc',                              // style
    	'encoded'                           // use
	);

// Define the method as a PHP function
function hello($name) {
    return 'Hello, ' . $name;
}

// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);

?>

