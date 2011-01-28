<?php
/**
	viewAuthHeader retrieving header for CAS2 token.
	 
	@file auth_header.php
	@author PGL pgl@erasme.org
	@param 
	@returns a string containg the xml header
*/
function viewAuthHeader() {
return /* "<?xml version='1.0' encoding='ISO-8859-1'?>\n". */
	   "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>\n".
	   "	<cas:authenticationSuccess>\n".
	   "		<cas:attributes>\n";
}

?>