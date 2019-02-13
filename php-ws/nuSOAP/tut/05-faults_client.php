<?php
// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the client instance
$client = new soapclient(
	'http://anarchy.cnb.uam.es/cgi-src/Grid/php-ws/nuSOAP/tut/05-faults_server.php'
	);

// Call the SOAP method
$result = $client->call(
                'joinparams',
		// call with an invalid second parameter (i is a string instead
		// of an integer)
                array('s' => '123', 'i' => '123', 'f' => 123., 'b' => true)
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

