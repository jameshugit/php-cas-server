<?php
/**
	viewAuthFooter retrieving footer for CAS2 token.
	 
	@file auth_header.php
	@author PGL pgl@erasme.org
	@param 
	@returns a string containg the xml footer
*/
function viewAuthFooter() {
	return  //"		</cas:attributes>\n".
	   		"	</cas:authenticationSuccess>\n".
		   	"</cas:serviceResponse>\n";
}

?>