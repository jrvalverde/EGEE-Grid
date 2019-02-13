<?
/*
 * The SOAP 1.1 specification defines a method for reporting errors on the 
 * server back to the client. This is the SOAP Fault. The client code we 
 * have been using checks for faults and displays any that are received. 
 * The NuSOAP server code will automatically generate faults in some cases, 
 * such as when the client requests a method that does not exist. The service 
 * code you write can also generate faults.
 * 
 * In the previous example, suppose we wanted to enforce data types on the 
 * incoming values. We could check the data types and return a SOAP Fault if 
 * any are incorrect. The modified service code is shown below. Note that,
 * to deal with faulty clients, the service method must check the variable 
 * types more loosely than you might expect. For example, since the string 
 * value '55.55' may be de-serialized as a float, we allow float values to 
 * be specified for the string (and our code would then cast the float to a 
 * string).
 */

// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the server instance
$server = new soap_server;

// Register the method to expose
$server->register('joinparams');

// Define the method as a PHP function
function joinparams($s, $i, $f, $b) {
    if (!(is_string($s) || is_int($s) || is_float($s))) {
        return new soap_fault('SERVER', '', 's must be a string', $s);
    }
    if (!is_int($i)) {
        return new soap_fault('SERVER', '', 'i must be an integer', $i);
    }
    if (!(is_float($f) || is_int($f))) {
        return new soap_fault('SERVER', '', 'f must be a float', $f);
    }
    if (!(is_bool($b) || is_int($b))) {
        return new soap_fault('SERVER', '', 'b must be a boolean', $b);
    }

    $ret = $s . ' is a ' . gettype($s);
    $ret .= ' ' . $i . ' is a ' . gettype($i);
    $ret .= ' ' . $f . ' is a ' . gettype($f);
    $ret .= ' ' . $b . ' is a ' . gettype($b);

    return new soapval('return', 'xsd:string', $ret);
}
// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>
