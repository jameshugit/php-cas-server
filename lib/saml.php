<?php

function samlResponseError1($inResponseTo, $issuer, $recipient, $error_code = 'samlp:Responder') {

	$samlp = 'urn:oasis:names:tc:SAML:1.0:protocol';
	$saml = 'urn:oasis:names:tc:SAML:1.0:assertion';
	$xs = 'http://www.w3.org/2001/XMLSchema';
	$xsi = 'http://www.w3.org/2001/XMLSchema-instance';

	$now = new DateTime();
	$now->setTimeZone(new DateTimeZone('UTC'));
	$issueInstant = $now->format('Y-m-d\TH:i:s\Z');

	$dom = new DOMDocument('1.0');

	$response = $dom->createElementNS($samlp, 'samlp:Response');
	$response->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:samlp', $samlp);
	$response->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:saml', $saml);
	$response->setAttribute('MajorVersion', '1');
	$response->setAttribute('MinorVersion', '1');
	$response->setAttribute('ResponseID', generateRand(16));
	$response->setAttribute('InResponseTo', $inResponseTo);
	$response->setAttribute('IssueInstant', $issueInstant);
	$response->setAttribute('Recipient', $recipient);
	$dom->appendChild($response);

	$status = $dom->createElementNS($samlp, 'samlp:Status');
	$response->appendChild($status);

	$statusCode = $dom->createElementNS($samlp, 'samlp:StatusCode');
	$statusCode->setAttribute('Value', 'samlp:Success');
	$status->appendChild($statusCode);

	return $response;
}


function samlResponse1($attributes, $inResponseTo, $issuer, $nameIdentifier, $recipient) {
	$samlp = 'urn:oasis:names:tc:SAML:1.0:protocol';
	$saml = 'urn:oasis:names:tc:SAML:1.0:assertion';
	$xs = 'http://www.w3.org/2001/XMLSchema';
	$xsi = 'http://www.w3.org/2001/XMLSchema-instance';

	$now = new DateTime();
	$now->setTimeZone(new DateTimeZone('UTC'));
	$issueInstant = $now->format('Y-m-d\TH:i:s\Z');

	$dateBegin = new DateTime();
	$dateBegin->setTimeZone(new DateTimeZone('UTC'));
	$dateBegin->sub(new DateInterval('PT1H0S'));
	$notBefore = $dateBegin->format('Y-m-d\TH:i:s\Z');

	$dateEnd = new DateTime();
	$dateEnd->setTimeZone(new DateTimeZone('UTC'));
	$dateEnd->add(new DateInterval('PT1H0S'));
	$notOnOrAfter = $dateEnd->format('Y-m-d\TH:i:s\Z');

	$dom = new DOMDocument('1.0');

	$response = $dom->createElementNS($samlp, 'samlp:Response');
	$response->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:samlp', $samlp);
	$response->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:saml', $saml);
	$response->setAttribute('MajorVersion', '1');
	$response->setAttribute('MinorVersion', '1');
	$response->setAttribute('ResponseID', generateRand(16));
	$response->setAttribute('InResponseTo', $inResponseTo);
	$response->setAttribute('IssueInstant', $issueInstant);
	$response->setAttribute('Recipient', $recipient);
	$dom->appendChild($response);

	$status = $dom->createElementNS($samlp, 'samlp:Status');
	$response->appendChild($status);

	$statusCode = $dom->createElementNS($samlp, 'samlp:StatusCode');
	$statusCode->setAttribute('Value', 'samlp:Success');
	$status->appendChild($statusCode);

	$assertion = $dom->createElementNS($saml, 'saml:Assertion');
	$assertion->setAttribute('MajorVersion', '1');
	$assertion->setAttribute('MinorVersion', '1');
	$assertion->setAttribute('AssertionID', generateRand(16));
	$assertion->setAttribute('Issuer', $issuer);
	$assertion->setAttribute('IssueInstant', $issueInstant);
	$response->appendChild($assertion);

	$conditions = $dom->createElementNS($saml, 'Conditions');
	$conditions->setAttribute('NotBefore', $notBefore);
	$conditions->setAttribute('NotOnOrAfter', $notOnOrAfter);
	$assertion->appendChild($conditions);

	$audienceRestrictionCondition = $dom->createElementNS($saml, 'AudienceRestrictionCondition');
	$conditions->appendChild($audienceRestrictionCondition);

	$audience = $dom->createElementNS($saml, 'Audience');
	$audience->nodeValue = $recipient;
	$audienceRestrictionCondition->appendChild($audience);

	$attributeStatement = $dom->createElementNS($saml, 'AttributeStatement');
	$assertion->appendChild($attributeStatement);

	$subject = $dom->createElementNS($saml, 'Subject');
	$attributeStatement->appendChild($subject);	
	$nameIdentifierNode = $dom->createElementNS($saml, 'NameIdentifier');
	$nameIdentifierNode->nodeValue = $nameIdentifier;
	$subject->appendChild($nameIdentifierNode);
	$subjectConfirmation = $dom->createElementNS($saml, 'SubjectConfirmation');
	$subject->appendChild($subjectConfirmation);
	$confirmationMethod = $dom->createElementNS($saml, 'ConfirmationMethod');
	$confirmationMethod->nodeValue = 'urn:oasis:names:tc:SAML:1.0:cm:artifact';
	$subjectConfirmation->appendChild($confirmationMethod);

	foreach($attributes as $key => $value) {
		$attribute = $dom->createElementNS($saml, 'Attribute');
		$attributeStatement->appendChild($attribute);

		$attribute->setAttribute('AttributeName', $key);
		$attribute->setAttribute('AttributeNamespace', $issuer);
		$attributeStatement->appendChild($attribute);

		if(!is_array($value)) {
			$value = array($value);
		}

		foreach($value as $v) {
			$attributeValue = $dom->createElementNS($saml, 'AttributeValue');
			$attributeValue->nodeValue = $v;
			$attribute->appendChild($attributeValue);
		}
	}

	$authenticationStatement = $dom->createElementNS($saml, 'AuthenticationStatement');
	// should be the time where the user really login
	$authenticationStatement->setAttribute('AuthenticationInstant', $issueInstant);
	// should change is the user use something else like AAF-SSO
	$authenticationStatement->setAttribute('AuthenticationMethod', 'urn:oasis:names:tc:SAML:1.0:am:password');
	$assertion->appendChild($authenticationStatement);

	$subject = $dom->createElementNS($saml, 'Subject');
	$authenticationStatement->appendChild($subject);	
	$nameIdentifierNode = $dom->createElementNS($saml, 'NameIdentifier');
	$nameIdentifierNode->nodeValue = $nameIdentifier;
	$subject->appendChild($nameIdentifierNode);
	$subjectConfirmation = $dom->createElementNS($saml, 'SubjectConfirmation');
	$subject->appendChild($subjectConfirmation);
	$confirmationMethod = $dom->createElementNS($saml, 'ConfirmationMethod');
	$confirmationMethod->nodeValue = 'urn:oasis:names:tc:SAML:1.0:cm:artifact';
	$subjectConfirmation->appendChild($confirmationMethod);

	return $response;
}

function generateSoapEnvelope($bodyContent) {
	$SOAPENV = 'http://schemas.xmlsoap.org/soap/envelope/';

	$now = new DateTime();
	$now->setTimeZone(new DateTimeZone('UTC'));
	$issueInstant = $now->format('Y-m-d\TH:i:s\Z');

	$dom = new DOMDocument('1.0');

	$envelope = $dom->createElementNS($SOAPENV, 'SOAP-ENV:Envelope');
	$envelope->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:SOAP-ENV', $SOAPENV);
	$dom->appendChild($envelope);

	$header = $dom->createElementNS($SOAPENV, 'Header');
	$envelope->appendChild($header);

	$body = $dom->createElementNS($SOAPENV, 'Body');
	$envelope->appendChild($body);

	$body->appendChild($dom->importNode($bodyContent, true));

	return $dom;
}

function getSoapBodyContent($dom) {
	$xpath = new DOMXpath($dom);
	$xpath->registerNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
	$body = $xpath->query("//SOAP-ENV:Body/*")[0];
	return $body;
}

function soapSamlResponseError($selfURL, $request, $recipient, $error_code = 'samlp:Responder') {
	$requestID = 'unknown';
	if($request != null) {
		$body = getSoapBodyContent($request);
		$requestID = $body->getAttribute('RequestID');
	}
	$samlResponse = samlResponseError1($requestID, $selfURL,
		$recipient, $error_code);
	return generateSoapEnvelope($samlResponse);
}

function soapSamlResponse($selfURL, $request, $attributes, $nameIdentifier, $recipient) {

	$body = getSoapBodyContent($request);
	$soapSamlResponse = null;

	$requestID = $body->getAttribute('RequestID');
	$samlResponse = samlResponse1($attributes, $requestID,
		$selfURL, $nameIdentifier, $recipient);
	$soapSamlResponse = generateSoapEnvelope($samlResponse);

	return $soapSamlResponse;
}

