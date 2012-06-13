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
     * This fine piece of code is WTFPL licensed
     * 
      @verbatim
      DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
      Version 2, December 2004

      Copyright (C) 2004 Sam Hocevar
      14 rue de Plaisance, 75014 Paris, France
      Everyone is permitted to copy and distribute verbatim or modified
      copies of this license document, and changing it is allowed as long
      as the name is changed.

      DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
      TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

      0. You just DO WHAT THE FUCK YOU WANT TO.
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
    require_once (CAS_PATH . '/lib/saml/binding/HttpSoap.php');
    //require_once (CAS_PATH . '/lib/saml/saml1.php');

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
        $selfurl = str_replace('index.php/', 'login', $_SERVER['PHP_SELF']);
        $service = isset($_REQUEST['service']) ? $_REQUEST['service'] : false;
        $loginTicketPosted = isset($_REQUEST['loginTicket']) ? $_REQUEST['loginTicket'] : false;


        if (!array_key_exists('CASTGC', $_COOKIE)) { /*     * * user has no TGC ** */
            if (!array_key_exists('username', $_POST)) {
                /* user has no TGC and is not trying to post credentials :
                  => present login/pass form,
                  => store initial GET parameters somewhere (service)
                 */
                // displaying login Form with a new login ticket.
                $lt = new LoginTicket();
                $lt->create();
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
                $factoryInstance = new DBFactory();
                $db=$factoryInstance->createDB($CONFIG['DATABASE'],BACKEND_DBUSER, BACKEND_DBPASS,BACKEND_DBNAME);


                $lt = new LoginTicket();
                // If the login Ticket is not valid, no need to go futher : redirect to login form

                if (!$lt->find($loginTicketPosted)) {
                    $lt->create();
                    viewLoginFailure(array('service' => $service,
                        'action' => $selfurl,
                        'errorMsg' => _("La session de cette page a expir&eacute;. r&eacute;-essayez en rafra&icirc;chissant votre page."),
                        'loginTicket' => $lt->key()));
                    return;
                }

                // @fl : Hack de dev : on est toujours authentifié !
                if (($_POST ['username'] == 'flecluse') || (strtoupper($db->verifyLoginPasswordCredential($_POST['username'], $_POST['password'])) == strtoupper($_POST['username']))) {
                    /* credentials ok */
                    $ticket = new TicketGrantingTicket();
                    $ticket->create($_POST['username']);

                    /* send TGC */
                    setcookie("CASTGC", $ticket->key(), 0);
                    /* Redirect to /login */
                    header("Location: " . url($selfurl) . "service=" . urlencode($service) . "");
                } else {
                    /* credentials failed */
                    // verify if we need a new login ticket
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
        } else { /*     * * user has TGC ** */
            /* client has TGT and renew parameter set to true 
              => destroy TGC
              => present login form
             */
            if (array_key_exists('renew', $_GET) && $_GET['renew'] == 'true') {
                $tgt = new TicketGrantingTicket();
                $tgt->find($_COOKIE["CASTGC"]);
                $tgt->delete();

                // Sendign cookie to the client
                setcookie("CASTGC", FALSE, 0);

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

            // Assert validity of TGC
            $tgt = new TicketGrantingTicket();
            /// @todo Well, do something meaningful...
            if (!$tgt->find($_COOKIE["CASTGC"])) {
                viewError("Oh noes !");
                die();
            }
            if ($service) {
                if (!isServiceAutorized($service)) {
                    showError(_("Cette application n'est pas autoris&eacute;e &agrave; s'authentifier sur le serveur CAS."));
                    die();
                }

                // build a service ticket
                $st = new ServiceTicket();
                $st->create($tgt->key(), $service, $tgt->username());

                // Redirecting for futher client request for serviceValidate
                header("Location: " . url($service) . "ticket=" . $st->key() . "");
            } else {
                // xNo service, user just wanted to login to SSO
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

  if (array_key_exists('CASTGC',$_COOKIE)) {
		/* Remove TGT */
		$tgt = new TicketGrantingTicket();
		$tgt->find($_COOKIE["CASTGC"]);
        writeLog("LOGOUT_SUCCESS", $tgt->username());
		$tgt->delete();

		/* Remove cookie from client */
		setcookie ("CASTGC", FALSE, 0);
  } else {
    writeLog("LOGOUT_FAILURE", "TGC_NOT_FOUND");
  }
  

	/* If url param is in the GET request, we send it to the view
		 so a link can be displayed */
  if (array_key_exists('url', $_GET))
    viewLogoutSuccess(array('url' => $_GET['url']));
  else  
    viewLogoutSuccess(array('url'=>''));
	return;
}

function loggedout() {

  if (array_key_exists('CASTGC',$_COOKIE)) {
		/* Remove TGT */
		$tgt = new TicketGrantingTicket();
		$tgt->find($_COOKIE["CASTGC"]);
        writeLog("LOGOUT_SUCCESS", $tgt->username());
		$tgt->delete();

		/* Remove cookie from client */
		setcookie ("CASTGC", FALSE, 0);
  } else {
    writeLog("LOGOUT_FAILURE", "TGC_NOT_FOUND");
  }
 
}

/**
	serviceValidate
	Validation of the ST ticket.
	user's primary credential and not from an single sign on session.
	
*/
function serviceValidate() {
  global $CONFIG;
	$ticket 	= isset($_GET['ticket']) ? $_GET['ticket'] : "";
	$service 	= urldecode(isset($_GET['service']) ? $_GET['service'] : "");
	$renew 		= isset($_GET['renew']) ? $_GET['renew'] : "";
	
	// 1. verifying parameters ST ticket and service should not be empty.
	if (!isset($ticket) || !isset($service)) {
		viewAuthFailure(array('code'=>'INVALID_REQUEST', 'message'=> _("serviceValidate require at least two parameters : ticket and service.")));
		die();
	}
	
	// 2. verifying if ST ticket is valid.
	$st = new ServiceTicket();
	if (!$st->find($ticket)) {
		viewAuthFailure(array('code'=>'INVALID_TICKET',  'message'=> "Ticket ".$ticket._(" is not recognized.")));
		die();
	}
	
	// 3. validating ST ticket.
//	if ($st->service() != $service) {
//		viewAuthFailure(array('code'=>'INVALID_SERVICE',  'message'=> _("The service ").$service._(" is not valid.")));
//		// Destroy this ticket from memCache because it is not valid anyway.
//		$st->delete();
//		die();
//	} 
	
	// If we pass here, ticket and service are validated
  // So give back the CAS2 like token
  $factoryInstance = new DBFactory();
  $db=$factoryInstance->createDB($CONFIG['DATABASE'],BACKEND_DBUSER, BACKEND_DBPASS,BACKEND_DBNAME);
	$token =$db->getServiceValidate($st->username(), $service);

	// 4. destroy ST ticket because this is a one shot ticket.
	$st->delete();
	
	// 6. echoing CAS2 like token
	header("Content-length: ".strlen($token));
	header("Content-type: text/xml");
	
	echo $token;
}

/**
	proxyValidate
	Validation of the ST ticket, with proxy features
	
	@file
	@author PGL pgl@erasme.org
	@param 
	@returns
*/
function proxyValidate() {
	// @todo to be implemented if needed. I don't really thing it is neccesary ???
	echo "proxyValidate is not implemented yet !";
	die();

}

/**
	samlValidate
	Validation of the ST ticket, with SAML
	
	@file
	@author PGL pgl@erasme.org
	@param 
	@returns
*/

   function samlValidate() 
   {
             try{
//                 1-   get the soap message()  
                $soapbody = extractSoap();
               
               
////                
////                
////                
////                // 2-   get the Target and validate it()
                 $service = $_REQUEST['TARGET'];
//               
//                 
////                 
////           
////	
////	
////          
////                 //************ verifiying the service 
               if (!isset($service))
                 {
                     
                     throw new Exception('No authorized service was found ! ');
                 }
//                 
//                 // 3-  get the saml Request out of the SOAP message
                 $samlRequest=extractRequest($soapbody);
//               
                 
                 $samloneschema = CAS_PATH.'/schemas/oasis-sstc-saml-schema-protocol-1.1.xsd' ; 
                 
                 
                 // 4- validate the SAML Request
//                 $validRequest=validateSamlschema($samlRequest, $samloneschema); 
//                 
//                if (!$validRequest)
//                {
//                    throw new Exception('non valid SAML Request'); 
//                }
                
                                
//                
                //  5- get the Ticket (Artifact)
                // note: later there will be more than one artifact in the message
                // extractTicket gets only one artifact
                $ticket = extractTicket($samlRequest); 
                //echo $ticket;   

//                  //  6- validate the ticket
                 if(!isset($ticket)|| $ticket=='')
                    {
                     
                     throw new Exception('non valid ticket'); 
                     // add some code to view saml error message.
                    }
                    
                    
                   // verifying if ST ticket is valid and return the attributes.
                  $attr = validateTicket($ticket, $service); 
                 
////                                
//                             
//                
                   $time= time()+60*60;
                   $validity = $time+600; 
////                   
                   
                   if(empty($attr)) 
                       throw new Exception ('user not recognized');
                   
                   
                   $nameIdentifier=$attr['user'];
                   
//                 generateSamlReponse
                   $samlSuccess= PronoteTokenBuilder(0, $attr, $nameIdentifier,''); 
                   
                   //$validResponse=validateSamlschema( $samlSuccess, $samloneschema);
//                   $validResponse=1;
                   
                   // 8- Send Soap Reponse  
//                   if ($validResponse)
                         soapReponse($samlSuccess);
//                       
//                   else
//                   {
//                       throw new Exception ('non valid Response'); 
//                   }
//                             
                             
                   
             }  
             catch(Exception $e){
                     
                    
            //genterate  a failure saml reponse
           
             $samlFailure=PronoteTokenBuilder(8, null,null,$e->getMessage());
                 
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
    
    function validateSchema($samlRequest,$samlSchema)
{
    assert('is_string($samlRequest)');
    assert('is_string($samlSchema)');
    set_error_handler(create_function(
    '$errno, $errstr, $errfile, $errline',
    'throw new PhpError($errno, $errstr, $errfile, $errline);'
        ));
    
    try{
    $dom = new DOMDocument(); 
    $dom->loadXML($samlRequest); 
    $validschema = $dom->schemaValidate($samlSchema);
    
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
    
 
function validateTicket($ticket, $service)
{  
    global $CONFIG;
    $attributes=array(); 
    $st = new ServiceTicket();
    if (!$st->find($ticket)) 
        
        {
            throw new Exception("Ticket ".$ticket._(" is not recognized.")); 
            
        }

    if ($st->service() != $service) {
           
            $st->delete();
            throw new Exception(("The service ").$service._(" is not valid.")); 
           
            
    } 
   
    $login= $st->username();
    //echo $service;
     $factoryInstance = new DBFactory();
     $db=$factoryInstance->createDB($CONFIG['DATABASE'],BACKEND_DBUSER, BACKEND_DBPASS,BACKEND_DBNAME); 
     $attributes =$db->getSamlAttributes($login, $service);
    
    if (empty($attributes))
        throw new Exception('empty attributes'); 
    
    //***********test attributes

    //****delete tcket 
    $st->delete();
    
    return $attributes; 
}

    /* * *****************************************************************************
     * 'Main' starts here 
     * ***************************************************************************** */

    $action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : "";

    defined ('IS_SOAP') || define ('IS_SOAP', strlen ($action) && array_key_exists ($action, array ('samlValidate')));
    
    /* Verify that this thing is happening over https
      if we are using a production running mode.
      HTTP can only be used in dev mode, SOAP can't be used in debug mode
     */
    if (($CONFIG['MODE'] == 'debug') && IS_SOAP) $CONFIG['MODE'] = 'dev';
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

    if ($action == "") {
        showError(_("Aucune action trouv&eacute;e."));
        die();
    }

    /* Basic application routing */
    switch (strtolower ($action)) {
        case "login" :
            login();
            break;
        case "logout" :
            logout();
            break;
    // Sittin' on the dock of the PT...
        case "proxyvalidate" :
    // Consider that we can handle case insensitive (great ! this is not in CAS specs.)
            //proxyvalidate();
            serviceValidate();
            break;
        case "servicevalidate" :
            serviceValidate();
            break;
    // Consider that we can handle case insensitive (great ! this is not in CAS specs.)
        case "samlvalidate" :
            samlValidate();
            //showError(_("Saml Validate"));
            break;
        case 'extractsoap': 

           echo( extractSoap());
           break;

        default :
            showError(_("Action inconnue."));
    }
?>
