<?php
// PHP4 supports string, integer, float and boolean types. The following 
// service has a method that uses all four.

// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the server instance
$server = new soap_server;

// Register the method to expose
//	Note we are not specifying the in/out types (!)
$server->register('joinparams');

// Define the method as a PHP function
function joinparams($s, $i, $f, $b) {
    $ret = $s . ' is a ' . gettype($s);
    $ret .= ', ' . $i . ' is a ' . gettype($i);
    $ret .= ', ' . $f . ' is a ' . gettype($f);
    $ret .= ' and ' . $b . ' is a ' . gettype($b);

    return new soapval('return', 'xsd:string', $ret);
}

// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>

