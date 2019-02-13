<?php
require_once 'SOAP/Client.php';

$soapclient = new SOAP_Client('http://localhost/talk/server3.php');

$header = new SOAP_Header(
        '{urn:Header_Handler}authenticate',
        'Struct',
        array('username'=>'foo','password'=>'bar'),
        1);

$soapclient->addHeader($header);

$ret = $soapclient->call('echoString',
                         $params=array('inputString'=>'test'),
                         'urn:Simple_Server');
?>
