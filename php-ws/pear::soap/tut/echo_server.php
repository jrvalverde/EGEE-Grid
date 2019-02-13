<?php
require_once 'SOAP/Server.php';

class SOAP_Example_Server {
    function echoString($inputString)
    {
    return $inputString;
    }
}

$server =& new SOAP_Server;
$soapclass =& new SOAP_Example_Server();
$server->addObjectMap($soapclass,'urn:SOAP_Example_Server');
$server->service($HTTP_RAW_POST_DATA);
?>
