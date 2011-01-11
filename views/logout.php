<?php
/*******************************************************************************
	@filename : logout.php 
	@description : Gestion de template de logout.
*******************************************************************************/
require_once('footer.php');
require_once('header.php');

//------------------------------------------------------------------------------
// Callback viewLogoutSuccess
//------------------------------------------------------------------------------
function viewLogoutSuccess($t) {
	$url_service = $t['url'];
	if ($url_service == "") $redirectMsg = "";
	else $redirectMsg = 'Le service duquel vous arrivez a fourni <a href="'.$url_service.'">un lien que vous pouvez suivre en cliquant ici</a>';
	getHeader();
	echo '
		<div id="msg" class="success">
			<h2>D&eacute;connexion r&eacute;ussie</h2>
			<p>Vous vous &ecirc;tes d&eacute;connect&eacute;(e) du Service Central d\'Authentification de Laclasse.com.</p>
			<p>Pour des raisons de s&eacute;curit&eacute;, veuillez fermer votre navigateur.</p>
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
	echo '<div id="status" class="errors">Les informations transmises n\'ont pas permis de vous authentifier.</div>';
	getFooter();
}

//------------------------------------------------------------------------------
?>
