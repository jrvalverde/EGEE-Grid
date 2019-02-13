<?php
require_once('SOAP/Client.php');

$key = 'xxxxxxxxxxxx';
$query = 'Caraveo';

$wsdlurl = 'http://api.google.com/GoogleSearch.wsdl';
$WSDL = new SOAP_WSDL($wsdlurl);
$client = $WSDL->getProxy();

$response = $client->doGoogleSearch(
                    $key,$query,0,4,
                    false,'',false,'','','');

print_r($response);

foreach ( $response->resultElements as $result ) {
    echo '<a href="'.$result->URL.'">';
    echo $result->title."</a><br><br>\n";
    echo $result->snippet."<br><br><br>\n";
}

?>
