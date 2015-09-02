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
  @verbatim
  See LICENSE File for more information
  @endverbatim
 *
 */
/**
 *
 * Main Application Controller for the CAS server
 *
 * @file index.php
 * @author Michel Blanc <mblanc@erasme.org>
 * @author Pierre-Gilles Levallois <pgl@erasme.org>
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

require_once(CAS_PATH . '/lib/functions.php');
require_once(CAS_PATH . '/lib/ticket.php');
require_once(CAS_PATH . '/lib/Utilities.php');
require_once(CAS_PATH . '/lib/saml/binding/HttpSoap.php');
require_once(CAS_PATH . '/lib/KLogger.php');
require_once(CAS_PATH . '/lib/Mobile_Detect.php');

require_once(CAS_PATH . '/views/error.php');
require_once(CAS_PATH . '/views/login.php');
require_once(CAS_PATH . '/views/logout.php');
require_once(CAS_PATH . '/views/auth_failure.php');
require_once(CAS_PATH . '/views/saml_pronote_token.php');

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

    $selfurl = str_replace('index.php', 'login', $_SERVER['PHP_SELF']);
    // Correct selfurl with virtual path if necessary
    if ( strpos($selfurl, CAS_URL ) === false ) {
      $selfurl = CAS_URL . $selfurl;
    }   
    $service = isset($_REQUEST['service']) ? $_REQUEST['service'] : false;
    $loginTicketPosted = isset($_REQUEST['loginTicket']) ? $_REQUEST['loginTicket'] : false;

    $log->LogDebug("selfurl:  $selfurl");
    $log->LogDebug("service:  $service");
    $log->LogDebug("loginTicketPosted: $loginTicketPosted");

    if (!array_key_exists('CASTGC', $_COOKIE)) { /*     * * user has no TGC ** */
        if (!array_key_exists('username', $_POST)) {
            /* user has no TGC and is not trying to post credentials :
              => present login/pass form,
              => store initial GET parameters somewhere (service)
             */
            // displaying login Form with a new login ticket.
            $lt = new LoginTicket();
            $lt->create();
            $loginticket = $lt->key();
            $log->LogDebug("user has no TGC and is not trying to post credentials, generate new login ticket:$loginticket");
            viewLoginForm(array('service' => $service,
                'action' => $selfurl,
                'loginTicket' => $lt->key()));
            return;
        } else {
            /* user has no TGC but is trying to post credentials
              user should have posted a valid LoginTicket.
              => check credentials
              => send TGT
              => redirect to login
             */
            // create database provider
            $log->LogDebug('user has no TGC but is trying to post credentials');
            $factoryInstance = new DBFactory();
            $db = $factoryInstance->createDB($CONFIG['DATABASE'], BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME);


            $lt = new LoginTicket();
            // If the login Ticket is not valid, no need to go futher : redirect to login form

            if (!$lt->find($loginTicketPosted)) {

                $lt->create();

                $log->LogError("the login Ticket is not valid, no need to go futher : redirect to login form");

                viewLoginFailure(array('service' => $service,
                    'action' => $selfurl,
                    'errorMsg' => _("La session de cette page a expir&eacute;. r&eacute;-essayez en rafra&icirc;chissant votre page."),
                    'loginTicket' => $lt->key()));
                return;
            }

            if ((strtoupper($db->verifyLoginPasswordCredential($_POST['username'], $_POST['password'])) == strtoupper($_POST['username']))) {
                /* credentials ok */
                $log->LogDebug('credentials are valid, generate a TGC');
                $ticket = new TicketGrantingTicket();
                $ticket->create($_POST['username']);
                $var = $ticket->key();
                $log->LogDebug("Generated ticket: $var");

                /* send TGC */
                //setcookie("CASTGC", $ticket->key(), 0);
		setcookie("CASTGC", $ticket->key(), 0, "/");
                $log->LogDebug("CASTGC cookie is set succesfully: ".$ticket->key());
                $log->LogDebug('redirect to login');

                /* Redirect to /login */
                header("Location: " . url($selfurl) . "service=" . urlencode($service) . "");
            } else {
                /* credentials failed */
              // verify if we need a new login ticket
                $user = $_POST['username'] ; 
                $log->LogError('Credentials faild for $user, try again');
                $newloginTicket = $loginTicketPosted;
                $lt = new LoginTicket();
                if (!$lt->find($loginTicketPosted)) {
                    $lt->create();
                    $newloginTicket = $lt->key();
                }

                viewLoginFailure(array('service' => $service,
                    'action' => $selfurl,
                    'loginTicket' => $newloginTicket));
            }
        }
    } else { /*** user has TGC ***/
        /* client has TGT and renew parameter set to true 
          => destroy TGC
          => present login form
         */
        $log->LogDebug('client has TGT and renew parameter set to true, destroy TGC, redirect to login form');
        if (array_key_exists('renew', $_GET) && $_GET['renew'] == 'true') {
            $tgt = new TicketGrantingTicket();
            $tgt->find($_COOKIE["CASTGC"]);
            $tgt->delete();

            // delete cookie
            setcookie("CASTGC", FALSE, 0, "/");

            // Choosing redirection
            if ($service)
                header("Location: " . url($selfurl) . "service=" . urlencode($service) . "");
            else
                header("Location: $selfurl");

            return;
        }
        /* client has valid TGT
          => build a service ticket
          => send a redirect to 'service' url with newly created ST as GET param
         */
        $log->LogDebug('client has valid TGT, build a service ticket');
        // Assert validity of TGC
        $tgt = new TicketGrantingTicket();
        if (!$tgt->find($_COOKIE["CASTGC"])) {
            // The TGC was nt found in storageTicket (perhaps it does not exist in Redis?)
            $log->LogError("Oops:Ticket Granting Ticket is not found");

            unset($_COOKIE['CASTGC']);
            setcookie('CASTGC', "", -1, '/');

            viewError("La session de cette page a expir&eacute;. r&eacute;-essayez en rafra&icirc;chissant votre page.");
            die(); 
        }
        if ($service) {
            if (!isServiceAutorized($service)) {
                $log->LogError("Oops:Service: $service is not authorized");
                showError(_("Cette application n'est pas autoris&eacute;e &agrave; s'authentifier sur le serveur CAS."));
                die();
            }

            // build a service ticket
            $st = new ServiceTicket();
            $st->create($tgt->key(), $service, $tgt->username());
            $log->LogDebug("Service Ticket :" . $st->key() . "");
            $log->LogDebug("Redirect to :" . url($service) . "&ticket=" . $st->key() . "");
            // Redirecting for futher client request for serviceValidate
            header("Location: " . url($service) . "ticket=" . $st->key() . "");
        } else {
            // xNo service, user just wanted to login to SSO
            $log->LogDebug("no Service was required");
            viewLoginSuccess();
        }
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
        /* Remove TGT */
        $tgt = new TicketGrantingTicket();
        $tgt->find($_COOKIE["CASTGC"]);
        $tgt->delete();

        $log->LogDebug("LOGOUT_SUCCES".$tgt->username()."");

        /* Remove cookie from client */
        setcookie("CASTGC", FALSE, 0, "/");
        $log->LogDebug("TGT is deleted...");
        $log->LogDebug("CASTGT is removed...");
    } else {
        $log->LogDebug("TGC_NOT_FOUND");
       // writeLog("LOGOUT_FAILURE", "TGC_NOT_FOUND");
    }


    /* If url param is in the GET request, we send it to the view
      so a link can be displayed */
    if (array_key_exists('url', $_GET)||array_key_exists('destination', $_GET))
	{
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
  serviceValidate
  Validation of the ST ticket.
  user's primary credential and not from an single sign on session.

 */
function serviceValidate() {
    global $CONFIG;
    $log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
    $log->LogDebug("Service Validate is called ...");

    $ticket = isset($_GET['ticket']) ? $_GET['ticket'] : "";
    $service = urldecode(isset($_GET['service']) ? $_GET['service'] : "");
    $renew = isset($_GET['renew']) ? $_GET['renew'] : "";

    $log->LogDebug("Request parameters .....");
    $log->LogDebug("Service Ticket :$ticket");
    $log->LogDebug("Service: $service");
    $log->logDebug("renew:$renew");

    //for proxy validate
    // 1. verifying parameters ST ticket and service should not be empty.
    if (!isset($ticket) || !isset($service)) {
        $log->LogError("INVALID_REQUEST: serviceValidate requires at least two parameters : ticket and service.");
        viewAuthFailure(array('code' => 'INVALID_REQUEST', 'message' => _("serviceValidate require at least two parameters : ticket and service.")));
        die();
    }

    // 2. verifying if ST ticket is valid.
    $st = new ServiceTicket();
    if (!$st->find($ticket)) {
        $log->LogError("INVALID_TICKET" . $ticket . " is not recognized.");
        viewAuthFailure(array('code' => 'INVALID_TICKET', 'message' => "Ticket " . $ticket . _(" is not recognized.")));
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
  *
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
      if($service && $login && $key){
        //verify signature
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
      } else { /*** user has TGC ***/
        $tgt = new TicketGrantingTicket();
        /// @todo Well, do something meaningful...
        if (!$tgt->find($_COOKIE["CASTGC"])) {
        $log->LogError("Oops:Ticket Granting Ticket is not found");
        viewError("le cookie n est pas valide");
        die();
        }else
        { 
          if($service){ 
            //create a service ticket depending on the sourceservice
            // => redirect desinedservice url with service ticket  encoded in the url
            $st = new ServiceTicket();
            $st->create($tgt->key(), $service, $tgt->username());
            $log->LogDebug("Service Ticket :" . $st->key() . "");
            $log->LogDebug("Redirect to :" . url($service) . "ticket=" . $st->key() . "");
            header("Location: " . url($service) . "ticket=" .$st->key(). ""); 
         }
          else{
            $log->LogDebug("le service nest pas valid");
            //no service is found
          }
        }
      }
}

/**
  proxy
  Provides proxy ticket to proxied service

  @file
  @author
  @param  targetService and PGT
  @returns  Proxy Ticket
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
  proxyValidate
  Validation of the ST ticket, with proxy features

  @file
  @author PGL pgl@erasme.org
  @param: service and ticket(PT)
  @returns
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
  samlValidate
  Validation of the ST ticket, with SAML

  @file
  @author PGL pgl@erasme.org
  @param
  @returns
 */
function samlValidate() {
    global $CONFIG;
    $log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
    $log->LogDebug("Saml Validate is called ...");
    try {

        //  1-   get the soap message()  
        $soapbody = extractSoap();
        $log->LogDebug("soap Request: $soapbody");

        // 2-   get the Target and validate it()
        $service = $_REQUEST['TARGET'];
        $log->LogDebug("Service: $service");
        //************ verifiying the service 
        if (!isset($service)) {
            $log->LogError($service."is not an authorized service  !");
            throw new Exception('No authorized service was found ! ');
        }

        // 3-  get the saml Request out of the SOAP message
        $samlRequest = extractRequest($soapbody);
        $log->LogDebug("Saml Request: $samlRequest");

        $samloneschema = CAS_PATH . '/schemas/oasis-sstc-saml-schema-protocol-1.1.xsd';
        // 4- validate the SAML Request (removed because it takes long time to return)
//                 $validRequest=validateSamlschema($samlRequest, $samloneschema); 
//                 
//                if (!$validRequest)
//                {
//                    throw new Exception('non valid SAML Request'); 
//                }
        //  5- get the Ticket (Artifact)
        // note: later there will be more than one artifact in the message
        // extractTicket gets only one artifact
        $ticket = extractTicket($samlRequest);
        $log->LogDebug("Extracted Ticket: $ticket");
        //echo $ticket;   
        //  6- validate the ticket
        if (!isset($ticket) || $ticket == '') {
            $log->LogError($ticket."is not a  valid ticket !");
            throw new Exception('No valid ticket');
            // add some code to view saml error message.
        }

        // verifying if ST ticket is valid and return the attributes.
        $attr = validateTicket($ticket, $service);
        $log->LogDebug("Validate the ticket");

        $time = time() + 60 * 60;
        $validity = $time + 600;
        if (empty($attr)) {
            $log->LogError("user not recognized !");
            throw new Exception('user not recognized');
        }


        $nameIdentifier = $attr['user'];

//                 generateSamlReponse
        $samlSuccess = PronoteTokenBuilder(0, $attr, $nameIdentifier, '');
        $log->LogDebug("Response: $samlSuccess");
        // 8- Send Soap Reponse  
//                   if ($validResponse)
        soapReponse($samlSuccess);
//                       
//                   else
//                   {
//                       throw new Exception ('non valid Response'); 
//                   }
//                             
    } catch (Exception $e) {

        //genterate  a failure saml reponse
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
    } catch (Exception $e) {

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
    //echo $service;
    $factoryInstance = new DBFactory();
    $db = $factoryInstance->createDB($CONFIG['DATABASE'], BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME);
    $attributes = $db->getSamlAttributes($login, $service);

    if (empty($attributes))
        throw new Exception('empty attributes');

    //***********test attributes
    //****delete tcket 
    $st->delete();
    $log->LogDebug("attributes were found");
    return $attributes;
}

/* * *****************************************************************************
 * 'Main' starts here 
 * ***************************************************************************** */

$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : "";

defined('IS_SOAP') || define('IS_SOAP', strlen($action) && array_key_exists($action, array('samlValidate')));

/* Verify that this thing is happening over https
  if we are using a production running mode.
  HTTP can only be used in dev mode, SOAP can't be used in debug mode
 */
if (($CONFIG['MODE'] == 'debug') && IS_SOAP)
    $CONFIG['MODE'] = 'dev';
if ($CONFIG['MODE'] == 'prod') {
    if (!$_SERVER['HTTPS']) {
        viewError(_("Erreur : ce script ne peut &ecirc;tre appel&eacute; qu'en HTTPS."));
        die();
    }
} else if ($CONFIG['MODE'] == 'debug') {
    echo("<h3>DEBUG MODE ACTIVATED</h3>");
} else if ($CONFIG['MODE'] != 'dev') {
    viewError(_("Erreur : mode d'exécution inconnu. Les modes possibles sont ") . "'prod' " . _("ou") . " 'dev' " . _("ou") . " 'debug'.");
    die();
}

setLanguage();

/*
  Storing device type in session
*/
$detect = new Mobile_Detect;
if(!isset($_SESSION['isMobile'])){
  $_SESSION['isMobile'] = $detect->isMobile();
}

if ($action == "") {
    showError(_("Aucune action trouv&eacute;e."));
    die();
}

/* Basic application routing */
switch (strtolower($action)) {
    case "login" :
        login();
        break;
    case "logout" :
        logout();
        break;
    // Sittin' on the dock of the PT...
    case "proxyvalidate" :
        // Consider that we can handle case insensitive (great ! this is not in CAS specs.)
        serviceValidate();
        break;
    case "servicevalidate" :
        serviceValidate();
        break;
    // Consider that we can handle case insensitive (great ! this is not in CAS specs.)
    case "samlvalidate" :
        samlValidate();
        break;

    case 'proxy':
        proxy();
        break;
    case 'serviceticket':
        serviceTicket();
        break;
    default :
        showError(_("Action inconnue."));
}
?>
