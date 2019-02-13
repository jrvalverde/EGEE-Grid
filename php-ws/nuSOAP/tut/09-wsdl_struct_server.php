<?php
/*
 * An important aspect of WSDL is that it can encapsulate one or more XML 
 * Schema, allowing programmers to describe the data structures used by a 
 * service.
 * To illustrate how NuSOAP supports this, we will add WSDL code to the 
 * SOAP struct example
 */

// Pull in the NuSOAP code
require_once('nusoap.php');

// Create the server instance
$server = new soap_server();

// Initialize WSDL support
$server->configureWSDL('hellowsdl2', 'urn:hellowsdl2');

// Register the data structures used by the service
//	Input data
$server->wsdl->addComplexType(
    'Person',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'firstname' => array('name' => 'firstname', 'type' => 'xsd:string'),
        'age' => array('name' => 'age', 'type' => 'xsd:int'),
        'gender' => array('name' => 'gender', 'type' => 'xsd:string')
    )
);

//	return result
$server->wsdl->addComplexType(
    'SweepstakesGreeting',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'greeting' => array('name' => 'greeting', 'type' => 'xsd:string'),
        'winner' => array('name' => 'winner', 'type' => 'xsd:boolean')
    )
);

// Register the method to expose
$server->register('hello',                    // method name
    array('person' => 'tns:Person'),          // input parameters
    array('return' => 'tns:SweepstakesGreeting'),    // output parameters
    'urn:hellowsdl2',                         // namespace
    'urn:hellowsdl2#hello',                   // soapaction
    'rpc',                                    // style
    'encoded',                                // use
    'Greet a person entering the sweepstakes'        // documentation
);

// Define the method as a PHP function
//	With WSDL, it is no longer necessary to use the soapval object to 
//	specify the name and data type for the return value.
function hello($person) {
    $greeting = 'Hello, ' . $person['firstname'] .
                '. It is nice to meet a ' . $person['age'] .
                ' year old ' . $person['gender'] . '.';
    
    $winner = $person['firstname'] == 'Scott';

    return array(
                'greeting' => $greeting,
                'winner' => $winner
                );
}
// Use the request to (try to) invoke the service
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);
?>

