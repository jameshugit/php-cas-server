<?php
/*******************************************************************************
	@file : auth_success.php 
	
	Template for CAS2 token when authetication as successed.
*******************************************************************************/
require_once('auth_footer.php');
require_once('auth_header.php');
require_once('auth_attribute.php');


/**
	viewAuthSuccess : Callback that displays the Authentication CAS2 like token.	
	
	@file auth_success.php 
	@author PGL pgl@erasme.org
	@param $t an array of param to be include in the view. this is a key/value array.
	@returns
*/
function viewAuthSuccess($t){
	viewAuthHeader();
	if (is_array($t))){
		foreach($t as $k => $v) {
			viewAuthAtttribute($k, $v);
		}
	}
	viewAuthFooter();
}
?>