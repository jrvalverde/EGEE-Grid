<?php
// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the client instance
$client = new soapclient(
	'http://anarchy.cnb.uam.es/cgi-src/Grid/php-ws/nuSOAP/01-hello_server+debug.php'
	);

// Call the SOAP method
$result = $client->call('hello', array('name' => 'Jose R.'));

// Display the result
print_r($result);

// Display the request and response
echo '<h2>Request</h2>';
echo '<pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';

echo '<h2>Response</h2>';
echo '<pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';

// Display the debug messages
echo '<h2>Debug</h2>';
echo '<pre>' . htmlspecialchars($client->debug_str, ENT_QUOTES) . '</pre>';


?>
