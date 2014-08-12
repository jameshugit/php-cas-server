<?php
require_once('../../config.inc.php');
require ('../../lib/Slim/Slim.php');
require_once(CAS_PATH . '/lib/functions.php');
require_once(CAS_PATH . '/lib/ticket.php');
require_once(CAS_PATH . '/lib/Utilities.php');
require_once (CAS_PATH . '/lib/KLogger.php');

 // register Slim auto-loader
    \Slim\Slim::registerAutoloader();
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */

$app = new \Slim\Slim(array(
    'debug' => true));

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, and `Slim::delete`
 * is an anonymous function.
 */

// create  a TGT
$app->post('/tickets', function ()  use ($app) {
    global $CONFIG;
    $log = new KLogger ( $CONFIG['DEBUG_FILE'] ,$CONFIG['DEBUG_LEVEL']);
    $log->LogInfo("create a ticket granting ticket is called");

    // get params
    $username = $app->request()->params('username');
    $password = $app->request()->params('password');
    if (!$username || !$password){
      $log->LogDebug('error: missing params username or password');
      $app->halt(400, '{"error":"missing params username or password"}');
    }
    $log->LogDebug("recieved params: username " . $username . "password: ". $password);

    $factoryInstance = new DBFactory();
    $db = $factoryInstance->createDB($CONFIG['DATABASE'],BACKEND_DBUSER, BACKEND_DBPASS,BACKEND_DBNAME);
    $log->LogDebug("search user");
    $log->LogInfo($db->verifyLoginPasswordCredential($username, $password));
    if (strtoupper($db->verifyLoginPasswordCredential($username, $password)) == strtoupper($username))
    {
    /* credentials ok */
        $log->LogDebug('credentials are valid, generate a TGC');
        $ticket = new TicketGrantingTicket();
    $ticket->create($username);
        $var = $ticket->key();
        $log->LogDebug("Generated ticket granting ticket: $var");
        echo $var;
    }
    else
    {
      // return 401 error
      $log->LogInfo("user does not exist " .$db->verifyLoginPasswordCredential($username, $password));
      $app->contentType('application/json');
      $app->halt(401, '{"error":"401 not authorized"}');
    }
});

// Request for a Service Ticket
$app->post('/tickets/:tgt_id', function($tgt_id) use ($app){
  global $CONFIG;
    $log = new KLogger ( $CONFIG['DEBUG_FILE'] ,$CONFIG['DEBUG_LEVEL']);
    $log->LogInfo("create a service ticket is called");
  // search for a ticket granting ticket
  $tgt = new TicketGrantingTicket();
    if (!$tgt->find($tgt_id)) {
      $log->LogError("Oops:Ticket Granting Ticket is not found");
      $app->contentType('application/json');
      $app->halt(404, '{"error":"404 ticket not found"}');
    } else
    {
    //build a service ticket
      $st = new ServiceTicket();
      if ($app->request()->params('service') != null){
        $service = $app->request()->params('service');
      }
      else
      {
        $app->halt(400, '{"error":"missing param service"}');
      }
      $st->create($tgt->key(), $service, $tgt->username());
      $log->LogDebug("Service Ticket :" . $st->key() . "");
    echo $st->key();
  }
});

/*
$app->get('/tickets/:tgt', function($tgt){
    echo 'returns the service ticket for the'.$tgt; 
}); 
*/


// DELETE route
$app->delete('/tickets/:tgt_id', function ($tgt_id) {
  global $CONFIG;
  $log = new KLogger ( $CONFIG['DEBUG_FILE'] ,$CONFIG['DEBUG_LEVEL']);
    $log->LogInfo("delete a ticket granting ticket is called");
    $tgt = new TicketGrantingTicket();
    if ($tgt->find($tgt_id)){
      $tgt->delete();
        $log->LogDebug("the ticket is successfully deleted".$tgt->username()."");
    }
});

$app->run();

?>