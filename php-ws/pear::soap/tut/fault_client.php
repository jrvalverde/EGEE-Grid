
<?php
require_once 'SOAP/Client.php';

$soapclient = new SOAP_Client('http://localhost/cgi-src/pdb_servlets/soap/fault_server.php');

$result = $soapclient->call('echoString',
                         $params=array('inputString' => NULL),
                         array('namespace' => 'urn:SOAP_Example_Server'));
if (PEAR::isError($result)) {
    // handle the error condition
    echo $result->getMessage();
}
?>
