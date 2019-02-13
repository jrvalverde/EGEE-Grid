
<?php
require_once 'SOAP/Server.php';

$server = new SOAP_Server;

class SOAP_Example_Server {
    var $method_namespace = 'urn:SOAP_Example_Server';

    function echoString($inputString)
    {
        if (!$inputString) {
            $faultcode = 'Client';
            $faultstring = 'You sent an empty string';
            $faultactor = $this->method_namespace;
            $detail = NULL;
            return new SOAP_Fault($faultstring,
                                  $faultcode,
                                  $faultactor,
                                  $detail);
        }
        return $inputString;
    }
}

$soapclass = new SOAP_Example_Server();
$server->addObjectMap($soapclass,'urn:SOAP_Example_Server');
$server->service($HTTP_RAW_POST_DATA);
?>
