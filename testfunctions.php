<?php
require_once(dirname(__file__).'/lib/Utilities.php'); 

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

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
        
      
        function microtime_float ()
{
    list ($msec, $sec) = explode(' ', microtime());
    $microtime = (float)$msec + (float)$sec;
    return $microtime;
}


function validateSaml($samlRequest,$samlSchema)
{
    assert('is_string($samlRequest)');
    assert('is_string($samlSchema)');
    
    try{
    $dom = new DOMDocument(); 
    $dom->loadXML($samlRequest);
    echo $dom->saveXML(); 
    $start = microtime_float(); 
    $validschema = $dom->schemaValidate($samlSchema);
    $end = microtime_float();
     echo 'Validation Execution Time: ' . round($end - $start, 3) . ' seconds';   
    
    
    return $validschema;
    }
    catch(Exception $e)
    {
        
        if ($e->getCode()==2)
            return 1; 
        else 
            return 0; 
    }
    //return $validschema;
}
class PhpError extends Exception {
    public function __construct() {
        list(
            $this->code,
            $this->message,
            $this->file,
            $this->line) = func_get_args();
    }
}

set_error_handler(create_function(
    '$errno, $errstr, $errfile, $errline',
    'throw new PhpError($errno, $errstr, $errfile, $errline);'
)); 

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


        
        $soapReponse='<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
  <SOAP-ENV:Header/>
  <SOAP-ENV:Body>
    <samlp:Request xmlns:samlp="urn:oasis:names:tc:SAML:1.0:protocol"
                   MajorVersion="1"
                   MinorVersion="1"
                   RequestID="_6FD897835BAEF695F81977EF77CE13A1"
                   IssueInstant="2012-03-21T11:02:45.068+01:00">
      <samlp:AssertionArtifact>ST-145-OtFElHMGJ7s3ib4h5IQ9</samlp:AssertionArtifact>
    </samlp:Request>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>'; 
        
       $samloneschema = dirname(__file__).'/schemas/oasis-sstc-saml-schema-protocol-1.1.xsd' ; 
        
        $response=extractRequest($soapReponse);
        //echo($response);
        
        //validateSaml($response, $samloneschema); 
        
        $dom = new DOMDocument();
        $start = microtime_float(); 
        //$dom->validateOnParse = true;
    $dom->load('test.xml');
    echo $dom->saveXML(); 
    
    $validschema = $dom->schemaValidate($samloneschema);
    $end = microtime_float();
     echo 'Validation Execution Time: ' . round($end - $start, 3) . ' seconds';   

      



        
        $ticket=  extractTicket($response); 
        //echo $ticket; 
        
?>
