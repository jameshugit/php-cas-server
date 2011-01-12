<?php
/*******************************************************************************
	@filename : config.inc.php 
	@description : Fichier de configuration du serveur.
*******************************************************************************/

/**
 * Configuration directives
 */
$CONFIG['MODE'] = 'dev';
$CONFIG['MEMCACHED_SERVERS'] = array(array('localhost', 11211));

/**
 * Authentication backend
 */
include_once('lib/backend.db.oracle.php');
//include_once('lib/backend.ldap.php');

//------------------------------------------------------------------------------
// Constantes de connexion au Backend.
//------------------------------------------------------------------------------
define('BACKEND_DBNAME', '//db.dev.laclasse.com:1521/MAQ1020');
define('BACKEND_DBUSER', 'laclasse_frmwrk');
define('BACKEND_DBPASS', '6n2ml29y');

//------------------------------------------------------------------------------
// Requete SQL de validation des login/pwd
//------------------------------------------------------------------------------
define('SQL_AUTH', 'select count(1) from utilisateurs u where u.login = lower(:LOGIN) and u.pwd = lower(:PWD)');

//------------------------------------------------------------------------------
// Requete SQL d'extration des donnÃ©es pour le jeton d'authentification CAS.
//------------------------------------------------------------------------------
define('SQL_TOKEN', 
		'select  distinct u.login login, u.id ent_id, u.uid_ldap "uid",
                 case ui.prof_id
                    when 8 then enfant.etb_id
                    else ui.etb_id end entpersonstructrattach,
                 case ui.prof_id
                    when 8 then null 
                    else ui.cls_id end enteleveclasses,
                 e.code_rne entpersonstructrattachrne, p.lib_men entpersonprofils,
                 comptes.formate_nivclasse_for_cas(n.nom) entelevenivformation,
                 
                 comptes.formate_us7ascii(u.nom) LaclasseNom, 
                 comptes.formate_us7ascii(u.prenom) LaclassePrenom,
                 u.dt_naissance LaclasseDateNais,
                 ui.civilite LaclasseCivilite,
                 ui.sexe LaclasseSexe,
                 u.adr LaclasseAdresse,
                 comptes.formate_cp(u.adr) ENTPersonCodePostal,
                 p.lib LaclasseProfil,
                 comptes.formate_nivclasse_for_cas(c.nom) LaclasseNomClasse,
                 u.email LaclasseEmail, 
                 ui.mail_institutionnel LaclasseEmailAca,
                 
                 comptes.formate_us7ascii(u.nom) PronoteNom, 
                 comptes.formate_us7ascii(u.prenom) PronotePrenom,
                 u.dt_naissance PronoteDateNais,
                 u.uid_ldap PronoteUid, 
                 u.login PronoteLogin,
                 p.lib_men PronoteENTPersonProfils,
                 comptes.formate_nivclasse_for_cas(c.nom) PronoteENTEleveClasses
                 
 	from   	utilisateurs u, 
 			utilisateurs_info ui, 
 			utilisateurs_info enfant, 
 			est_responsable_legal_de parent_de,
        	classes c, 
        	etablissements e, 
        	niveaux n, 
        	dispositif_formation d, 
        	profil p
	where   {0} and ui.id = u.id
        	and e.id(+) = ui.etb_id
       		and ui.prof_id = p.id
        	and ui.cls_id = c.id(+)
       		and c.niv_id = n.id(+)
        	and n.id = d.laclasse_niv_id(+)
        	and ui.id = parent_de.usr_id(+)
        	and parent_de.elv_id = enfant.id(+)');

//------------------------------------------------------------------------------
// Services autorisŽs ˆ s'authentifier avec le service CAS.
//------------------------------------------------------------------------------
$autorized_sites = array();

$autorized_sites[0]['siteName'] 	= 'ENT Laclasse.com';
$autorized_sites[0]['url'] 			= '*://*dev.laclasse.com/pls/education/*';
$autorized_sites[0]['autorizedAttributes'] = 'uid, LaclasseNom, LaclassePrenom, LaclasseEmail';


//------------------------------------------------------------------------------
// Attributs Applicatifs renvoyŽs dans le jeton d'authentification
// Modifier ce tableau avec 
//------------------------------------------------------------------------------
$authorized_keys = "";


?>