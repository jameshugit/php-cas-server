<?php
//------------------------------------------------------------------------------
// Header 
//------------------------------------------------------------------------------
function getHeader(){
	header("Content-type: text/html");
	echo '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <title>'._('Service d\'Authentification Central de laclasse.com').'</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
      <style type="text/css" media="screen">@import \'css/cas-laclasse.css\'/**/;</style>
      <!--[if gte IE 6]><style type="text/css" media="screen">@import \'css/ie_cas.css\';</style><![endif]-->
      <script type="text/javascript" src="js/common_rosters.js"></script>
    </head>
    
    <body id="cas" onload="init();">
      <div id="page">
        <h1 id="app-name">'._('Service d\'Authentification Central de laclasse.com').'</h1>
';
}

//------------------------------------------------------------------------------
// Header formobile Device
//------------------------------------------------------------------------------
function getHeaderMobile(){
	header("Content-type: text/html");
	echo '
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>
        </title>
        <link rel="stylesheet" href="https://ajax.aspnetcdn.com/ajax/jquery.mobile/1.1.1/jquery.mobile-1.1.1.min.css" />
        <link rel="stylesheet" href="css/mobile.css" />
       
        <style>
            body {background-color:#ffffff;}
            .errors {
                  border: 1px dotted #D21033;
                  color: #D21033;
                  padding-bottom: 20px;
              }
              .info, .errors, .success {
                  clear: both;
                  font-size: 10px;
                  line-height: 1.5;
                  margin: 5px 0;
                  padding: 10px 20px 10px 20px;
                  
              }
        </style>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js">
        </script>
        <script src="https://ajax.aspnetcdn.com/ajax/jquery.mobile/1.1.1/jquery.mobile-1.1.1.min.js">
        </script>
        <script src="js/mobile.js">
        </script>
    </head>
    <body>
';
}


function getHeader2()
{
  header("Content-type: text/html");
echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
              <html xmlns="http://www.w3.org/1999/xhtml" lang="en">
                   <head>
                     <title>'._('Service d\'Authentification Central de laclasse.com').'</title>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <style type="text/css" media="screen">@import \'../css/cas-laclasse.css\'/**/;</style>
                     <!--[if gte IE 6]><style type="text/css" media="screen">@import \'css/ie_cas.css\';</style><![endif]-->
                     <script type="text/javascript" src="js/common_rosters.js"></script>
                     <script type="text/javascript" src="/var/www/sso/js/jquery.js"></script>
                     <script type="text/javascript" src="/var/www/sso/js/jquery.lightbox_me.js"></script>
                       <script language="JavaScript" type="text/javascript">
                       $(function() {
                          function launch() {
                            $(\'#sign_up\').lightbox_me({centered: true, onLoad: function() { $(\'#sign_up\').find(\'input:first\').focus()}});
                         }
                               $(\'#try-1\').click(function(e) {
                                      $(\'#sign_up\').lightbox_me({centered: true, onLoad: function() {
                                    $(\'#sign_up\').find(\'input:first\').focus();
                                     }});
                                                e.preventDefault();
                                           });
                                               $(\'table tr:nth-child(even)\').addClass(\'stripe\');
                                      });
                        </script>

                  <link rel="stylesheet" href="../css/style.css" type="text/css" media="screen" title="no title" charset="utf-8">
                         </head>
                    <body id="cas" onload="init();">
                    <div id="page">
                     <h1 id="app-name">'._('Service d\'Authentification Central de laclasse.com').'</h1>'; 


}

?>
