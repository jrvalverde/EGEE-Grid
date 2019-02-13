<?php
/* 
 * A WSDL document provides metadata for a service. NuSOAP allows a programmer 
 * to specify the WSDL to be generated for the service programmatically using 
 * additional fields and methods of the soap_server class.
 * 
 * The service code must do a number of things in order for correct WSDL to be 
 * generated. Information about the service is specified by calling the 
 * configureWSDL method. Information about each method is specified by 
 * supplying additional parameters to the register method. Service code for 
 * using WSDL is shown in the following example.
 */

// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the server instance
$server = new soap_server();

// Initialize WSDL support
$server->configureWSDL('hellowsdl', 'urn:hellowsdl');

// Register the method to expose
$server->register('hello',                // method name
    array('name' => 'xsd:string'),        // input parameters
    array('return' => 'xsd:string'),      // output parameters
    'urn:hellowsdl',                      // namespace
    'urn:hellowsdl#hello',                // soapaction
    'rpc',                                // style
    'encoded',                            // use
    'Says hello to the caller'            // documentation
);

// Define the method as a PHP function
function hello($name) {
        return 'Hello, ' . $name;
}

// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>

