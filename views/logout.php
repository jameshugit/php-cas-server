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
	
	$url_service = $t['url'];
	if ($url_service == "") $redirectMsg = "";
	else $redirectMsg = _('Le service duquel vous arrivez a fourni <a href="'.$url_service.'">un lien que vous pouvez suivre en cliquant ici').'</a>';
	getHeader();
	logoutText($redirectMsg);
	getFooter();
}

//------------------------------------------------------------------------------
// Logout content page
//------------------------------------------------------------------------------
function logoutText($redirect) {

	echo
'		<div class="box" style="max-width: 400px; text-align: left">
			<div style="font-size: 30px; text-align: center; margin-bottom: 10px; padding: 10px; color: white; background-color: #1aaacc;">
				'._("D&eacute;connexion r&eacute;ussie").'
			</div>
			<div style="margin-bottom: 20px;">
				<div>
					<p>'._('Vous vous &ecirc;tes d&eacute;connect&eacute;(e) du Service Central d\'Authentification de Laclasse.com.').'</p>
					<p>'._('Pour des raisons de s&eacute;curit&eacute;, veuillez vous d&eacute;connecter et fermer votre navigateur lorsque vous avez fini d\'acc&eacute;der aux services authentifi&eacute;s.').'</p>
					<p>'.$redirect.'</p>
				</div>
			</div>
		</div>
';

}

//------------------------------------------------------------------------------
// Callback viewLogoutFailure
//------------------------------------------------------------------------------
function viewLogoutFailure() {
	getHeader();
	echo
'		<div class="box" style="max-width: 400px; text-align: left">
			<div style="font-size: 30px; text-align: center; margin-bottom: 10px; padding: 10px; color: white; background-color: #eb5454;">
				Erreur
			</div>
			<div style="margin-bottom: 20px; color: #eb5454">
				<div>
					'._("Probl&egrave;me de d&eacute;connexion...").'
				</div>
			</div>
		</div>
';		
	getFooter();
}

//------------------------------------------------------------------------------
?>
