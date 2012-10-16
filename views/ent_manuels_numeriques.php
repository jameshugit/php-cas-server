<?php
/**
	Jetons spécifiques aux manuels scolaires numériques
	Les balises apparaîssent même si elles ne sont pas valuées.
	
	@file ent_manuels_numeriques.php
	@author PGL pgl@erasme.org
	@param 
	@returns a string with the name o f the attribute and its value
*/
define('T', "\t");

function view_ent_manuels_numeriques($t) {
	$jeton = _addCasAttr("user", $t['user'], 1);
	$jeton .= T."<cas:UserAttributes>\n";
	$jeton .= _addCasAttr("UAI", $t['UAI'], 2);
	$jeton .= _addCasAttr("Profil", $t['Profil'], 2);
	// Pour les élèves
	if ($t['Profil'] == 'National_1') $jeton .= _Eleve($t);
	if ($t['Profil'] == 'National_3') $jeton .= _Enseignant($t);
	$jeton .= T."</cas:UserAttributes>\n";
	return $jeton;
}


/**
	Partie Prof du jeton
	@author PGL pgl@erasme.org
*/
function _Enseignant($t) {
	$jetonProf ="";
	$p = 3;
	$jetonProf = T.T."<cas:Enseignant>\n";
	// Enseignements de l'élève
	$jetonProf .= _addCasMultiValAttr('Classes', 'Classe', $t, $p);
	$jetonProf .= _addCasMultiValAttr('Groupes', 'Groupe', $t, $p);
	$jetonProf .= _addCasMultiValAttr('CategoDisciplines', 'CategoDiscipline', $t, $p);
	$jetonProf .= _addCasMultiValAttr('MatieresEnseignEtab', 'MatiereEnseignEtab', $t, $p);
	$jetonProf .= T.T."</cas:Enseignant>\n";
	return $jetonProf;
}

/**
	Partie Eleve du jeton
	@author PGL pgl@erasme.org
*/
function _Eleve($t){
	$jetonElv = "";
	$p = 3;
	$jetonElv = T.T."<cas:Eleve>\n";
	$jetonElv .= _addCasAttr('CodeNivFormation', $t['CodeNivFormation'], $p);
	$jetonElv .= _addCasAttr('NivFormation', $t['NivFormation'], $p);
	$jetonElv .= _addCasAttr('NivFormationDiplome', $t['NivFormationDiplome'], $p);
	$jetonElv .= _addCasAttr('Filiere', $t['Filiere'], $p);
	$jetonElv .= _addCasAttr('Specialite', $t['Specialite'], $p);
	// Enseignements de l'élève
	$jetonElv .= _addCasMultiValAttr('Enseignements', 'Enseignement', $t, $p);
	// Classe
	$jetonElv .= _addCasAttr('Classe', $t['Classe'], $p);
	// Groupe de l'élève
	$jetonElv .= _addCasMultiValAttr('Groupes', 'Groupe', $t, $p);
	$jetonElv .= T.T."</cas:Eleve>\n";
	return $jetonElv;	
}

/**
	_addCasAttr : returns a well xml formated CAS attributes.
	@author PGL pgl@erasme.org
	@param $n name
	@param $v value
	@param $tab number of indenting tabs
	@returns an xml formated cas attribute
*/
function _addCasAttr($n,$v,$tab){
	$att="<cas:".$n.">".trim($v, " ")."</cas:".$n.">\n";
	$tabs="";
	for($i=1;$i<=$tab;$i++) $tabs.=T;
	return $tabs.$att;
}

/**
	_addCasMultiValAttr : returns a well formated xml multivalued CAS atrribute.
	@file
	@author PGL pgl@erasme.org
	@param $groupName name of the attribute group
	@param $n name
	@param $v value
	@param $tab number of indenting tabs
	@returns an xml formated cas attribute
	@example : 
	This function build a multivalued attribute like this :
	Classes is the groupname and Classe is the name.
		<cas:Classes>
				<cas:Classe> 101</cas:Classe>
				<cas:Classe>101</cas:Classe>
				<cas:Classe>102</cas:Classe>
				<cas:Classe>103</cas:Classe>
		</cas:Classes>
*/
function _addCasMultiValAttr($groupName, $n, $t, $tab){
	$att="";
	$tabs="";
	for($i=1;$i<=$tab;$i++) $tabs.="\t";
	$att .= "<cas:".$groupName.">\n";
	// S'il n'y a pas de valeur (tableau null, on initialise le tableau avec un élément à la valeur nulle
	// de façon à faire apparaître dans le jeton XML, la balise mais non valuée.
	$grps = (isset($t[$n]) && $t[$n] != "") ? split(',', $t[$n]) : Array("$n" => "");
	foreach($grps as $k => $v) {
		$att .=  _addCasAttr($n, str_replace('"','', $v), $tab+1);
	}
	$att .= $tabs."</cas:".$groupName.">\n";
	return viewAuthHeader() . $tabs . $att . viewAuthFooter ();
}
