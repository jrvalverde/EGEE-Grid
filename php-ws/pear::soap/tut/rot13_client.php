<?php
require_once 'SOAP/Client.php';
$client = new SOAP_Client('http://localhost/cgi-src/pdb_servlets/rot13_server.php');

// this namespace is the same as declared earlier
$options = array('namespace' => 'urn:SOAP_Server_rot13');

$original_string = "Rotate me!";

echo "<p>Original string:<h1>";
print $original_string;
echo "</H1></p>\n";

$rotated_string = $client->call("rotate",
                                $params = array("input" =>
                                                $original_string),
                                $options);
echo "<p>Rotated string:";
print $rotated_string;
echo "</p>\n";

$recovered_string = $client->call("rotate",
				$params = array("input" =>
						$rotated_string),
				$options);
echo "<p>Re-rotated string:";
print $recovered_string;
echo "</p>\n";
?>
