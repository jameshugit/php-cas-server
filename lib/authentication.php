<?

/** 
 * @file authentication.php
 * Interface that must be implemented by authenticators
 */

interface casAuthentication {
	/**
	 * Validates login and password
	 * This function returns the username that has to be associated to the TGT
	 * This is useful, for instance, if you want to change the username on the fly after authentication
	 * e.g. changin the username to uppercase, or appending some string, etc...
	 * @param login User login
	 * @param password User password
	 * @return string containing the user login (credentials ok) or an empty string (authentication failed)
	 */
	public function verifyLoginPasswordCredential($login, $password);

	/** 
	 * Returns the serviceValidate CAS 2.0 XML fragment response
	 * @param login Login for user (as returned by verifyLoginPasswordCredential)
	 * @param service Service that requests ST validation
	 * @return string containing loads of XML
	 */
	public function getServiceValidate($login, $service);

	/** 
	 * Returns the validate CAS 1.0 response
	 * @param login Login for user (as returned by verifyLoginPasswordCredential)
	 * @param service Service that requests ST validation
	 * @return string containing loads of XML
	 */
	public function getValidate($login, $service);
}

?>