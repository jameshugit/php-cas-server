<?php

/*
 * This script is meant as the core of the federation login profile parent éleve
 *
 */


/*
 * We need access to the various simpleSAMLphp classes. These are loaded
 * by the simpleSAMLphp autoloader.
 * 
 */
//add the path to the CAS configuration file.
require_once('/var/www/sso/config.inc.php'); 

//the simpleSAMlphp autoloader class
require_once(SimpleSamlPATH.'/_autoload.php');

require_once(CAS_PATH . '/lib/federation.php'); 
$profiles= array('agent'=>6, 'eleve'=>4 , 'parent'=>8); 

/*
 * We use the simpleexample authentication source defined in /SimpleSamlPATH/config/authsources.php.
 */
$as = new SimpleSAML_Auth_Simple('Agentportal');
$CASauthenticated = false; 
$attributes=array(); 
$var=''; 
$noresult=false; 

/* This handles logout requests. */
if (array_key_exists('logout', $_REQUEST)) {
	/*
	 * We redirect to the current URL _without_ the query parameter. This
	 * avoids a redirect loop, since otherwise it will access the logout
	 * endpoint again.
	 */
	
		/* Remove cookie from client */
		setcookie ("CASTGC", FALSE, 0, "/sso/");
		setcookie ("info", FALSE, 0);	
   // if (isset($_SESSION["noresult"]))

    $as->logout(SimpleSAML_Utilities::selfURLNoQuery());
   // $url = SimpleSAML_Utilities::selfURLNoQuery(); 
   // $c = curl_init('https://viesco.ac-lyon.fr/slo/response/AP');
   // curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
   // $page = curl_exec($c);
   // curl_close($c);


   // $as->logout('http://www.dev.laclasse.com/saml/example-simple/loginidp.php');
	/* The previous function will never return. */
}

if(array_key_exists("loginidp", $_POST)) { // this case is for treating the familly account after a callback
	$login = $_POST['loginidp'];
    echo 'you will be logged in as'. $login.'</br>'; 
	    CASlogin($login); 
}


if (array_key_exists('login', $_REQUEST)) {  //handling  the login request 
	/*
	 * If the login parameter is requested, it means that we should log
	 * the user in. We do that by requiring the user to be authenticated.
	 *
	 * Note that the requireAuth-function will preserve all GET-parameters
	 * and POST-parameters by default.
	   this is the IDP authentication..
	 */
	$as->requireAuth();
        $session = SimpleSAML_Session::getInstance();
	/* The previous function will only return if the user is authenticated to an IDP. */
	
  /* get attributes sent by the federation server */		

	$attributes = $as->getAttributes();
  // call the login function which treat all cases 
  //login($attributes); 
   print_r($attributes); 
  agentLogin($attributes); 
	
}

/*
 * We set a variable depending on whether the user is authenticated or not.
 * This allows us to show the user a login link or a logout link depending
 * on the authentication state.
 */
$isAuth = $as->isAuthenticated();



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<style type="text/css">
		  #popupbox{
		  margin: 0; 
		  margin-left: 40%; 
		  margin-right: 40%;
		  margin-top: 50px; 
		  padding-top: 10px; 
		  width: 20%; 
		  height: 150px; 
		  position: absolute; 
		  background: #FBFBF0; 
		  border: solid #000000 2px; 
		  z-index: 9; 
		  font-family: arial; 
		  visibility: hidden; 
	  }
	 </style>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<script language="JavaScript" type="text/javascript">
		  function login(showhide){
		    if(showhide == "show"){
			document.getElementById('popupbox').style.visibility="visible";
		    }else if(showhide == "hide"){
			document.getElementById('popupbox').style.visibility="hidden"; 
		    }
		  }
  </script>
<script type="text/javascript" src="../js/jquery.js"></script>
<script type="text/javascript" src="../js/jquery.lightbox_me.js"></script>
<script language="JavaScript" type="text/javascript">
$(function() {
            function launch() {
                 $('#sign_up').lightbox_me({centered: true, onLoad: function() { $('#sign_up').find('input:first').focus()}});
            }
            
            $('#try-1').click(function(e) {
                $("#sign_up").lightbox_me({centered: true, onLoad: function() {
					$("#sign_up").find("input:first").focus();
				}});
				
                e.preventDefault();
            });
            function WaitForIFrame(frame) {
                     if (frame.readyState != "complete") {
                       setTimeout("WaitForIFrame(frame);", 200);
                     } else { 
                         }
            };
            function done(){
              location.reload();
            }

            $('table tr:nth-child(even)').addClass('stripe');
            $('#logout').click(function(){
             //se deconnecter de FIM 
              $('#myIFrame').attr("src","?logout");WaitForIFrame($('#myIFrame'));
              //se deconnecter de l'academie de lyon
               $('#myIFrame2').attr("src","https://viesco.ac-lyon.fr/login/ct_logout.jsp"); WaitForIFrame($('#myIFrame2'));
              //return false;
             //location.reload();
              // $(location).reload();
             // window.location.replace("http://www.dev.laclasse.com/sso/lib/agentPortalIdp.php");
             // $(location).attr("href", "https://www.dev.laclasse.com/sso/logout?");
             // $.get('https://viesco.ac-lyon.fr/login/ct_logout.jsp');return false;
            return false;
                      });

});

    iframe = document.getElementById("myIFrame");
    function done() {
              //some code after iframe has been loaded
       }; 

</script>
<link rel="stylesheet" href="../css/style.css" type="text/css" media="screen" title="no title" charset="utf-8">
<link rel="stylesheet" href="../css/cas-laclasse.css" type="text/css" media="screen" title="no title" charset="utf-8">
	</head>
<body id="cas">
      <div id="page">
        <h1 id="app-name"> Service D'Authentification Central de laclasse.com</h1>

<?php
/* Show a logout message if authenticated or a login message if not. */

echo '<div id="mire">';
echo '<div class="box"  id="login">'; 
if ($isAuth) {

 // echo '<p> Vous êtes actuellement authentifié à l\'academie de lyon: <ol><li><a href="https://viesco.ac-lyon.fr/login/ct_logout.jsp"> se déconnecter de l\'academie de Lyon </a></li>'; 
 // echo '<li><a href="?logout">se déconnecter du serveur de féderatio</a></li></ol></p>';
  echo '<a id = "logout" href="#">
           se déconnecter </a>'; 
  echo '<iframe id="myIFrame" style="display:none" ></iframe>';
  echo '<iframe id="myIFrame2" style="display:none" ></iframe>';
	//echo '<p>Authenticate to server CAS <a href="?redirect">CAS Authentication</a>.</p>';
	     } 
else {
	echo '<p>Vous n\'êtes pas authentifié<a href="?login"> se connecter </a></p>';
     }
?>


<?php


if ($isAuth) {
/*	
if (isset($_COOKIE["info"]))
  echo "BienVenu " . $_COOKIE["info"] . "!<br />";
else
  echo "BienVenu visiteur!<br />";
 */
}


?>
</div>
</div>
        <div id="footer">
          <div id="copyleft">
            <p>ERASME 2011-2012. Logiciel sous <a href="http://fr.wikipedia.org/wiki/WTF_Public_License">license WTFPL</a>.</p>
            <p>D&eacute;velopp&eacute; et Maintenu par <a href="http://reseau.erasme.org">ERASME</a></p>
          </div>
          <a href="http://www.laclasse.com" title="http://www.laclasse.com/">http://www.laclasse.com</a>
        </div>
</div>
</body>
</html>
