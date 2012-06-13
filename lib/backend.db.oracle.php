<?php 
include_once(CAS_PATH.'/lib/functions.php');
require_once(CAS_PATH.'/views/auth_success.php'); 
require_once(CAS_PATH.'/views/auth_failure.php'); 

/**
 * Functions to implement oracle connectivity backend 
 *
 * This stands for login/pwd validation and CAS2 like authentication token.
 * It is possible to develop various backend so as to handle with ldap, mysaq, activeDirectory,etc ...
 * connectivity.
 * We just MUST assume that functions have the same signature for the public functions.
 *
 * @file backend.db.oracle.php
 * @author PGL pgl@erasme.org
 *
 */


/**
 * Connecting to oracle database via OCI8.
 *	
 * @author PGL pgl@erasme.org
 * @returns connection object
 */
function _dbConnect() {
	$conn = oci_connect(BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME);
	if (!$conn)		   {
    	$e = oci_error();
    	//trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
        throw new Exception($e['message']); 
	}
	return $conn;
}

/**
 * Disconecting from oracle database via OCI8.
 *
 * @author PGL pgl@erasme.org
 * @param $conn the db connection object
 */
function _dbDisconnect($conn) {
	oci_close($conn);
}

/**
	Preparing and execution a sql statment.
	
	@author PGL pgl@erasme.org
	@param $conn the db connection object
	@param $sql the sql statment to execute
	@param $param sql parameters to bind in the sql statment
	@returns a recordset
*/
function _dbExecuteSQL($conn, $sql, $param){
	$recordSet = array();
	$idx = 0;
	// Prepare SQL
	$stid = oci_parse($conn, $sql);
	
	if (is_array($param) && isset($param)) {
		foreach ($param as $key => $val) {
    		// oci_bind_by_name($stid, $key, $val) does not work
    		// because it binds each placeholder to the same location: $val
    		// instead use the actual location of the data: $param[$key]
    		oci_bind_by_name($stid, $key, $param[$key], 2000, SQLT_CHR);
		}
	}

	if (!$stid) {
    	$e = oci_error($conn);
    	//trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
	 throw new Exception($e['message']); 
	}
	
	// Exécution de la logique de la requête
	$b = oci_execute($stid);
	if (!$b) {
    	$e = oci_error($stid);
    	//trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
 	throw new Exception($e['message']); 
	}
	
	// fetch data
	while ($row = oci_fetch_assoc($stid)) {
		$recordSet[$idx] = $row;
		$idx++;
	}
		
	// Frre oci's mem
	oci_free_statement($stid);
	
	// Returning dataset
	return $recordSet;
}

/**
	verifyLoginPasswordCredential : implementation of authentication on db backend.
	
	If authentication is successful the username is returned, else an empty string is returned.
	
	@file backend.db.oracle.php
	@author PGL pgl@erasme.org
	@param $login : the login which was entered on the web autentication form.
	@param $pwd : the password of the login user account.
	@returns the username or empty string ""
*/
function verifyLoginPasswordCredential($login, $pwd) {
	$sqlParam = array('LOGIN'=>$login, 'PWD'=>$pwd);
	$db = _dbConnect();
	$r = _dbExecuteSQL($db, SQL_AUTH, $sqlParam);
	_dbDisconnect($db);
	
	// If no record returned, credential is not valid.	
	if (!$r) return "";

	// See what we have to do.
	// just deal with the first reccord.
	$rowSet = $r[0];
	if (isset($rowSet)) {
		if (strtoupper($rowSet['LOGIN']) == strtoupper($login)) {
			// Yes ! this was successfull
			return strtoupper($rowSet['LOGIN']);
		}
	}
	// here is an unsuccessful attempt...
	return "";
}
/* 
	function to search a user in the database
	@param $nom : user last name.
	@param $prenom : user first name.
	@returns array of attributes.
*/

function search_user_by_name($nom,$prenom)
{
    
        global $CONFIG;
        $myAttributesProvider = Search_Agent_by_Name; 
	$db = _dbConnect();
	$r = _dbExecuteSQL($db, $myAttributesProvider, array('nom'=>$nom, 'prenom' =>$prenom));
	_dbDisconnect($db);
        
        $attributes= array(); 
        // must treat the case with more than one response
        if(count($r)>1)
        {
            //throw new Exception('more than one user with the same last and first name'); 
            for ($i=0;$i< count($r);$i++) {
                $rowSet=$r[$i];
                foreach($rowSet as $idx => $val) {
	                   $attributes[$i][$idx] = $val;
			
		}
                
                
            }
        }
 else {
         if(count($r)==1)
        {
            //throw new Exception('more record exist in the database'); 
       
                $rowSet=$r[0];
                foreach($rowSet as $idx => $val) {
	                   $attributes[0][$idx] = $val;
			
		}
                
                
            }
        }  
    return $attributes; 
}

function create_utilisateur($nom,$prenom,$login,$password,$sex,$mail,$profile,$UaiEtab, $eleveid)
{
    
        global $CONFIG;
        //$procedure= create_Agent_procedure; 
        $query = create_user; 
	$db = _dbConnect();
	//$s = oci_parse($db, $procedure);
   	//oci_execute($s, OCI_DEFAULT);
	_dbExecuteSQL($db, $query, array('nom'=>$nom, 'prenom'=>$prenom,'password'=>$password, 'sex'=>$sex, 'login'=>$login , 'mail'=>$mail, 'profile'=> $profile, 'eleveid'=>$eleveid, 'UaiEtab'=>$UaiEtab));
	_dbDisconnect($db);
        
        
}

function create_parent($nom,$prenom,$login,$password,$sex,$mail,$eleveid,$etab)
{
  $profile = 8;
  $result = false;
  $r= search_eleve_by_sconetid($eleveid);

    if(!empty($r))
     {
               $UaiEtab =null;  // the etablissement will be attached later
               $elev = null; // eleve id will be attached later
               //get etablissement id and test that it is not empty ..to create a user
               create_utilisateur($nom,$prenom,$login,$password,$sex,$mail,$profile,$UaiEtab, $eleveid);

               $result=true;
     }
     return $result;

 }

function create_eleve($nom,$prenom,$login,$password,$sex,$mail,$UaiEtab, $eleveid)
{
                 $profile= 4;
                 $result= false;
                 $r= search_eleve_by_sconetid($eleveid); 
                 if(empty($r))
                 { 
                   //if not empty (get etablissement id)  then create eleve, else does not exist  send an erreur //
                   //$UaiEtab is the Code_RNE in the database
                      $attr=Get_etablissement_id($UaiEtab); 
                      if(!empty($attr))
                      {
                        $etabid= $attr[0]["id"]; 
                        create_utilisateur($nom,$prenom,$login,$password,$sex,$mail,$profile,$etabid, $eleveid);
                        $result=true; 
                      }
                 }
                 return $result; 

}

function create_prof($nom,$prenom,$login,$password,$sex,$mail,$UaiEtab)
{
	$profile = 3; 
	$eleveid = null; 
	create_utilisateur($nom,$prenom,$login,$password,$sex,$mail,$profile,$UaiEtab, $eleveid);	
}

function create_Agent($nom,$prenom,$login,$password,$sex,$mail,$UaiEtab)
{
	$profile= 6 ; 
	$eleveid= null; 
	create_utilisateur($nom,$prenom,$login,$password,$sex,$mail,$profile,$UaiEtab, $eleveid);
}

function set_relation_parent_eleve($parentid, $eleveid)
{
	$elevearray=array(); 
	$elevearray[0]=$eleveid.''; 
	global $CONFIG;
        //$procedure= create_Agent_procedure; 
        $query = set_relation_parent_eleve; 
	$db = _dbConnect();
	$statement = oci_parse($db, $query);
	oci_bind_array_by_name($statement, ":list_enfant", $array, 2, -1, SQLT_CHR);
	oci_bind_by_name($statement, ":parent_id", $parentid);
   	oci_execute($statement); 
	_dbDisconnect($db);

}
function search_eleve_by_sconetid($eleveid)
{
              global $CONFIG;
                  $query = Search_student_By_Id; 
            $db = _dbConnect();
            $r = _dbExecuteSQL($db, $query, array('eleveid' => $eleveid));
              _dbDisconnect($db);
                    
                    return $r; 
}

function attach_parent_eleve($nom, $prenom, $UaiEtab, $eleveid)
{
		if(search_eleve_by_id($eleveid))
			{
				if(search_user_by_name($nom,$prenom)) // problem if we have parents with the same first and last name
					set_relation_parent_eleve($parent, $eleveid); 
			else {
					create_parent($nom,$prenom,$login,$password,$sex,$mail); 
					set_relation_parent_eleve($parent, $eleveid); 					
      }
     }
		else{
			 return 'le compte ne peut pas etre creier car l:eleve nexist pas' ; 	
		    }		
}


function Get_profiles()
{
    global $CONFIG;
        $query = Get_categories; 
	$db = _dbConnect();
	$r = _dbExecuteSQL($db, $query, null);
	_dbDisconnect($db);
        
        return $r; 
}

function Get_sconet_ids()
{
    global $CONFIG;
        $query = Get_sconet_ids; 
	$db = _dbConnect();
	$r = _dbExecuteSQL($db, $query, null);
	_dbDisconnect($db);
        
        return $r; 
}

function Get_user_id($eleveid)
{
	global $CONFIG;
        $query = Get_user_id; 
	$db = _dbConnect();
	$r = _dbExecuteSQL($db, $query, array('elevid' => $eleveid));
	_dbDisconnect($db);
        
        return $r; 
}

function Search_Etablissment($etab)
{
	global $CONFIG;
        $query = Search_Etablissment; 
	$db = _dbConnect();
	$r = _dbExecuteSQL($db, $query, array('UaiEtab' => $etab));
	_dbDisconnect($db);
        
        return $r; 
}

function Search_Parent_By_EleveId($eleveid)
{
	 global $CONFIG;
        $query = Search_Parent_By_EleveId; 
	$db = _dbConnect();
	$r = _dbExecuteSQL($db, $query, array('elevid' => $eleveid));
	_dbDisconnect($db);
        
        return $r; 
}

function Search_Parent_By_Name_Etab_EleveId($nom, $prenom, $eleveid)
{
    global $CONFIG;
    $query = Search_Parent_By_Name_EleveId;
    $db = _dbConnect();
    $r = _dbExecuteSQL($db, $query, array('nom'=> $nom,  'prenom'=> $prenom, 'elevid' => $eleveid));
    _dbDisconnect($db);
    return $r;
}

function Search_Parent_By_SconetID($nom, $prenom, $eleveid)
 {
     global $CONFIG;
     $query = Search_Parent_By_SconetID;
      $db = _dbConnect();
      $r = _dbExecuteSQL($db, $query, array('nom'=> $nom,  'prenom'=> $prenom, 'elevid' => $eleveid));
       _dbDisconnect($db);
       return $r;
  }
// search agent by institutional email sent by the academie //
function Search_Agent_by_mail($mail)
{
         global $CONFIG;
         $query = Search_Agent_by_mail;
         $db = _dbConnect();
          $r = _dbExecuteSQL($db, $query, array('mail'=> $mail));
         _dbDisconnect($db);
          return $r;

}

// search user by email in the utilisateurs table, for google login//
function Search_user_by_email($mail)
    {
          global $CONFIG;
          $query = Search_Agent_by_mail;
          $db = _dbConnect();
           $r = _dbExecuteSQL($db, $query, array('mail'=> $mail));
          _dbDisconnect($db);
          return $r;
       }

function Get_etablissement_id($UaiEtab)
{
	global $CONFIG;
        $query = get_Etablissement_id; 
	$db = _dbConnect();
	$r = _dbExecuteSQL($db, $query, array('UaiEtab'=>$UaiEtab));
	_dbDisconnect($db);
        
        return $r[0]["ID"]; 	
}

function Register_Service()
{
  global $CONFIG; 
  $query = register_service; 
  $db = _dbConnect();
  $r = _dbExecuteSQL($db, $query, NULL);
  _dbDisconnect($db);
   return $r;

}




/**
	getServiceValidate : Implementation of the CAS like token
	
	If the version of cas protocol desired is 2.0, this function should 
	return some attributes from a second sql statment called SQL_FOR_ATTRIBUTES
	
	The format of the generated token is xml as follows :
	@code
	<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
    	<cas:authenticationSuccess>
       	 	<cas:user>PLEVALLOIS</cas:user>
       	 	
       	 	<!-- Here are the CAS2 Attributes -->
        	<cas:uid>87654321</cas:uid>
       	 	<cas:ENTPersonStructRattachRNE>0690001B</cas:ENTPersonStructRattachRNE>
        	<cas:ENT_id>10010</cas:ENT_id>
        	<cas:ENTPersonProfils>National_3</cas:ENTPersonProfils>
        	
    	</cas:authenticationSuccess>
	</cas:serviceResponse>
	@endcode
	
	@file backend.db.oracle.php
	@author PGL pgl@erasme.org
	@param $login : the login which was autenticated.
	@param $service : the associated service.
	@returns string containing loads of XML
*/
function getServiceValidate($login, $service) {
	global $CONFIG;
	// index of the global array containing the list of autorized sites.
	$idxOfAutorizedSiteArray = getServiceIndex($service);
	$myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider']) ? 
							$CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider'] : SQL_FOR_ATTRIBUTES;

	$myTokenView = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele']) ? 
				   $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele'] : 'Default';
	// If service index is null, service is not allow to connect to our sso.
	//if ($idxOfAutorizedSiteArray == "")
	//	return viewAuthFailure(array('code'=> '', 
	//								 'message'=> _('This application is not allowed to authenticate on this server')));
	
	// An array with the needed attributes for this service.
	$neededAttr = explode(	",", 
							str_replace(" ", "", 
							strtoupper($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']))
						);
	$attributes = array(); // What to pass to the function that generate token
	
	/// @note : no need for the moment... $CASversion = $CONFIG['CAS_VERSION'];
	
	// Adding data to the array for displaying.
	// user attribute is requiered in any way.
	// this is requiered in CAS 1.0 for phpCAS Client.
	$attributes['user'] = $login;
	
	// executing second SQL Statment for other attributes.

	$db = _dbConnect();
	$r = _dbExecuteSQL($db, $myAttributesProvider, array('LOGIN'=>$login));
	_dbDisconnect($db);
	
	// Should have only one row returned.
	$rowSet = $r[0];
	if (isset($rowSet)) {
		// For all attributes returned
		foreach($rowSet as $idx => $val) {
			if (in_array(strtoupper($idx), $neededAttr)) {
				$attributes[$idx] = $val;
			}
		}
	}
	
	// call the token model with the default view or custom view
	return viewAuthSuccess($myTokenView, $attributes);
}

/**
	getSamlValidate
	
	Implementation of SAML validation
	
	@file backend.db.oracle.php
	@author PGL pgl@erasme.org
	@param 
	@returns String : the SAML response
*/
function getSamlAttributes($login, $service) {
	global $CONFIG;
	// index of the global array containing the list of autorized sites.
	$idxOfAutorizedSiteArray = getServiceIndex($service);
	$myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider']) ? 
							$CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider'] : SQL_FOR_ATTRIBUTES;

	$myTokenView = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele']) ? 
				   $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele'] : 'Default';
	// If service index is null, service is not allow to connect to our sso.
	//if ($idxOfAutorizedSiteArray == "")
	//	return viewAuthFailure(array('code'=> '', 
	//								 'message'=> _('This application is not allowed to authenticate on this server')));
	
	// An array with the needed attributes for this service.
	$neededAttr = explode(	",", 
							str_replace(" ", "", 
							strtoupper($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']))
						);
        
//                                                foreach ($neededAttr as $value) {
//                                                    echo "$value \n"; 
//                                                    
//                                                }
	$attributes = array(); // What to pass to the function that generate token
	
	/// @note : no need for the moment... $CASversion = $CONFIG['CAS_VERSION'];
	
	// Adding data to the array for displaying.
	// user attribute is requiered in any way.
	// this is requiered in CAS 1.0 for phpCAS Client.
	$attributes['user'] = $login;
        //echo $myAttributesProvider; 
	
	// executing second SQL Statment for other attributes.

	$db = _dbConnect();
	$r = _dbExecuteSQL($db, $myAttributesProvider, array('LOGIN'=>$login));
	_dbDisconnect($db);
	
	// Should have only one row returned.
	$rowSet = $r[0];
        //echo count($rowSet);  
	if (isset($rowSet)) {
		// For all attributes returned
		foreach($rowSet as $idx => $val) {
			if (in_array(strtoupper($idx), $neededAttr)) {
				$attributes[$idx] = $val;
			}
		}
	}
        
        //echo count($attributes); 
        return $attributes; 
}

?>
