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
	/** Create Memcached instance **/
	$cache = new Rediska();

	foreach ($CONFIG['REDIS_SERVERS'] as $srvary) {
	    $cache->addServer($srvary[0], $srvary[1]);
	}

	$news = str_replace($CONFIG['TWITTER_HASHTAG'], '', utf8_decode($cache->get($CONFIG['REDIS_NEWS_ROOT'].".text")));
	
	if ($news != "0" && $news) {
		echo '
		<div id="newsbox">
    <div id="tweet">'.htmlentities($news).' (' . $cache->get($CONFIG['REDIS_NEWS_ROOT'].".date") . ')</div>
			<div id="followus">'._('Suivez-nous sur').' <b><a href="https://twitter.com/'.str_replace('@', '', $CONFIG['TWITTER_ACCOUNT']).'">'.$CONFIG['TWITTER_ACCOUNT'].'</b></a></div>
		</div>
		<script>
		//(elId, sr, sg, sb, er, eg, eb, step, current, speed)
		if(getRef("tweet")) fade("tweet", 252,237,49, 255,255,255, 100,1,10);
		</script>';
	}
}

//------------------------------------------------------------------------------
// Callback getFormLogin
//------------------------------------------------------------------------------
function getFormLogin($t) {
	$actionForm = $t["action"];
	$service = urldecode($t["service"]);
	$lt = $t["loginTicket"];
	echo '
			<form id="fm1" class="fm-v clearfix" method="post" action="'.$actionForm.'"> 
	
            <input type="hidden" name="action" value="login"/>
            <input type="hidden" name="service" value="'.$service.'"/>
            <input type="hidden" name="loginTicket" value="'.$lt.'"/>
            
            <div class="box" id="login">
              <h2>'._('Entrez votre identifiant et votre mot de passe.').'</h2>
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
';          
}

//------------------------------------------------------------------------------
// Callback viewLoginForm
//------------------------------------------------------------------------------
function viewLoginForm($t) {
	global $CONFIG;
	getHeader();
	if ($CONFIG['DISPLAY_NEWS']) getNewsList($t);
	echo '<div id="mire">';
	getFormLogin($t);
	echo '</div>';
	getFooter();
}

//------------------------------------------------------------------------------
// Callback viewLoginSuccess
//------------------------------------------------------------------------------
function viewLoginSuccess() {
	getHeader();
	echo '
	<div id="mire">
		<div id="msg" class="success">
			<h2>'._('Connexion r&eacute;ussie').'</h2>
			<p>'._('Vous vous &ecirc;tes authentifi&eacute;(e) aupr&egrave;s du Service Central d\'Authentification.').'</p>
			<p>'._('Pour des raisons de s&eacute;curit&eacute;, veuillez vous d&eacute;connecter et fermer votre navigateur lorsque vous avez fini d\'acc&eacute;der aux services authentifi&eacute;s.').'</p>
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
	echo '<div id="mire">';
	echo '		<div id="status" class="errors">'.$msg.'</div>';
	getFormLogin($t);
	echo '</div>';
	getFooter();
}

//------------------------------------------------------------------------------
?>
