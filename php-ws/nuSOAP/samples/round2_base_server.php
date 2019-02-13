<?php

//$log = true;
require_once('config.php');

// 1. include client and server
//require_once('../lib/nusoap.php');

// 2. instantiate server object
$server = new soap_server();

//configureWSDL($serviceName,$namespace = false,$endpoint = false,$style='rpc', $transport = 'http://schemas.xmlsoap.org/soap/http');
$server->configureWSDL('InteropTest','http://soapinterop.org/');

// set schema target namespace
$server->wsdl->schemaTargetNamespace = 'http://soapinterop.org/xsd/';

// add types
$server->wsdl->addComplexType(
	'ArrayOfstring',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'string[]')),
	'xsd:string'
);

$server->wsdl->addComplexType(
	'ArrayOfint',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'int[]')),
	'xsd:int'
);

$server->wsdl->addComplexType(
	'ArrayOffloat',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'float[]')),
	'xsd:float'
);

$server->wsdl->addComplexType(
	'SOAPStruct',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'varString' => array('name'=>'varString','type'=>'string'),
		'varInt' => array('name'=>'varInt','type'=>'int'),
		'varFloat' => array('name'=>'varFloat','type'=>'float')
	)
);

$server->wsdl->addComplexType(
	'ArrayOfSOAPStruct',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'SOAPStruct[]')),
	'SOAPStruct'
);

// 3. call the register() method for each service (function) you want to expose:
$server->register(
	'echoString',
	array('inputString'=>'xsd:string'),
	array('return'=>'xsd:string'),
	'http://soapinterop.org/');
function echoString($inputString){
	return new soapval('return','string',$inputString);
}

$server->register('echoStringArray',
	array('inputStringArray'=>'tns:ArrayOfstring'),
	array('return'=>'tns:ArrayOfstring'),
	'http://soapinterop.org/');
function echoStringArray($inputStringArray){
	return $inputStringArray;
}

$server->register('echoInteger',
	array('inputInteger'=>'xsd:int'),
	array('outputInteger'=>'xsd:int'),
	'http://soapinterop.org/');
function echoInteger($inputInteger){
	return $inputInteger;
}

$server->register('echoIntegerArray',
	array('inputIntegerArray'=>'tns:ArrayOfint'),
	array('return'=>'tns:ArrayOfint'),
	'http://soapinterop.org/');
function echoIntegerArray($inputIntegerArray){
	return $inputIntegerArray;
}

$server->register('echoFloat',
	array('inputFloat'=>'xsd:float'),
	array('return'=>'xsd:float'),
	'http://soapinterop.org/');
function echoFloat($inputFloat){
	return $inputFloat;
}

$server->register('echoFloatArray',
	array('inputFloatArray'=>'tns:ArrayOffloat'),
	array('return'=>'tns:ArrayOffloat'),
	'http://soapinterop.org/');
function echoFloatArray($inputFloatArray){
	return $inputFloatArray;
}

$server->register('echoStruct',
	array('inputStruct'=>'tns:SOAPStruct'),
	array('return'=>'tns:SOAPStruct'),
	'http://soapinterop.org/');
function echoStruct($inputStruct){
	return $inputStruct;
}

$server->register('echoStructArray',
	array('inputStructArray'=>'tns:ArrayOfSOAPStruct'),
	array('return'=>'tns:ArrayOfSOAPStruct'),
	'http://soapinterop.org/');
function echoStructArray($inputStructArray){
	return $inputStructArray;
}

$server->register('echoVoid',array(),array(),'http://soapinterop.org');
function echoVoid(){
}

$server->register('echoBase64',
	array('inputBase64'=>'xsd:base64Binary'),
	array('return'=>'xsd:base64Binary'),
	'http://soapinterop.org/');
function echoBase64($b_encoded){
	return base64_encode(base64_decode($b_encoded));
}

$server->register('echoDate',
	array('inputDate'=>'xsd:dateTime'),
	array('return'=>'xsd:dateTime'),
	'http://soapinterop.org/');
function echoDate($timeInstant){
	return $timeInstant;
}

$server->register('echoHexBinary',
	array('inputDate'=>'xsd:hexBinary'),
	array('return'=>'xsd:hexBinary'),
	'http://soapinterop.org/');
function echoHexBinary($hb){
	return $hb;
}

$server->register('echoDecimal',
	array('inputDate'=>'xsd:decimal'),
	array('return'=>'xsd:decimal'),
	'http://soapinterop.org/');
function echoDecimal($dec){
	return $dec;
}

$server->register('echoBoolean',
	array('inputDate'=>'xsd:boolean'),
	array('return'=>'xsd:boolean'),
	'http://soapinterop.org/');
function echoBoolean($b){
	if($b){
    	return $b;
    }
    return false;
}

// 4. call the service method to initiate the transaction and send the response
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);

if(isset($log) and $log != ''){
	harness('nusoap_r2_base_server',$server->headers['User-Agent'],$server->methodname,$server->request,$server->response,$server->result);
}
?>
