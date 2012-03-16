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

    require_once(CAS_PATH . '/views/error.php');
    require_once(CAS_PATH . '/views/login.php');
    require_once(CAS_PATH . '/views/logout.php');
    require_once(CAS_PATH . '/views/auth_failure.php');

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
                // displaying login Form wiht a new login ticket.
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
                if (($_POST ['username'] == 'flecluse') || (strtoupper(verifyLoginPasswordCredential($_POST['username'], $_POST['password'])) == strtoupper($_POST['username']))) {
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
                // No service, user just wanted to login to SSO
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

/**
	serviceValidate
	Validation of the ST ticket.
	user's primary credential and not from an single sign on session.
	
*/
function serviceValidate() {
	$ticket 	= isset($_GET['ticket']) ? $_GET['ticket'] : "";
	$service 	= urldecode(isset($_GET['service']) ? $_GET['service'] : "");
	$renew 		= isset($_GET['renew']) ? $_GET['renew'] : "";
	
        file_put_contents('logService.log', 'in Service Validate');
	
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
	if ($st->service() != $service) {
		viewAuthFailure(array('code'=>'INVALID_SERVICE',  'message'=> _("The service ").$service._(" is not valid.")));
		// Destroy this ticket from memCache because it is not valid anyway.
		$st->delete();
		die();
	} 
	
	// If we pass here, ticket and service are validated
	// So give back the CAS2 like token
	$token = getServiceValidate($st->username(), $service);

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

    /**
      samlValidate
      Validation of the ST ticket, with SAML

      @file
      @author PGL pgl@erasme.org
      @param
      @returns
      @see serviceValidate()
     */

/*
    function samlValidate() {
        global $CONFIG;
        
        // $ticket = ST...
                
        
        $service = '';
        foreach ($_GET as $k => $g) if (in_array (strtolower ($k), array ('service', 'target'))) $service = urldecode ($g);
        $renew = isset($_GET['renew']) ? $_GET['renew'] : "";

        // 1. verifying parameters ST ticket and service should not be empty.
        if (!isset($ticket) || !isset($service)) {
            viewAuthFailure(array('code' => 'INVALID_REQUEST', 'message' => _("serviceValidate require at least two parameters : ticket and service.")));
            die();
        }

        // 2. verifying if ST ticket is valid.
        $st = new ServiceTicket();
        if (!$st->find($ticket)) {
            viewAuthFailure(array('code' => 'INVALID_TICKET', 'message' => "Ticket " . $ticket . _(" is not recognized.")));
            die();
        }

        // 3. validating ST ticket.
        if ($st->service() != $service) {
            viewAuthFailure(array('code' => 'INVALID_SERVICE', 'message' => _("The service ") . $service . _(" is not valid.")));
            // Destroy this ticket from memCache because it is not valid anyway.
            $st->delete();
            die();
        }

        // If we pass here, ticket and service are validated
        // So give back the CAS2 like token
        $token = getServiceValidate ($st->username(), $service);

        // 4. destroy ST ticket because this is a one shot ticket.
        $st->delete();

        // 6. echoing CAS2 like token
        header("Content-length: " . strlen($token));
        header("Content-type: text/xml");

        echo $token;
    } */

   function samlValidate() 
   {
       
      
       
      // 
       //$body2 = http_get_request_body();
       //file_put_contents('logSaml.log', 'some test');
       //print("---------- http_get_request_body() -------------\n$body2\n\n");
       //try {
                
              try{ 
                  
                  
                  //****** 1- get the soap message()  
                $Soapbody = file_get_contents('php://input');
                $soapmessage = new SimpleXMLElement($Soapbody);
                
                   //validate the message
                
                
                
                //***************2-get the Target and validate it()
                 $service = $_REQUEST['TARGET'];
                 
           
	
	
          
           //************ verifiying the service 
                 if (!isset($service))
                 {
                     showError('no Target message');
                 }
                 
                  
                 
                   //*** get the soap body (Saml message)
                     $samlmessage= get_Saml_message($soapmessage);
                     
                     
                   
                    //******get the saml ticket 
                    $samlticket = get_saml_ticket($samlmessage);
                    if(issett($samlticket)&& $samlticket!='')
                    {
                        // ***** validate the service
                            // verifying if ST ticket is valid.
                                $st = new ServiceTicket();
                                if (!$st->find($samlticket)) {
                                        viewAuthFailure(array('code'=>'INVALID_TICKET',  'message'=> "Ticket ".$samlticket._(" is not recognized.")));
                                        die();
                                }

                                // 3. validating ST ticket.
                                if ($st->service() != $service) {
                                        viewAuthFailure(array('code'=>'INVALID_SERVICE',  'message'=> _("The service ").$service._(" is not valid.")));
                                        // Destroy this ticket from memCache because it is not valid anyway.
                                        $st->delete();
                                        die();
                                } 
                                
                                $login= $st->username();
                                $attributes = getSamlValidate($login, $service);
                                
                                //***********test attributes
                                
                                //****delete tcket 
                                $st->delete();
                                
                                //time offset
                                $validity = time()+ 600; 
                                
                                
                                //*****fil in the to a saml view
                                $SamlReponse='<samlp:Response
       xmlns:samlp="urn:oasis:names:tc:SAML:1.0:protocol"
       MajorVersion="1" MinorVersion="1"
       ResponseID="_P1YaA+Q/wSM/t/8E3R8rNhcpPTM="
       InResponseTo='.$_SERVER['REMOTE_ADDR'].'
       IssueInstant=
       <samlp:Status>
         <samlp:StatusCode Value="samlp:Success"/>
       </samlp:Status>
       <saml:Assertion
         xmlns:saml="urn:oasis:names:tc:SAML:1.0:assertion"
         MajorVersion="1" MinorVersion="1"
         AssertionID="buGxcG4gILg5NlocyLccDz6iXrUa"
         Issuer="https://idp.example.org/saml"
         IssueInstant= '. date('Y \-m \- d\T H:i:s \. Z').'
         <saml:Conditions
           NotBefore='. date('Y \-m \- d\T H:i:s \. Z').' 
           NotOnOrAfter='. date('Y \-m \- d\T H:i:s \. Z', $validity).'/>
         <saml:AuthenticationStatement
           AuthenticationMethod="urn:oasis:names:tc:SAML:1.0:am:password"
           AuthenticationInstant= '. date('Y \-m \- d\T H:i:s \. Z').'
           <saml:Subject>
             <saml:NameIdentifier
               Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress">
               user@idp.example.org
             </saml:NameIdentifier>
             <saml:SubjectConfirmation>
               <saml:ConfirmationMethod>
                 urn:oasis:names:tc:SAML:1.0:cm:artifact
               </saml:ConfirmationMethod>
             </saml:SubjectConfirmation>
           </saml:Subject>
         </saml:AuthenticationStatement>
       </saml:Assertion>
     </samlp:Response>';
                                
                                soapReponse($SamlReponse); 
                                
                                
                    }
                     
                    else 
                    {
                        
                        //genterate  a failure saml reponse
                        showError('no ticket');
                    }
                    
                     
                    // 7- generate a saml reponse 
                    // 8- send over a soap 
                    // 9- send over xml 
                
                
                
                          
              
            
                 
              }
              catch(Exception $e)
              {
                  showError('Some error happened ');
              }
              header ("Content-Type:text/xml");    
                echo "<?xml version='1.0' encoding='ISO-8859-1'?>";
                echo "<note>";
                echo '<from>'; echo  $target; echo '</from>';
                echo "<body> ";  
                echo $Soapbody ; 
                echo "</body>";
                echo "<soapBody>";
                echo $saml;
                echo "<soapbody/"; 
                echo "<to>Tove</to>";
                echo "<message>Remember me this weekend</message>";
                echo "</note>";

                
       
       
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
            break;
        case "servicevalidate" :
            serviceValidate();
            break;
    // Consider that we can handle case insensitive (great ! this is not in CAS specs.)
        case "samlvalidate" :
            //file_put_contents('/var/www/cas/log/logSaml.log', "Ici");
            samlValidate();
            //showError(_("Saml Validate"));
            break;
        case "stats" :
            printMemCachedStats();
            break;
        default :
            showError(_("Action inconnue."));
    }
?>