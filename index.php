<?php

/*

Application Controller


 * logic flow

  /login : 
	 * client has no TGT => present login/pass form, store initial GET parameters somewhere (service)
   * client POSTing credentials => check credentials, send TGT, 
   * client has valid TGT, renew parameter set to true => destroy TGC, present login form
   * client has valid TGT => send a redirect to 'service' url with newly created ST

  /logout :
	 * client has valid TGT => destroy client TGC, display link to 'url' 
   * client has no TGC => display blank pahe

  /serviceValidate :
	 * checks that ST is valid for service S

 * 'action' parameter is internal for routing purposes between login/logout/serviceValidate

*/ 


function login() {
}

function logout() {
}

function serviceValidate() {
}

/*
 * showError
 * Loads error template and display errors
 */
function showError($msg) {
	require_once("views/error.php");

	Views::error($msg);
}



// Merging GET & POST so lookups are easier
$parameters = array_merge($_GET, $_POST);

// Basic app routing
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
	showError("");
}

