<?
include('SOAP/Client.php');
//$client = SoapObject(
$client =& new SOAP_Client(
    'http://localhost/cgi-src/pdb_servlets/soap/multiout_server.php');


class SOAPStruct {
    var $varString;
    var $varInt;
    var $varFloat;
    function SOAPStruct($s=NULL, $i=NULL, $f=NULL) {
        $this->varString = $s;
        $this->varInt = $i;
        $this->varFloat = $f;
    }
}

$struct = new SOAPStruct('test string',123,123.123);

list($string, $int, $float) =
    $client->call("echoStructAsSimpleTypes",$struct,$options);
print_r($string);
print_r($int);
print_r($float);
?>
