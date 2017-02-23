<?php
/*******************************************************************************
	@file : auth_success.php 
	
	Template for CAS2 token when authentication as successed.
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
function viewAuthSuccess($t, $pgtIou){
	$token = viewAuthHeader();
	if (is_array($t)){
		foreach($t as $k => $v) {
			$token .= viewAuthAtttribute($k, $v);
		}
	}
	if($pgtIou!=null)
		$token .= viewProxyGrantingTicket($pgtIou);   
	$token .= viewAuthFooter();
	return $token;
}
/*
 proxy token generated after success /proxy request and PGT (proxy granting ticket) exists 
*/
function proxySuccesToken($PT)
{
	return '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
	<cas:proxySuccess>
		<cas:proxyTicket>'.$PT.'</cas:proxyTicket>
	</cas:proxySuccess>
</cas:serviceResponse>
';
}
/*
   proxy authentication token generated after successful proxy ticket validation
*/
function proxyAuthSuccess($username,$pgtiou,$proxy)
{
	return '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
	<cas:authenticationSuccess>
		<cas:user>'.$username.'</cas:user>
		<cas:proxyGrantingTicket>'.$pgtiou.'</cas:proxyGrantingTicket>
		<cas:proxies>
			<cas:proxy>'.$proxy.'</cas:proxy>
		</cas:proxies>
	</cas:authenticationSuccess>
</cas:serviceResponse>
';
}



