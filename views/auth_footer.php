<?php
/**
	viewAuthFooter retrieving footer for CAS2 token.
	 
	@file auth_header.php
	@author PGL pgl@erasme.org
	@param 
	@returns a string containg the xml footer
*/
function viewAuthFooter() {
    if (! IS_SOAP) {
        return  
               // @bug : suppressed "attributes" xml node, because of different buggy client xml parser ???	
                //"		</cas:attributes>\n".
                "	</cas:authenticationSuccess>\n".
                "</cas:serviceResponse>\n";
    } else {
        /** @todo : **** FL **** */
    }
}
