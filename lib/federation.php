<?php

/*
 * We need access to the various simpleSAMLphp classes. These are loaded
 * by the simpleSAMLphp autoloader.
 * 
 */
//add the path to the CAS configuration file.
require_once('/var/www/sso/config.inc.php'); 

//the simpleSAMlphp autoloader class
//require_once(SimpleSamlPATH.'/_autoload.php');

//require_once('/var/www/sso/lib/backend.db.oracle.php'); 
//include_once('../../../sso/index.php');
require_once(CAS_PATH .'/lib/ticket.php');
//require_once(CAS_PATH . '/views/error.php');
//require_once(CAS_PATH . '/views/login.php');
//require_once(CAS_PATH . '/views/logout.php');
//require_once(CAS_PATH . '/views/auth_failure.php');
require_once(CAS_PATH . '/lib/backend.db.oracle.php');

//CASLogin function takes the (laclasse login) as input and generates  a ticket to login to Laclasse server.
function CASLogin($nom,$idp) {
        global $CONFIG;
        $Casurl = 'https://www.dev.laclasse.com/sso/login';
        // $service = 'https://www.dev.laclasse.com/saml/example-simple/loginidp.php';
        if($idp=='FIM')
        // I ADDED LoggedFromExternalFim = Y  parameter to assure that the authentification is issued by  the academie.
        $service = 'http://www.dev.laclasse.com/pls/education/!page.laclasse?LoggedFromExternalFim=Y'; 
        else 
        {
          if($idp='GOOGLE') //if the authentication is issued by GOOGLE
             $service = 'http://www.dev.laclasse.com/pls/education/!page.laclasse?LoggedFromGoogle=Y';
          else 
             $service = 'http://www.dev.laclasse.com/pls/education/!page.laclasse'; 
        }

        if (!array_key_exists('CASTGC', $_COOKIE)) { /*** user has no TGC ***/
            
                /* user has no TGC 
                  => send TGT
                  => redirect to login with a service
                 */
                    $ticket = new TicketGrantingTicket();
                    $ticket->create($nom);

                    /* send TGC */
                    setcookie("CASTGC", $ticket->key(), 0,"/sso/");
		              // setcookie("info", $nom, 0);
                    
		    /* Redirect to /login */
                    header("Location: " . url($Casurl) . "service=" . urlencode($service) . "");
                
            }
         else { /*** user has TGC ***/
            
             
            

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
                    setcookie("CASTGC", $ticket->key(), 0,"/sso/");
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

function extractVector($attributes)
{
  $attr=array();
  if(!empty($attributes))
  {
    if(array_key_exists('FrEduVecteur',$attributes))
    {
      if(count($attributes['FrEduVecteur'])>=1)
      {
        foreach($attributes['FrEduVecteur']as $val)
        {
      $temp=explode("|",$val);
      foreach ($temp as $key => $value){
        if ($key==0) $att['profile']=$value;
        if ($key==1) $att['nom']=$value;
        if ($key==2) $att['prenom']=$value;
        if ($key==3) $att['eleveid']=$value;
        if ($key==4) $att['UaiEtab']=$value;
      }
      array_push($attr, $att); 
        }
      }
    }
  }
    return $attr;


}
//extractEmail gets the email sent by the academie as an array
//extractEmail($attributes)['email']= user email.
function extractEmail($attributes)
     {
        $attr=array();
         if(!empty($attributes))
         {
           if(array_key_exists('ctemail',$attributes))
           {
              // echo 'key exists <br/>'; 
                 $attr['email']=$attributes['ctemail'][0];
              // i dont know if i had to  extract the name and the first name
           }
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
function extractGoogleInfo($attributes)
{
   $info = array(); 
       if (!empty($attributes))
             {
                   foreach($attributes as $key => $value)
                       {
                          if($key=='http://axschema.org/namePerson/first')
                              $info['prenom']=$value[0]; 
                          if($key=='http://axschema.org/contact/email')
                              $info['email']=$value[0];
                          if($key=='http://axschema.org/namePerson/last')
                              $info['nom']=$value[0]; 
                         }
            }
           return $info; 
}

//utility function to find the union of two arrays
function array_union($a,$b)
{
  $union = array_map('unserialize', array_unique(array_map('serialize',array_merge($a,$b)))); 
  return $union; 

}

//utility function to find the unique accounts in an array
function unique_by_person($a)
{
      $temp = array(); 
          $index = array(); 
           foreach($a as $record){
                      foreach ($record as $akey=>$avalue)
                                 {
                                     foreach($a as $row)
                                     {
                                       foreach($row as $bkey=>$bvalue)
                                       {  
                                         if($akey == 'prenom' && $bkey == 'prenom'  && $avalue!=$bvalue && !in_array($avalue, $index))
                                         {
                                          array_push($temp, $record);
                                          array_push($index,$avalue); 
                                         }
                                        }
                                       }
                                      }
                           }
           return $temp;
}

// sendalert sends an email to the administrator when multiple accounts for the same person are found
function sendalert($attributes)
       {
        
                     global $CONFIG;
                     $to = $CONFIG['MAIL_ADMIN'];// admin email 
                    //$to = 'bashar.ah.saleh@gmail.com';
                     $subject = 'Differents ids pour la meme personne';
                     $message = 'Bonjour,';
                     $message = $message."\n"; 
                     $message = $message. "le système a trouvé des ids différents pour la même personne ! \n";
                     $message=$message."IDS: \n";

                     foreach ($attributes as $record) {
                                                             
                         foreach ($record as $key => $value) {
                        $message.= $key." : ".$value."\n";   
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
function login($attributes){

if(empty($attributes)) // no attributes sent by the idp 
            {
              echo 'le serveur de la féderation n\'a pas envoyé des attributs, vous allez être redirigé vers la page d\'inscription';
              echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
               exit();

            }
          else
            {
              $attr= extractVector($attributes);
            
            // print_r($attr) ;
             if(count($attr)>0)      // identity vector is successfully extracted
              {
                   if(count($attr)==1)  // case of one identity vector
                       {
                             $nom = $attr[0]['nom'];
                             $prenom = $attr[0]['prenom'];
                             $eleveid = $attr[0]['eleveid'];
                             $UaiEtab = $attr[0]['UaiEtab'];
                             $casattributes = Search_Parent_By_Name_Etab_EleveId($nom, $prenom, $eleveid);
                             if(count($casattributes)==1) // one corresponding record is found in the database
                             {
                                $var=$casattributes[0]['login'];
                                CASLogin($casattributes[0]['login'],'FIM');
                             }
                             else
                             {
                                 if (count($casattributes)==0)  // 'no matching is found'
                                  {


                                    if($attr[0]["profile"]==3 || $attr[0]["profile"]==4) { // profile eleve·

                                      // echo '<br> you dont have an account on the laclasse.com, you will be redirected to inscription page <br/>';
                                       echo '<h1>Vous n\'avez pas de compte sur le laclasse.com, vous serez redirig&eacute; vers la page d\'inscription </h1>';
                                      echo '<META HTTP-EQUIV="Refresh" Content="2; URL= http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&profil=ELEVE&petape=2&rubrique=0">';
                                     exit();
                                      }
                                    if ($attr[0]["profile"]==1 || $attr[0]["profile"]==2) { // profile parent

                                      // echo '<br> you dont have an account on the laclasse.com, you will be redirected to inscription page <br/>';
                                       echo '<h1>Vous n\'avez pas de compte sur le laclasse.com, vous serez redirig&eacute; vers la page d\'inscription </h1>';
                                     echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
                                     exit();
                                     }

                                  }
                                   else //'more than one record are found ! '
                                   {

                                                //sand an email to the administrator and then login in with the most recent id
                                                 sendalert($casattributes);
                                                 CASLogin($casattributes[0]['login'],'FIM');

                                   }
                               }

                       }
                        else
                        {
                          // actullay this version treat multiple identity vector for the same person
                          $casattributes = array();
                         $temp=array();
                         foreach($attr as $record)
                         {
                           $nom = $record['nom']; 
                          
                           $prenom = $record['prenom'];
                           $eleveid = $record['eleveid'];
                           $UaiEtab = $record['UaiEtab'];
                             $temp =  array_union(Search_Parent_By_Name_Etab_EleveId($nom, $prenom, $eleveid), $temp);
                             //print_r(Search_Parent_By_Name_Etab_EleveId($nom, $prenom, $eleveid)); 
                           $casattributes = $temp; 
                          // print_r($temp); 
                         }
                       // echo '<br>account founded <br/>'; 
                       // print_r($casattributes); 
                        
                           if(count($casattributes)==1) // one corresponding record is found in the database
                            {
                              $var=$casattributes[0]['login'];
                              CASLogin($casattributes[0]['login'],'FIM');
                            }
                           else
                                {
                                  if (count($casattributes)==0)  // 'no matching is found ! '
                                     {
                                       // session_start();
                                       // $_SESSION["noresult"]= $attr;
                                        echo ' <h1>Vous n\'avez pas de compte sur le laclasse.com, vous serez redirig&eacute; vers la page d\'inscription </h1>';
                                       echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
                                       exit();

                                     }
                                  else //multiple corresponding records are found in the database! '
                                    {
                                      // session_start() ;
                                      // $_SESSION["Result"]= $casattributes;
                                      // send email to administrator and login the user. 
                                      $unique= unique_by_person($casattributes); 
                                     //print_r($casattributes);
                                      if(!empty($unique)) {  //famlilly account
                                       // echo '<br>familly account<br/>';
                                       // print_r($unique);
                                        //session_start(); 
                                        $_SESSION['famillyAccount']=$unique; 
                                      }
                                     else{
                                       sendalert($casattributes);
                                       CASLogin($casattributes[0]['login'],'FIM');
                                        // echo 'person with multiple accounts';
                                       }


                                    }
                               }


                        }
              }
             else{
               echo '<h1>Aucun vecteur d\'identité est reçu, vous serez redirigé vers la page d\'inscription</h1>';
               echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
               exit();

                 }
            }

}

//agent login handles the different cases of profile agent/prof 
function agentLogin($attributes)
{
   $email =  extractEmail($attributes);
   //print_r($email);
   if(empty($email))
   {
      echo 'le vecteur d\'identité est vide;vous serez redirigé vers la page d\'inscription <br/>';
      echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
      exit();
   }
   else
   {
     $search= Search_Agent_by_mail($email['email']); 
     if(empty($search))
     {
       // echo 'the user does not exist in the database';
       echo ' <h1>Vous n\'avez pas un compte sur le laclasse.com, vous serez redirigé vers la page d\'inscription </h1>';
       // redirect to inscription page
       // may be i had to add some information to the request
       echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
      exit();

     }
     else 
     {
       if(count($search)==1)
       {
       //echo '' . $search[0]['login']; 
       CASlogin($search[0]['login'],'FIM'); 
       }
       else
       {
         //person with multiple accounts
         sendalert($search); 
         CASlogin($search[0]['login'],'FIM');
       }
     }

   }
}
function googlelogin($attributes)
{
  $info = extractGoogleInfo($attributes); 
  if(empty($info))
  {
     echo 'le vecteur d\'identité est vide;vous serez redirigé vers la page d\'inscription <br/>';
     echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
     exit();

  }else
  {
     if(!array_key_exists('email', $info))
     {
          echo ' votre email n\'est pas valide,  vous serez redirigé vers la page d\'inscription <br/>';
           echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
          exit();

     }
     else {
        $search = Search_user_by_email($info['email']); 
        if(empty($search))
        {
          echo ' <h1>Vous n\'avez pas un compte sur le laclasse.com, vous serez redirigé vers la page d\'inscription </h1>';
           // redirect to inscription page
           // may be i had to add some information to the request
            echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
           exit();

        }
        else  
        {
          if(count($search)==1)
             { 
               //echo '' . $search[0]['login'];·
                CASlogin($search[0]['login'],'GOOGLE');
              } 
               else
               { 
                 //person with multiple accounts
               sendalert($search);
                CASlogin($search[0]['login'],'GOOGLE');
               } 


        }
     }
  }
}


?>
