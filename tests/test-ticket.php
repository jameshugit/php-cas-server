<?php
/*
 * Test de la classe de gŽnŽration de tickets.
 */
include('../lib/ticket.php'); 

echo "<h1>Tests unitaires de la classe de g&eacute;n&eacute;ration de tickets.</h1>";


$monTicket = new ticket();
echo "<br/> ST : ".$monTicket->getServiceTicket();
echo "<br/> TGT : ".$monTicket->getTicketGrantingTicket();
?>