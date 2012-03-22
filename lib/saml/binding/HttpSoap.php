<?php

require_once (CAS_PATH.'/lib/Utilities.php');

function extractSoap()
{
    $soapbody = file_get_contents('php://input');
    return $soapbody; 
    
}
   
function extractRequest($soapResponse) {
		assert('is_string($soapResponse)');

		$doc = new DOMDocument();
		if (!$doc->loadXML($soapResponse)) {
			throw new Exception('Error parsing SAML 1 response.');
		}

		$soapEnvelope = $doc->firstChild;
		if (!SimpleSAML_Utilities::isDOMElementOfType($soapEnvelope, 'Envelope', 'http://schemas.xmlsoap.org/soap/envelope/')) {
			throw new Exception('Expected request to contain a <soap:Envelope> element.');
		}

		$soapBody = SimpleSAML_Utilities::getDOMChildren($soapEnvelope, 'Body', 'http://schemas.xmlsoap.org/soap/envelope/');
		if (count($soapBody) === 0) {
			throw new Exception('Couldn\'t find <soap:Body> in <soap:Envelope>.');
		}
		$soapBody = $soapBody[0];


		$requestElement = SimpleSAML_Utilities::getDOMChildren($soapBody, 'Request', 'urn:oasis:names:tc:SAML:1.0:protocol');
		if (count($requestElement) === 0) {
			throw new Exception('Couldn\'t find <saml1p:Request> in <soap:Body>.');
		}
		$requestElement = $requestElement[0];
                //print_r($requestElement);

		/*
		 * Save the <saml1p:Response> element. Note that we need to import it
		 * into a new document, in order to preserve namespace declarations.
		 */
		$newDoc = new DOMDocument();
		$newDoc->appendChild($newDoc->importNode($requestElement, TRUE));
		$requestXML = $newDoc->saveXML();
                  

		return $requestXML;
	}
  function  soapReponse($SamlReponse)
                {
                header('Content-Type: application/xml; charset="utf-8"'); 
		$outputFromIdp = '<?xml version="1.0"?>'; 
              	$outputFromIdp .= '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">';
                $outputFromIdp.= '<SOAP-ENV:Header/>';

		$outputFromIdp .= '<SOAP-ENV:Body>';
                
                
                $outputFromIdp.=$SamlReponse;
		
                
                
                
		$outputFromIdp .= '</SOAP-ENV:Body>';
		$outputFromIdp .= '</SOAP-ENV:Envelope>';
		print($outputFromIdp);
		exit(0);
                }      
       
  

function extractTicket($samlrequest)
{
    assert('is_string($samlrequest)');
    $doc = new DOMDocument();
    if (!$doc->loadXML($samlrequest)) {
        throw new Exception('Error parsing the request.');
		}
     $Request = $doc->firstChild;
     if (!SimpleSAML_Utilities::isDOMElementOfType($Request, 'Request', 'urn:oasis:names:tc:SAML:1.0:protocol')) {
          throw new Exception('Expected request to contain a <Request> element.');
	}
     
      $artifact = SimpleSAML_Utilities::getDOMChildren($Request, 'AssertionArtifact', 'urn:oasis:names:tc:SAML:1.0:protocol');
       if (count($artifact) === 0) {
			throw new Exception('Couldn\'t find any artifacts in <Request>.');
		}
                
       $artifact= $artifact[0]; 
       $ticket=SimpleSAML_Utilities::getDOMText($artifact); 
     
      return $ticket; 
       
}

?>