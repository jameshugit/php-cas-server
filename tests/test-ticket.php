<?php
/*
 * Test de la classe de génération de tickets.
 */

require_once('../config.inc.php');
require_once('../lib/ticket.php'); 

$SERVICE  = 'Umpa-lumpa';
$USERNAME = 'charlie';

echo "<h1>TGT unit tests</h1>";

echo "<h2>Testing ticket creation</h2><ul>";

echo "<li>TGT creation for user USER...";
$tgt = new TicketGrantingTicket();
if ($tgt->create($USERNAME))
	echo "...OK</li>";
else
	echo "...FAILED!</li>";

echo "<li>TGT value...";
$key = $tgt->key();

if ($key)
	echo "...OK</li>";
else
	echo "...FAILED</li>";


echo "</ul><h2>Testing ticket retrieval</h2><ul>";

echo "<li>Retrieving new ticket from storage...";
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

echo "</ul><h2>Testing ticket removal</h2><ul>";

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

echo "</ul>";
$tgt = $key;

echo "<h1>ST unit tests</h1>";

echo "<h2>Testing ticket creation</h2><ul>";

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

echo "<li>ST lookup for TGT $tgt and service $SERVICE...";
$st = new ServiceTicket();
$newst = $st->find($tgt, $SERVICE);
if ($newst == $stkey)
	echo "...OK</li>";
else
	echo "...FAILED!</li>";

echo "<li>Ensuring second ST lookup for TGT $tgt and service $SERVICE fails...";
$st = new ServiceTicket();
if (!$st->find($tgt, $SERVICE))
	echo "...OK</li>";
else
	echo "...FAILED!</li>";

echo "</ul>";


?>
