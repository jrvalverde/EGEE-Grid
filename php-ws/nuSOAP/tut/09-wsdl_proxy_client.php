<?php
/* 
 * WSDL enables one more capability on the client. Instead of using the call 
 * method of the soapclient class, a proxy can be used. The proxy is a class 
 * that mirrors the service, in that it has the same methods with the same 
 * parameters as the service. Some programmers prefer to use proxies because 
 * the code reads as method calls on object instances, rather than invocations 
 * through the call method. A client that uses a proxy is shown below.
 */
 
// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the client instance
$client = new soapclient(
'http://anarchy.cnb.uam.es/cgi-src/Grid/php-ws/nuSOAP/tut/09-wsdl_struct_server.php?wsdl', 
	true);

// Check for an error
$err = $client->getError();
if ($err) {
    // Display the error
    echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
    // At this point, you know the call that follows will fail
}

// Create the proxy
$proxy = $client->getProxy();

// Call the SOAP method
$person = array('firstname' => 'Willi', 'age' => 22, 'gender' => 'male');
$result = $proxy->hello($person);

// Check for a fault
if ($proxy->fault) {
    echo '<h2>Fault</h2><pre>';
    print_r($result);
    echo '</pre>';
} else {
    // Check for errors
    $err = $proxy->getError();
    if ($err) {
        // Display the error
        echo '<h2>Error</h2><pre>' . $err . '</pre>';
    } else {
        // Display the result
        echo '<h2>Result</h2><pre>';
        print_r($result);
    echo '</pre>';
    }
}

// Display the request and response
echo '<h2>Request</h2>';
echo '<pre>' . htmlspecialchars($proxy->request, ENT_QUOTES) . '</pre>';
echo '<h2>Response</h2>';
echo '<pre>' . htmlspecialchars($proxy->response, ENT_QUOTES) . '</pre>';
// Display the debug messages
echo '<h2>Debug</h2>';
echo '<pre>' . htmlspecialchars($proxy->debug_str, ENT_QUOTES) . '</pre>';
?>
