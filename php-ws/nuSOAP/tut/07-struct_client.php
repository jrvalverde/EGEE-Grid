<?php
// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the client instance
$client = new soapclient(
	'http://anarchy.cnb.uam.es/cgi-src/Grid/php-ws/nuSOAP/tut/07-struct_server.php'
	);

// Check for an error
$err = $client->getError();
if ($err) {
    // Display the error
    echo '<p><b>Constructor error: ' . $err . '</b></p>';
    // At this point, you know the call that follows will fail
}

// Call the SOAP method
//	first generate the array that conveys the structure
//	on call use soapval to specify it's struct persuasion
$person = array('firstname' => 'Willi', 'age' => 22, 'gender' => 'male');
$result = $client->call(
    'hello',                    // method name
    array('person' => new soapval('person', 'Person',
                                  $person, false, 'urn:MyURN'))    // input parameters
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

