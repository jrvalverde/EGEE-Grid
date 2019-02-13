<?php
/*
 * The client changes only in that it passes a parameter that is an array 
 * of names, rather than a single scalar name.
 */
 
// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the client instance
$client = new soapclient(
	'http://anarchy.cnb.uam.es/cgi-src/Grid/php-ws/nuSOAP/tut/06-array_server.php'
	);

// Check for an error
$err = $client->getError();
if ($err) {
    // Display the error
    echo '<p><b>Constructor error: ' . $err . '</b></p>';
    // At this point, you know the call that follows will fail
}

// Call the SOAP method
$names = array('Scott', 'Albert', 'Robert', 'Phyllis');
$result = $client->call(
    'hello',                       // method name
    array('names' => $names)    // input parameters
);

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

