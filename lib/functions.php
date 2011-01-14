<?php
/*******************************************************************************
	@file functions.php 
	All useful and various functions 
*******************************************************************************/
/**
	Function that does matching with a regular expression
	@author PGL pgl@erasme.org
	@param string to match
	@param matching pattern
	@returns array of matches
	@bug does'nt work ????
*
function matchString($str, $model){
	$pattern  = preg_quote($model);
	$pattern = str_replace('\\*', '.*', $pattern);
	$pattern = str_replace('/', '\/', $pattern);
	preg_match("/$pattern/", $str, $matches);
	return $matches;
}
*/

/**
 * Verifying if the requested service is autorized to request SSO. 
 
 If ok then returns true.
 If the site is not autorized the return false.
 *	
 * @author PGL pgl@erasme.org
 * @param $pService url of the service.
 * @returns boolean
 */
function isServiceAutorized($pService){
	global $CONFIG;

	/* Verifying the service is listed in $CONFIG['AUTHORIZED_SITES'] array. */
	if ($pService != "") {
		foreach($CONFIG['AUTHORIZED_SITES'] as $k => $site) {
			$pattern  = preg_quote($CONFIG['AUTHORIZED_SITES'][$k]['url']);
			$pattern = str_replace('\\*', '.*', $pattern);
			$pattern = str_replace('/', '\/', $pattern);
			preg_match("/$pattern/", $pService, $matches);
			
			if (isset($matches) && count($matches) > 0 && $matches[0] == $pService) {
				return true;
			}
		}
	} else {
		return true; // Service is null
	}

	return false;
}

/**
 * Retrieves the index of array $CONFIG['AUTHORIZED_SITES'] for a service.
 * If the service is not in the list of authorized services, this function returns null
 *	
 * @author PGL pgl@erasme.org
 * @param pService url of the service.
 * @returns index of array $CONFIG['AUTHORIZED_SITES'] or null
 */
function getServiceIndex($pService) {
	global $CONFIG;
	/* Verifying the service is listed in $CONFIG['AUTHORIZED_SITES'] array. */
		foreach($CONFIG['AUTHORIZED_SITES'] as $k => $site) {
			$pattern  = preg_quote($CONFIG['AUTHORIZED_SITES'][$k]['url']);
			$pattern = str_replace('\\*', '.*', $pattern);
			$pattern = str_replace('/', '\/', $pattern);
			preg_match("/$pattern/", $pService, $matches);
			if (isset($matches) && count($matches) > 0 && $matches[0] == $pService) {
				return $k;
			}
		}
	return null;
}

/**
 * Sanitizes HTTP_ACCEPT_LANGUAGE server variable and returns array of
 * preferred languages
 *	
 * @returns ordered array of languages
 */
function getPrefLanguageArray() {
  $langs = explode(',', $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
  $qcandidat = 0;
  $nblang = count($langs);

  for ($i=0; $i<$nblang; $i++) {
    for ($j=0; $j<count($langs); $j++) {
      $lang = trim($langs[$j]);
      
      if (!strstr($lang, ';') && $qcandidat != 1) {
        $candidat = $lang;
        $qcandidat = 1;
        $indicecandidat = $j;
      } else {
        $q = ereg_replace('.*;q=(.*)', '\\1', $lang);
				
        if ($q > $qcandidat) {
          $candidat = ereg_replace('(.*);.*', '\\1', $lang); ;
          $qcandidat = $q;
          $indicecandidat = $j;     
        }
      }
    }
    
    $resultat[$i] = $candidat;
		
    $qcandidat=0;
    unset($langs[$indicecandidat]);   
    $langs = array_values($langs);
  }
  return $resultat;
}

?>
