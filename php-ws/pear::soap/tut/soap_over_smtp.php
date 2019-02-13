<?php
require_once('SOAP/Client.php');

$soapclient = new SOAP_Client('mailto:jr@cnb.uam.es');

$options = array(
        'namespace' => 'http://soapinterop.org/',
        'from' => 'jrvalverde@cnb.uam.es',
        'host' => 'cnb.uam.es',
        'port' => 25 );

$return = $soapclient->call('echoStringArray',
            array('inputStringArray' =>
              array('good','bad','ugly')),
            $options);

// don't expect much of a result!
print_r($return);
?>
