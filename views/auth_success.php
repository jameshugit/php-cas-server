<?php
/*******************************************************************************
	@file : auth_success.php 
	
	Template for CAS2 token when authentication as successed.
*******************************************************************************/

//
// proxy token generated after success /proxy request and PGT (proxy granting
// ticket) exists 
//
function proxySuccesToken($PT)
{
	return 
'<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
	<cas:proxySuccess>
		<cas:proxyTicket>'.$PT.'</cas:proxyTicket>
	</cas:proxySuccess>
</cas:serviceResponse>
';
}

//
// proxy authentication token generated after successful proxy ticket validation
//
function proxyAuthSuccess($username,$pgtiou,$proxy)
{
	return 
'<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
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



