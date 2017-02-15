<?php
/**
 * @mainpage php-cas-server 
 * @section What What ?
 * php-cas-server is a pure-PHP CAS 2.0 server implementation
 *
 * @section Why Why ?
 * While other implementations exist, this one is designed whith the following goals in mind :
 * - SuckLess(TM) technologies : no-java and other brainfucking stuff here, just LAMP
 * - Kiss : simple design, no big software engineering bulshitting, just some code that works
 * 
 * @section Installation Installation
 *
 * @section Caveats Caveats
 * This implementation doesn't support CAS proxy stuff. We just don't care. If you need it
 * just fork off !
 *
 * @section License License
 * This fine piece of code is AGPL licensed
 * 
 * @verbatim
 * See LICENSE File for more information
 * @endverbatim
 *
 */

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
require_once('config.inc.php');

require_once('lib/functions.php');
require_once('lib/ticket.php');
require_once('lib/Utilities.php');
require_once('lib/saml/binding/HttpSoap.php');
require_once('lib/KLogger.php');

require_once('views/error.php');
require_once('views/login.php');
require_once('views/logout.php');
require_once('views/auth_failure.php');
require_once('views/saml_pronote_token.php');

/**
 * login
 * Handles sso login requests.
 *
 * @returns void
 */
function login() {
	global $CONFIG;
	$log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
	$log->LogDebug("Login Function is called");

	$log->LogDebug("REQUEST_URI". $_SERVER['REQUEST_URI']);
	$request_uris = explode("?", $_SERVER['REQUEST_URI']);
	$selfurl = $request_uris[0];

	$service = isset($_REQUEST['service']) ? $_REQUEST['service'] : false;

	$log->LogDebug("selfurl:  $selfurl");
	$log->LogDebug("service:  $service");

	$authentication_done = false;
	$authentication_error = false;
	$ticket = '';
	$username = '';

	// user is trying to post credentials
	if(array_key_exists('username', $_POST)) {

		//  user should have posted a valid LoginTicket.
		//  => check credentials
		//  => send TGT
		//  => redirect to login
		// create database provider
		$log->LogDebug('user is trying to post credentials');
		$factoryInstance = new DBFactory();
		$db = $factoryInstance->createDB($CONFIG['DATABASE'], BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME);

		if ((strtoupper($db->verifyLoginPasswordCredential($_POST['username'], $_POST['password'])) == strtoupper($_POST['username']))) {
			// credentials ok
			$log->LogDebug('credentials are valid, generate a TGC');
			$tgt = new TicketGrantingTicket();
			$tgt->create($_POST['username']);
			$log->LogDebug("Generated ticket: ".$tgt->key());

			// send TGC
			setcookie("CASTGC", $tgt->key(), 0, "/");
			$log->LogDebug("CASTGC cookie is set succesfully: ".$tgt->key());

			$ticket = $tgt->key();
			$username = $_POST['username'];
			$authentication_done = true;
		}
		else {
			// credentials failed
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
		viewLoginFailure(array('service' => $service, 'action' => $selfurl));
	}
	// else display the login form
	else {
		viewLoginForm(array('service' => $service, 'action' => $selfurl));
	}
}

/**
 * logout
 * Logout handler
 * @return void
 */
function logout() {
	global $CONFIG;
	$log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
	$log->LogDebug("Logout function is called");

	if (array_key_exists('CASTGC', $_COOKIE)) {
		// Remove TGT
		$tgt = new TicketGrantingTicket();
		$tgt->find($_COOKIE["CASTGC"]);
		$tgt->delete();

		$log->LogDebug("LOGOUT_SUCCES".$tgt->username()."");

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
		viewLogoutSuccess(array('url' => SERVICE));
	return;
}

/**
 * serviceValidate
 * Validation of the ST ticket.
 * user's primary credential and not from an single sign on session.
 */
function serviceValidate() {
    global $CONFIG;
    $log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
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

    // 3. validating ST ticket.
//	if ($st->service() != $service) {
//		viewAuthFailure(array('code'=>'INVALID_SERVICE',  'message'=> _("The service ").$service._(" is not valid.")));
//		// Destroy this ticket from memCache because it is not valid anyway.
//		$st->delete();
//		die();
//	} 

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
    $factoryInstance = new DBFactory();
    $db = $factoryInstance->createDB($CONFIG['DATABASE'], BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME);
    $token = $db->getServiceValidate($st->username(), $service, $pgtIou);
    $log->LogDebug("call getServiceValidate");

    // 4. destroy ST ticket because this is a one shot ticket.
    $st->delete();
    $log->LogDebug("Delete the service ticket:" . $st->key() . "");
    $log->LogDebug("Response of serviceValidate:$token");

    // 6. echoing CAS2 like token
    header("Content-length: " . strlen($token));
    header("Content-type: text/xml");

    echo $token;
    $log->LogDebug("End of ServiceValidate ...");
}

/**
 * serviceTicket sends a ticket directly to a destination site
 */
function serviceTicket(){
	global $CONFIG; 
	//start debugging
	$log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
	$log->LogInfo("Service Ticket Function is called");
	$log->LogDebug("sent cookies:".print_r($_COOKIE,true));
	$log->LogDebug("sent Request:".print_r($_REQUEST,true));
	$service = isset($_REQUEST['service']) ? $_REQUEST['service'] : false;
	$login = isset($_REQUEST['login'])?$REQUEST['login']: false; 
	$key =  isset($_REQUEST['key'])?$REQUEST['key']: false;
	if($service && $login && $key) {
		// verify signature
		$string_to_sign = "login=".$login.",service=".$service.",key=secret";
		$signature = sha1($string_to_sign);
		$log->LogDebug("signature= $signature");
	}
	if (!array_key_exists('CASTGC', $_COOKIE)) { 
		/*     * * user has no TGC ** */
		// redirect to login page
		/* user has no TGC 
		*=> notnecessary in this case 
		=> create a service ticket depending on the sourceservice
		=> redirect desinedservice url with service ticket  encoded in the url
		header("Location: " . url($destinedservice) . "serviceticket=" . ticket. ""); 
		*/
	}
	// user has TGC
	else {
		$tgt = new TicketGrantingTicket();
		/// @todo Well, do something meaningful...
		if (!$tgt->find($_COOKIE["CASTGC"])) {
			$log->LogError("Oops:Ticket Granting Ticket is not found");
			viewError("le cookie n est pas valide");
			die();
		}
		else { 
			if($service) { 
				// create a service ticket depending on the sourceservice
				// => redirect desinedservice url with service ticket  encoded in the url
				$st = new ServiceTicket();
				$st->create($tgt->key(), $service, $tgt->username());
				$log->LogDebug("Service Ticket :" . $st->key() . "");
				$log->LogDebug("Redirect to :" . url($service) . "ticket=" . $st->key() . "");
				header("Location: " . url($service) . "ticket=" .$st->key(). ""); 
			}
			// no service is found
			else {
				$log->LogDebug("le service nest pas valid");
			}
		}
	}
}

/**
 * proxy
 * Provides proxy ticket to proxied service
 *
 * @param  targetService and PGT
 * @returns  Proxy Ticket
 */
function proxy() {
    global $CONFIG;

    // prepare loggin parameters
    $log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
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
    header("Content-length: " . strlen($token));
    header("Content-type: text/xml");

    echo $token;
    $log->LogDebug("/proxy call ended..");
}

/**
 * proxyValidate
 * Validation of the ST ticket, with proxy features
 *
 * @param: service and ticket(PT)
 * @returns
 */
function proxyValidate() {
    global $CONFIG;

    //logging 
    $log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
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
        die();
    }

    // 2. verifying if PT ticket is valid.
    $ptkt = new ProxyTicket();
    if (!$ptkt->find($proxyticket)) {
        $log->LogError("INVALID_PROXY_TICKET" . $proxyticket . " is not recognized.");
        viewAuthFailure(array('code' => 'INVALID_PROXY_TICKET', 'message' => "Ticket " . $proxyticket . _(" is not recognized.")));
        die();
    }

    // 3. generate success reponse
    $token = proxyAuthSuccess($ptkt->username(), $ptkt->PGTIOU(), $ptkt->proxy());
    //delete tiket after validation
    $ptkt->delete();
    $log->LogDebug("proxy ticket deleted");
    $log->LogDebug("Response: $token");

    // 4. echoing  Proxy validation response 
    header("Content-length: " . strlen($token));
    header("Content-type: text/xml");

    echo $token;
    $log->LogDebug("Proxy Validate Ended ...");
}

/**
 * samlValidate
 * Validation of the ST ticket, with SAML
 *
 * @param
 * @returns
 */
function samlValidate() {
	global $CONFIG;
	$log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
	$log->LogDebug("Saml Validate is called ...");
	try {
		//  1- get the soap message()  
        $soapbody = extractSoap();
        $log->LogDebug("soap Request: $soapbody");

		// 2- get the Target and validate it()
		//************ verifiying the service 
		if(!isset($_REQUEST['TARGET'])) {
			$log->LogError("TARGET argument is needed !");
			throw new Exception('No authorized service was found !');
		}
        $service = $_REQUEST['TARGET'];
        $log->LogDebug("Service: $service");

		// 3- get the saml Request out of the SOAP message
		$samlRequest = extractRequest($soapbody);
		$log->LogDebug("Saml Request: $samlRequest");

        // 4- validate the SAML Request (removed because it takes long time to return)
//		$samloneschema = 'schemas/oasis-sstc-saml-schema-protocol-1.1.xsd';
//		$validRequest=validateSamlschema($samlRequest, $samloneschema); 
//                 
//		if(!$validRequest) {
//			throw new Exception('non valid SAML Request'); 
//		}

		//  5- get the Ticket (Artifact)
		// note: later there will be more than one artifact in the message
		// extractTicket gets only one artifact
		$ticket = extractTicket($samlRequest);
		$log->LogDebug("Extracted Ticket: $ticket");

		//  6- validate the ticket
		if(!isset($ticket) || $ticket == '') {
			$log->LogError($ticket." is not a  valid ticket !");
			throw new Exception('No valid ticket');
			// add some code to view saml error message.
		}

		// verifying if ST ticket is valid and return the attributes.
		$attr = validateTicket($ticket, $service);
		$log->LogDebug("Validate the ticket". $attr);

		$time = time() + 60 * 60;
		$validity = $time + 600;
		if(empty($attr)) {
			$log->LogError("user not recognized !");
			throw new Exception('user not recognized');
		}

		// DANIEL: CHANGE FOR SDET v5 FOR BRNE TEST
		// $attr['ENTPersonProfils'] = 'National_3';

        $nameIdentifier = $attr['user'];

		// generateSamlReponse
        $samlSuccess = PronoteTokenBuilder(0, $attr, $nameIdentifier, '');
        $log->LogDebug("Response: $samlSuccess");
        // 8- Send Soap Reponse  		

		// DANIEL: FOR DEBUG ONLY
		// file_put_contents("/var/log/sso/$nameIdentifier-samlSuccess", $samlSuccess);

		soapReponse($samlSuccess);
	}
	catch (Exception $e) {
		// generate  a failure saml reponse
		$samlFailure = PronoteTokenBuilder(8, null, null, $e->getMessage());
		$log->LogError("Error". $e->getMessage());
		soapReponse($samlFailure);
	}
}

/**
 * showError
 * Loads error template and display errors
 * @param msg Error message to display
 * @return void
 */
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

function validateSchema($samlRequest, $samlSchema) {
	assert('is_string($samlRequest)');
	assert('is_string($samlSchema)');
	set_error_handler(create_function('$errno, $errstr, $errfile, $errline', 'throw new PhpError($errno, $errstr, $errfile, $errline);'));

	try {
		$dom = new DOMDocument();
		$dom->loadXML($samlRequest);
		$validschema = $dom->schemaValidate($samlSchema);

		return $validschema;
	}
	catch (Exception $e) {
		if ($e->getCode() == 2)
			return 1;
		else
			return 0;
	}
	//return $validschema;
}

function validateTicket($ticket, $service) {
	global $CONFIG;
	$log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
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

	$factoryInstance = new DBFactory();
	$db = $factoryInstance->createDB($CONFIG['DATABASE'], BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME);
	$attributes = $db->getSamlAttributes($login, $service);

	if (empty($attributes))
		throw new Exception('empty attributes');

	// test attributes
    // delete ticket 
	$st->delete();
	$log->LogDebug("attributes were found");
	return $attributes;
}

/* * *****************************************************************************
 * 'Main' starts here 
 * ***************************************************************************** */

$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : "";

setLanguage();

if($action == "") {
	showError(_("Aucune action trouv&eacute;e."));
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
	default:
		showError(_("Action inconnue."));
}

