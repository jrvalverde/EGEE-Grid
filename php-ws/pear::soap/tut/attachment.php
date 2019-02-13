<?php
// MIME attachment
require_once('SOAP/Client.php');

$filename = 'attachment.php';
$v =  new SOAP_Attachment('test','text/plain',$filename);

$methodValue = new SOAP_Value('testattach', 'Struct', array($v));

$client = new SOAP_Client(
    'http://www.caraveo.com/soap_interop/server_round2.php');

$resp = $client->call('echoMimeAttachment',$v,
                array('attachments'=>'Mime',
                'namespace'=>'http://soapinterop.org/'));
print_r($resp);
?>

<hr>
<?php
// DIME attachment
require_once('SOAP/Client.php');

$filename = 'attachment.php';
$v =  new SOAP_Attachment('test','text/plain',$filename);

$methodValue = new SOAP_Value('testattach', 'Struct', array($v));

$client = new SOAP_Client(
    'http://www.caraveo.com/soap_interop/server_round2.php');

$resp = $client->call('echoMimeAttachment',$v,
                array('namespace'=>'http://soapinterop.org/'));
print_r($resp);

?>

<?php
// Calling with MIME and mail
include('SOAP/Client.php');

$filename = 'attachment.php';
$v =  new SOAP_Attachment('test','text/plain',$filename);
$methodValue = new SOAP_Value('testattach', 'Struct', array($v));

$client = new SOAP_Client('mailto:jr@cnb.uam.es');
# calling with mime
$resp = $client->call('echoMimeAttachment',array($v),
                array('attachments'=>'Mime',
                    'namespace'=>'http://soapinterop.org/',
                    'from'=>'jrvalverde@cnb.uam.es',
                    'host'=>'cnb.uam.es'));
?>
