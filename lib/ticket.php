<?php 
/*******************************************************************************
	@filename : ticket.php 
	@description : Classe de gestion des tickets ST et TGT. Comporte 2 méhodes 
	publiques de restitution de tickets.
	
	TGT-1-kFisTM5FtfkwZ6hnPBF96hQPAnpl9sd6oWpZdkR3HJTECFYbHY
*******************************************************************************/

class Ticket {
	protected $_prefix;
	protected $_username;
	protected $_value =  false;

	const SEPARATOR = '-';
	const NUMERICAL = "0123456789";
	const ALPHABETICAL = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

	abstract protected function generateTicket();

	// Constructeur
	function __construct($id = "") {
		$_prefix = $prefix;
		if ($id != "")
			// They want me to retrieve a stored ticket
			lookupTicket($id);
		else {
			generateUniqueTicket();
			storeTicket();
		}
	}

	// Renvoie une chaine de caractère random en fonction du charset et de la longueur désirée
	final protected function getRandomString($pCharSet, $pLength){
		$randomString = "";
		for ($i=0; $i<$pLength; $i++) $randomString .= $pCharSet[(mt_rand(0,(strlen($pCharSet)-1)))];
		return $randomString;
	}
	
	// Renvoyer un nombre compris entre 1 et 99999
	final protected function getUniqueId($pLen = 5) {
		return ticket::getRandomString(self::NUMERICAL, $pLen);
	}

	// Renvoyer une clé
	final protected function getUniqueKey($pLen = 20) {
		return ticket::getRandomString(self::ALPHABETICAL.self::NUMERICAL, $pLen);
	}

	protected function storeTicket($duration = 300) {
		$m = new Memcached();
		$m->addServer('localhost', 11211);
		
		// TODO : assert $_ticket & $_username are ok
		$m->set("SSO".$_ticket, $self);
	}
	
	protected function lookupTicket() {
		$m = new Memcached();
		$m->addServer('localhost', 11211);
		
		// TODO : assert $_ticket & $_username are ok
		$self = $m->get("SSO" . $_ticket);
	}

	protected function generateUniqueTicket() {
		if (!$_value)
			$_value = generateTicket();
	}

	public getTicket() {
		return $_ticket;
	}
}

class TicketGrantingTicket extends Ticket {
	const PREFIX = 'TGT';

	// Constructeur
	function __construct() {
		parent::__construct(PREFIX);
	}

	private function generateTicket() {
		return PREFIX . self::SEPARATOR . getUniqueId(6) . self::SEPARATOR . getUniqueKey(50);
	}	
}

class ServiceTicket extends Ticket {
	const PREFIX = 'ST';

	// Constructeur
	function __construct($id = "") {
		parent::__construct($id);
	}

	public function generateTicket() {
		return PREFIX . self::SEPARATOR . getUniqueId(5) . self::SEPARATOR . getUniqueKey(20);
	}
}


?>