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
function viewAuthSuccess($viewName, $t, $pgtIou){
	if ($viewName == 'Default') {
        $token = viewAuthHeader();
		if (is_array($t)){
			foreach($t as $k => $v) {
				$token .= viewAuthAtttribute($k, $v);
			}
		}
        if($pgtIou!=null)
               $token .= viewProxyGrantingTicket($pgtIou);   
    	$token .= viewAuthFooter();
	}
	else { // custom view 
		if (file_exists($viewName.'.php')) {
			require_once($viewName.'.php');
			if (function_exists("view_$viewName")) $token =  call_user_func("view_$viewName", $t);
			else $token = viewAuthHeader () . _('The function "view_'.$viewName.'" does not exist in file "'.$viewName.'.php"  !') . viewAuthFooter ();
		}
		else $token .= _('The file "'.$viewName.'.php" does not exist !');
	}
	return $token;
}
/*
 proxy token generated after success /proxy request and PGT (proxy granting ticket) exists 
*/
function proxySuccesToken($PT)
{
	$token= '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
   		<cas:proxySuccess>
     		<cas:proxyTicket>'.$PT.'</cas:proxyTicket>
   		</cas:proxySuccess>
		</cas:serviceResponse>'; 
         
	return $token;
}
/*
   proxy authentication token generated after successful proxy ticket validation
*/
function proxyAuthSuccess($username,$pgtiou,$proxy)
{
	$token= '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
		    <cas:authenticationSuccess>
			<cas:user>'.$username.'</cas:user>
			<cas:proxyGrantingTicket>'.$pgtiou.'</cas:proxyGrantingTicket>
			<cas:proxies>
			  <cas:proxy>'.$proxy.'</cas:proxy>
			  </cas:proxies>
		    </cas:authenticationSuccess>
		</cas:serviceResponse>';
        return $token; 

}



