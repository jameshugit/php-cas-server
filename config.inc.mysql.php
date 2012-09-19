<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

//-----------------------------------------------------------------
// query for authentication 
define('MySQL_AUTH', 'select login from user u where u.login = upper(:LOGIN) and upper(u.password) = upper(:PWD)');

//-------------------------------------------------------------------
// You can define one Attributes provider per authorized service
// By default, the sql statment for MySQl DB backend is MySQL_FOR_ATTRIBUTES
define('MySQL_FOR_ATTRIBUTES', 'select u.login as login, 
                                   u.password as pass, 
                                   u.id as ENT_id, 
                                   u.id as uid, 
                                   u.nom as LaclasseNom, 
                                   u.prenom as LaclassePrenom , 
                                   u.date_naissance as  LaclasseDateNais,
                                   CASE u.sexe 
                                    WHEN  u.sexe ="M" THEN "Mr."
                                    WHEN  u.sexe ="F" THEN "Mme."
                                   END as LaclasseCivilite,
                                   u.sexe as  LaclasseSexe , 
                                   u.adresse  LaclasseAdresse , 
                                   u.code_postal as  ENTPersonCodePostal , 
                                   pu.etablissement_id as  ENTPersonStructRattach ,
                                   pu.etablissement_id as  ENTPersonStructRattachRNE ,
                                   p.code_ent as  ENTPersonProfils , 
                                   /*e.adresse as  LaclasseEmailAca , */
                                   replace(r.lib, "°", "EME") as  ENTEleveClasses , 
                                   replace(r.lib, "°", "EME") as  LaclasseNomClasse ,
                                   replace(n.lib, "°", "EME") as  ENTEleveNivFormation, 
                                   e.adresse as LaclasseEmail, 
                                   ins.adresse as LaclasseEmailAca
                                from user u 
                                left join profil_user pu on ( pu.user_id = u.id)
                                left join profil p on (   p.id = pu.profil_id)
                                left join email e on (   e.user_id = u.id and   e.type_email_id = "PERS" )
                                left join email ins on (   ins.user_id = u.id and   ins.type_email_id = "ACAD" )
                                left join membre_regroupement mr on (   mr.user_id = u.id)
                                left join regroupement r on (   r.id = mr.regroupement_id)
                                left join niveau n on (   n.id = r.niveau_id) 

                                where  
                                    upper(u.login)= upper(:LOGIN)
                                    and ((r.id is not null and r.type_regroupement_id =  "CLS" and r.etablissement_id = pu.etablissement_id) or r.id is null);'
        );



//--------------------------------------------------------------------
// sql query for searching an agent of the (education national) 
define('Mysql_Search_Agent_by_mail', 'select  u.login  , u.nom , u.prenom
                                      from    user u, email e 
                                      where   u.id = e.user_id and e.type_email_id = "ACAD"
                                              and  upper(e.adresse) = upper(:mail)'
      );

//-------------------------------------------------------------------------
define('Mysql_Search_User_by_mail', 'select   u.login  , u.nom , u.prenom
                                     from     user u, email e 
                                     where    u.id = e.user_id and e.type_email_id = "PERS" or e.type_email_id = "PRO"
                                              and  upper(e.adresse) = upper(:mail)'
      );
//-------------------------------------------------------------------------
define('Mysql_FOR_PRONOTE', 'select u.nom , u.prenom, u.date_naissance as dateNaissance, u.code_postal as codePostal
                                ,p.code_ent as categories
                            from user u, profil p , profil_user pu  
                            where upper(u.login)= upper(:LOGIN) and pu.user_id = u.id and pu.profil_id = p.id '
      ); 

//-------------------------------------------------------------------------

define('Mysql_Search_student_By_Id', 'select  u.login , u.nom , u.prenom ,
                                        u.date_naissance as dateNaissance, 
                                        u.code_postal  as  codePostal,
                                        p.code_ent  as    categories
  
                                      from   user u,
                                             profil_user pu,
                                             profil p 
                                      where 
                                            u.id = pu.user_id 
                                            and pu.profil_id = p.id  and u.id_sconet=(:eleveid) and upper(u.nom)=upper(:nom) 
                                            and upper(u.prenom=upper(:prenom))'
       );
//-------------------------------------------------------------------------

define('Mysql_Search_Parent_By_Name_EleveId', 'select   u.id, u.login , u.prenom , u.nom
                                              from user u,
                                                relation_eleve re
                                              where  u.id = re.user_id and re.eleve_id in (select c.id from user c  where  c.id_sconet = (:elevid))
                                                     and upper(u.nom) = upper(:nom) and upper(u.prenom) = upper(:prenom)
                                              ORDER BY u.id desc
      ');

//-------------------------------------------------------------------------
define('Mysql_Search_Parent_By_EleveId', 'select   u.id, u.login , u.prenom , u.nom
                                         from user u,
                                              relation_eleve re
                                         where  u.id = re.user_id and re.eleve_id in (select c.id from user c  where  c.id_sconet = (:elevid))
                                         ORDER BY u.id desc
      ');
//-------------------------------------------------------------------------
/* Cette requête ne gère que les PROFS et LES ELEVES pour les manuels
scolaires numériques */

define('MySQL_FOR_ATTRIBUTES_MEN', 
    /* ELEVES */
'select   distinct u.id "user"
     pu.etablissement_id as "UAI",
     p.code_ent as  "ENTPersonProfils",
     null as "CodeNivFormation",
     replace(n.lib, "°", "EME") as "NivFormation",
     null "NivFormationDiplome",
     null "Filiere",
     null "Specialite",
     null "Enseignement",
     replace(r.lib, "°", "EME") as "Classe",
     grp.Groupe "Groupe"
from   user u 
       left join ( 
        select GROUP_CONCAT(r.lib) as "Groupe", u.id 
        from 
          user u , 
          regroupement r, 
          membre_regroupement mr, 
          profil_user pu 
        where  
          u.id = mr.user_id
          and pu.user_id = u.id 
          and mr.regroupement_id = r.id 
          and type_regroupement_id = "GRP" 
          /*and pu.actif= true */
          and pu.etablissement_id = r.etablissement_id
            group by u.id) grp  on (u.id = grp.id), 
        profil_user pu,  
        profil p,
        membre_regroupement mr,
        regroupement r, 
        niveau n

where   upper(u.login) = upper(:LOGIN) and 
        pu.user_id = u.id and 
        p.id = pu.profil_id and 
        mr.user_id = u.id  and 
        r.id = mr.regroupement_id and    
        n.id = r.niveau_id

UNION
    
select   distinct  u.id "user",
         pu.etablissement_id "UAI",
         p.code_ent "Profil",
         null "CodeNivFormation",
         null "NivFormationDiplome",
         null "Filiere",
         null "Specialite",
         null "Enseignement",
         m.matiere "MatiereEnseignEtab",
         c.classes "Classe",
         grp.groupe "Groupe"
from   user u, 
       (select distinct u.id as id,  GROUP_CONCAT(distinct me.libelle_court) as Matiere 
            from user u, profil_user pu, enseigne_regroupement er, matiere_enseignee me, regroupement r 
            , profil p 
            where me. id = er.matiere_enseignee_id and u.id = er.user_id 
            and pu.user_id = u.id
            and pu.actif =true 
            and pu.etablissement_id = r.etablissement_id
            and r.id= er.regroupement_id and 
            pu.profil_id = p.id    
            group by u.id ) m, 
       (select distinct u.id as id ,  GROUP_CONCAT(DISTINCT r.lib) as classes
            from user u, profil_user pu, enseigne_regroupement er, matiere_enseignee me, regroupement r, 
            profil p 
            where me. id = er.matiere_enseignee_id and u.id = er.user_id 
            and pu.user_id = u.id
            and pu.actif =true 
            and pu.etablissement_id = r.etablissement_id
            and r.id= er.regroupement_id and 
            pu.profil_id = p.id and 
            r.type_regroupement_id =  "CLS"
            group by u.id) c,  
       (select distinct u.id as id,  GROUP_CONCAT(DISTINCT r.lib) as groupe
            from user u, profil_user pu, enseigne_regroupement er, matiere_enseignee me, regroupement r, 
            profil p 
            where me. id = er.matiere_enseignee_id and u.id = er.user_id 
            and pu.user_id = u.id
            and pu.actif =true 
            and pu.etablissement_id = r.etablissement_id
            and r.id= er.regroupement_id and 
            pu.profil_id = p.id and 
            r.type_regroupement_id =  "GRP"
            group by u.id) grp , 
       profil_user pu, 
       profil p

where upper(u.login) = upper(:LOGIN) and
    and u.id= pu.user_id
    and pu.profil_id  = p.id
    and p.code_men = "ENS"
    and  m.id = u.id 
    and c.id = u.id
    and grp.id = u.id

            '); 
//---------------------------------------------------------------------------------

?>
