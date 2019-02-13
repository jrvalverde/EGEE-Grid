<?php
// Call local server

require_once("SOAP/Client.php");

$soapclient =& new SOAP_Client("http://localhost/cgi-src/pdb_servlets/soap/echo_server.php");

// this namespace is the same as declared in server.php
$options = array('namespace' => 'urn:SOAP_Example_Server',
                 'trace' => 1);

$ret = $soapclient->call("echoString",
                        $params = array("inputString"=>
                                        "this is a test"),
                        $options);

print_r($ret);
?>
<hr>
<?php
// Call MS server using RPC/Encoded SOAP call

require_once('SOAP/Client.php');

$WSDL = new SOAP_WSDL(
    'http://mssoapinterop.org/stkV3/Interop.wsdl');
$client = $WSDL->getProxy();
$response = $client->echoString('this is a test');

print $client->xml;
?>
<hr>
<?php
// Call MS server using Document/Literal SOAP request

require_once('SOAP/Client.php');

$WSDL = new SOAP_WSDL('
    http://mssoapinterop.org/stkv3/wsdl/interopTestDocLit.wsdl');
$client = $WSDL->getProxy();
$response = $client->echoString('this is a test');

print $client->xml;
?>


<?php
// Call MS server using Document/Literal without WSDL
include("SOAP/Client.php");

$endpoint = 'http://mssoapinterop.org/stkv3/wsdl/interopTestDocLit.wsdl';
$client =& new SOAP_Client($endpoint);

$echoStringParam =& new SOAP_Value(
        '{http://soapinterop.org/xsd}echoStringParam',
        'string','Hello World');
        
$return = $client->call("echoString",
                $v = array("echoStringParam"=>$echoStringParam),
                array('namespace'=>'http://soapinterop.org/WSDLInteropTestDocLit',
                    'soapaction'=>'http://soapinterop.org/',
                    'style'=>'document',
                    'use'=>'literal' ));

print $return;
?>
