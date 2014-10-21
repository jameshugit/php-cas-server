<?php

/*
 * We need access to the various simpleSAMLphp classes. These are loaded
 * by the simpleSAMLphp autoloader.
 * 
 */
//add the path to the CAS configuration file.
require_once('../config.inc.php');

//the simpleSAMlphp autoloader class
//require_once(SimpleSamlPATH.'/_autoload.php');
//require_once('/var/www/sso/lib/backend.db.oracle.php'); 
//include_once('../../../sso/index.php');
require_once(CAS_PATH . '/lib/ticket.php');
require_once(CAS_PATH . '/views/error.php');
require_once(CAS_PATH . '/views/header.php');
//require_once(CAS_PATH . '/views/logout.php');
//require_once(CAS_PATH . '/views/auth_failure.php');
require_once(CAS_PATH . '/lib/backend.db.oracle.php');
require_once (CAS_PATH . '/lib/KLogger.php');

//CASLogin function takes the (laclasse login) as input and generates  a ticket to login to Laclasse server.
function CASLogin($nom, $idp) {
    global $CONFIG;

    $Casurl = CAS_URL . '/login';
    if ($idp == 'FIM')
    // I ADDED LoggedFromExternalFim = Y  parameter to assure that the authentification is issued by  the academie.
        $service = SERVICE . '?LoggedFromExternalFim=Y';
    else {
        if ($idp = 'GOOGLE') //if the authentication is issued by GOOGLE
            $service = SERVICE . '?LoggedFromGoogle=Y';
        else
            $service = SERVICE;
    }

    if (!array_key_exists('CASTGC', $_COOKIE)) { /*     * * user has no TGC ** */

        /* user has no TGC 
          => send TGT
          => redirect to login with a service
         */
        $ticket = new TicketGrantingTicket();
        $ticket->create($nom);

        /* send TGC */
        setcookie("CASTGC", $ticket->key(), 0, "/");
        // setcookie("info", $nom, 0);

        /* Redirect to /login */
        header("Location: " . url($Casurl) . "service=" . urlencode($service) . "");
    } else { /*     * * user has TGC ** */




        // Assert validity of TGC
        $tgt = new TicketGrantingTicket();
        /// @todo Well, do something meaningful...
        if (!$tgt->find($_COOKIE["CASTGC"])) {
            /* client has not a valid TGT
              => generate a new ticket
              => redirect to login with the service
             */
            $ticket = new TicketGrantingTicket();
            $ticket->create($nom);

            /* send TGC */
            setcookie("CASTGC", $ticket->key(), 0, "/");
            /* Redirect to /login */
            header("Location: " . url($Casurl) . "service=" . urlencode($service) . "");
        } else {
            /* client has a valid ticket
              => redirect to /login
             */

            header("Location: " . url($Casurl) . "service=" . urlencode($service) . "");
        }
    }
}

// extractVector function gets the vector sent by the academie and returns an array that 
// contains the different information in the vector like nom, prenom, eleveid.
//the array may contain multiple records depending on the number of sent vectors.

function extractVector($attributes) {
  global $CONFIG;
  $log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
  $log->LogDebug("Extracting Vector...");
  $attr = array();
  if (!empty($attributes)) {
    if (array_key_exists('FrEduVecteur', $attributes)) {
      if (count($attributes['FrEduVecteur']) >= 1) {
        foreach ($attributes['FrEduVecteur']as $val) {
          if (!empty($val)) {
            $temp = explode("|", $val);
            foreach ($temp as $key => $value) {
              $msg = "";
              if ($key == 0) {
                $att['profile'] = $value;
                $msg = "profile : ";
              }
              if ($key == 1) {
                $att['nom'] = $value;
                $msg = "nom : ";
              }
              if ($key == 2) {
                $att['prenom'] = $value;
                $msg = "prenom : ";
              }
              if ($key == 3) {
                $att['eleveid'] = $value;
                $msg = "eleveid : ";
              }
              if ($key == 4) {
                $att['UaiEtab'] = $value;
                $msg = "UaiEtab : ";
              }
              $msg .= "'$value'";
              $log->LogDebug($msg);
            }
            array_push($attr, $att);
          } else {
            $log->LogDebug("le vecteur d'identite est vide");
            throw new Exception("le vecteur d'identite est vide");
          }
        }
      } else {
        throw new Exception("le vecteur d'identite est vide");
      }
    }
  } else {
    throw new Exception("la response de l'academie est vide");
  }
  $log->LogDebug("Vector extracted.");
  return $attr;
}

//extractEmail gets the email sent by the academie as an array
//extractEmail($attributes)['email']= user email.
function extractEmail($attributes) {
    $attr = array();
    if (!empty($attributes)) {
        if (array_key_exists('ctemail', $attributes)) {
            // echo 'key exists <br/>'; 
            $attr['email'] = $attributes['ctemail'][0];
            // i dont know if i had to  extract the name and the first name
        }
    }else{
      throw new Exception("l'academie n'pas envoyé d'information");
    }
    return $attr;
}

//exttractGoogleInfo returns an array of information sent by google
//OUTPUT : Array
//(
//    [prenom] => **
//    [email] => ***
//    [nom] => *****
//)
function extractGoogleInfo($attributes) {
    $info = array();
    if (!empty($attributes)) {
        foreach ($attributes as $key => $value) {
            if ($key == 'http://axschema.org/namePerson/first')
                $info['prenom'] = $value[0];
            if ($key == 'http://axschema.org/contact/email')
                $info['email'] = $value[0];
            if ($key == 'http://axschema.org/namePerson/last')
                $info['nom'] = $value[0];
        }
    }
    else{
         throw new Exception("l'academie n'pas envoyé d'information");
       }

    return $info;
}

//utility function to find the union of two arrays
function array_union($a, $b) {
    $union = array_map('unserialize', array_unique(array_map('serialize', array_merge($a, $b))));
    return $union;
}

//utility function to find the unique accounts in an array
function unique_by_person($a) {
    $temp = array();
    $index = array();
    foreach ($a as $record) {
        foreach ($record as $akey => $avalue) {
            foreach ($a as $row) {
                foreach ($row as $bkey => $bvalue) {
                    if ($akey == 'prenom' && $bkey == 'prenom' && $avalue != $bvalue && !in_array($avalue, $index)) {
                        array_push($temp, $record);
                        array_push($index, $avalue);
                    }
                }
            }
        }
    }
    return $temp;
}

// sendalert sends an email to the administrator when multiple accounts for the same person are found
function sendalert($attributes) {

    global $CONFIG;
    $to = $CONFIG['MAIL_ADMIN']; // admin email 
    //$to = 'bashar.ah.saleh@gmail.com';
    $subject = 'Differents ids pour la meme personne';
    $message = 'Bonjour,';
    $message = $message . "\n";
    $message = $message . "le système a trouvé des ids différents pour la même personne ! \n";
    $message = $message . "IDS: \n";

    foreach ($attributes as $record) {

        foreach ($record as $key => $value) {
            $message.= $key . " : " . $value . "\n";
        }
    }
    //$message = $message.; 
    $headers = 'From: webmaster@laclasse.com' . "\r\n" .
            'Reply-To: no-replay' . "\r\n" .
            'X-Mailer: PHP/';
    //
    mail($to, $subject, $message, $headers);
}

//login function handles the different cases of profile parent/eleve and make the good action (login to CAS server , redirect to inscription page, ..)
function login($attributes) {
    global $CONFIG;
    $log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
    $log->LogDebug("Login profile parent/eleve is called");
    try {
      if (empty($attributes)) { // no attributes sent by the idp 
        $log->LogError("Recieved identity vector :".print_r($attributes,true));
        throw new Exception("le serveur de la f&eacute;deration n\'a pas envoy&eacute; des attributs");
      } else {
        $attr = extractVector($attributes);
        $log->LogDebug("Recieved identity vector :".print_r($attributes,true));
        $log->LogDebug("extracted attributes:".print_r($attr,true));

        // print_r($attr) ;
        if (count($attr) > 0) {      // identity vector is successfully extracted
            if (count($attr) == 1) {  // case of one identity vector
                $nom = $attr[0]['nom'];
                $prenom = $attr[0]['prenom'];
                $eleveid = $attr[0]['eleveid'];
                $UaiEtab = $attr[0]['UaiEtab'];
                $profil = $attr[0]['profile'];

                //profil eleve
                if ($profil == 3 || $profil == 4) {
                    $factoryInstance = new DBFactory();
                    $db = $factoryInstance->createDB($CONFIG['DATABASE'], BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME);
                    $casattributes = $db->Search_Eleve_By_Name_SconetId($nom, $prenom, $eleveid);
                    $log->LogDebug("eleve attributes in database:".print_r($casattributes,true));

                }
                // profil parent 
                if ($profil == 1 || $profil == 2) {
                    $factoryInstance = new DBFactory();
                    $db = $factoryInstance->createDB($CONFIG['DATABASE'], BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME);
                    $casattributes = $db->Search_Parent_By_Name_EleveSconetId($nom, $prenom, $eleveid);
                }
                $log->LogDebug("casattributes".print_r($casattributes,true));
                $log->LogError("attributes in database:".print_r($casattributes,true));
                if (count($casattributes) == 1) { // one corresponding record is found in the database
                    $var = $casattributes[0]['login'];
                    //user has a default password that need to be changed
                    $log->LogDebug("user has default passowrd:".$db->has_default_password($var));
                    if ($db->has_default_password($var))
                        $db->update_password($var,  generatePassword(3,3));
                    CASLogin($casattributes[0]['login'], 'FIM');
                } else {
                    if (count($casattributes) == 0) {  // 'no matching is found'
                        if ($attr[0]["profile"] == 3 || $attr[0]["profile"] == 4) { // profile eleve·
                          // echo '<br> you dont have an account on the laclasse.com, you will be redirected to inscription page <br/>';
                            $log->LogError("retrieved cas attr :".print_r($casattributes,true));
                            throw new Exception ("Vous n'avez pas de compte sur le laclasse.com");
                        }
                        if ($attr[0]["profile"] == 1 || $attr[0]["profile"] == 2) { // profile parent
                          // echo '<br> you dont have an account on the laclasse.com, you will be redirected to inscription page <br/>';
                          $log->LogError("retrieved cas attr :".print_r($casattributes,true));
                          throw new Exception ("Vous n'avez pas de compte sur le laclasse.com");
                        }
                    } else { //'more than one record are found ! '
                      //sand an email to the administrator and then login in with the most recent idi
                        $log->LogError("multi accounts were found :".print_r($casattributes,true));
                        sendalert($casattributes);
                        if ($db->has_default_password($casattributes[0]['login']))
                            $db->update_password($casattributes[0]['login'], generatePassword(3,3));
                        CASLogin($casattributes[0]['login'], 'FIM');
                    }
                }
            } else {
                // actullay this version treat multiple identity vector for the same person
                $casattributes = array();
                $temp = array();
                if ($attr[0]['profile'] == 3 || $attr[0]['profile'] == 4) {
                  throw new Exception ("un vecteur multivalué pour un eleve");
                }
                $factoryInstance = new DBFactory();
                $db = $factoryInstance->createDB($CONFIG['DATABASE'], BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME);
                $casattributes = array();
                $temp = array();
                foreach ($attr as $record) {
                    $nom = $record['nom'];

                    $prenom = $record['prenom'];
                    $eleveid = $record['eleveid'];
                    $profil = $record['profile'];
                    $temp = array_union($db->Search_Parent_By_Name_EleveSconetId($nom, $prenom, $eleveid), $temp);

                    //print_r(Search_Parent_By_Name_Etab_EleveId($nom, $prenom, $eleveid)); 
                    $casattributes = $temp;
                    // print_r($temp); 
                }
                // echo '<br>account founded <br/>'; 
                // print_r($casattributes); 
                $log->LogError("CAS attributes in database:".print_r($casattributes,true));
                $log->LogDebug("eleve attributes in database:".print_r($casattributes,true));

                if (count($casattributes) == 1) { // one corresponding record is found in the database
                    $var = $casattributes[0]['login'];
                    //user has a default password that need to be changed
                    if ($db->has_default_password($var))
                        $db->update_password($var,  generatePassword(3,3));
                    CASLogin($casattributes[0]['login'], 'FIM');
                } else {
                    if (count($casattributes) == 0) {  // 'no matching is found ! '
                        // session_start();
                      // $_SESSION["noresult"]= $attr;
                      $log->LogError("retrieved cas attr :".print_r($casattributes,true));
                      throw new Exception ("Vous n'avez pas de compte sur le laclasse.com");
                    } else { //multiple corresponding records are found in the database! '
                        // session_start() ;
                        // $_SESSION["Result"]= $casattributes;
                        // send email to administrator and login the user. 
                        $unique = unique_by_person($casattributes);
                        //print_r($casattributes);
                        if (!empty($unique)) {  //famlilly account
                            // echo '<br>familly account<br/>';
                            // print_r($unique);
                            //session_start(); 
                            $_SESSION['famillyAccount'] = $unique;
                        } else {
                            sendalert($casattributes);
                            //user has a default password
                            if ($db->has_default_password($casattributes[0]['login']))
                            $db->update_password($casattributes[0]['login'],  generatePassword(3,3));
                            CASLogin($casattributes[0]['login'], 'FIM');
                            // echo 'person with multiple accounts';
                        }
                    }
                }
            }
        } else {
            throw new Exception ("Aucun vecteur d'identité est reçu de l'academie");
        }
      }
    }//try
    catch(Exception $e)
    {
      $log->LogError("Error: ".$e->getMessage());
      $log->LogError("Recieved identity vector :".print_r($attributes,true));

      logoutAcademie($e->getMessage());
      exit();
    }
}

//agent login handles the different cases of profile agent/prof 
function agentLogin($attributes) {
  global $CONFIG;
  $log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
  $log->LogDebug("Login profile agent is called");
  $log->LogDebug("recieved attributes from the academie".print_r($attributes,true));

  try{
    $email = extractEmail($attributes);
    $log->LogDebug("recieved email".print_r($email,true));
    //print_r($email);
    if (empty($email)) {
      $log->LogError("recieved attributes from the academie :".print_r($attributes,true));
       throw new Exception("le vecteur d'identité est vide");
    } else {
        // create database connection
        $factoryInstance = new DBFactory();
        $db = $factoryInstance->createDB($CONFIG['DATABASE'], BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME);

        $search = $db->Search_Agent_By_InsEmail($email['email']);
        if (empty($search)) {
          // echo 'the user does not exist in the database';
          $log->LogError("retrieved cas attr :".print_r($search,true));
          throw new Exception("Vous n'avez pas un compte sur le laclasse.com");
        } else {
            if (count($search) == 1) {
                //echo '' . $search[0]['login'];
                if ($db->has_default_password($search[0]['login']))
                    $db->update_password($search[0]['login'],  generatePassword(3,3)); 
                CASlogin($search[0]['login'], 'FIM');
            } else {
              //person with multiple accounts
                $log->LogError("retrieved cas attr :".print_r($search,true));
                sendalert($search);
                if ($db->has_default_password($search[0]['login']))
                    $db->update_password($search[0]['login'],  generatePassword(3,3)); 
                CASlogin($search[0]['login'], 'FIM');
            }
        }
    }
  }
  catch(Exception $e)
  {
    $log->LogError("Error: ".$e.getMessage);
    $log->LogError("recieved attributes from the acadmie".$attributes);
    logoutAcademie($e->getMessage());
    exit();
  }
}

function googlelogin($attributes) {
  global $CONFIG;
  $log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
  $log->LogInfo("Login with google id  is called");
  $log->LogDebug("recieved attributes from the academie".print_r($attributes,true));

  try {
    $info = extractGoogleInfo($attributes);
    $log->LogDebug("recieved information from google ".print_r($info,true));
    if (empty($info)) {
      throw new Exception("le vecteur d'identité est vide");
    } else {
      if (!array_key_exists('email', $info)) {
          throw new Exception("votre email n'est pas valide");
        } else {
            $factoryInstance = new DBFactory();
            $db = $factoryInstance->createDB($CONFIG['DATABASE'], BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME);
            $search = $db->Search_user_by_email($info['email']);
            if (empty($search)) {
              throw new Exception("Vous n'avez pas un compte sur le laclasse.com");
            } else {
                if (count($search) == 1) {
                    //echo '' . $search[0]['login'];·
                    CASlogin($search[0]['login'], 'GOOGLE');
                } else {
                    //person with multiple accounts
                    sendalert($search);
                    CASlogin($search[0]['login'], 'GOOGLE');
                }
            }
        }
    }
  }
  catch(Exception $e)
  {
    logoutAcademie($e->getMessage());
    exit();

  }
}
function logoutAcademie($message = "erreur")
{
  global $CONFIG;
  header("Content-type: text/html");
  echo '
        <!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
          <html xmlns="http://www.w3.org/1999/xhtml" lang="en">
             <head>
              <title>'._('Service d\'Authentification Central de laclasse.com').'</title>
              <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
              <style type="text/css" media="screen">@import \'../css/cas-laclasse.css\'/**/;</style>
                <!--[if gte IE 6]><style type="text/css" media="screen">@import \'../css/ie_cas.css\';</style><![endif]-->
              <script type="text/javascript">
                   function logout(){
                       //IE8 or lower
                       if (document.all && !document.addEventListener) {
                          //document//La deconnexion des services académique ne marche pas avec des iframes
                           //des//On est donc obligé d\'ouvrir une fenêtre...
                       window.open("https://services.ac-lyon.fr/login/ct_logout.jsp", "Deconnexion");
                       //window.open("?logout", "Deconnexion");
                      }
                    }
               </script></head>';
echo '<body id="cas" >
      <div id="page">
      <h1 id="app-name">'._('Service d\'Authentification Central de laclasse.com').'</h1>
      <div id="mire">
         <div id="status" class="errors" style="height:300px;">
            <h3>une erreur est survenue, les donn&eacute;es envoy&eacute;es par l\'academie ne permettent pas de vous identifier. </h3>
            <p> Message d\'erreur: '.$message.'.</p>
            <ul>
             <li><a href="'.INSCRIPTION_URL.'" onclick="logout();">cre&eacute;z un compte sur laclasse.com </a></li>
             <li> <a href="'.ENT_SERVER.'" onclick = "logout();"> Connectez-vous avec un compte laclasse.com </a></li>
             <li><A HREF="mailto:support@laclasse.com" onclick="logout();" > Envoyer le message d\'erreur au support</a></li>
           
            <iframe id="myIFrame"   style="display:none" src="https://services.ac-lyon.fr/login/ct_logout.jsp" ></iframe>
            <iframe id="myIFrame"   style="display:none" src="?logout" ></iframe>
          </div>
      </div>
      <div id="footer">
          <img src="../images/erasme.png" align="top" />
      </div>
      </body>
    </html>';

}

?>
