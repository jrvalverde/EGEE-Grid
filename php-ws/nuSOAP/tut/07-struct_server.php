<?
/* 
 * NuSOAP leverages the capabilities of PHP, using associative arrays to 
 * represent SOAP Structs. For this example, we will use an array containing 
 * the first name, age and gender for a person. In PHP, this will look like
 *
 * $person = array(
 *              'firstname' => 'Betty',
 *              'age' => 32,
 *              'gender' => 'female'
 *           );
 * 
 * The service accepts a parameter that is an associative array as shown above. It returns another array like the following.
 * 
 * $return = array(
 *              'greeting' => 'Hello...',
 *              'winner' => false
 *           );
 */

// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the server instance
$server = new soap_server;

// Register the method to expose
$server->register(
    'hello'                            // method name
);

// Define the method as a PHP function
function hello($person) {
    $greeting = 'Hello, ' . $person['firstname'] .
                '. It is nice to meet a ' . $person['age'] .
                ' year old ' . $person['gender'] . '.';
    
    $winner = $person['firstname'] == 'Scott';

    $retval = array(
                'greeting' => $greeting,
                'winner' => $winner
                );

    // notice that the service method returns a soapval, not just the
    // associative array directly, so that the XML data type, in this 
    // case urn:MyURN:ContestInfo, can be specified for the return value.
    return new soapval('return', 'ContestInfo', $retval, false, 'urn:MyURN');
}

// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>

