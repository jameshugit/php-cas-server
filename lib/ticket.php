<?php 

/**
 * @file ticket.php 
 * ST and TGT handling classes
 */
 
require_once 'Rediska.php';

/** 
 * Ticket class
 */

final class TicketStorage {
	/**
	 * MemCache Object for ticket storage.
	 */
	 protected $_cache = false;
	 
	/**
	 * The ticket key related to the key
	 */	
	protected $_key = false;

	/**
	 * The value associated to the key, here, a username
	 */	
	protected $_value = false;
	
	/**
	 * The prefix associated to the ticket. This can be ST, TGT, LT, ...
	 */	
	protected $_prefix = false;
		
	/**
	 * The memcahce object storing the ticket counter
	 */	
	protected $_ticket_counter = false;
		
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
	 
	 protected function addCounter() {
		// If counter does not exist, then create one
		if (!$this->_cache->get("ST_COUNTER")) {
			$this->_cache->setAndExpire("ST_COUNTER", 0);
		}

		$this->_ticket_counter = $this->_cache;
	 }
	 
	 // reads and increments ticket counter
	 protected function readCounter() {
		$counterValue = $this->_cache->get("ST_COUNTER");
		$this->_cache->increment("ST_COUNTER", 1);
		return $counterValue;
	 }

	/**
	 * Constructor
	 * @param $username Username the ticket will be created for. 
	 *   If this ticket is created just for look up, username should not be provided
	 */
	function __construct($prefix = false) {
		global $CONFIG;
		$this->_prefix = $prefix;
		$this->_key = $this->_value = false;

		/** Create Rediska instance **/
		$this->_cache = new Rediska();

    foreach ($CONFIG['REDIS_SERVERS'] as $srvary) {
  		$this->_cache->addServer($srvary[0], $srvary[1]);
    }
	}

	/**
		Create a new ticket.
		
		@file
		@author PGL pgl@erasme.org
		@param $alphaLength Desired Lenght of the alphanumerical part.
		@param $value value for the ticket.
		@param $timout validity timeout for the ticket.
		@returns void
	*/
	public function create($alphaLength, $value, $timout) {
		/** Create a Ticket Counter if necessary. */
		$this->addCounter();
		
		// Default values
		$number = self::getRandomString(self::NUMERICAL, 5);
		$suffixString = self::getRandomString(self::ALPHABETICAL.self::NUMERICAL, $alphaLength);
		// defining a counter for ServiceTicket type
		if ($this->_prefix == 'ST') $number = $this->readCounter();
		
/*		if ($this->_prefix == 'LT') {
			$number = "0";
			$suffixString = date('ymj-his'); 
		}
*/		
		$this->key($this->_prefix . self::SEPARATOR . 
					$number . 
					self::SEPARATOR . 
					$suffixString);
		// value
		$this->value($value);
		// Storing this ticket.
		$this->store($timout);
	}
	
	
	/** accessors */
	public function key($key = false) {
		if ($key)
			$this->_key = $key;

		return $this->_key;
	}

	public function value($val = false) {
		if ($val)
			$this->_value = $val;

		return $this->_value;
	}
	
	/**
	 * Delete ticket from storage
	 */
	public function delete() {
		if ($this->_value !== false) {
			$retval = $this->_cache->delete("SSO". self::SEPARATOR. $this->_key);
			$this->_key = $this->_value = false;
			return $retval;
		}
		return false;
	}

	/*
	 * Utility that returns a random string of given length build with given charset
	 * @param pCharSet String containing charset used to build the random string
	 * @param pLength Required length for the randopm string
	 */
	static public function getRandomString($pCharSet, $pLength){
		$randomString = "";
		for ($i=0; $i<$pLength; $i++) $randomString .= $pCharSet[(mt_rand(0,(strlen($pCharSet)-1)))];
		return $randomString;
	}
	

	public function store($duration = 300) {
		// TODO : assert $_value & $_username are ok
		try {
      $this->_cache->set("SSO" . self::SEPARATOR. $this->_key, $this->_value);
    } catch(Rediska8Exception $e) {
			echo _("Unable to store TGT to database, error ") . $e->getCode() . "(" . $e->getMessage() . ")";
			exit;			
		}
	}
	
	public function lookup($key) {
		// @todo : assert $_value is ok
		$object = $this->_cache->get("SSO". self::SEPARATOR. $key);
		if ($this->_cache->getResultCode() == Memcached::RES_NOTFOUND) {
			return false;
		}
		else {
			$this->_key = $key;
			$this->_value = $object;
		}
		return true;
		 
	}
	
	public function resetCounter() {
		assert($this->_cache);
		$this->_cache->set("ST_COUNTER", 0);
	}
}

/** 
 * TicketGrantingTicket class
 */

class TicketGrantingTicket {
	private $_ticket = false;

	// Constructeur
	function __construct() {
	}

	// creates a ticket for username
	public function create($username = false) {
		global $CONFIG;
		assert($username != false);
		assert(!$this->_ticket); // can only be initialized once
		assert(strlen($username)> 0);

		$this->_ticket = new TicketStorage('TGT');
		$this->_ticket->create(50, $username, $CONFIG['TGT_TIMOUT'] );
		return true;
	}

	// returns username associated to key
	public function find($key = false) {
		assert($key != false);
		assert(!$this->_ticket); // can only be initialized once
		
		$this->_ticket = new TicketStorage();
		$this->_initialized = true;

		return $this->_ticket->lookup($key);
	}

	public function key() {
		assert($this->_ticket);
		return $this->_ticket->key();
	}

	public function username() {
		assert($this->_ticket);
		return $this->_ticket->value();
	}

	public function delete() {
		assert($this->_ticket);
		return $this->_ticket->delete();
	}
}

/** 
 * ServiceTicket class
 */

class ServiceTicket {
	private $_ticket = false;
	
	// Constructeur
	function __construct() {
	}

	// creates a st ticket for tgt
	public function create($tgt = false, $service = false, $username = false) {
		global $CONFIG;
		assert($tgt && $service && $username);
		assert(!$this->_ticket); // can only be initialized once
		assert(strlen($tgt) * strlen($service) * strlen($username));


		$this->_ticket = new TicketStorage('ST');		
		$this->_ticket->create(20, array($username,$service), $CONFIG['ST_TIMOUT'] );
		return true;
	}

	// returns username associated to key
	public function find($st = false) {
		assert($st !== false);
		assert(strlen($st));
		assert(!$this->_ticket); // can only be initialized once		

		$this->_ticket = new TicketStorage();

		$bag = $this->_ticket->lookup($st);
		return $bag;
	}

	public function key() {
		assert($this->_ticket);
		return $this->_ticket->key();
	}

	public function username() {
		assert($this->_ticket);
		$arr = $this->_ticket->value();
		return $arr[0];
	}

	public function service() {
		assert($this->_ticket);
		$arr = $this->_ticket->value();
		return $arr[1];
	}

	public function delete() {
		assert($this->_ticket);
		return $this->_ticket->delete();
	}
	
	public function resetCounter() {
		assert($this->_ticket);
		$this->_ticket->resetCounter();
	}
}

/** 
 * LoginTicket class
 * 
 * This kind of ticket is use to provide more security on the login form.
 * LT Tickets are one shot tickets which are generated when display login Form, and
 * are validated when the user posts its credential.
 * Their cycle life are very short in way to avoid reposting credentials when typing 'back' on the navigator.
 * @note : The ticket destruction occured when it times out.
 *
 */

class LoginTicket {
	private $_ticket = false;

	// Constructeur
	function __construct() {
	}

	// creates a st ticket for tgt
	public function create() {
		global $CONFIG;
		$this->_ticket = new TicketStorage('LT');
		$this->_ticket->create(20, "LOGIN-TICKET", $CONFIG['LT_TIMOUT']);
		return true;
	}

	// returns username associated to key
	public function find($lt = false) {
		//assert($lt !== false);
		//assert(!$this->_ticket); 
		// can only be initialized once
		$this->_ticket = new TicketStorage('LT');
		$this->_initialized = true;
		$louqueEup = $this->_ticket->lookup($lt);
		$this->delete();
		return $louqueEup;
	}
	
	public function key() {
		assert($this->_ticket);
		return $this->_ticket->key();
	}
	
	public function delete() {
		assert($this->_ticket);
		return $this->_ticket->delete();
	}

}

?>
