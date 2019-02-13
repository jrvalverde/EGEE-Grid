<?php
// A SOAP server that handles e-mail
// Reads and e-mail from stdin containing a SOAP request and services it
require_once 'SOAP/Server/Email.php';

$server = new SOAP_Server_Email;

class SOAP_Example_Server {
    function echoString($inputString)
    {
        return $inputString;
    }
}

# read stdin
$fin = fopen('php://stdin','rb');
if (!$fin) exit(0);

$email = '';
while (!feof($fin) && $data = fread($fin, 8096)) {
  $email .= $data;
}

fclose($fin);

$soapclass = new SOAP_Example_Server();
$server->addObjectMap($soapclass,'urn:SOAP_Example_Server');
$server->service($email);
?>
