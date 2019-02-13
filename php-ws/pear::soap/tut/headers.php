<?php
require_once('SOAP/Client.php');

$client =& new SOAP_Client(
    'http://www.caraveo.com/soap_interop/server_round2.php');

$header =& new SOAP_Header(
        '{http://soapinterop.org/echoheader/}echoMeStringRequest',
        'string',
        'This is a test header',
        1, /* defaults to zero */
        /* this is the default */
        'http://schemas.xmlsoap.org/soap/actor/next'  
        );

$client->addHeader($header);

$response = $client->call('echoVoid', $p = NULL);
print $client->wire;
?>
