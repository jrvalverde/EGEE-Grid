<?php

require 'SOAP/Client.php';

// We have a human readable explanation for the API.
//	Create a service using this URL as description
$wsdl_url = 'http://soap.amazon.com/schemas3/AmazonWebServices.wsdl';
$WSDL = new SOAP_WSDL($wsdl_url);
//	Build the local API
$client = $WSDL->getProxy();

// Make query
//	Look for OXO kitchen products
$params = array('manufacturer' => 'oxo',
                'mode'         => 'kitchen',
                'page'         => 1,
                'type'         => 'lite',
                'tag'          => 'trachtenberg-20',
                'devtag'       => 'XXXXXX'
               );
$hits = $client->ManufacturerSearchRequest($params);

// See what we got
foreach ($hits->Details as $hit) {
    printf('<p style="clear:both"><img src="%s" alt="%s"
            align="left" /><a href="%s">%s</a><br/>%s</p>',
            $hit->ImageUrlSmall,
            htmlspecialchars($hit->ProductName),
            $this->Url, htmlspecialchars($hit->ProductName),
            htmlspecialchars($hit->OurPrice)
          );
}


?>
