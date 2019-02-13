<?php
require_once 'SOAP/Server.php';

class SOAP_Example_Server {
    var $name = NULL;
    
    function setName($inputString)
    {
        $this->name = $inputString;
        return true;
    }

    function getName()
    {
        return $this->name;
    }
}

$server = new SoapServer('urn:SOAP_Example_Server');
$server->bind('SOAP_Example_Server.wsdl');
$server->setClass('MyClass');

# tell the server to use PHP's session capabilities
# to persist the object
$server->setPersistence(SOAP_PERSISTENCE_SESSION);

$server->handle();
?>
