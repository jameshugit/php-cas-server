<?php
/*******************************************************************************
	@filename : login.php 
	@description : Gestion de template de login.
*******************************************************************************/
require_once(CAS_PATH.'/views/footer.php');
require_once(CAS_PATH.'/views/header.php');

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
	$lt = $t["loginTicket"];

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
		            <input type="hidden" name="loginTicket" value="'.$lt.'">
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

/*
function getFormLogin($t, $msg="") {
	$actionForm = $t["action"];
	$service = urldecode($t["service"]);
	$lt = $t["loginTicket"];
	echo '<div id="mire">';
	if ($msg != "") echo '<div id="status" class="errors">'.$msg.'</div>';
	echo '
			<form id="fm1" class="fm-v clearfix" method="post" action="'.$actionForm.'"> 
	
            <input type="hidden" name="action" value="login"/>
            <input type="hidden" name="service" value="'.$service.'"/>
            <input type="hidden" name="loginTicket" value="'.$lt.'"/>

             <div style = "border-bottom: solid #3399FF; background-color:white; width: 260px; padding: 5px;">
               <div class="row">
                 <p> <img src="images/AcademieLogo.jpeg" style="float: left;" /><b>Connectez-vous avec votre compte académique. </b> </p>
               </div>
               <div  style=" margin:0px auto ;  text-align:center;">
                    <p><a  href="lib/parentPortalIdp.php?login" class="tt" ><img src="images/parents_eleves1.png" alt="ADLyon"/><span class="tooltip"><span class="top"> </span>
                    <span class="middle">se connecter avec votre profil parent/élève de l\'academie de lyon</span><span class="bottom"></span></span>
                    </a><span style="margin-left:30px;"></span>
                    <a  href="lib/agentPortalIdp.php?login" class="tt"><img src="images/profs_agents1.png" alt="ADLyon"/><span class="tooltip"><span class="top"> </span>
                    <span class="middle">se connecter avec votre profil prof/agent  de l\'academie de lyon</span><span class="bottom"></span></span></a></p>
                    </div>
                </div>
            <div class="box" id="login">
              <h2>'._('Connectez-vous avec votre compte laclasse.').'</h2>
              <div class="row">
                <label for="username">'._('<span class="accesskey">I</span>dentifiant').':</label>
                <input id="username" name="username" class="required" tabindex="1" accesskey="i" type="text" value="" size="25" autocomplete="false"/>
              </div>
              <div class="row">
                <label for="password">'._('<span class="accesskey">M</span>ot de passe').':</label>
                <input id="password" name="password" class="required" tabindex="2" accesskey="m" type="password" value="" size="25"/>
              </div>

              <div class="row btn-row">
                <input type="hidden" name="lt" value="" />
                <input type="hidden" name="_eventId" value="submit" />

                <input class="btn-submit" name="submit" accesskey="l" value="'._('SE CONNECTER').'" tabindex="4" type="submit" />
                <input class="btn-reset" name="reset" accesskey="c" value="'._('EFFACER').'" tabindex="5" type="reset" />
              </div>

              <div class="row" style="padding:20px 0 0 27px;">
                <a href="http://www.laclasse.com/pls/public/!page.laclasse?contexte=QUESTION&rubrique=0">Mot de passe perdu</a>&nbsp;
                <a href="javascript:void()" onClick="javascript:open(\'http://www.laclasse.com/pls/public/!page.laclasse?contexte=CONTACT&rubrique=1\',\'win\', \'resizable=yes\')">Trouver de l\'aide</a>
              </div>
              <br class="clear" />
              <br />
              </div>

              <div id="sidebar">
              <p>'._('Pour des raisons de s&eacute;curit&eacute;, veuillez vous d&eacute;connecter et fermer votre navigateur lorsque vous avez fini d\'acc&eacute;der aux services authentifi&eacute;s.').'</p>
              <div id="list-languages">
              </div>
            </div>
          </form>
        </div>
';          
}*/

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
