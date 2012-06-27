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
	
	putenv("LANG=$lang"); // On modifie la variable d'environnement
	putenv("LC_ALL=$lang"); // On modifie la variable d'environnement
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

?>

