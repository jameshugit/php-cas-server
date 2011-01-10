<?php

/*

Application Controller


 * logic flow

  /login : 
   * client has no TGT => present login/pass form, store initial GET parameters somewhere (service)
   * client POSTing credentials => check credentials, send TGT, redirect to login
   * client has valid TGT, renew parameter set to true => destroy TGC, present login form
   * client has valid TGT => send a redirect to 'service' url with newly created ST

  /logout :
   * client has valid TGT => destroy client TGC, display link to 'url' 
   * client has no TGC => display blank page

  /serviceValidate :
	 * checks that ST is valid for service S

 * 'action' parameter is internal for routing purposes between login/logout/serviceValidate

*/ 



function login() {
	if (!array_key_exists('CASTGC',$_COOKIE)) {
		// user has no TGC
		if (!array_key_exists('username',$_POST)) {
			// user has no TGC and is not trying to post credentials => present login/pass form, store initial GET parameters somewhere (service)
			require_once("views/login.php");
			viewLoginForm(array('SERVICE' => $_GET['service']));
			return;
		} else {
			// user has no TGC but is trying to post credentials => check credentials, send TGT, redirect to login
			if (($_POST['username'] == 'root') and $_POST['password'] == 'toortoor') {
				require_once("lib/ticket.php"); 
				$monTicket = new ticket();
				setcookie ("CASTGC", $monTicket->getTicketGrantingTicket(), 0);
			}
		}	
	}	
}

function logout() {
	require_once("views/logout.php");

	setcookie ("CASTGC", "", time() - 3600);

	if (array_key_exists('url', $_GET))
		viewLogout($_GET['url']);
	else 	
		viewLogout($_GET['url']);
}

function serviceValidate() {
}

/*
 * showError
 * Loads error template and display errors
 */
function showError($msg) {
	require_once("views/error.php");

	viewError($msg);
}



// Verify that this things is happening over https
// #TODO

// Merging GET & POST so lookups are easier
$parameters = array_merge($_GET, $_POST);

// Basic app routing
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
		showError("Unknown action");
	}
} else { // no action key
	showError("Action not set");
}

