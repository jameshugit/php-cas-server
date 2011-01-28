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
	$url_service = $t['url'];
	if ($url_service == "") $redirectMsg = "";
	else $redirectMsg = _('Le service duquel vous arrivez a fourni <a href="'.$url_service.'">un lien que vous pouvez suivre en cliquant ici').'</a>';
	getHeader();
	echo '
		<div id="msg" class="success">
			<h2>'._("D&eacute;connexion réussie").'</h2>
			<p>'._('Vous vous &ecirc;tes d&eacute;connect&eacute;(e) du Service Central d\'Authentification de Laclasse.com.').'</p>
			<p>'._('Pour des raisons de s&eacute;curit&eacute;, veuillez vous d&eacute;connecter et fermer votre navigateur lorsque vous avez fini d\'acc&eacute;der aux services authentifi&eacute;s.').'</p>
			<p>'.$redirectMsg.'</p>
		</div>
';
	getFooter();
}

//------------------------------------------------------------------------------
// Callback viewLogoutFailure
//------------------------------------------------------------------------------
function viewLogoutFailure() {
	getHeader();
	echo '<div id="status" class="errors">'._("Probl&egrave;me de déconnexion...").'.</div>';
	getFooter();
}

//------------------------------------------------------------------------------
?>