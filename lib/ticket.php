<?php 

/**
 * @file ticket.php 
 * @description ST and TGT handling classes
 */

/** 
 * Ticket class
 */

class Ticket {
	/**
	 * The prefix used to generate a random key
	 */
	protected $_prefix;

	/**
	 * The username related to the key
	 */	
	protected $_username;

	/**
	 * The key itself
	 */	
	protected $_value =  false;

	/**
	 * The key itself
	 */	
	protected $_value =  false;

	/**
	 * Character classes for key generation
	 */
	const SEPARATOR = '-';
	const NUMERICAL = "0123456789";
	const ALPHABETICAL = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

	/** 
	 * Pure virtual function generateTicket has to be overloaded by child classes
	 */
	abstract protected function generateTicket();

	/**
	 * Base class constructor
	 * @param id Ticket id as parameter; a new ticket will be generated if no id id provided
	 */
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

	/*
	 * Utility that returns a random string of given length build with given charset
	 * @param pCharSet String containing charset used to build the random string
	 * @param pLength Required length for the randopm string
	 */
	final protected function getRandomString($pCharSet, $pLength){
		$randomString = "";
		for ($i=0; $i<$pLength; $i++) $randomString .= $pCharSet[(mt_rand(0,(strlen($pCharSet)-1)))];
		return $randomString;
	}
	
	/* Returns a zero left padded number
	 * @param pLen How many digits to genarate, default is 5
 
	final protected function getRandomId($pLen = 5) {
		return ticket::getRandomString(self::NUMERICAL, $pLen);
	}
	*/

	/* Returns a random alphanuerical key
	 * @param pLen Key length, default 20
	final protected function getRandomKey($pLen = 20) {
		return ticket::getRandomString(self::ALPHABETICAL.self::NUMERICAL, $pLen);
	}
	*/

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

/** 
 * TicketGrantingTicket class
 */

class TicketGrantingTicket extends Ticket {
	const PREFIX = 'TGT';

	// Constructeur
	function __construct() {
		parent::__construct(PREFIX);
	}

	private function generateTicket() {
		return PREFIX . self::SEPARATOR . getRandomString(self::NUMERICAL, 6) . self::SEPARATOR . getRandomString(self::ALPHABETICAL.self::NUMERICAL, 50);
	}	
}

/** 
 * ServiceTicket class
 */

class ServiceTicket extends Ticket {
	const PREFIX = 'ST';

	// Constructeur
	function __construct($id = "") {
		parent::__construct($id);
	}

	public function generateTicket() {
		return PREFIX . self::SEPARATOR . getRandomString(self::NUMERICAL, 5) . self::SEPARATOR . getRandomString(self::ALPHABETICAL.self::NUMERICAL, 20);
	}
}


?>