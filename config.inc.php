<?php
/**
 * @file config.inc.php 
 * Server configuration directives
 *
 * @defgroup confdir Configuration Directives
 * Configuration directives for CAS server
 * @{
 */

/** Server mode
 * @param MODE
 *  - 'dev'   : http protocol allowed
 *  - 'prod'  : https required
 *  - 'debug' : 'dev' mode plus debug info display
 */
$CONFIG['MODE'] = 'dev';

/** CAS protocol compatibility
 * @param CAS_VERSION Possible values are : 1.0, 2.0.
 * @note Not used ATM
 */
$CONFIG['CAS_VERSION'] = '2.0';

/** Memcached server array
 * @param MEMCACHED_SERVERS Array of servers. Each server is an array of (host, port).
 * Thus, MEMCACHED_SERVERS contains an array of arrays. It is passed as parameters to
 * Memcached::addservers as is.
 */
$CONFIG['MEMCACHED_SERVERS'] = array(array('localhost', 11211));


//------------------------------------------------------------------------------
// Services autorisés à s'authentifier avec le service CAS.
//------------------------------------------------------------------------------
/** Site allowed to use this CAS server for authentication
 * @param AUTHORIZED_SITES Array of authorized sites. Each authorized site is itself an associative array
 * having the following keys : sitename, url and authorizedAttributes which respectively contain the site name, the site URL
 * and the attributes that the site will get in serviceValidate.
 */
$CONFIG['AUTHORIZED_SITES'] = array(
			array(	'sitename'  		=>  'Partenaire_SESAMATH',
					'url'  				=>  '*://sesamath2.sesamath.net/*',
					'allowedAttributes' =>  'LOGIN,uid,ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation'),
			
			array(	'sitename'  		=>  'Partenaire_CNS',
					'url'  				=>  'https://www.e-interforum.com/auth/casservice/*/aas/48/*',
					'allowedAttributes' =>  'LOGIN,uid,ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation'),
			
			array(	'sitename'  		=>  'Partenaire_KNE',
					'url'  				=>  '*://www.kiosque-edu.com/*',
					'allowedAttributes' =>  'LOGIN,uid,ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation'),
			
			array(	'sitename'  		=>  'Pronote_Cas_2010',
					'url'  				=>  '*://sso.dev.laclasse.com/PronoteCAS2010/*',
					'allowedAttributes' =>  'nom,prenom,user,login,categories,dateNaissance,codePostal,eleveClasses'),
			
			array(	'sitename'  		=>  'Blogs_Wordpress_Laclasse.com',
					'url'  				=>  '*://*blogs.dev.laclasse.com/*',
					'allowedAttributes' =>  'LOGIN,ENT_id,uid,ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation,LaclasseNom,LaclassePrenom,LaclasseDateNais,LaclasseCivilite,LaclasseSexe,LaclasseProfil,LaclasseNomClasse,LaclasseEmail,LaclasseEmailAca'),
			
			array(	'sitename'  		=>  'Plateforme_Laclasse.com',
					'url'  				=>  '*://*.laclasse.com/pls/education/!page.*',
					'allowedAttributes' =>  'LOGIN,ENT_id,uid, ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation'),
				);
			
	
/*
 * Authentication backend
 */
include_once('lib/backend.db.oracle.php');
//include_once('lib/backend.ldap.php');

//------------------------------------------------------------------------------
// Constantes de connexion au Backend.
//------------------------------------------------------------------------------
/** Database name */
define('BACKEND_DBNAME', '//db.dev.laclasse.com:1521/MAQ1020');
/** Database username */
define('BACKEND_DBUSER', 'laclasse_frmwrk');
/** Database password */
define('BACKEND_DBPASS', '6n2ml29y');

//------------------------------------------------------------------------------
// Requete SQL de validation des login/pwd
//------------------------------------------------------------------------------
define('SQL_AUTH', 'select login from utilisateurs u where u.login = upper(:LOGIN) and upper(u.pwd) = upper(:PWD)');

//------------------------------------------------------------------------------
// Requete SQL d'extration des donnÃ©es pour le jeton d'authentification CAS.
//------------------------------------------------------------------------------
define('SQL_FOR_ATTRIBUTES', 
		'select  distinct u.login login, u.id "ENT_id", u.uid_ldap "uid",
                 case ui.prof_id
                    when 8 then enfant.etb_id
                    else ui.etb_id end "ENTPersonStructRattach",
                 case ui.prof_id
                    when 8 then null 
                    else ui.cls_id end "ENTEleveClasses",
                 e.code_rne "ENTPersonStructRattachRNE", 
                 p.lib_men "ENTPersonProfils",
                 comptes.formate_nivclasse_for_cas(n.nom) "ENTEleveNivFormation",
                 
                 comptes.formate_us7ascii(u.nom) "LaclasseNom", 
                 comptes.formate_us7ascii(u.prenom) "LaclassePrenom",
                 u.dt_naissance "LaclasseDateNais",
                 ui.civilite "LaclasseCivilite",
                 ui.sexe "LaclasseSexe",
                 u.adr "LaclasseAdresse",
                 comptes.formate_cp(u.adr) "ENTPersonCodePostal",
                 p.lib "LaclasseProfil",
                 comptes.formate_nivclasse_for_cas(c.nom) "LaclasseNomClasse",
                 u.email "LaclasseEmail", 
                 ui.mail_institutionnel "LaclasseEmailAca",
                 
                 comptes.formate_us7ascii(u.nom) "PronoteNom", 
                 comptes.formate_us7ascii(u.prenom) "PronotePrenom",
                 u.dt_naissance "PronoteDateNais",
                 u.uid_ldap "PronoteUid", 
                 u.login "PronoteLogin",
                 p.lib_men "PronoteENTPersonProfils",
                 comptes.formate_nivclasse_for_cas(c.nom) "PronoteENTEleveClasses"
                 
 	from   	utilisateurs u, 
 			utilisateurs_info ui, 
 			utilisateurs_info enfant, 
 			est_responsable_legal_de parent_de,
        	classes c, 
        	etablissements e, 
        	niveaux n, 
        	dispositif_formation d, 
        	profil p
	where   u.login = upper(:LOGIN) and ui.id = u.id
        	and e.id(+) = ui.etb_id
       		and ui.prof_id = p.id
        	and ui.cls_id = c.id(+)
       		and c.niv_id = n.id(+)
        	and n.id = d.laclasse_niv_id(+)
        	and ui.id = parent_de.usr_id(+)
        	and parent_de.elv_id = enfant.id(+)');


?>