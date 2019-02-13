<?php
/*
 * So, the server uses only the method name we specified when registering it. 
 * We can still control the name (and type) of the response data. Instead of 
 * returning just a string as we have done, we can return a soapval, a class 
 * provided by NuSOAP. Here is a variation of the original helloworld.asp that 
 * does just that.
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
//	Instead of returning directly a string, we will 
//	hardcode the return type using soapval:
function hello($name) {
//    return 'Hello, ' . $name;
    return new soapval('return', 'xsd:string', 'Hello, ' . $name);
}
// Looking at the constructor for the soapval class, we can see that we 
// could also specify a namespace for the element. We really want to 
// specify the namespace of the element containing the return value, so 
// there is no need to specify a namespace here.

// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);

?>

