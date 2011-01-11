<?php
/*******************************************************************************
	@file : functions.php 
	All useful and various functions 
*******************************************************************************/

/**
	isServiceAutorized : Verifying if the requested service is autorized to request SSO
	
	@author PGL pgl@erasme.org
	@param $pService url of the service.
	@returns boolean
*/
function isServiceAutorized($pService){
	global $autorized_sites;
	$siteIsAutoriezd = false;
	/* Verifying the service is listed in autorized_sites array. */
	if ($pService != "") {
		foreach($autorized_sites as $k => $site) {

			$pattern  = preg_quote($autorized_sites[$k]['url']);
			$pattern = str_replace('\\*', '.*', $pattern);
			$pattern = str_replace('/', '\/', $pattern);
	
			preg_match("/$pattern/", $pService, $matches);
			if (isset($matches) && count($matches) > 0 && $matches[0] == $pService) {
				$siteIsAutoriezd = true;
				break;
			}
		}
	} 
	else {
		return true; // Service is null
	}
	return $siteIsAutoriezd;
}

?>
