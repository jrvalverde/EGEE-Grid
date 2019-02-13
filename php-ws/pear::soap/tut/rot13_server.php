<?php
require_once 'SOAP/Server.php';

class SOAP_Server_rot13 {
    function rotate($input) {
        return str_rot13($input);
    }
}

$server = new SOAP_Server;
$soapclass = new SOAP_Server_rot13();
// Associate PHP class with SOAP message
$server->addObjectMap($soapclass ,'urn:SOAP_Server_rot13');
$server->service($HTTP_RAW_POST_DATA);

?>
