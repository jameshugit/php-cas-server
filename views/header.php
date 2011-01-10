<?php
//------------------------------------------------------------------------------
// Footer 
//------------------------------------------------------------------------------
function getHeader(){
	echo '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <title>Service d\'Authentification Central de laclasse.com</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
      <style type="text/css" media="screen">@import \'css/cas-laclasse.css\'/**/;</style>
      <!--[if gte IE 6]><style type="text/css" media="screen">@import \'css/ie_cas.css\';</style><![endif]-->
      <script type="text/javascript" src="js/common_rosters.js"></script>
    </head>
    
    <body id="cas" onload="init();">
      <div id="page">
        <h1 id="app-name">Service d\'Authentification Central de l\'ENT laclasse.com</h1>
                <div id="mire"><!--Pierre Gilles-->

';
}
?>