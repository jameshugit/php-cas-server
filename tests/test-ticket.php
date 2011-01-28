<?php
/*
 * Test de la classe de génération de tickets.
 */

require_once('../config.inc.php');
require_once(CAS_PATH.'/lib/ticket.php'); 

$SERVICE  = 'Umpa-lumpa-Serv';
$USERNAME = 'charlie';

echo "<h1>TGT unit tests</h1>";

echo "<h2>Testing ticket creation</h2><ol>";

echo "<li>TGT creation for user $USERNAME...";
$tgt = new TicketGrantingTicket();
if ($tgt->create($USERNAME))
	echo "...OK</li>";
else
	echo "...FAILED!</li>";

echo "<li>TGT value...";
$key = $tgt->key();
echo $key;
if ($key)
	echo "...OK</li>";
else
	echo "...FAILED</li>";


echo "</ol><h2>Testing ticket retrieval</h2><ol>";

echo "<li>Retrieving ticket $key from storage...";
$tgt = new TicketGrantingTicket();
if ($tgt->find($key)) 
	echo "...OK</li>";
else
	echo "...FAILED!</li>";

echo "<li>Stored ticket ID...";
echo $tgt->key();
if ($tgt->key() == $key) 
	echo "...OK</li>";
else
	echo "...FAILED!</li>";

echo "<li>Ticket was stored for user...";
echo $tgt->username();
if ($tgt->username() == $USERNAME)
	echo "...OK</li>";
else
	echo "...FAILED!</li>";

echo "</ol><h2>Testing ticket removal</h2><ol>";

echo "<li>Retrieving previous ticket from storage...";
$tgt = new TicketGrantingTicket();
if ($tgt->find($key)) 
	echo "...OK</li>";
else
	echo "...FAILED!</li>";

echo "<li>Removing ticket ID $key...";
if ($tgt->delete())
	echo "...OK</li>";
else
	echo "...FAILED!</li>";

echo "<li>Checking that ID $key has been removed...";
$tgt = new TicketGrantingTicket();
if (!$tgt->find($key)) 
	echo "...OK</li>";
else
	echo "...FAILED!</li>";

echo "</ol>";
$tgt = $key;

echo "<h1>ST unit tests</h1>";

echo "<h2>Testing ticket creation</h2><ol>";

echo "<li>ST creation for TGT $tgt and service $SERVICE...";
$st = new ServiceTicket();
if ($st->create($tgt, $SERVICE, $USERNAME))
	echo "...OK</li>";
else
	echo "...FAILED!</li>";

echo "<li>Getting ST key...";
$stkey = $st->key();
echo $stkey;
if (strlen($stkey) == 29) 
	echo "...OK</li>";
else
	echo "...FAILED!</li>";

echo "<li>ST lookup for ST key $stkey and service $SERVICE...";
$st = new ServiceTicket();
if ($st->find($stkey))
	echo "...OK</li>";
else {
	echo "...FAILED!</li>";
}


echo "<li>ST checking match for ST key $stkey and service $SERVICE...";
if ($st->key() == $stkey)
	echo "...OK for user " . $st->username() . "</li>";
else {
	echo "...FAILED!</li>";
	exit;	
}

echo "<li>Ensuring second ST lookup for TGT $tgt and service $SERVICE fails...";
$st = new ServiceTicket();
if (!$st->find($tgt))
	echo "...OK</li>";
else {
	echo "...FAILED!</li>";
	exit;	
}

echo "</ol>";

echo "<h1>LT unit tests</h1>";

echo "<h2>Testing ticket creation</h2><ol>";

echo "<li>Creating a login ticket...";
$lt = new LoginTicket();
$lt->create();
$ltkey = $lt->key();
if ($ltkey)
	echo "...OK Login Ticket is " . $ltkey . "</li>";
else {
	echo "...FAILED!</li>";
	exit;	
}
echo "</ol>";

echo "<h2>Testing ticket retrieval</h2><ol>";

echo "<li>Retrieving a login ticket like $ltkey...";
$lt = new LoginTicket();
if ($lt->find($ltkey))
	echo "...OK Login Ticket found .</li>";
else {
	echo "...FAILED!</li>";
	exit;	
}

echo "</ol>";

//------------------------------------------------------------------------------------
// Performance testing.
//------------------------------------------------------------------------------------
echo "<h1>Performances unit tests</h1>";
$i = 0;
$nb = 1000;
error_reporting(0);

list($usec, $sec) = explode(' ', microtime());
$script_start = (float) $sec + (float) $usec;
///
while ($i < $nb) {
	$tgt = new TicketGrantingTicket();
	$tgt->create($USERNAME.$i);
	$st = new ServiceTicket();
	$st->create($tgt, $SERVICE, $USERNAME.$i);
	$t[$i] = $st->key();
	$i++;
}
///
list($usec, $sec) = explode(' ', microtime());
$script_end = (float) $sec + (float) $usec;
$elapsed_time = round($script_end - $script_start, 5);
echo "<h2>Creating $nb tickets in $elapsed_time seconds...</h2><ol>";

$randomTicket = rand(0, $nb);
$ltkey = $t[$randomTicket];
echo "<li>Retrieving the ticket #'$randomTicket' ($ltkey) within $nb...</li>";

list($usec, $sec) = explode(' ', microtime());
$script_start = (float) $sec + (float) $usec;
///
$st = new ServiceTicket();
$st->find($ltkey);
///
list($usec, $sec) = explode(' ', microtime());
$script_end = (float) $sec + (float) $usec;
$elapsed_time = round($script_end - $script_start, 5);

echo "<li>Ticket found : ".$st->key()." in $elapsed_time seconds.</li>";
echo "</ol>";


echo "<H2>END// All tests successful !</h2>";


?>
