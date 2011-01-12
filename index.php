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
require_once('lib/functions.php');
require_once('views/error.php');

/**
 * login
 * Handles sso login requests.
 *
 * @returns void
 */
function login() {
	$selfurl = str_replace('index.php/', 'login', $_SERVER['PHP_SELF']);
	$service = array_key_exists('service',$_GET) ? $_GET['service'] : false;
	
	require_once("views/login.php");

  // If service is null : no matter this ok for CAS.
  if ( $service && !isServiceAutorized($service)) {
  	showError(_("This application is not allowed to authenticate on this server"));
  	die();
  }

  if (!array_key_exists('CASTGC',$_COOKIE)) {     /*** user has no TGC ***/
    if (!array_key_exists('username',$_POST)) {
      /* user has no TGC and is not trying to post credentials :
         => present login/pass form, 
         => store initial GET parameters somewhere (service)
      */
      viewLoginForm(array('service' => $srv,
                          'action'  => $selfurl));
      return;
    } else {
      /* user has no TGC but is trying to post credentials
         => check credentials
         => send TGT
         => redirect to login
      */
      if (($_POST['username'] == 'root') and $_POST['password'] == 'toortoor') { 
        /* credentials ok */
        require_once("lib/ticket.php"); 
        $monTicket = new TicketGrantingTicket($_POST['username']);
        /* send TGC */
        setcookie ("CASTGC", $monTicket->getTicket(), 0);
        /* Redirect to /login */
				header("Location: $selfurl");
      } else { 
        /* credentials failed */
        viewLoginFailure(array('service' => $srv,
															 'action'  => $selfurl));
      }
    } 
  } else { /*** user has TGC ***/ 
    /* client has TGT and renew parameter set to true 
       => destroy TGC
       => present login form
    */
    require_once("lib/ticket.php"); 
    if (array_key_exists('renew',$_GET) && $_GET['renew'] == 'true') {
			$tgt = new TicketGrantingTicket();
			$tgt->getTicket($_COOKIE["CASTGC"]);
			$tgt->delete();
      setcookie ("CASTGC", FALSE, 0);
			if ($service) 
				header("Location: $selfurl?service=$service");
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
    if ($tgt->getTicket($_COOKIE["CASTGC"])) {
      
    }
    
    if ($service) {
      // TODO : build a service ticket
      header("Location: $service?ST=$st");
    } else {
      // No service, user just wanted to login to SSO
			require_once("views/login.php");
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
  require_once("views/logout.php");

	/* No cookie ? No logout ! */
  if (!array_key_exists('CASTGC',$_COOKIE)) {
		viewError(_("You are already connected"));
		return;
	}

	/* Remove cookie from client */
	setcookie ("CASTGC", FALSE, 0);

	/* If url param is in the GET request, we send it to the view
		 so a link can be displayed */
  if (array_key_exists('url', $_GET))
    viewLogoutSuccess(array('url' => $_GET['url']));
  else  
    viewLogoutSuccess(array('url'=>''));

	return;
}

function serviceValidate() {
}

/**
 * showError
 * Loads error template and display errors
 * @param msg Error message to display
 * @return void
 */
function showError($msg) {
  require_once("views/error.php");
  viewError($msg);

	return;
}

/*
 * 'Main' starts here 
 */

var_dump(getPrefLanguageArray());

/* Verify that this thing is happening over https
	 if we are using a production running mode.
	 HTTP can only be used in dev mode */
if ($CONFIG['MODE'] == 'prod') {
	if (! $_SERVER['HTTPS']) {
		require_once("views/error.php");
		viewError(_("Error : this script can only be used with HTTPS"));
		die();
	}
} else if ($CONFIG['MODE'] != 'dev') {
		require_once("views/error.php");
		viewError(_("Error : unknown running mode. Must be 'prod' or 'dev'"));
		die();
}

/** @todo Use the best locale for user
 * getPrefLanguageArray
 * putenv("LANG=$langage"); // On modifie la variable d'environnement
 * setlocale(LC_ALL, $langage); // On modifie les informations de localisation en fonction de la langue
	
 * $nomDesFichiersDeLangue = 'traductions'; // Le nom de nos fichiers .mo
	
 * bindtextdomain($nomDesFichiersDeLangue, "./locale"); // On indique le chemin vers les fichiers .mo
 * textdomain($nomDesFichiersDeLangue); // Le nom du domaine par défaut
 *
 * http://www.siteduzero.com/tutoriel-3-74650-un-site-multilingue-avec-gettext.html#ss_part_1
 */


/* Merging GET & POST so lookups are easier */
$parameters = array_merge($_GET, $_POST);

/* Basic application routing */
if (array_key_exists('action', $parameters)) {
  switch ($parameters['action']) {
  case "login" :
    login();
    break;
  case "logout" :
    logout();
    break;
  case "serviceValidate" :
    serviceValidate();
    break;
  default :
    showError(_("Unknown action"));
  }
} else { // no action key
  showError(_("Action not set"));
}


