<?php

/* 
 * The client code that follows specifies the namespace and SOAPAction for the 
 * call. Although, as noted in the server, the NuSOAP service appears to ignore 
 * these, it is a good practice to include them, since they will be required to 
 * interoperate with most services based on other SOAP implementations, such as 
 * .NET or Apache Axis.
 */
 
// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the client instance
$client = new soapclient(
	'http://anarchy.cnb.uam.es/cgi-src/Grid/php-ws/nuSOAP/tut/02-hello_server.php'
	);

// Check for an error
$err = $client->getError();

if ($err) {
    // Display the error
    echo '<p><b>Constructor error: ' . $err . '</b></p>';
    // At this point, you know the call that follows will fail
}

// Call the SOAP method
$result = $client->call(
    'hello',                     // method name
    array('name' => 'Scott'),    // input parameters
    'uri:helloworld',            // namespace
    'uri:helloworld/hello'       // SOAPAction
);

// Strange: the following works just as well!
//$result = $client->call('hello', array('name' => 'Scott'));

// Check for a fault
if ($client->fault) {
    echo '<p><b>Fault: ';
    print_r($result);
    echo '</b></p>';
} else {
    // Check for errors
    $err = $client->getError();
    if ($err) {
        // Display the error
        echo '<p><b>Error: ' . $err . '</b></p>';
    } else {
        // Display the result
        print_r($result);
    }
}

// Display the request and response
echo '<h2>Request</h2>';
echo '<pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';

echo '<h2>Response</h2>';
echo '<pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
?>
