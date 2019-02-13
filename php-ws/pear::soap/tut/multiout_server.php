<?php
require_once 'SOAP/Server.php';

$server = new SOAP_Server;

class SOAP_Example_Server {
    var $__dispatch_map = array();
    
    function SOAP_Interop_GroupB() {
    $this->__dispatch_map['echoStructAsSimpleTypes'] =
        array('in' => array('inputStruct' => 'SOAPStruct'),
              'out' => array(
                        'outputString' => 'string',
                        'outputInteger' => 'int',
                        'outputFloat' => 'float')
              );
    }
    
    function &echoStructAsSimpleTypes (&$struct)
    {
    # convert a SOAPStruct to an array
    return array(
        new SOAP_Value('outputString','string',$struct->varString),
        new SOAP_Value('outputInteger','int',$struct->varInt),
        new SOAP_Value('outputFloat','float',$struct->varFloat)
        );
    }
}

$soapclass = new SOAP_Example_Server();
$server->addObjectMap($soapclass,'urn:SOAP_Example_Server');
$server->service($HTTP_RAW_POST_DATA);
?>
