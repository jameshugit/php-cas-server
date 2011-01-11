<?php 

/**
 * @file ticket.php 
 * @description ST and TGT handling classes
 */

require_once('../config.inc.php');

/** 
 * Ticket class
 */

abstract class Ticket {
	/**
	 * The username related to the key
	 */	
	protected $_username = false;

	/**
	 * The key itself
	 */	
	protected $_value = false;

	/**
	 * @defgroup Constants Character classes for key generation
	 * @{
	 */

	/** SEPARATOR character class **/
	const SEPARATOR = '-';

	/** NUMERICAL characted class contains all 10 decimal digits **/
	const NUMERICAL = "0123456789";

	/** ALPHABETICAL character class contains all upper case and lower case characters **/
	const ALPHABETICAL = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

	/**
	 * @}
	 */

	/** 
	 * Pure virtual function generateTicket has to be overloaded by child classes
	 */
	abstract protected function generateTicket();

	/**
	 * Base class constructor
	 * @param $username Username the ticket will be created for. 
	 *   If this ticket is created just for look up, username should not be provided
	 */
	function __construct($username = false) {
		global $CONFIG;
		$this->_username = $username;

		/** Create Memcached instance **/
		$this->_cache = new Memcached();
		$this->_cache->addServers($CONFIG['MEMCACHED_SERVERS']);

		/** @warning Persistant Memcached instance cause apache process to core dump
		 * so cache object is actually created from scratch everytime
		 */
			var_dump($this->_cache);
			//}
	}

	/**
	 * getTicket returns a ticket
	 *
	 * This method returns an existing ticket if id is passed as a parameter or creates a ticket
	 * if a username ha been set (@see ::setUsername).
	 *
	 * @param id Id of existing ticket to look up
	 * @return ticket value
	 * @todo there should be some error handling around...
	 */

	public function getTicket($id = false) {
		if ($this->_value === false) {
			if ($id !== false) {
				// They want me to retrieve a stored ticket
				echo "&gt;lookup&lt;";
				$this->lookupTicket($id);
			}	else if ($this->_username !== false) {
				echo "&gt;store&lt;";
				$this->generateUniqueTicket();
				$this->storeTicket();
			} else {
				echo "&gt;error&lt;";
				var_dump($this);
				/** @todo Raise error since this is not a creation or a retrieval */
			}
		}
		return $this->_value;
	}

	public function getUsername() {	return $this->_username; }

	/**
	 * Delete ticket from storage
	 */
	public function delete() {
		if ($this->_value !== false) {
			$this->_cache->delete($this->_value);
			$this->_value = false;
			}
	}

	/**
	 *
	 */
	//	public function getUsername() {	return $this->_username; }
	//public function setUsername($value) {	$this->_username = $value; }

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
		// TODO : assert $_value & $_username are ok
		if (! $this->_cache->set("SSO-".$this->_value, $this)) {
			echo "Unable to store TGT to database, error " . $this->_cache->getResultCode() . "(" . $this->_cache->getResultMessage() . ")";
			exit;			
		}
	}
	
	protected function lookupTicket($id) {
		// @todo : assert $_value is ok
		$object = $this->_cache->get("SSO-".$id);
		var_dump($object);
		if ($object !== false) {
			$this->_username = $object->getUsername();
			$this->_value = $object->getTicket();
		} else {
			/// @todo Handle error here
		}
	}

	protected function generateUniqueTicket() {
		if (!$this->_value)
			$this->_value = $this->generateTicket();
	}
}

/** 
 * TicketGrantingTicket class
 */

class TicketGrantingTicket extends Ticket {
	const PREFIX = 'TGT';

	// Constructeur
	function __construct($username = false) {
		parent::__construct($username);
	}

	protected function generateTicket() {
		return self::PREFIX . self::SEPARATOR . $this->getRandomString(self::NUMERICAL, 6) . self::SEPARATOR . $this->getRandomString(self::ALPHABETICAL.self::NUMERICAL, 50);
	}

	public function delete() {
	}
}

/** 
 * ServiceTicket class
 */

class ServiceTicket extends Ticket {
	const PREFIX = 'ST';

	// Constructeur
	function __construct($username = false) {
		parent::__construct($username);
	}

	protected function generateTicket() {
		return self::PREFIX . self::SEPARATOR . $this->getRandomString(self::NUMERICAL, 5) . self::SEPARATOR . $this->getRandomString(self::ALPHABETICAL.self::NUMERICAL, 20);
	}
}


?>
