<?php
/*******************************************************************************
	@filename : logout.php 
	@description : Gestion de template de logout.
*******************************************************************************/
require_once(CAS_PATH.'/views/footer.php');
require_once(CAS_PATH.'/views/header.php');

//------------------------------------------------------------------------------
// Callback viewLogoutSuccess
//------------------------------------------------------------------------------
function viewLogoutSuccess($t) {
	global $CONFIG;
	
  $device = ""; // By default the device is a true real pc browser (not a mobile one)
  if ($_SESSION['isMobile']) {
    $device = 'Mobile';
  }

	$url_service = $t['url'];
	if ($url_service == "") $redirectMsg = "";
	else $redirectMsg = _('Le service duquel vous arrivez a fourni <a href="'.$url_service.'">un lien que vous pouvez suivre en cliquant ici').'</a>';
	call_user_func('getHeader'.$device);
	call_user_func('logoutText'.$device, $redirectMsg);
	call_user_func('getFooter'.$device);
}

//------------------------------------------------------------------------------
// Logout content page
//------------------------------------------------------------------------------
function logoutText($redirect){
    global $CONFIG;
    if ($CONFIG['DISPLAY_NEWS']) getNewsList($t);
	echo '
	<div id="mire">
		<div id="msg" class="success">
			<h2>'._("D&eacute;connexion r&eacute;ussie").'</h2>
			<p>'._('Vous vous &ecirc;tes d&eacute;connect&eacute;(e) du Service Central d\'Authentification de Laclasse.com.').'</p>
			<p>'._('Pour des raisons de s&eacute;curit&eacute;, veuillez vous d&eacute;connecter et fermer votre navigateur lorsque vous avez fini d\'acc&eacute;der aux services authentifi&eacute;s.').'</p>
			<p>'.$redirect.'</p>
		</div>
	</div>
';
}

//------------------------------------------------------------------------------
// Logout content page for mobile
//------------------------------------------------------------------------------
function logoutTextMobile($redirect){
  echo '       <div data-role="page" id="page1">
            <div data-theme="a" data-role="header">
                <h3>
                    Laclasse.com
                </h3>
            </div>
            <div data-role="content" style="padding: 15px">
                <div style=" text-align:center">
                    <img style="width: 120px; height: 120px" src="http://www.laclasse.com/v25/images/logo-laclasse.gif" />
                </div>
                <h2>
                    Déconnexion réussie
                </h2>
                <p>
                    Vous vous êtes déconnecté(e) du Service Central d\'Authentification de Laclasse.com.
                </p>
                <p>'.$redirect.'</p>
            </div>
        </div>';
}
//------------------------------------------------------------------------------
// Callback viewLogoutFailure
//------------------------------------------------------------------------------
function viewLogoutFailure() {
  $device = ""; // By default the device is a true real pc browser (not a mobile one)
  if ($_SESSION['isMobile']) {
    $device = 'Mobile';
  }

	call_user_func('getHeader'.$device);
	echo '<div id="status" class="errors">'._("Probl&egrave;me de d&eacute;connexion...").'.</div>';
	call_user_func('getFooter'.$device);
}

//------------------------------------------------------------------------------
?>
