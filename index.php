<?php
//
// @mainpage php-cas-server 
// @section What What ?
// php-cas-server is a pure-PHP CAS 2.0 server implementation
//
// @section Why Why ?
// While other implementations exist, this one is designed whith the following goals in mind :
// - SuckLess(TM) technologies : no-java and other brainfucking stuff here, just LAMP
// - Kiss : simple design, no big software engineering bullshitting, just some code that works
// 
// @section Installation Installation
//
// @section License License
// This fine piece of code is AGPL licensed
// 
// @verbatim
// See LICENSE File for more information
// @endverbatim
//

/**
 *
 * Main Application Controller for the CAS server
 *
 * @file index.php
 * @author Michel Blanc <mblanc@erasme.org>
 * @author Pierre-Gilles Levallois <pgl@erasme.org>
 * @author Daniel LACROIX <dlacroix@erasme.org>
 *
 * This application controller routes login, logout and serviceValidate
 * requests, and enforce logic flow for those 3 CAS URIs
 *
 * 'action' parameter is internal for routing purposes between login/logout/serviceValidate
 *
 * @section flow Logic flow
 *
 * @subsection login /login
 *  - client has no TGT => present login/pass form, store initial GET parameters somewhere (service)
 *  - client POSTing credentials => check credentials, send TGT, redirect to login
 *  - client has TGT, renew parameter set to true => destroy TGC, present login form
 *  - client has valid TGT => send a redirect to 'service' url with newly created ST
 *
 * @subsection logout /logout
 *  - client has valid TGT => destroy client TGC, display link to 'url' 
 *  - client has no TGC => display blank page
 *
 * @subsection serviceValidate /serviceValidate
 *  - checks that ST is valid for service S
 *
 */

// Add lib/ directory in the include path
set_include_path('.'.PATH_SEPARATOR.__DIR__.PATH_SEPARATOR.__DIR__.'/lib/'.PATH_SEPARATOR.get_include_path());

require_once('config.inc.php');

require_once('lib/functions.php');
require_once('lib/ticket.php');
//require_once('lib/Utilities.php');
require_once('lib/saml.php');
//require_once('lib/saml/binding/HttpSoap.php');
require_once('lib/KLogger.php');

require_once('views/error.php');
require_once('views/login.php');
require_once('views/logout.php');
require_once('views/auth_success.php');
require_once('views/auth_failure.php');
//require_once('views/saml_pronote_token.php');

$log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);

// get the SSO public URL
$selfURL = getSelfURL();

//
// login
// Handles sso login requests.
//
// @returns void
//

function login() {
	global $CONFIG, $log;
	$log->LogDebug("Login Function is called");

	$log->LogDebug("REQUEST_URI". $_SERVER['REQUEST_URI']);

	$service = isset($_REQUEST['service']) ? $_REQUEST['service'] : false;

	$log->LogDebug("service:  $service");

	$authentication_done = false;
	$authentication_error = false;
	$ticket = '';
	$username = '';

	// user is trying to post credentials
	if(array_key_exists('username', $_POST) && !empty($_POST['username'])) {

		//  user should have posted a valid LoginTicket.
		//  => check credentials
		//  => send TGT
		//  => redirect to login
		// create database provider
		$log->LogDebug('user is trying to post credentials');

		$ch = curl_init();
		$url = $CONFIG['API_URL'].'api/sso?login='.urlencode($_POST['username'])."&password=".urlencode($_POST['password']);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_ENCODING ,"");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERPWD, $CONFIG['API_KEY'] . ":" . $CONFIG['API_PASS']);
		$data = curl_exec($ch);
		if(curl_errno($ch)) {
			$log->LogError("HTTP error with the request '$url'. Got: ".curl_error($ch));
			viewLoginFailure($service, "Un problème dans laclasse.com nous empêche de vous authentifier. Rééssayez plus tard ou contacter votre administrateur.");
			curl_close($ch);
			return;
		}
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		// credentials are ok
		if($http_code == 200) {
			$json = json_decode($data);
			$login = $json->login;

			// credentials ok
			$log->LogDebug('credentials are valid, generate a TGC');
			$tgt = new TicketGrantingTicket();
			$tgt->create($login);
			$log->LogDebug("Generated ticket: ".$tgt->key());

			// send TGC
			setcookie("CASTGC", $tgt->key(), 0, "/");
			$log->LogDebug("CASTGC cookie is set succesfully: ".$tgt->key());

			$ticket = $tgt->key();
			$username = $_POST['username'];
			$authentication_done = true;
		}
		// credentials fails
		else {
			$log->LogError("Credentials failed for ".$_POST['username'].", try again");
			$authentication_error = true;
		}
	}
	// user is not posting credentials
	else {
		// user has TGT and renew is not set
		if(array_key_exists('CASTGC', $_COOKIE) && !isset($_GET['renew'])) {
			$tgt = new TicketGrantingTicket();
			// if the ticket is valid
			if($tgt->find($_COOKIE["CASTGC"])) {
				$ticket = $tgt->key();
				$username = $tgt->username();
				$authentication_done = true;
			}
		}
	}

	// is authentication succeed, redirect to the service
	if($authentication_done) {
		if ($service) {
			if (!isServiceAutorized($service)) {
				$log->LogError("Oups:Service: $service is not authorized");
				showError(_("Cette application n'est pas autoris&eacute;e &agrave; s'authentifier sur le serveur CAS."));
			}
			else {
				// build a service ticket
				$st = new ServiceTicket();
				$st->create($ticket, $service, $username);
				$log->LogDebug("Service Ticket :" . $st->key() . "");
				$log->LogDebug("Redirect to :" . url($service) . "&ticket=" . $st->key() . "");
				// Redirecting for futher client request for serviceValidate
				header("Location: " . url($service) . "ticket=" . $st->key() . "");
			}
		}
		else {
			// xNo service, user just wanted to login to SSO
			$log->LogDebug("no Service was required");
			viewLoginSuccess();
			return;
		}
	}
	// invalid user or password
	elseif($authentication_error) {
		viewLoginFailure($service);
	}
	// else display the login form
	else {
		viewLoginForm($service);
	}
}

//
// logout
// Logout handler
// @return void
//

function logout() {
	global $CONFIG, $log;
	$log->LogDebug("Logout function is called");

	if (array_key_exists('CASTGC', $_COOKIE)) {
		// Remove TGT
		$tgt = new TicketGrantingTicket();
		$tgt->find($_COOKIE["CASTGC"]);
		$tgt->delete();

		$log->LogDebug("LOGOUT_SUCCESS ".$tgt->username()."");

		// Remove cookie from client
		setcookie("CASTGC", FALSE, 0, "/");
		$log->LogDebug("TGT is deleted...");
		$log->LogDebug("CASTGT is removed...");
	}
	else {
		$log->LogDebug("TGC_NOT_FOUND");
		// writeLog("LOGOUT_FAILURE", "TGC_NOT_FOUND");
	}

	// remove all cookies set for our domain
	if (isset($_SERVER['HTTP_COOKIE'])) {
		$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
		foreach($cookies as $cookie) {
			$parts = explode('=', $cookie);
			$name = trim($parts[0]);
			setcookie($name, '', time()-1000);
			setcookie($name, '', time()-1000, '/');
		}
	}

    // If url param is in the GET request, we send it to the view
    //  so a link can be displayed
	if (array_key_exists('url', $_GET) || array_key_exists('destination', $_GET)) {
		if (array_key_exists('url', $_GET))
			viewLogoutSuccess(array('url' => $_GET['url'])); 
		else 
			header("Location:".url($_GET['destination']));
	}
	else
		viewLogoutSuccess(array('url' => '/'));
	return;
}


function getAttributes($login, $service) {
	global $CONFIG;
	// index of the global array containing the list of autorized sites.
	$idxOfAutorizedSiteArray = getServiceIndex($service);
	$myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider']) ?
		$CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider'] : 'sso_attributes';

	// An array with the needed attributes for this service.
	$neededAttr = explode(",", str_replace(" ", "", strtoupper(isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']) ?
		$CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes'] :
		'login,nom,prenom,date_naissance,code_postal,categories')));

	$attributes = array(); // What to pass to the function that generate token
	$attributes['user'] = $login;

	// get the attributes
	$ch = curl_init();
	$url = $CONFIG['API_URL'].'api/sso/'.urlencode($myAttributesProvider).'/'.urlencode($login);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_ENCODING ,"");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERPWD, $CONFIG['API_KEY'] . ":" . $CONFIG['API_PASS']);
	$data = curl_exec($ch);
	if(curl_errno($ch) || (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200)) {
		$log->LogError("HTTP error with the request '$url'. Got: ".curl_error($ch));
		curl_close($ch);
		return null;
	}
	curl_close($ch);

	$rowSet = json_decode($data, true);

	if (isset($rowSet)) {
		// For all attributes returned
		foreach ($rowSet as $idx => $val) {
			if (in_array(strtoupper($idx), $neededAttr)) {
				$attributes[$idx] = $val;
			}
		}
	}
	return $attributes;
}


function getServiceValidate($login, $service, $pgtIou) {
	$attributes = getAttributes($login, $service);
	if($attributes == null) {
        viewAuthFailure(array('code' => 'INVALID_TICKET', 'message' => "Ticket " . $pgtIou . " is not recognized."));
		die();
	}
	// call the token model with the default view or custom view
	return viewAuthSuccess($attributes, $pgtIou);
}

//
// serviceValidate
// Validation of the ST ticket.
// user's primary credential and not from an single sign on session.
//

function serviceValidate() {
    global $CONFIG, $log;
    $log->LogDebug("Service Validate is called ...");

    $ticket = isset($_GET['ticket']) ? $_GET['ticket'] : "";
    $service = isset($_GET['service']) ? urldecode($_GET['service']) : "";
    $renew = isset($_GET['renew']) ? $_GET['renew'] : "";

    $log->LogDebug("Request parameters .....");
    $log->LogDebug("Service Ticket : $ticket");
    $log->LogDebug("Service: $service");
    $log->logDebug("renew: $renew");

    //for proxy validate
    // 1. verifying parameters ST ticket and service should not be empty.
    if (!isset($_GET['ticket']) || !isset($_GET['service'])) {
        $log->LogError("INVALID_REQUEST: serviceValidate requires at least two parameters : ticket and service.");
        viewAuthFailure(array('code' => 'INVALID_REQUEST', 'message' => "serviceValidate require at least two parameters : ticket and service."));
        die();
    }

    // 2. verifying if ST ticket is valid.
    $st = new ServiceTicket();
    if (!$st->find($ticket)) {
        $log->LogError("INVALID_TICKET " . $ticket . " is not recognized.");
        viewAuthFailure(array('code' => 'INVALID_TICKET', 'message' => "Ticket " . $ticket . " is not recognized."));
        die();
    }

    $pgtIou = null;
    //The web application asks for  a proxy ticket by sending pgtUrl(callback to storing proxy tickets)
    if (isset($_GET['pgtUrl'])) {
        $log->LogDebug("The service asks for a proxy ticket to a proxied service");
        $pgtUrl = urldecode($_GET['pgtUrl']);
        $log->LogDebug("Proxied Service:  $pgtUrl ");
        // creating pgtIou ticket to be used in PGT 
        $pgtou = new ProxyGrantingTicketIOU();
        $pgtou->create($service, $st->username());
        $pgtIou = $pgtou->key();

        //creating indexed proxyGrantingTicket            
        $pgt = new ProxyGrantingTicket();
        $pgt->create($pgtIou, $service, $st->username());
        $pgtid = $pgt->key();

        $log->LogDebug("Generate PGTIOU: $pgtIou");
        $log->LogDebug("Generate PGTID: $pgtid");

        $url = urldecode($pgtUrl);
        $pos = strpos($url, '?');
        if ($pos === false)
            $url = $url . '?pgtIou=' . $pgtIou . '&pgtId=' . $pgtid;
        else
            $url = $url . '&pgtIou=' . $pgtIou . '&pgtId=' . $pgtid;
        $log->LogDebug("Send Request to: $url");

        //send Pgtid and pgtIou to the call back address pgtUrl
        $content = get_web_page($url);
    }

    // If we pass here, ticket and service are validated
    // So give back the CAS2 like token
    $token = getServiceValidate($st->username(), $service, $pgtIou);
    $log->LogDebug("call getServiceValidate");

    // 4. destroy ST ticket because this is a one shot ticket.
    $st->delete();
    $log->LogDebug("Delete the service ticket:" . $st->key() . "");

    // 6. echoing CAS2 like token
    header('Content-Type: text/xml; charset="UTF-8"');

    echo $token;
    $log->LogDebug("End of ServiceValidate ...");
}

//
// serviceTicket sends a ticket directly to a destination site
//

function serviceTicket(){
	global $CONFIG, $log; 
	//start debugging
	$log->LogInfo("Service Ticket Function is called");
	$log->LogDebug("sent cookies:" . print_r($_COOKIE,true));
	$log->LogDebug("sent Request:" . print_r($_REQUEST,true));
	$service = isset($_REQUEST['service']) ? $_REQUEST['service'] : false;
	// user has TGC
	$tgt = new TicketGrantingTicket();
	/// @todo Well, do something meaningful...
	if (!$tgt->find($_COOKIE["CASTGC"])) {
		$log->LogError("Oops:Ticket Granting Ticket is not found");
		viewError("le cookie n'est pas valide");
		die();
	}
	else { 
		if($service) { 
			// create a service ticket depending on the sourceservice
			// => redirect desinedservice url with service ticket  encoded in the url
			$st = new ServiceTicket();
			$st->create($tgt->key(), $service, $tgt->username());
			$log->LogDebug("Service Ticket :" . $st->key());
			$log->LogDebug("Redirect to :" . url($service) . "ticket=" . $st->key());
			header("Location: " . url($service) . "ticket=" . $st->key()); 
		}
		// no service is found
		else {
			$log->LogDebug("le service n'est pas valide");
		}
	}
}

//
// proxy
// Provides proxy ticket to proxied service
//
// @param  targetService and PGT
// @returns  Proxy Ticket
//

function proxy() {
    global $CONFIG, $log;

    // prepare loggin parameters
    $log->LogDebug("/Proxy is called ...");
    $log->LogDebug("request parameters");

    $PGT = isset($_GET['pgt']) ? $_GET['pgt'] : "";
    $targetService = urldecode(isset($_GET['targetService']) ? $_GET['targetService'] : "");
    $log->LogDebug("PGT:$PGT");
    $log->LogDebug("TargetService:$targetService");

    // 1. verifying parameters PGT ticket and targetService should not be empty.
    if (!isset($PGT) || !isset($targetService)) {
        $log->LogError("INVALID_REQUEST: proxy requires at least two parameters : PGT ticket and targetService.");
        viewProxyAuthFailure(array('code' => 'INVALID_REQUEST', 'message' => _("proxy require at least two parameters : PGT ticket and targetService.")));
        die();
    }
    // 2. validating the PGT ticket
    $pgtkt = new ProxyGrantingTicket();
    if (!$pgtkt->find($PGT)) {
        $log->LogError("INVALID_PROXY_GRANTING_TICKET" . $PGT . " is not recognized.");
        viewProxyAuthFailure(array('code' => 'INVALID_PROXY_GRANTING_TICKET', 'message' => "Ticket " . $PGT . _(" is not recognized.")));
        die();
    }

    // 3. generate ProxyTicket  and send success reponse
    $log->LogInfo('generate proxy ticket with the following parameters: ');
    $log->LogDebug("Pgt: " . $pgtkt->key() . "\tPGTIOU: " . $pgtkt->PGTIOU() . "\t username: " . $pgtkt->username() . "\t service: " . $pgtkt->service() . "");
    $PT = new ProxyTicket();
    $PT->create($pgtkt->key(), $pgtkt->PGTIOU(), $targetService, $pgtkt->username(), $pgtkt->service());

    //there is some doubt about deleting the ticket	
    //$pgt->delete();
    $log->LogDebug("proxy ticket: " . $PT->key() . "");
    $token = proxySuccesToken($PT->key());
    $log->LogDebug("Response: $token");

    // echoing CAS2 Proxy like token
    header('Content-Type: text/xml; charset="UTF-8"');

    echo $token;
    $log->LogDebug("/proxy call ended..");
}

//
// proxyValidate
// Validation of the ST ticket, with proxy features
//
// @param: service and ticket(PT)
// @returns
//

function proxyValidate() {
	global $CONFIG, $log;

	//logging 
	$log->LogDebug("/Proxy Validate is called ...");
	$log->LogDebug("request parameters");

	$proxyticket = isset($_GET['ticket']) ? $_GET['ticket'] : "";
	$service = urldecode(isset($_GET['service']) ? $_GET['service'] : "");
	$log->LogDebug("proxyticket: $proxyticket");
	$log->LogDebug("service: $service");

	// 1. verifying parameters PT ticket and service should not be empty.
	if (!isset($proxyticket) || !isset($service)) {
		$log->LogError("INVALID_REQUEST: proxy Validate requires at least two parameters : PT ticket and Service.");
		viewAuthFailure(array('code' => 'INVALID_REQUEST', 'message' => _("proxyValidate require at least two parameters : ticket and service.")));
		return;
	}

	// 2. verifying if PT ticket is valid.
	$ptkt = new ProxyTicket();
	if (!$ptkt->find($proxyticket)) {
		$log->LogError("INVALID_PROXY_TICKET" . $proxyticket . " is not recognized.");
		viewAuthFailure(array('code' => 'INVALID_PROXY_TICKET', 'message' => "Ticket " . $proxyticket . _(" is not recognized.")));
		return;
	}

	// 3. generate success reponse
	$token = proxyAuthSuccess($ptkt->username(), $ptkt->PGTIOU(), $ptkt->proxy());
	// delete ticket after validation
	$ptkt->delete();
	$log->LogDebug("proxy ticket deleted");
	$log->LogDebug("Response: $token");

	// 4. echoing  Proxy validation response 
	header('Content-Type: text/xml; charset="UTF-8"');

	echo $token;
	$log->LogDebug("Proxy Validate Ended ...");
}

//
// samlValidate
// Validation of the ST ticket, with SAML
//
// @param
// @returns
//

function samlValidate() {
	global $CONFIG, $log, $selfURL;
	$doc = null;
	$service = '';
	$log->LogDebug("Saml Validate is called ...");
	try {
		// 1 - get the soap message from the request body
		$soapbody = file_get_contents('php://input');
		$log->LogDebug("soap Request: $soapbody");

		// 2 - get the Target and validate it()
		//     verifiying the service 
		if(!isset($_REQUEST['TARGET'])) {
			$log->LogError("TARGET argument is needed !");
			header('Content-Type: text/xml; charset="UTF-8"');
			print soapSamlResponseError($selfURL, null, 'unknown');
			return;
		}
		$service = $_REQUEST['TARGET'];
		$log->LogDebug("Service: $service");

		// 3 - find the ticket (Artifact)
		$doc = new DOMDocument('1.0');
		$doc->loadXML($soapbody);
		$xpath = new DOMXpath($doc);
		$xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:1.0:protocol');
		$assertionArtifact = $xpath->query("//samlp:AssertionArtifact");
		// if no artifact or multiples artifacts found or not SAML 1.0 protocol, stop here
		if($assertionArtifact->length == 0) {
			$log->LogError("samlValidate AssertionArtifact not found in: $soapbody");
			header('Content-Type: text/xml; charset="UTF-8"');
			print soapSamlResponseError($selfURL, $doc, $service);
			return;
        }
        $ticket = $assertionArtifact[0]->nodeValue;
		$log->LogDebug("Extracted Ticket: $ticket");

		// 4 - validate the ticket
		if(!isset($ticket) || empty($ticket)) {
			$log->LogError("samlValidate No valid ticket");
			header('Content-Type: text/xml; charset="UTF-8"');
			print soapSamlResponseError($selfURL, $doc, $service);
			return;
		}

		// verifying if ST ticket is valid and return the attributes.
		$attr = validateTicket($ticket, $service);
		$log->LogDebug("Validate the ticket". $attr);
		if(empty($attr)) {
			$log->LogError("samlValidate user not recognized !");
			header('Content-Type: text/xml; charset="UTF-8"');
			print soapSamlResponseError($selfURL, $doc, $service);
			return;
		}

        $nameIdentifier = $attr['user'];

		// generate and send the SAML Response
		$r = soapSamlResponse($selfURL, $doc, $attr, $nameIdentifier, $service);
		$r->formatOutput = true;
		header('Content-Type: text/xml; charset="UTF-8"');
		print $r->saveXML()."\n";
	}
	catch (Exception $e) {
		$log->LogError("Error". $e->getMessage());
		header('Content-Type: text/xml; charset="UTF-8"');
		print soapSamlResponseError($selfURL, $doc, $service);
	}
}

//
// showError
// Loads error template and display errors
// @param msg Error message to display
// @return void
//

function showError($msg) {
	viewError($msg);
	return;
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

function validateTicket($ticket, $service) {
	global $CONFIG, $log;
	$log->LogDebug("Validate ticket  is called ...");

	global $CONFIG;
	$attributes = array();
	$st = new ServiceTicket();
	if (!$st->find($ticket)) {
		throw new Exception("Ticket " . $ticket . _(" is not recognized."));
	}

	if ($st->service() != $service) {
		$st->delete();
		throw new Exception(("The service ") . $service . _(" is not valid."));
	}

	$login = $st->username();
	$attributes = getAttributes($login, $service);

	if(($attributes == null) || empty($attributes))
		throw new Exception('empty attributes');

	// test attributes
    // delete ticket 
	$st->delete();
	$log->LogDebug("attributes were found");
	return $attributes;
}


//
// verify an XML digital signature
//
// @content: string of and XML document the verify
// @key: string containing the PEM public key
//
// return: true is the digital signature is valid for the given key

function verifySignature($content, $key) {
	$doc = new DOMDocument('1.0');
	$doc->loadXML($content);

	$xpath = new DOMXpath($doc);
	$xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

	// find the signature
	$signature = $xpath->query("//ds:Signature");
	// if no signature or multiples signatures found, stop here
	if(($signature->length == 0) || ($signature->length > 1)) {
		return false;
	}
	$signature = $signature[0];

	// create a document only with the signature part
	$signatureDoc = new DOMDocument('1.0');
	$signatureDoc->loadXML($signature->C14N());
	$xpath2 = new DOMXpath($signatureDoc);
	$xpath2->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

	// get the signature value
	$signatureValue = $xpath2->query('//ds:SignatureValue');
	if(($signatureValue->length == 0) || ($signatureValue->length > 1)) {
		return false;
	}
	$signatureValue = $signatureValue[0]->nodeValue;
	$signatureValue = base64_decode($signatureValue);

	// get the signedInfo part
	$signedInfo = $xpath2->query("//ds:SignedInfo");
	// if no signedInfo or multiples signedInfo found, stop here
	if(($signedInfo->length == 0) || ($signedInfo->length > 1)) {
		return false;
	}
	$signedInfo = $signedInfo[0];

	// create a document only with the signedInfo part
	$signedInfoDoc = new DOMDocument('1.0');
	$signedInfoDoc->loadXML($signedInfo->C14N(true));

	// check the signedInfo part signature using RSA key and SHA1
	$pubkeyid = openssl_pkey_get_public($key);
	$res = openssl_verify($signedInfoDoc->C14N(true), $signatureValue, $pubkeyid, OPENSSL_ALGO_SHA1);
	openssl_free_key($pubkeyid);

	return($res == 1);
}

// verify the digest value
// the digest value is the SHA1 of a part of the XML document.
// This digest value is part of the 
//
// @content: string of and XML document the verify
//
// return: 
//   - the digested part of the XML document if the digest is correct
//   - null if the digest is not valid

function verifyDigest($content) {

	$doc = new DOMDocument('1.0');
	$doc->loadXML($content);

	$xpath = new DOMXpath($doc);
	$xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
	$xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

	// get the digest value for the signature
	$digestValue = $xpath->query("//ds:DigestValue");
	if(($digestValue->length == 0) || ($digestValue->length > 1)) {
		return null;
	}
	$digestValue = $digestValue[0]->nodeValue;

	// find the reference part ID which is used by the digest
	$referenceId = $xpath->query("//ds:Reference[@URI]");
	if(($referenceId->length == 0) || ($referenceId->length > 1)) {
		return null;
	}
	$referenceId = $referenceId[0]->getAttribute('URI');
	// remove the #
	$referenceId = substr($referenceId, 1);

	// get the reference node
	$refNode = $xpath->query("//*[@ID='$referenceId']");
	if(($refNode->length == 0) || ($refNode->length > 1)) {
		return null;
	}
	$refNode = $refNode[0];

	// create a document only with the reference part
	$refNodeDoc = new DOMDocument('1.0');
	$refNodeDoc->loadXML($refNode->C14N());
	$xpath2 = new DOMXpath($refNodeDoc);
	$xpath2->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

	// remove signature
	$signature = $xpath2->query("//ds:Signature");
	if($signature->length > 0) {
		$signature = $signature[0];
		$signature->parentNode->removeChild($signature);
	}

	// generate the SHA1 signature
	$localDigestValue = base64_encode(sha1($refNodeDoc->C14N(true), TRUE));

	return ($localDigestValue === $digestValue) ? $refNodeDoc : null;
}

function CASlogin($login, $service) {
	global $log;

	$tgt = new TicketGrantingTicket();
	$tgt->create($login);
	$log->LogDebug("Generated ticket: ".$tgt->key());

	$ticket = $tgt->key();

	// send TGC
	setcookie("CASTGC", $tgt->key(), 0, "/");
	$log->LogDebug("CASTGC cookie is set succesfully: ".$tgt->key());

	if ($service) {
		if (!isServiceAutorized($service)) {
			$log->LogError("Oups:Service: $service is not authorized");
			showError(_("Cette application n'est pas autoris&eacute;e &agrave; s'authentifier sur le serveur CAS."));
		}
		else {
			// build a service ticket
			$st = new ServiceTicket();
			$st->create($ticket, $service, $login);
			$log->LogDebug("Service Ticket :" . $st->key() . "");
			$log->LogDebug("Redirect to :" . url($service) . "&ticket=" . $st->key() . "");
			// Redirecting for futher client request for serviceValidate
			header("Location: " . url($service) . "ticket=" . $st->key() . "");
		}
	}
	else {
		// xNo service, user just wanted to login to SSO
		$log->LogDebug("no Service was required");
		viewLoginSuccess();
		return;
	}
}


//
// agentPortalIdp
// Handles AAF-SSO SAML requests
//

function agentPortalIdp() {
	global $CONFIG, $log, $selfURL;

	$service = "";
	if(isset($_REQUEST['service'])) {
		$service = $_REQUEST['service'];
	}

	// SAML authentication done, decode response and logon
	if(isset($_REQUEST['SAMLResponse'])) {
		$samlResponse = base64_decode($_REQUEST['SAMLResponse']);

		$doc = new DOMDocument('1.0');
		$doc->loadXML($samlResponse);
		$id = $doc->documentElement->getAttribute('InResponseTo');

		//file_put_contents("/tmp/samlResponse", $samlResponse);

		// verify that the digest present in the signature is correct
		$refNodeDoc = verifyDigest($samlResponse);
		if($refNodeDoc === null) {
			$log->LogError("agentPortalIdp invalid DigestValue in: $samlResponse");
			viewLoginFailure($service, "Les données d'authentification de l'Académie ne sont pas valides et ne nous permette pas de vous identifier. Rééssayez plus tard ou contacter votre administrateur.");
			return;
		}
		// verify the digital signature
		if(!verifySignature($samlResponse, $CONFIG['AGENTS_AAF_SSO_CERT'])) {
			$log->LogError("agentPortalIdp digital signature in: $samlResponse");
			viewLoginFailure($service, "Les données d'authentification de l'Académie ne sont pas valides et ne nous permette pas de vous identifier. Rééssayez plus tard ou contacter votre administrateur.");
			return;
		}

		$xpath = new DOMXpath($refNodeDoc);
		$xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

		// search for the ctemail attribut
		$ctemail = $xpath->query('//saml:Assertion/saml:AttributeStatement/saml:Attribute[@Name="ctemail"]/saml:AttributeValue')[0]->nodeValue;
		if(!isset($ctemail) || empty($ctemail)) {
			$log->LogDebug("agentPortalIdp ctemail '$ctemail' not found");
			viewLoginFailure($service, "Les données d'authentification de l'Académie ne nous permette pas de vous identifier. Rééssayez plus tard ou contacter votre administrateur.");
			return;
		}

		// decode the target service from the InResponseTo attribute
		$decode = hex2bin(substr($id, 1));
		$pos = strpos($decode, ':');
		if($pos) {
			$service = substr($decode, $pos+1);
		}

		$log->LogDebug("agentPortalIdp authentication done for '$ctemail' for service $service");

		// search the user by its email
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $CONFIG['API_URL'].'api/sso/agents?email='.urlencode($ctemail));
		curl_setopt($ch, CURLOPT_ENCODING ,"");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERPWD, $CONFIG['API_KEY'] . ":" . $CONFIG['API_PASS']);
		$data = curl_exec($ch);
		if(curl_errno($ch) || (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200)) {
			$log->LogError("HTTP error with the request '".$CONFIG['API_URL'].'api/sso/agents?email='.urlencode($ctemail).". Got: ".curl_error($ch));
			viewLoginFailure($service, "Un problème dans laclasse.com nous empêche de vous authentifier. Rééssayez plus tard ou contacter votre administrateur.");
			curl_close($ch);
			return;
		}
		curl_close($ch);

		$json = json_decode($data);
		// login with the found user login
		if(isset($json->Data) && is_array($json->Data) && (count($json->Data) > 0)) {
			CASLogin($json->Data[0]->login, $service);
		}
		else {
			viewLoginFailure($service, "Compte utilisateur non trouvé dans laclasse.com. Votre compte doit être provisionné dans laclasse.com avant de pouvoir vous connecter en utilisant votre compte Académique.");
		}
	
	}
	else {
		$AssertionConsumerServiceURL = $selfURL . "agentPortalIdp";
		// DANIEL: temporary until change done in AAF-SSO
		$AssertionConsumerServiceURL = "https://www.laclasse.com/saml/module.php/saml/sp/saml2-acs.php/agents-portal";
		$AafSsoUrl = $CONFIG['AGENTS_AAF_SSO_URL'];

		// encode the target service in the ID
		$ID = "_".bin2hex(generateRand(10).":".$service);		

		$now = new DateTime();
		$now->setTimeZone(new DateTimeZone("UTC"));
		$issueInstant = $now->format('Y-m-d\TH:i:s\Z');

		$samlXML = <<<SAMLXML
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="$ID" Version="2.0" IssueInstant="$issueInstant" Destination="$AafSsoUrl" AssertionConsumerServiceURL="$AssertionConsumerServiceURL" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
	<saml:Issuer>portail-agents</saml:Issuer>
	<samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/>
</samlp:AuthnRequest>
SAMLXML;

		$samlRequest = base64_encode(gzdeflate($samlXML, 9));

		$location = $AafSsoUrl."?SAMLRequest=".
			urlencode($samlRequest)."&RelayState=".
			urlencode($AssertionConsumerServiceURL);

		header("Location: $location");
	}
}

//
// parentPortalIdp
// Handles AAF-SSO SAML requests
//

function parentPortalIdp() {
	global $CONFIG, $log, $selfURL;

	$service = "";
	if(isset($_REQUEST['service'])) {
		$service = $_REQUEST['service'];
	}

	// SAML authentication done, decode response and logon
	if(isset($_REQUEST['SAMLResponse'])) {
		$samlResponse = base64_decode($_REQUEST['SAMLResponse']);

		$doc = new DOMDocument('1.0');
		$doc->loadXML($samlResponse);
		$id = $doc->documentElement->getAttribute('InResponseTo');

		file_put_contents("/tmp/samlResponse-parents", $samlResponse);

		// verify that the digest present in the signature is correct
		$refNodeDoc = verifyDigest($samlResponse);
		if($refNodeDoc === null) {
			$log->LogError("parentPortalIdp invalid DigestValue in: $samlResponse");
			viewLoginFailure($service, "Les données d'authentification de l'Académie ne sont pas valides et ne nous permette pas de vous identifier. Rééssayez plus tard ou contacter votre administrateur.");
			return;
		}
		// verify the digital signature
		if(!verifySignature($samlResponse, $CONFIG['PARENTS_AAF_SSO_CERT'])) {
			$log->LogError("parentPortalIdp invalid digital signature in: $samlResponse");
			viewLoginFailure($service, "Les données d'authentification de l'Académie ne sont pas valides et ne nous permette pas de vous identifier. Rééssayez plus tard ou contacter votre administrateur.");
			return;
		}

		$xpath = new DOMXpath($refNodeDoc);
		$xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

		// TODO: handle multiples values
		// search for the FrEduVecteur attribut
		$FrEduVecteur = $xpath->query('//saml:Attribute[@Name="FrEduVecteur"]/saml:AttributeValue')[0]->nodeValue;
		if(!isset($FrEduVecteur) || empty($FrEduVecteur)) {
			$log->LogDebug("parentPortalIdp FrEduVecteur '$FrEduVecteur' not found");
			viewLoginFailure($service, "Les données d'authentification de l'Académie ne nous permette pas de vous identifier. Rééssayez plus tard ou contacter votre administrateur.");
			return;
		}

		// decode the target service from the InResponseTo attribute
		$decode = hex2bin(substr($id, 1));
		$pos = strpos($decode, ':');
		if($pos) {
			$service = substr($decode, $pos+1);
		}

		$log->LogDebug("parentPortalIdp AAF authentication done for '$FrEduVecteur' for service $service");

		// search the user by its FrEduVecteur
		$tab = explode('|', $FrEduVecteur);
		if(count($tab) !== 5) {
			$log->LogError("parentPortalIdp invalid FrEduVecteur '$FrEduVecteur'");
			viewLoginFailure($service, "Les données d'authentification de l'Académie ne nous permette pas de vous identifier. Rééssayez plus tard ou contacter votre administrateur.");
			return;
		}
		$type = $tab[0];
		$lastname = $tab[1];
		$firstname = $tab[2];
		$id_sconet = $tab[3];
		$uai = $tab[4];

		// if parents
		if(($type == '1') || ($type == '2')) {
			$ch = curl_init();
			$url = $CONFIG['API_URL'].'api/sso/parents?nom='.urlencode($lastname)."&prenom=".urlencode($firstname)."&id_sconet=".urlencode($id_sconet)."&uai=".urlencode($uai);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_ENCODING ,"");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERPWD, $CONFIG['API_KEY'] . ":" . $CONFIG['API_PASS']);
			$data = curl_exec($ch);
			if(curl_errno($ch) || (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200)) {
				$log->LogError("HTTP error with the request '$url'. Got: ".curl_error($ch));
				viewLoginFailure($service, "Un problème dans laclasse.com nous empêche de vous authentifier. Rééssayez plus tard ou contacter votre administrateur.");
				curl_close($ch);
				return;
			}
			curl_close($ch);

			$json = json_decode($data);
			// login with the found user login
			if(isset($json->Data) && is_array($json->Data) && (count($json->Data) > 0)) {
				CASLogin($json->Data[0]->login, $service);
			}
			else {
				$log->LogError("parentPortalIdp FrEduVecteur '$FrEduVecteur' not found in laclasse.com");
				viewLoginFailure($service, "Compte utilisateur non trouvé dans laclasse.com. Votre compte doit être provisionné dans laclasse.com avant de pouvoir vous connecter en utilisant votre compte Académique.");
			}
		}
		// if student
		else {
			$ch = curl_init();
			$url = $CONFIG['API_URL'].'api/sso/eleves?nom='.urlencode($lastname)."&prenom=".urlencode	($firstname)."&id_sconet=".urlencode($id_sconet)."&uai=".urlencode($uai);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_ENCODING ,"");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERPWD, $CONFIG['API_KEY'] . ":" . $CONFIG['API_PASS']);
			$data = curl_exec($ch);
			if(curl_errno($ch) || (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200)) {
				$log->LogError("HTTP error with the request '$url'. Got: ".curl_error($ch));
				viewLoginFailure($service, "Un problème dans laclasse.com nous empêche de vous authentifier. Rééssayez plus tard ou contacter votre administrateur.");
				curl_close($ch);
				return;
			}
			curl_close($ch);

			$json = json_decode($data);
			// login with the found user login
			if(isset($json->Data) && is_array($json->Data) && (count($json->Data) > 0)) {
				CASLogin($json->Data[0]->login, $service);
			}
			else {
				$log->LogError("parentPortalIdp FrEduVecteur '$FrEduVecteur' not found in laclasse.com");
				viewLoginFailure($service, "Compte utilisateur non trouvé dans laclasse.com. Votre compte doit être provisionné dans laclasse.com avant de pouvoir vous connecter en utilisant votre compte Académique.");
			}
		}
	}
	else {
		$AssertionConsumerServiceURL = $selfURL . "agentPortalIdp";
		// DANIEL: temporary until change done in AAF-SSO
		$AssertionConsumerServiceURL = "https://www.laclasse.com/saml/module.php/saml/sp/saml2-acs.php/parents-portal";
		$AafSsoUrl = $CONFIG['PARENTS_AAF_SSO_URL'];

		// encode the target service in the ID
		$ID = "_".bin2hex(generateRand(10).":".$service);		

		$now = new DateTime();
		$now->setTimeZone(new DateTimeZone("UTC"));
		$issueInstant = $now->format('Y-m-d\TH:i:s\Z');

		$samlXML = <<<SAMLXML
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="$ID" Version="2.0" IssueInstant="$issueInstant" Destination="$AafSsoUrl" AssertionConsumerServiceURL="$AssertionConsumerServiceURL" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">
	<saml:Issuer>portail-parents</saml:Issuer>
	<samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/>
</samlp:AuthnRequest>
SAMLXML;

		$samlRequest = base64_encode(gzdeflate($samlXML, 9));

		$location = $AafSsoUrl."?SAMLRequest=".
			urlencode($samlRequest)."&RelayState=".
			urlencode($AssertionConsumerServiceURL);

		header("Location: $location");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Main starts here 
////////////////////////////////////////////////////////////////////////////////

$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : "";

setLanguage();

if($action == "") {
	showError(_("Aucune action trouvée."));
	die();
}

// Basic application routing
switch(strtolower($action)) {
	case "login":
		login();
		break;
	case "logout":
		logout();
		break;
    case "proxyvalidate":
		serviceValidate();
		break;
	case "servicevalidate":
    case "p3/servicevalidate":
		serviceValidate();
		break;
	case "samlvalidate":
		samlValidate();
		break;
	case 'proxy':
		proxy();
		break;
	case 'serviceticket':
		serviceTicket();
		break;
	case 'agentportalidp':
		agentPortalIdp();
		break;
	case 'parentportalidp':
		parentPortalIdp();
		break;
	default:
		showError(_("Action inconnue."));
}

