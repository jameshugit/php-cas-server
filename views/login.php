<?php
/*******************************************************************************
	@filename : login.php 
	@description : Gestion de template de login.
*******************************************************************************/
require_once('footer.php');
require_once('header.php');

//------------------------------------------------------------------------------
// Callback getNewsList : displays news on login form
//------------------------------------------------------------------------------
function getNewsList($t) {
	global $CONFIG;
	$cache=false;
	
    /** Create Rediska instance **/
    $options = array('servers' => array());
    foreach ($CONFIG['REDIS_SERVERS'] as $srvary) {
      //error_log("Added server " . $srvary[0]);
      array_push($options['servers'], array('host' => $srvary[0], 'port' => $srvary[1]));
    }
	$cache = new Rediska($options);

	$news = str_replace($CONFIG['TWITTER_HASHTAG'], '', utf8_decode($cache->get($CONFIG['REDIS_NEWS_ROOT']."text")));
	
	if ($news != "0" && $news) {
		echo '
		<div id="newsbox">
    <div id="tweet">'.htmlentities($news).' (' . $cache->get($CONFIG['REDIS_NEWS_ROOT']."date") . ')</div>
			<div id="followus">'._('Suivez-nous sur').' <b><a href="https://twitter.com/'.str_replace('@', '', $CONFIG['TWITTER_ACCOUNT']).'">'.$CONFIG['TWITTER_ACCOUNT'].'</b></a></div>
		</div>
		<script>
		if(getRef("tweet")) fade("tweet", 252,237,49, 255,255,255, 100,1,10);
		</script>';
	}
}

//------------------------------------------------------------------------------
// Callback getFormLogin
//------------------------------------------------------------------------------

function getFormLogin($t, $msg="") {
	$actionForm = $t["action"];
	$service = urldecode($t["service"]);

	echo
'		<div class="box" style="max-width: 400px; text-align: left">';

	if ($msg != "") {
		echo
'			<div style="font-size: 30px; text-align: center; margin-bottom: 10px; padding: 10px; color: white; background-color: #eb5454;">
				Erreur
			</div>
			<div style="margin-bottom: 20px; color: #eb5454">
				<div>
					'.$msg.'
				</div>
			</div>
';
	}

	echo 
'			<div style="font-size: 30px; text-align: center; margin-bottom: 10px; padding: 10px; color: white; background-color: #1aaacc;">
				Authentification
			</div>

			<div style="margin-bottom: 20px;">
				<div class="title">Connectez-vous avec votre compte Académique.</div>
				<div>
					<a class="btn" href="lib/parentPortalIdp.php?login">Parents/Elèves</a>
					<a class="btn" href="lib/agentPortalIdp.php?login">Profs/Agents</a>
				</div>
			</div>

                       <br>
                       <div style="height: 2px; background-color: #fff; text-align: center; margin-bottom: 1em">
                            <span style="background-color: #48bbd6; position: relative; top: -0.5em; margin: 0px auto;font-weight: bold">&nbsp;OU&nbsp;</span>
                       </div>
                       <br>

			<div style="margin-bottom: 20px;">
				<div class="title">Connectez-vous avec votre compte Laclasse.com.</div>
				<form method="post" action="'.$actionForm.'">
		            <input type="hidden" name="action" value="login">
		            <input type="hidden" name="service" value="'.$service.'">
					<div>Identifiant:</div>
					<input name="username" type="text" style="width: 80%; margin-bottom: 10px;">
					<div>Mot de passe:</div>
					<input name="password" type="password" style="width: 80%; margin-bottom: 10px;">
					<br>
					<input class="btn" name="submit" type="submit" value="'._('SE CONNECTER').'">
				</form>
			</div>
		</div>
';
}

//------------------------------------------------------------------------------
// Callback viewLoginForm
// 
// Analyzing browser type and deciding if this is a mobile device or not.
//------------------------------------------------------------------------------
function viewLoginForm($t) {
	global $CONFIG;
	
	getHeader();
//	if ($CONFIG['DISPLAY_NEWS']) getNewsList($t);
	getFormLogin($t);
	getFooter();
}

//------------------------------------------------------------------------------
// Callback viewLoginSuccess
//------------------------------------------------------------------------------
function viewLoginSuccess() {
	getHeader();
	echo
'		<div class="box" style="max-width: 400px; text-align: left">
			<div style="font-size: 30px; text-align: center; margin-bottom: 10px; padding: 10px; color: white; background-color: #1aaacc;">
				Authentification
			</div>
			<div style="margin-bottom: 20px;">
				<div class="title">'._('Connexion r&eacute;ussie').'</div>
				<div>
					<p>'._('Vous vous &ecirc;tes authentifi&eacute;(e) aupr&egrave;s du Service Central d\'Authentification.').'</p>
					<p>'._('Pour des raisons de s&eacute;curit&eacute;, veuillez vous d&eacute;connecter et fermer votre navigateur lorsque vous avez fini d\'acc&eacute;der aux services authentifi&eacute;s.').'</p>
				</div>
			</div>
		</div>
';
	getFooter();
}

//------------------------------------------------------------------------------
// Callback viewLoginFailure
//------------------------------------------------------------------------------
function viewLoginFailure($t) {
	$msg = array_key_exists('errorMsg', $t)? $t['errorMsg'] : _("Les informations transmises n'ont pas permis de vous authentifier.");

	getHeader();
	getFormLogin($t, $msg);
	getFooter();
}

//------------------------------------------------------------------------------
?>
