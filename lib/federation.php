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

function CASLogin($nom) {
        global $CONFIG;
        $Casurl = 'https://www.dev.laclasse.com/sso/login';
       // $service = 'https://www.dev.laclasse.com/saml/example-simple/loginidp.php';
        $service = 'http://www.dev.laclasse.com/pls/education/!page.laclasse'; 
        

        if (!array_key_exists('CASTGC', $_COOKIE)) { /*** user has no TGC ***/
            
                /* user has no TGC 
                  => send TGT
                  => redirect to login with a service
                 */
                    $ticket = new TicketGrantingTicket();
                    $ticket->create($nom);

                    /* send TGC */
                    setcookie("CASTGC", $ticket->key(), 0,"/sso/");
		     setcookie("info", $nom, 0);
                    
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

// extractVector function gets the vector send by the academie and returns an array that 
// contains the different information in the vector.
//

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


function sendalert($attributes)
       {
        
                     global $CONFIG;
                    // $to = $CONFIG['MAIL_ADMIN'];
                     $to = 'bashar.ah.saleh@gmail.com';
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

function login($attributes){

if(empty($attributes)) // no attributes sent by the idp 
            {
                  echo 'no attributes could be found you will be redirected to the inscription page';
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
                                CASLogin($casattributes[0]['login']);
                             }
                             else
                             {
                                 if (count($casattributes)==0)  // 'no matching is found'
                                  {


                                    if($attr[0]["profile"]==3 || $attr[0]["profile"]==4) { // profile eleve·

                                       echo '<br> you dont have an account on the laclasse.com, you will be redirected to inscription page <br/>';
                                      echo '<META HTTP-EQUIV="Refresh" Content="2; URL= http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&profil=ELEVE&petape=2&rubrique=0">';
                                     exit();
                                      }
                                    if ($attr[0]["profile"]==1 || $attr[0]["profile"]==2) { // profile parent

                                       echo '<br> you dont have an account on the laclasse.com, you will be redirected to inscription page <br/>';
                                     echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
                                     exit();
                                     }

                                  }
                                   else //'more than one record are found ! '
                                   {

                                                //sand an email to the administrator and then login in with the most recent id
                                                 sendalert($casattributes);
                                                 CASLogin($casattributes[0]['login']);

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
                              CASLogin($casattributes[0]['login']);
                            }
                           else
                                {
                                  if (count($casattributes)==0)  // 'no matching is found ! '
                                     {
                                       // session_start();
                                       // $_SESSION["noresult"]= $attr;
                                       echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
                                       exit();

                                     }
                                  else //multiple corresponding records are found in the database! '
                                    {
                                      // session_start() ;
                                      // $_SESSION["Result"]= $casattributes;
                                      // send email to administrator and login the user. 
                                      $unique= unique_by_person($casattributes); 
                                      if(!empty($unique)) {  //famlilly account
                                       // echo '<br>familly account<br/>';
                                        //print_r($unique);
                                        //session_start(); 
                                        $_SESSION['famillyAccount']=$unique; 
                                      }
                                     else{
                                       sendalert($casattributes);
                                       CASLogin($casattributes[0]['login']);
                                        // echo 'person with multiple accounts';
                                       }


                                    }
                               }


                        }
              }
             else{
               echo 'no identity vector is found you will be redirected to inscription  page'; 
               echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
               exit();

                 }
            }

}

function agentLogin($attributes)
{
   $email =  extractEmail($attributes);
   print_r($email);
   if(empty($email))
     echo 'empty identity vector <br/>';
   else
   {
     $search= Search_Agent_by_mail($email['email']); 
     if(empty($search))
     {
       // echo 'the user does not exist in the database';
       // redirect to inscription page
       // may be i had to add some information to the request
       echo '<META HTTP-EQUIV="Refresh" Content="2; http://www.dev.laclasse.com/pls/public/!page.laclasse?contexte=INSCRIPTION&rubrique=0">';
       exit();

     }
     else 
     {
       echo 'the user will be logedd in as:' . $search[0]['login']; 
       CASlogin($search[0]['login']); 
     }

   }
}

?>

