<?php
/*******************************************************************************
	@filename : login.php 
	@description : Gestion de template de login.
*******************************************************************************/
require_once('footer.php');
require_once('header.php');

//------------------------------------------------------------------------------
// Callback getFormLogin
//------------------------------------------------------------------------------

function getFormLogin($service, $msg = "") {

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
					<a class="btn" href="parentPortalIdp?service='.urlencode($service).'">Parents/Elèves</a>
					<a class="btn" href="agentPortalIdp?service='.urlencode($service).'">Profs/Agents</a>
				</div>
			</div>

                       <br>
                       <div style="height: 2px; background-color: #fff; text-align: center; margin-bottom: 1em">
                            <span style="background-color: #48bbd6; position: relative; top: -0.5em; margin: 0px auto;font-weight: bold">&nbsp;OU&nbsp;</span>
                       </div>
                       <br>

			<div style="margin-bottom: 20px;">
				<div class="title">Connectez-vous avec votre compte Laclasse.com.</div>
				<form method="post" action="?">
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
//------------------------------------------------------------------------------
function viewLoginForm($service) {
	getHeader();
	getFormLogin($service);
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
function viewLoginFailure($service, $msg = "Les informations transmises n'ont pas permis de vous authentifier.") {
	getHeader();
	getFormLogin($service, $msg);
	getFooter();
}

//------------------------------------------------------------------------------

