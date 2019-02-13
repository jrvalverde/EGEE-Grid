<?php

require_once('SOAP/Client.php');

$babel_url = 'http://eris.cnb.uam.es/cgi-src/pdb_servlets/babel-ws.php?wsdl';

$babel_wsdl = new SOAP_WSDL($babel_url);

$babel = getProxy($babel_wsdl);

$server_usage = $babel->usage();

print $server_usage;

?>
