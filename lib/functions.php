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
 * __
 * Specific i18n function that pass the text to html entities
 */

function __($text) {
	return htmlentities(_($text));
}

function get_Saml_message($Soapmessage)
{
     $Request= '';  
  foreach ($Soapmesssage->children("http://schemas.xmlsoap.org/soap/envelope/") as $tag => $item) {
      if ($tag=='Body')
      {    
        //printf("balise : %s\n", $tag);
        //print_r($item);

       foreach($item->children("urn:oasis:names:tc:SAML:1.0:protocol") as $key => $value)
       {
           if ($key=='Request')
           {
             printf("balise : %s\n", $key);

             $Request= $value;
             return $Request;
             //print_r($value);
            }
        }
        }

    }
    throw new Exception('error parsing the message'); 
}

function get_saml_ticket($samlmessage)
{
    foreach ($samlmessage->children("urn:oasis:names:tc:SAML:1.0:protocol") as $tag => $item)
  {
      //printf("balise : %s\n", $tag);

       //print_r($item);
       if ($tag=='AssertionArtifact')
       {
           $ticket_value= (String)$item[0];
           //print_r($ticket_value);
           return $ticket_value;
       }
  }
  return '';
}


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
        foreach ($CONFIG['AUTHORIZED_SITES'] as $k => $site) {
            if (is_array($site['url'])) 
                {
                  foreach($site['url'] as $url)
                  {
                      $pattern = preg_quote($url);
                      $pattern = str_replace('\\*', '.*', $pattern);
                      $pattern = str_replace('/', '\/', $pattern);
                       preg_match("/$pattern/", $pService, $matches);

                       if (isset($matches) && count($matches) > 0 && $matches[0] == $pService) {
                            return true;
                             }
                  }
                }  
            else{
                $pattern = preg_quote($CONFIG['AUTHORIZED_SITES'][$k]['url']);
                $pattern = str_replace('\\*', '.*', $pattern);
                $pattern = str_replace('/', '\/', $pattern);
                preg_match("/$pattern/", $pService, $matches);

                if (isset($matches) && count($matches) > 0 && $matches[0] == $pService) {
                    return true;
                }
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

    foreach ($CONFIG['AUTHORIZED_SITES'] as $k => $site) {
        if (is_array($site['url'])) {
            foreach ($site['url'] as $url) {
                $pattern = preg_quote($url);
                $pattern = str_replace('\\*', '.*', $pattern);
                $pattern = str_replace('/', '\/', $pattern);
                preg_match("/$pattern/", $pService, $matches);

                if (isset($matches) && count($matches) > 0 && $matches[0] == $pService) {
                    return $k;
                }
            }
        } else {

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
      } else {
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
	url : rewrite the url correctly with adding '&' at the end if the url has got some parameters
	or adding '?' if the url has no parameter.
	
	
	@file
	@author PGL pgl@erasme.org
	@param $url  : the url we want to deal with
	@returns string
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
	writeLog
	@author PGL pgl@erasme.org
	@param $code the code of the log line @see config.inc.sample
	@param $username the username
	@param $msg a custom message (nullable)
*/
function writeLog($code, $username, $msg="") {
    $t = "  "; // this a tab
    $xfwdedfor = array_key_exists("HTTP_X_FORWARDED_FOR", $_SERVER) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : "";
    $clientip  = array_key_exists("HTTP_CLIENT_IP", $_SERVER) ? $_SERVER["HTTP_CLIENT_IP"] : "";
    $proxy     = "";
    $service = isset($_REQUEST['service'])? $_REQUEST['service'] : "";
    
    if ($xfwdedfor != "") {
       if ($clientip != "") {
        $proxy = $clientip;
      } else {
        $proxy = $_SERVER["REMOTE_ADDR"];
      }
      $ip = $xfwdedfor;
    } else {
      if ($clientip != "") {
        $ip = $clientip;
      } else {
        $ip = $_SERVER["REMOTE_ADDR"];
      }
    }
    
    openlog("php-cas-server", LOG_PID | LOG_PERROR, LOG_LOCAL0);
    
    syslog(LOG_INFO, 
          $code.$t.
          $username.$t.
          $ip.$t.
          $proxy.$t.
          date('YmdHis').$t.
          $service.$t.
          $msg);
    closelog();
}

/**
	trackUser
	@author PGL pgl@erasme.org
	@param the user login
	@returns
*/
function trackUser($login) {
    global $CONFIG;
    $message = "This user is in the tracking list. See config.inc.php";
   
    // 1. check if we want to track this user
    if ( ! in_array(strtoupper($login), $CONFIG['TRACKED_USERS'])) return;
    
    // 2. logging ALERT into logfile
    writeLog('TRACKING_ALERT', strtoupper($login), $message);
    
    // 3.Send à mail alert to admin
    if ($CONFIG['SEND_ME_A_MAIL'])
        mail($CONFIG['MAIL_ADMIN'], "Tracking alert from ".$_SERVER['SERVER_NAME'], 
        "The user '".strtoupper($login)."' has just logged on ".$_SERVER['SERVER_NAME'].".\n".
        $message." to remove him from ['TRACKED_USERS'] array if you do not want to track him anymore.\n\n".
        "This mail is automatically send because you activated tracking feature on ".$_SERVER['SERVER_NAME']."\n".
        "Please do not answer to this mail.");
}

/*
function to send get request using curl
this function must also handles the ssl certificates 
for the moment this is not done
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

	$ch      = curl_init( $url );
	curl_setopt_array( $ch, $options );
	
	//SSL CONFIGURATIONS to trust CA certificate  
	if (isset($CONFIG['caCertPath'])) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_CAINFO, $this->caCertPath);
            phpCAS::trace('CURL: Set CURLOPT_CAINFO');
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

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


/**
generate password function 
@param $ch number of characters 
@param $let number of numbers 
@returns a string that contains $ch characters and $let random numbers followd by a postfix ="_sconet"

*/
function generatePassword($ch=3, $let=3) {

  // start with a blank password
    $password = "";
    $postfix = "_sconet";

    // define possible characters - any character in this string can be
    // picked for use in the password, so if you want to put vowels back in
    // or add special characters such as exclamation marks, this is where
    // you should do it
    $chiffres = "123456789";
    $lettres = "abcdefghijklmnopqrtvwxyz";
    $possible = array($chiffres, $lettres);

    // we refer to the length of $possible a few times, so let's grab it now
    $length = $ch + $let;

    // set up a counter for how many characters are in the password so far
    $i = 0;
    $j = 0;

    // add random characters to $password until $length is reached
    while ($i < $ch) {
        // pick a random character from the possible chiffres ones 
        $char = substr($possible[0], mt_rand(0, 8), 1);
        $password .= $char;
        $i++;
    }

    while ($j < $let) {
        $char = substr($possible[1], mt_rand(0, 23), 1);
        $password .= $char;
        $j++;
    }
    //suffle the string 
    $password = str_shuffle($password);
    // done!
    $password .= $postfix;
    return $password;
}
/**
function to merge keys array with values array 
@param $a array of keys
@param $b array of values
@returns associative array of keys and values
          null if cannot merge
*/
function array_real_combine($a, $b)
{
    return is_array($a) && is_array($b) && sizeof($a)== sizeof($b) ? array_combine($a, $b) : null ;
}

?>
