<?php 

/**
 * @file ticket.php 
 * ST and TGT handling classes
 */

/** 
 * Ticket class
 */

final class TicketStorage {
	/**
	 * The ticket key related to the key
	 */	
	protected $_key = false;

	/**
	 * The value associated to the key, here, a username
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
	 * Constructor
	 * @param $username Username the ticket will be created for. 
	 *   If this ticket is created just for look up, username should not be provided
	 */
	function __construct() {
		global $CONFIG;
		//$this->_prefix = $prefix;
		$this->_key =	$this->_value = false;

		/** Create Memcached instance **/
		$this->_cache = new Memcached();
		$this->_cache->addServers($CONFIG['MEMCACHED_SERVERS']);

		/** @warning Persistant Memcached instance cause apache process to core dump
		 * so cache object is actually created from scratch everytime
		 */
		//			var_dump($this->_cache);
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
		global $CONFIG;
		if ($this->_value !== false) {
			$retval = $this->_cache->delete("SSO-".$this->_key);
			$this->_key = $this->_value = false;
			if ($CONFIG['MODE'] == 'debug')
				echo ".>d<.";
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
		global $CONFIG;
		// TODO : assert $_value & $_username are ok
		if ($CONFIG['MODE'] == 'debug') 
			echo ".>s<.";

		if (! $this->_cache->set("SSO-".$this->_key, $this->_value)) {
			echo _("Unable to store TGT to database, error ") . $this->_cache->getResultCode() . "(" . $this->_cache->getResultMessage() . ")";
			exit;			
		}
	}
	
	public function lookup($key) {
		global $CONFIG;
		if ($CONFIG['MODE'] == 'debug')
			echo ".>l<.";
		// @todo : assert $_value is ok
		$object = $this->_cache->get("SSO-".$key);
		if ($object !== false) {
			$this->_key = $key;
			$this->_value = $object;
			return true;
		} else {
			return false;
		}
	}
}

/** 
 * TicketGrantingTicket class
 */

class TicketGrantingTicket {
	const PREFIX = 'TGT';

	private $_ticket = false;

	// Constructeur
	function __construct() {
	}

	// creates a ticket for username
	public function create($username = false) {
		assert($username != false);
		assert(!$this->_ticket); // can only be initialized once
		assert(strlen($username)> 0);

		$this->_ticket = new TicketStorage('TGT');
		
		$this->_ticket->key('TGT' . TicketStorage::SEPARATOR . TicketStorage::getRandomString(TicketStorage::NUMERICAL, 6) . 
												TicketStorage::SEPARATOR . TicketStorage::getRandomString(TicketStorage::ALPHABETICAL.TicketStorage::NUMERICAL, 50));

		$this->_ticket->value($username);
		$this->_ticket->store(8*60*60);

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
		assert($tgt && $service && $username);
		assert(!$this->_ticket); // can only be initialized once
		assert(strlen($tgt) * strlen($service) * strlen($username));

		$this->_ticket = new TicketStorage();
		
		$this->_ticket->key('ST' . TicketStorage::SEPARATOR . TicketStorage::getRandomString(TicketStorage::NUMERICAL, 5) . 
												TicketStorage::SEPARATOR . 
												TicketStorage::getRandomString(TicketStorage::ALPHABETICAL.TicketStorage::NUMERICAL, 20));

		$this->_ticket->value(array($username,$service));
		$this->_ticket->store(8*60*60);

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
		
		$this->_ticket = new TicketStorage();
		
		$this->_ticket->key('LT' . TicketStorage::SEPARATOR . TicketStorage::getRandomString(TicketStorage::NUMERICAL, 5) . 
												TicketStorage::SEPARATOR . 
												TicketStorage::getRandomString(TicketStorage::ALPHABETICAL.TicketStorage::NUMERICAL, 20));

		$this->_ticket->value($this->_ticket->key());
		// ticket is just valid for 5 minutes
		$this->_ticket->store(5*60);

		return true;
	}

	// returns username associated to key
	public function find($lt = false) {
		assert($lt !== false);
		assert(!$this->_ticket); 
		// can only be initialized once
		$this->_ticket = new TicketStorage();
		$this->_initialized = true;

		return $this->_ticket->lookup($lt);
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
