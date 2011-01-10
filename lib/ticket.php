<?php 
/*******************************************************************************
	@filename : ticket.php 
	@description : Classe de gestion des tickets ST et TGT. Comporte 2 mŽthodes 
	publiques de restitution de tickets.
	
	TGT-1-kFisTM5FtfkwZ6hnPBF96hQPAnpl9sd6oWpZdkR3HJTECFYbHY
*******************************************************************************/
class ticket {
	const ST_PREFIX = 'ST';
	const TGT_PREFIX = 'TGT';
	const SEPARATOR = '-';
	const NUMERICAL = "0123456789";
	const ALPHABETICAL = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";


	// Constructeur
	function __construct() {
	}

	// Renvoie une chaine de caractre random en fonction du charset et de la longueur dŽsirŽe
	private function getRandomString($pCharSet, $pLength){
		$randomString = "";
		for ($i=0; $i<$pLength; $i++) $randomString .= $pCharSet[(mt_rand(0,(strlen($pCharSet)-1)))];
		return $randomString;
	}
	
	// Renvoyer un nombre compris entre 1 et 99999
	private function getUniqueId($pLen = 5) {
		return ticket::getRandomString(self::NUMERICAL, $pLen);
	}

	// Renvoyer une clŽ 
	private function getUniqueKey($pLen = 20) {
		return ticket::getRandomString(self::ALPHABETICAL.self::NUMERICAL, $pLen);
	}

	// Revouyer un service Ticket.
	public function getServiceTicket() {
		return self::ST_PREFIX.self::SEPARATOR.ticket::getUniqueId(5).self::SEPARATOR.ticket::getUniqueKey(20);
	}
	
	// Revouyer un service Ticket.
	public function getTicketGrantingTicket() {
		return self::TGT_PREFIX.self::SEPARATOR.ticket::getUniqueId(6).self::SEPARATOR.ticket::getUniqueKey(50);
	}
}

?>