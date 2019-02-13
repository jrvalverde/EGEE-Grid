<?
/*
 * SOAP arrays are numerically-indexed (non-associative), similar to those in 
 * many programming languages such as C and FORTRAN. Therefore, our service 
 * can access elements of the array using numeric indices, rather than 
 * associative keys. 
 */

// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the server instance
$server = new soap_server;

// Register the method to expose
// Note: with NuSOAP 0.6.3, only method name is used w/o WSDL
$server->register(
    'hello'                            // method name
);

// Define the method as a PHP function
function hello($names) {
    for ($i = 0; $i < count($names); $i++) {
        $retval[$i] = 'Hello, ' . $names[$i];
    }
    
    return $retval;
}
// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>

