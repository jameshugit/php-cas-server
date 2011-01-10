<?php
//------------------------------------------------------------------------------
// Callback viewLoginForm
//------------------------------------------------------------------------------
function viewLoginForm($t) {
	print_r($_SERVER);
	print_r($t);
	$url_service = $t["SERVICE"];
}

//------------------------------------------------------------------------------
// Callback viewLoginSuccess
//------------------------------------------------------------------------------

//------------------------------------------------------------------------------
// Callback viewLoginFailure
//------------------------------------------------------------------------------


//------------------------------------------------------------------------------
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <title>Service d'Authentification Central de laclasse.com</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
      <style type="text/css" media="screen">@import 'css/cas-laclasse.css'/**/;</style>
      <!--[if gte IE 6]><style type="text/css" media="screen">@import 'css/ie_cas.css';</style><![endif]-->
      <script type="text/javascript" src="js/common_rosters.js"></script>
    </head>
    
    <body id="cas" onload="init();">
      <div id="page">
        <h1 id="app-name">Service d'Authentification Central de l'ENT laclasse.com</h1>
        <div id="mire"><!--Pierre Gilles-->
          
<!--          <form id="fm1" class="fm-v clearfix" method="post" action="<?= str_replace('.php', '', $_SERVER['PHP_SELF']) ?>" onsubmit="submitMyCredential();"> -->
          <form id="fm1" class="fm-v clearfix" method="post" action="<? echo $url_service; ?>" onsubmit="submitMyCredential();"> 
            
            <div class="box" id="login">
              <h2>Entrez votre identifiant et votre mot de passe.</h2>
              <div class="row">
                <label for="username"><span class="accesskey">I</span>dentifiant:</label>
                <input id="username" name="username" class="required" tabindex="1" accesskey="i" type="text" value="" size="25" autocomplete="false"/>
              </div>
              <div class="row">
                <label for="password"><span class="accesskey">M</span>ot de passe:</label>
                <input id="password" name="password" class="required" tabindex="2" accesskey="m" type="password" value="" size="25"/>
              </div>

              <div class="row btn-row">
                <input type="hidden" name="lt" value="" />
                <input type="hidden" name="_eventId" value="submit" />

                <input class="btn-submit" name="submit" accesskey="l" value="SE CONNECTER" tabindex="4" type="submit" />
                <input class="btn-reset" name="reset" accesskey="c" value="EFFACER" tabindex="5" type="reset" />
              </div>

              <div class="row" style="padding:20px 0 0 27px;">
                <a href="http://www.laclasse.com/pls/public/!page.laclasse?contexte=QUESTION&rubrique=0">Mot de passe perdu</a>&nbsp;
                <a href="javascript:void()" onClick="javascript:open('http://www.laclasse.com/pls/public/!page.laclasse?contexte=CONTACT&rubrique=1','win', 'resizable=yes')">Trouver de l'aide</a>
              </div>
              <br class="clear" /><!--PGL-->
              <br /><!--PGL-->
            </div>
               
            <div id="sidebar">
              <p>Pour des raisons de sécurité, veuillez vous déconnecter et fermer votre navigateur lorsque vous avez fini d'accéder aux services authentifiés.</p>
              <div id="list-languages">
              </div>
            </div>
          </form>
          <script>
          function submitMyCredential() {
            //document.getElementById('username').value=document.getElementById('username').value.toUpperCase();
            var u = document.getElementById('username');
            var p = document.getElementById('password');
            u.value = u.value.toUpperCase();
            p.value = p.value.toLowerCase();
          }
          </script>
        </div>
        <div id="footer">
          <div>
            <p>Copyright &copy; 2011 ERASME. Tout droits r&eacute;serv&eacute;s.</p>
            <p>Maintenu par ERASME</p>
          </div>
          <a href="http://www.laclasse.com" title="http://www.laclasse.com">http://www.laclasse.com</a>
        </div>
      </div>
    </body>
</html>

