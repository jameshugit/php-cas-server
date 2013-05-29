<?php
/**
	viewAuthHeader retrieving header for CAS2 token.
	 
	@file auth_header.php
	@author PGL pgl@erasme.org
	@param 
	@returns a string containg the xml header
*/
function viewAuthHeader( $xmlns="" ) {
    $xmlns = ($xmlns == "") ? "": " ".$xmlns;
    return 
           // @bug : suppressed xml header, because of different buggy client xml parser ???
           /*"<?xml version='1.0' encoding='ISO-8859-1'?>\n". 
           */
           "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'".$xmlns.">\n".
           "	<cas:authenticationSuccess>\n";
           // @bug : suppressed "attributes" xml node, because of different buggy client xml parser ???	
           /*"		<cas:attributes>\n";
           */
}