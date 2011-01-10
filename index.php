<?php

require_once('config.php');
require_once('views/error.php');

/*

Application Controller


 * logic flow

  /login : 
   * client has no TGT => present login/pass form, store initial GET parameters somewhere (service)
   * client POSTing credentials => check credentials, send TGT, redirect to login
   * client has TGT, renew parameter set to true => destroy TGC, present login form
   * client has valid TGT => send a redirect to 'service' url with newly created ST

  /logout :
   * client has valid TGT => destroy client TGC, display link to 'url' 
   * client has no TGC => display blank page

  /serviceValidate :
   * checks that ST is valid for service S

 * 'action' parameter is internal for routing purposes between login/logout/serviceValidate

*/ 



function login() {
  $selfurl = str_replace('index.php/', 'login', $_SERVER['PHP_SELF']);
	$service = array_key_exists('service',$_GET) ? $_GET['service'] : '';

	require_once("views/login.php");

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
        $monTicket = new ticket();
        /* send TGC */
        setcookie ("CASTGC", $monTicket->getTicketGrantingTicket(), 0);
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
      setcookie ("CASTGC", FALSE, 0);
      $srv = array_key_exists('service',$_GET) ? $_GET['service'] : '';
      header("Location: $selfurl?service=$srv");
      return;
    }

    /* client has valid TGT
       => build a service ticket
       => send a redirect to 'service' url with newly created ST as GET param
    */

    // Assert validity of TGC
    /*    $m = new Memcached();
    $m->addServer('localhost', 11211);
    if ($m->get($_COOKIE['CASTGC']) == '') {
      
    }*/
    
    if (array_key_exists('service',$_GET)) {
      // TODO : build a service ticket
      header("Location: " . $_GET['service'] . "?ST=$st");
    } else {
      // No service, user just wanted to login to SSO
			require_once("views/login.php");
      viewLoginSuccess();
    }
  }
}

function logout() {
  require_once("views/logout.php");

  //setcookie ("CASTGC", "", time() - 3600);
	setcookie ("CASTGC", FALSE, 0);

  if (array_key_exists('url', $_GET))
    viewLogoutSuccess(array('url' => $_GET['url']));
  else  
    viewLogoutSuccess(array('url'=>''));
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

