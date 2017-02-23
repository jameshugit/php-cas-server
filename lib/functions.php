<?php
/*******************************************************************************
	@file functions.php 
	All useful and various functions 
*******************************************************************************/

// generate a random string of the given size
//
// @size: string size
//
// return: 
//   the random string

function generateRand($size) {
	$res = '';
	$chars = 'abcdefghijklmonpqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	for($i = 0; $i < $size; $i++) {
		$res .= $chars[rand(0, strlen($chars))];
	}
	return $res;
}

//
// Get the public URL of the current page
//
function getSelfURL() {

	$protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
	if(isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
		$protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
	}

	$host = $_SERVER['HTTP_HOST'];

	$path = $_SERVER['PHP_SELF'];
	$path = str_replace("index.php", "", $path);

	return "$protocol://$host$path";
}

/**
 * __
 * Specific i18n function that pass the text to html entities
 */

function __($text) {
	return htmlentities(_($text));
}

/**
 * Retrieves the index of array $CONFIG['AUTHORIZED_SITES'] for a service.
 * If the service is not in the list of authorized services, this function returns null
 *	
 * @param pService url of the service.
 * @returns index of array $CONFIG['AUTHORIZED_SITES'] or null
 */
function getServiceIndex($pService) {
	global $CONFIG;
	
	// Verifying the service is listed in $CONFIG['AUTHORIZED_SITES'] array.
	foreach ($CONFIG['AUTHORIZED_SITES'] as $k => $site) {
		if (is_array($site['url'])) {
			foreach ($site['url'] as $url) {
				$pattern = $url;
				preg_match($pattern, $pService, $matches);
				if (isset($matches) && (count($matches) > 0)) {
					return $k;
				}
			}
		}
		else {
			$pattern = preg_quote($CONFIG['AUTHORIZED_SITES'][$k]['url']);
			$pattern = str_replace('\\*', '.*', $pattern);
			$pattern = str_replace('/', '\/', $pattern);
			preg_match("/$pattern/", $pService, $matches);
			if (isset($matches) && count($matches) > 0 && $matches[0] == $pService) {
				return $k;
			}
		}
	}
	return null;
}

/**
 * Verifying if the requested service is autorized to request SSO. 
 *
 * If ok then returns true.
 * If the site is not autorized the return false.
 *	
 * @param $pService url of the service.
 * @returns boolean
 */
function isServiceAutorized($pService){
	global $CONFIG;
	return getServiceIndex($pService) !== null;
}


/**
 * Sanitizes HTTP_ACCEPT_LANGUAGE server variable and returns array of
 * preferred languages
 *	
 * @returns ordered array of languages
 */
function getPrefLanguageArray() {
	if (!array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) return;

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
			}
			else {
				$q = preg_replace('/.*;q=(.*)/', '$1', $lang);

				if ($q > $qcandidat) {
					$candidat = preg_replace('/(.*);.*/', '$1', $lang); ;
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

/**
 * url : rewrite the url correctly with adding '&' at the end if the url has got some parameters
 * or adding '?' if the url has no parameter.
 *
 * @param $url  : the url we want to deal with
 * @returns string
 */
function url($url){
	$t = parse_url($url);
	if (isset($t['query'])) if ($t['query'] != "") return $url."&";
	return $url.'?';
}

/**
 * setLanguage
 * Sets the best language according to user browser preferences
 */
function setLanguage() {
	$lang = getPrefLanguageArray();
	
	$lang='fr_FR';
	
	setlocale(LC_ALL, $lang); // On modifie les informations de localisation en fonction de la langue
	setlocale(LC_MESSAGES, $lang.".utf8"); 
	
	$langfiles = 'translations'; // Le nom de nos fichiers .mo
	
	bindtextdomain($langfiles, "./locale"); // On indique le chemin vers les fichiers .mo
	textdomain($langfiles); // Le nom du domaine par défaut
}

/**
 * function to send get request using curl
 * this function must also handles the ssl certificates 
 * for the moment this is not done
 */
function get_web_page($url)
{
	global $CONFIG; 
	$options = array(
		CURLOPT_RETURNTRANSFER => true,     // return web page
		CURLOPT_HEADER         => false,    // don't return headers
		//CURLOPT_FOLLOWLOCATION => true,     // follow redirects
		CURLOPT_ENCODING       => "",       // handle compressed
		//CURLOPT_USERAGENT      => "spider", // who am i
		CURLOPT_AUTOREFERER    => true,     // set referer on redirect
		CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
		CURLOPT_TIMEOUT        => 120,      // timeout on response
		CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
	);

	$ch = curl_init($url);
	curl_setopt_array($ch, $options);
	
	//SSL CONFIGURATIONS to trust CA certificate  
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

	$content = curl_exec( $ch );
	$err     = curl_errno( $ch );
	$errmsg  = curl_error( $ch );
	$header  = curl_getinfo( $ch );
	curl_close( $ch );

	$header['errno']   = $err;
	$header['errmsg']  = $errmsg;
	$header['content'] = $content;
	return $header;
}


