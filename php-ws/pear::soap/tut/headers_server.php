<?php
require_once 'SOAP/Server.php';

class Header_Handler {
    var $method_namespace = 'urn:Header_Handler';
    var $authenticated = FALSE;
    
    function authenticate($authinfo)
    {
        if ($authinfo->username == 'foo' &&
            $authinfo->password == 'bar') {
            $this->authenticated = TRUE;
            return 'Authentication OK';
        }
        $faultcode = 'Client';
        $faultstring = 'Invalid Authentication';
        $faultactor = $this->method_namespace;
        $detail = NULL;
        return new SOAP_Fault($faultstring,
                              $faultcode,
                              $faultactor,
                              $detail);
    }
}

class Simple_Server {
    var $method_namespace = 'urn:Simple_Server';
    
    function Simple_Server() {
        global $server;
        $this->headers = new Header_Handler();
        $server->addObjectMap($this->headers);
    }
    
    function echoString($text) {
        if (!$this->headers->authenticated) {
            $faultcode = 'Client';
            $faultstring = 'You must send authentication headers';
            $faultactor = $this->method_namespace;
            $detail = NULL;
            return new SOAP_Fault($faultstring,
                                  $faultcode,
                                  $faultactor,
                                  $this);
        }
        return $text;
    }
}

$server = new SOAP_Server;
$myserver = new Simple_Server();
$server->addObjectMap($myserver);
$server->service($HTTP_RAW_POST_DATA);
?>
