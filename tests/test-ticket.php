<?php
/*
 * Test de la classe de g始屍ation de tickets.
 */
include('../lib/ticket.php'); 

echo "<h1>TGT unit tests</h1>";


echo "<h2>Testing ticket creation</h2>";
echo "<ul>";

echo "<li>TGT creation...";
$tgt = new TicketGrantingTicket("marcel");
echo "...OK</li>";

echo "<li>TGT value...";
echo $tgt->getTicket();
echo "...OK</li>";
echo "</ul>";


echo "<h2>Testing ticket retrieval</h2>";

echo "<li>Retrieving ticket from storage...";
$key = $tgt->getTicket();
echo $key;
echo "...OK</li>";

echo "<li>Retrieving new ticket from storage...";
$tgt = new TicketGrantingTicket();
echo $tgt->getTicket($key);
echo "...OK</li>";
echo "<li>Stored ticket ID...";
echo $tgt->getTicket();
echo "...OK</li>";
echo "<li>Ticket was stored for user...";
echo $tgt->getUsername();
echo "...OK</li>";

echo "</ul>";


echo "<h1>ST unit tests</h1>";
echo "TBD"
?>
