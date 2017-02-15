<?php
/**
	Template of the attribute of CAS2 like token.
	
	By default this formatted model looks like this :
		<cas:NameOfAttribute>Value</cas:NameOfAttribute>
	
	@file auth_attribute.php
	@author PGL pgl@erasme.org
	@param 
	@returns a string with the name o f the attribute and its value
*/
function viewAuthAtttribute($name, $value) {
	return "<cas:".$name.">".$value."</cas:".$name.">\n";
}

function viewProxyGrantingTicket($pgtIou){
    return "<cas:proxyGrantingTicket>".$pgtIou."</cas:proxyGrantingTicket>\n";
}

