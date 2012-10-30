<?php
/**
 * @file config.inc.oracle.php
 * Oracle requests 
 *
*/

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
         comptes.formate_nivclasse_for_cas(c.nom)   "PronoteENTEleveClasses"
        
   from     
      utilisateurs u,
      utilisateurs_info ui,
      utilisateurs_info enfant,
      est_responsable_legal_de parent_de,
      classes c,
      etablissements e,
      niveaux n,
      dispositif_formation d,
      profil p
    
    where   
      u.login = upper(:LOGIN) and ui.id = u.id
      and e.id(+) = ui.etb_id
         and ui.prof_id = p.id
      and ui.cls_id = c.id(+)
         and c.niv_id = n.id(+)
      and n.id = d.laclasse_niv_id(+)
      and ui.id = parent_de.usr_id(+)
      and parent_de.elv_id = enfant.id(+)');

/* Cette requête ne gère que les PROFS et LES ELEVES pour les manuels
scolaires numériques */

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

//-------------------------------
define('SQL_FOR_PRONOTE',
     'select   distinct u.login  "login",
               comptes.formate_us7ascii(u.nom)   "nom",
               comptes.formate_us7ascii(u.prenom) "prenom",
               to_char(u.dt_naissance,\'RRRR-MM-DD\') "dateNaissance",
               comptes.formate_cp(u.adr)        "codePostal",
               p.lib_men                  "categories"
  
      from utilisateurs u,
           utilisateurs_info ui,
           profil p
      
      where u.login = upper(:LOGIN) and ui.id = u.id
            and ui.prof_id = p.id');

//-------------------------------
define('SQL_FOR_GRR',
   'select  distinct lower(login) "login",
            comptes.formate_us7ascii(u.nom)   "nom",
            comptes.formate_us7ascii(u.prenom) "prenom",
            p.lib "profil",
            nvl(ui.titre, \'-\') "fonction",
            u.email "email"
  from utilisateurs u,
       utilisateurs_info ui,
       profil p
  where u.login = upper(:LOGIN) 
        and ui.id = u.id
        and ui.prof_id = p.id');


//------------------------------------------------------------------------------
// SEARCH functions 
//------------------------------------------------------------------------------

define('Search_Agent_by_mail',  
        'select  distinct u.login     "login",
                 comptes.formate_us7ascii(u.nom)   "nom",
                 comptes.formate_us7ascii(u.prenom) "prenom",
                 to_char(u.dt_naissance,\'RRRR-MM-DD\') "dateNaissance",
                 comptes.formate_cp(u.adr)    "codePostal",
                 p.lib_men          "categories"
        from     utilisateurs u,
                 utilisateurs_info ui,
                 profil p
        where     ui.id = u.id and ui.prof_id = p.id and upper(ui.mail_institutionnel)= upper(:mail)');

//-------------------------------------------------------------------------------
define('Search_user_by_mail',  
       'select  distinct u.login     "login",
                comptes.formate_us7ascii(u.nom)   "nom",                     
                comptes.formate_us7ascii(u.prenom) "prenom",
                to_char(u.dt_naissance,\'RRRR-MM-DD\') "dateNaissance",
                comptes.formate_cp(u.adr)    "codePostal",
                p.lib_men          "categories"
       from     utilisateurs u,
                utilisateurs_info ui,
                profil p
       where    ui.id = u.id and ui.prof_id = p.id and upper(u.EMAIL)= upper(:mail)');
//------------------------------------------------------------------------------
define('Search_Parent_By_Name_EleveId', ' select  distinct u.id, ui.etb_id, 
                          u.login   "login",
                           comptes.formate_us7ascii(u.nom)   "nom",
                           comptes.formate_us7ascii(u.prenom) "prenom",
                           to_char(u.dt_naissance,\'RRRR-MM-DD\') "dateNaissance",
                           comptes.formate_cp(u.adr)    "codePostal",
                           p.lib_men          "categories"
                      from utilisateurs u,
                         utilisateurs_info ui,
                         profil p, 
                         etablissements e,
                         est_responsable_legal_de r
                      where  ui.id = u.id and u.id = r.USR_ID and r.elv_id = (select u.id from utilisateurs u, utilisateurs_info ui where u.id=ui.id and ui.Sconet_elv_id =(:elevid))
                             and upper(convert(u.nom,\'US7ASCII\'))= upper(convert(:nom,\'US7ASCII\')) and upper(convert(u.prenom,\'US7ASCII\'))=upper(convert(:prenom,\'US7ASCII\')) 
                             and ui.prof_id = p.id');
//--------------------------------------------------------------------------------
define('Search_student_By_Name_SconetId', ' select distinct u.id, u.login "login",
                           comptes.formate_us7ascii(u.nom)   "nom",
                           comptes.formate_us7ascii(u.prenom) "prenom",
                           to_char(u.dt_naissance,\'RRRR-MM-DD\') "dateNaissance",
                           comptes.formate_cp(u.adr)    "codePostal",
                           p.lib_men          "categories"
                      from utilisateurs u,
                           utilisateurs_info ui,
                           profil p
                       where u.id = ui.id and ui.Sconet_elv_id = (:eleveid) and ui.prof_id=p.id');

//--------------------------------------------------------------------------------
// password functions
define('Is_Default_password', 'select COMPTES.IS_DEFAULT_PASSWORD(:login) from dual');
define('Update_password',     'update utilisateurs set pwd = :pwd where login = :login');
//--------------------------------------------------------------------------------
?>
