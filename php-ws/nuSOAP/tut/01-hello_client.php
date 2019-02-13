<?php

// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the client instance:
// new client that will use the URL specified
$client = new soapclient(
	'http://anarchy.cnb.uam.es/cgi-src/Grid/php-ws/nuSOAP/tut/01-hello_server.php'
	);

// Call the SOAP method
//	we specify the name of the remote service/service to call from those
//	offered by the server.
$result = $client->call('hello', array('name' => 'J. R.'));

// Display the result
print_r($result);
?>
