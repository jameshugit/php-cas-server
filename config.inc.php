<?php
/**
 * @file config.inc.php
 * Server configuration directives
 *
 * @defgroup confdir Configuration Directives
 * Configuration directives for CAS server
 * @{
 */
/**
    Absolute path to your CAS install directory.
*/
define ('CAS_PATH', '/var/www/sso');

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
 * @param MEMCACHED_SERVERS Array of servers. Each server is an array of
(host, port).
 * Thus, MEMCACHED_SERVERS contains an array of arrays. It is passed as
parameters to
 * Memcached::addservers as is.
 */
 //$CONFIG['REDIS_SERVERS'] = array(array('redis.dev.laclasse.lan', 6379));
   //36 $CONFIG['REDIS_ROOT'] = 'com.laclasse.dev.sso.tickets.';

 $CONFIG['MEMCACHED_SERVERS'] = array(array('localhost', 11211));
 $CONFIG['REDIS_SERVERS'] = array(array('localhost', 6379));
 $CONFIG['REDIS_ROOT'] = 'com.laclasse.dev.sso.tickets.';


/**
    Timeout in second for each kind of ticket
    LT for LoginTicket : The timeout shouldbe very short. It should not
be over 4 minutes.
    ST for ServiceTicket : Could be short too because it  is a one shot
ticket tha have to
                           be validated : 4 minutes for example
    ST for TicketGrantingTicket : Could be long : 8 hours for example
*/

$CONFIG['LT_TIMOUT']  = 4*60;
$CONFIG['ST_TIMOUT']  = 4*60;
$CONFIG['TGT_TIMOUT'] = 8*60*60;

/**
    See if we have to display news on the authentication form   
    The news are feeded by a twitter account
*/
$CONFIG['DISPLAY_NEWS'] = true;
$CONFIG['TWITTER_ACCOUNT'] = '@laclasse';
$CONFIG['TWITTER_HASHTAG'] = '#sys';
$CONFIG['REDIS_NEWS_ROOT'] = 'com.laclasse.dev.sso.last_message';

/*
 * Authentication backend
 */
 
include_once('lib/authentication.php');
include_once('lib/backend.db.oracle.php');
//include_once('lib/backend.ldap.php');

//------------------------------------------------------------------------------
// Constantes de connexion au Backend.
//------------------------------------------------------------------------------
/** Database name */
define('BACKEND_DBNAME', '//db.dev.laclasse.lan:1521/MAQ1020');
/** Database username */
define('BACKEND_DBUSER', 'laclasse_frmwrk');
/** Database password */
define('BACKEND_DBPASS', '6n2ml29y');

//------------------------------------------------------------------------------
// Requete SQL de validation des login/pwd
//------------------------------------------------------------------------------
define('SQL_AUTH', 'select login from utilisateurs u where u.login =
upper(:LOGIN) and upper(u.pwd) = upper(:PWD)');

//------------------------------------------------------------------------------
// Requete SQL d'extration des donnÃ©es pour le jeton d'authentification CAS.
//
// You can define one Attributes provider per authorized service
// By default, the sql statment for DB backend is SQL_FOR_ATTRIBUTES
//------------------------------------------------------------------------------
define('SQL_FOR_ATTRIBUTES',
        'select  distinct u.login login, u.pwd pass, u.id "ENT_id",
u.uid_ldap "uid",
                 case ui.prof_id
                    when 8 then enfant.etb_id
                    else ui.etb_id end "ENTPersonStructRattach",
                 case ui.prof_id
                    when 8 then null
                    else comptes.formate_nivclasse_for_cas(c.nom)
/*ui.cls_id*/ end "ENTEleveClasses",
                 e.code_rne "ENTPersonStructRattachRNE",
                 p.lib_men "ENTPersonProfils",
                 comptes.formate_nivclasse_for_cas(n.nom)
"ENTEleveNivFormation",
                
                 comptes.formate_us7ascii(u.nom) "LaclasseNom",
                 comptes.formate_us7ascii(u.prenom) "LaclassePrenom",
                 u.dt_naissance "LaclasseDateNais",
                 ui.civilite "LaclasseCivilite",
                 ui.sexe "LaclasseSexe",
                 u.adr "LaclasseAdresse",
                 comptes.formate_cp(u.adr) "ENTPersonCodePostal",
                 p.lib "LaclasseProfil",
                 comptes.formate_nivclasse_for_cas(c.nom)
"LaclasseNomClasse",
                 u.email "LaclasseEmail",
                 ui.mail_institutionnel "LaclasseEmailAca",
                
                 comptes.formate_us7ascii(u.nom) "PronoteNom",
                 comptes.formate_us7ascii(u.prenom) "PronotePrenom",
                 u.dt_naissance "PronoteDateNais",
                 u.uid_ldap "PronoteUid",
                 u.login "PronoteLogin",
                 p.lib_men "PronoteENTPersonProfils",
                 comptes.formate_nivclasse_for_cas(c.nom)
"PronoteENTEleveClasses"
                
     from       utilisateurs u,
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

/* Cette requête ne gère que les PROFS et LES ELEVES pour les manuels
scolaires numériques */


//-------------------------------
define('SQL_FOR_PRONOTE',
     'select  distinct u.login                   "login",
                   comptes.formate_us7ascii(u.nom)    "nom",
                   comptes.formate_us7ascii(u.prenom) "prenom",
                   u.dt_naissance                     "dateNaissance",
                   comptes.formate_cp(u.adr)          "codePostal",
                   p.lib_men                          "categories"
  
             from utilisateurs u,
                    utilisateurs_info ui,
                    profil p
               where u.login = upper(:LOGIN) and ui.id = u.id
                 and ui.prof_id = p.id');

//---------------------------------

define('SQL_FOR_ATTRIBUTES_MEN',
        '/* ELEVES */
        select  distinct u.uid_ldap "user",
                 e.code_rne "UAI",
                 p.lib_men "Profil",
                 null "CodeNivFormation",
                 comptes.formate_nivclasse_for_cas(n.nom) "NivFormation",
                 null "NivFormationDiplome",
                 null "Filiere",
                 null "Specialite",
                 null "Enseignement",
                 comptes.formate_nivclasse_for_cas(c.nom) "Classe",
                 groupes_eleves.get_liste_grp_elv(ui.id) "Groupe"
     from   utilisateurs u,
            utilisateurs_info ui,
            classes c,
            etablissements e,
            niveaux n,
            profil p
    where   u.login = upper(:LOGIN)
            and ui.id = u.id
            and ui.cls_id = c.id
            and ui.etb_id = e.id
            and c.etb_id = e.id
            and c.niv_id = n.id
            and ui.prof_id = p.id
    UNION
    /* PROFS */
    select  distinct u.uid_ldap "user",
                 e.code_rne "UAI",
                 p.lib_men "Profil",
                 null "CodeNivFormation",
                 null "NivFormationDiplome",
                 null "Filiere",
                 null "Specialite",
                 null "Enseignement",
                 application.get_liste_matieres(ui.id) "MatiereEnseignEtab",
                 groupes_eleves.get_liste_cls_prf(ui.id) "Classe",
                 groupes_eleves.get_liste_grp_prf(ui.id) "Groupe"
     from   utilisateurs u,
            utilisateurs_info ui,
            etablissements e, 
            profil p
        where   u.login = upper(:LOGIN)
            and ui.id = u.id
            and ui.etb_id = e.id
            and ui.prof_id = p.id

');

//------------------------------------------------------------------------------
// Services autorisés à s'authentifier avec le service CAS.
//------------------------------------------------------------------------------
/*******************************************************************************
 Site allowed to use this CAS server for authentication
  @param AUTHORIZED_SITES Array of authorized sites. Each authorized
site is itself an associative array
  having the following keys : sitename, url and authorizedAttributes
which respectively contain the site name, the site URL
  and the attributes that the site will get in serviceValidate.
 
 Structure of $CONFIG['AUTHORIZED_SITES'] :
 sitename : this is the short name of the site you need to authenticate
via this CAS server.

 url      : Contains the url of the servie that is authorized to
authenticate ith your CAS server.
             This parameter accepts wilcards.
             "http*://mysurpersite.com/*" will authorize both http and
https urls
             and all the virtual pathes under the site's root.

 allowedAttributes : these are the attributes that are passed in token
after service validate.
                      The names MUST match with the names in the
attributes Provider.
                      For example, in the attributes provider is a
database, you need to match the names of SQL statement
                      with the attributes list in
$CONFIG['AUTHORIZED_SITES'] array.
                      In the case (SQL request attributes proveider),
your SQL request should return ONE rows.
                      The multivalued attributes should appear in one
line separated by comma and encapsulated
                      with double quotes.

 tokenModele       : By default the model of the token is CAS conpliant
(Great !)
 
                     <cas:serviceResponse
xmlns:cas='http://www.yale.edu/tp/cas'>
                        <cas:authenticationSuccess>
                                <cas:user>[Here is the login the user
entered]</cas:user>
                                <Some other attributes you defined />
                        </cas:authenticationSuccess>
                    </cas:serviceResponse>
                    You can define your own token model for attributes
by defining the 'tokenModele' key.
                    In this case the custom model is only available for
the site for which it is defined.
                    2 rules you have to respect to define your custo model :
                        1. the name of the model must be unique
                        2. the model 'my_custom_model' must match with
the name of the view you will code and
                           the file is located in
'/views/my_custom_model.php'
                        3. This file must contain the function
'view_my_custom_model'.
                       
                    Keep in mind that the header and the footer of the
token are unchanged.
                   
 attributesProvider : This is a constant you define in this config file
containing  :
                         - the SQL request for the DB backend of the service
                         - the LDAP request for the LDAP backend of the
service
                         - ...
*************************************************************************************/
$CONFIG['AUTHORIZED_SITES'] = array(
            array(    'sitename'          =>  'Partenaire_SESAMATH',
                    'url'                  =>
'*://sesamath2.sesamath.net/*',
                    'allowedAttributes' =>
'uid,ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation'),
           
            array(    'sitename'              =>  'Partenaire_CNS',
                    'url'                      =>
'https://www.e-interforum.com/auth/casservice/*/aas/48/*',
                    'allowedAttributes'     =>  'user, UAI, Profil,
CategoDiscipline, MatiereEnseignEtab, Classe, Groupe, CodeNivFormation,
NivFormation, NivFormationDiplome, Filiere, Specialite, Enseignement,
Classe, Groupe',
                    'tokenModele'            =>  'ent_manuels_numeriques',
                    'authenticationMethod'    => 'SQL',
                    'attributesProvider'    =>    SQL_FOR_ATTRIBUTES_MEN),

           
            array(    'sitename'              =>  'Le Livre Scolaire',
                    'url'                      =>
'*://81.252.204.221/*', //'http*://*lelivrescolaire.fr/*',
                    'allowedAttributes'     =>  'user, UAI, Profil,
CategoDiscipline, MatiereEnseignEtab, Classe, Groupe, CodeNivFormation,
NivFormation, NivFormationDiplome, Filiere, Specialite, Enseignement,
Classe, Groupe',
                    'tokenModele'            =>  'ent_manuels_numeriques',
                    'authenticationMethod'    => 'SQL',
                    'attributesProvider'    =>    SQL_FOR_ATTRIBUTES_MEN),

            array(    'sitename'          =>  'Partenaire_KNE',
                    'url'                  =>  '*://www.kiosque-edu.com/*',
                    'allowedAttributes' =>
'uid,ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation'),
           
            array(    'sitename'          =>  'Pronote_Cas_2010',
                    'url'                  =>
'*://sso.dev.laclasse.com/PronoteCAS2010/*',
                    'allowedAttributes' =>
'nom,prenom,user,login,categories,dateNaissance,codePostal,eleveClasses'),
           
            array(    'sitename'          =>
'Blogs_Wordpress_Laclasse.com',
                    'url'                  =>
'*://*blogs.dev.laclasse.com/*',
                    'allowedAttributes' =>
'LOGIN,ENT_id,uid,ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation,LaclasseNom,LaclassePrenom,LaclasseDateNais,LaclasseCivilite,LaclasseSexe,LaclasseProfil,LaclasseNomClasse,LaclasseEmail,LaclasseEmailAca'),
           
            array(    'sitename'          =>  'Passerelle vers Pronote',
                    'url'                  =>
'*://*cas.erasme.lan/PronoteCAS2010/*',
                    'allowedAttributes' =>
'LOGIN,ENT_id,uid,ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation,LaclasseNom,LaclassePrenom,LaclasseDateNais,LaclasseCivilite,LaclasseSexe,LaclasseProfil,LaclasseNomClasse,LaclasseEmail,LaclasseEmailAca'),
           
            array(    'sitename'          =>  'Plateforme_Laclasse.com',
                    'url'                  =>
'*://*.laclasse.com/pls/education/!page.*',
                    'allowedAttributes' =>  'LOGIN,ENT_id,uid,
ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation'),

            array(    'sitename'          =>  'Tests Laclasse-Mobile',
                    'url'                  =>  '*://*daniel.erasme.lan/*',
                    'allowedAttributes' =>
'LOGIN,ENT_id,uid,ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation,LaclasseNom,LaclassePrenom,LaclasseDateNais,LaclasseCivilite,LaclasseSexe,LaclasseProfil,LaclasseNomClasse,LaclasseEmail,LaclasseEmailAca'),

            array(    'sitename'          =>  'Tests Andreas',
                    'url'                  =>  '*://*andreas.erasme.lan/*',
                    'allowedAttributes' =>
'LOGIN,ENT_id,uid,ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation,LaclasseNom,LaclassePrenom,LaclasseDateNais,LaclasseCivilite,LaclasseSexe,LaclasseProfil,LaclasseNomClasse,LaclasseEmail,LaclasseEmailAca'),
            array(    'sitename'          =>  'Tests Franois',
                    'url'                  =>  '*://*portable/*',
                    'allowedAttributes' =>
'LOGIN,ENT_id,uid,ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation,LaclasseNom,LaclassePrenom,LaclasseDateNais,LaclasseCivilite,LaclasseSexe,LaclasseProfil,LaclasseNomClasse,LaclasseEmail,LaclasseEmailAca'),

            array(    'sitename'          =>  'File Server',
                    'url'                  =>  '*://file.erasme.lan/*',
                    'allowedAttributes' =>
'LOGIN,ENT_id,uid,ENTPersonStructRattach,ENTEleveClasses,ENTPersonStructRattachRNE,ENTPersonProfils,ENTEleveNivFormation,LaclasseNom,LaclassePrenom,LaclasseDateNais,LaclasseCivilite,LaclasseSexe,LaclasseProfil,LaclasseNomClasse,LaclasseEmail,LaclasseEmailAca'),

            array(    'sitename'          =>  'Annuaire ENT',
                    'url'                  =>
'*://ldap.erasme.lan/exploitation/previsualisation/*',
                    'allowedAttributes' =>
'ENT_id,uid,ENTPersonStructRattachRNE,LaclasseProfil'),

            array(    'sitename'          =>  'Annuaire ENT',
                    'url'                  =>
'*://annuaire.dev.laclasse.lan/exploitation/previsualisation/*',
                    'allowedAttributes' =>
'ENT_id,uid,ENTPersonStructRattachRNE,LaclasseProfil'),

            array(    'sitename'          =>  'Trombinoscope',
                    'url'                  =>
'*://*.dev.laclasse.com/annuaire/*',
                    'allowedAttributes' =>
'ENT_id,uid,ENTPersonStructRattachRNE,LaclasseProfil'),

array(    'sitename'          =>  'Trombinoscope',
                    'url'                  =>
'*://*.dev.laclasse.lan:*/*',
                    'allowedAttributes'     =>  'login,nom,
                    prenom,dateNaissance,codePostal,categories',
                    'tokenModele'            =>  'ent_manuels_numeriques',
                    'authenticationMethod'    => 'SQL',
                    'attributesProvider'    =>   SQL_FOR_PRONOTE),


            array(  'sitename'              =>  'Serveur de viescolaire',
                    'url'                   =>
'*://viescolaire*.laclasse.lan/*',
                    'allowedAttributes'     =>
'uid,ENTPersonStructRattachRNE,ENT_id,ENTPersonProfils,ENTEleveClasses,ENTEleveNivFormation,LaclasseNom,LaclassePrenom,LaclasseDateNais,LaclasseCivilite,LaclasseSexe,LaclasseProfil,LaclasseNomClasse'),

            array(  'sitename'              =>  'Serveur de viescolaire',
                    'url'                   =>
'*://*.dev.laclasse.com/viescolaire/*',
                    'allowedAttributes'     =>
'uid,ENTPersonStructRattachRNE,ENT_id,ENTPersonProfils,ENTEleveClasses,ENTEleveNivFormation,LaclasseNom,LaclassePrenom,LaclasseDateNais,LaclasseCivilite,LaclasseSexe,LaclasseProfil,LaclasseNomClasse'),

            // On rajoute localhost pour les dev des thématiques SPIP.
            array(  'sitename'              =>  'Thématiques SPIP',
                    'url'                   =>
'*://*thematiques.localhost*/*',
                    'allowedAttributes'     =>
'LOGIN,uid,ENTPersonStructRattachRNE,ENT_id,ENTPersonProfils,ENTEleveClasses,ENTEleveNivFormation,LaclasseNom,LaclassePrenom,LaclasseDateNais,LaclasseCivilite,LaclasseSexe,LaclasseProfil,LaclasseNomClasse,LaclasseEmail,LaclasseEmailAca'),

            array(  'sitename'              =>  'Thématiques SPIP',
                    'url'                   =>
'*://*thematiques.laclasse.com/*',
                    'allowedAttributes'     =>
'LOGIN,uid,ENTPersonStructRattachRNE,ENT_id,ENTPersonProfils,ENTEleveClasses,ENTEleveNivFormation,LaclasseNom,LaclassePrenom,LaclasseDateNais,LaclasseCivilite,LaclasseSexe,LaclasseProfil,LaclasseNomClasse,LaclasseEmail,LaclasseEmailAca'),

            // On rajoute localhost pour les dev de vie scolaire
            array(  'sitename'              =>  'Serveur de viescolaire',
                    'url'                   =>  '*://localhost*/*',
                    'allowedAttributes'     =>
'uid,ENTPersonStructRattachRNE,ENT_id,ENTPersonProfils,ENTEleveClasses,ENTEleveNivFormation,LaclasseNom,LaclassePrenom,LaclasseDateNais,LaclasseCivilite,LaclasseSexe,LaclasseProfil,LaclasseNomClasse'),

            // Attributs pour le MAIL. /!\ le password est en clair !
            array(    'sitename'          =>  'Mail RoundCube',
                    'url'                  =>
'*://www.dev.laclasse.com/mail/*',
                    'allowedAttributes' =>  'LOGIN, pass, ENT_id,
LaclasseCivilite, LaclasseNom, LaclassePrenom, LaclasseEmail'),
                   
            array(  'sitename'          =>  'Mail RoundCube Internal',
                    'url'               =>  '*://rc.dev.laclasse.lan/*',
                    'allowedAttributes' =>  'LOGIN, pass, ENT_id,
LaclasseCivilite, LaclasseNom, LaclassePrenom, LaclasseEmail'),

            array(  'sitename'          =>  'Test local',
                    'url'               =>  '*://samlclient/*',
                    'allowedAttributes' =>  'LOGIN, pass, ENT_id,
LaclasseCivilite, LaclasseNom, LaclassePrenom, LaclasseEmail'),
  );              
?>
