<?php
// Pull in the NuSOAP code
require_once('nusoap.php');

// Enable debugging *before* creating server instance
// This will return the debugging information to the client as a comment
// in the XML response. The client may then view this information by
// displaying the XML response it got.
$debug = 1;

// Create the server instance
$server = new soap_server;

// Register the method to expose
$server->register('hello');

// Define the method as a PHP function
function hello($name) {
    return 'Hello, ' . $name;
}

// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>

