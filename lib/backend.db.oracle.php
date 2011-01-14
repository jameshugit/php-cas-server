<?php 
include_once('functions.php');

/**
	Functions to implement oracle connectivity backend 
	
	This stands for login/pwd validation and CAS2 like authentication token.
	It is possible to develop various backend so as to handle with ldap, mysaq, activeDirectory,etc ...
	connectivity.
	We just MUST assume that functions have the same signature for the public functions.
	
	@file backend.db.oracle.php
	@author PGL pgl@erasme.org

*/


/**
	Conecting to oracle database via OCI8.
	
	@author PGL pgl@erasme.org
	@returns connection object
*/
function _dbConnect() {
	$conn = oci_connect(BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME);
	if (!$conn)		   {
    	$e = oci_error();
    	trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
	}
	return $conn;
}

/**
	Disconecting from oracle database via OCI8.
	
	@author PGL pgl@erasme.org
	@param $conn the db connection object
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
    	trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
	}
	
	// Exécution de la logique de la requête
	$b = oci_execute($stid);
	if (!$b) {
    	$e = oci_error($stid);
    	trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
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
	global $autorized_sites;
	// index of the global array containing the list of autorized sites.
	$idxOfAutorizedSiteArray = getServiceIndex($service);
	// An array with the needed attributes for this service.
	$neededAttr = explode(",", 
					str_replace(" ", "", 
						strtoupper($autorized_sites[$idxOfAutorizedSiteArray]['autorizedAttributes'])
					)
				  );
	$attributes = array(); // What to pass to the function that generate token
	
	/// @note : no need for the moment... global $CONFIG;
	/// @note : no need for the moment... $CASversion = $CONFIG['CAS_VERSION'];
	
	// loading models...
	require_once("../views/auth_success.php");
	// Adding data to the array for displaying.
	// user attribute is requiered in any way.
	$attributes['user'] = $login;
	
	// executing second SQL Statment for other attributes.
	$db = _dbConnect();
	$r = _dbExecuteSQL($db, SQL_FOR_ATTRIBUTES, array('LOGIN'=>$login));
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
	
	// call the token model
	return viewAuthSuccess($attributes);
}

?>