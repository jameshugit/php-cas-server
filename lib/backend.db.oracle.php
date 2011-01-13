<?php 
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
	$stid = oci_parse($conn, SQL_AUTH);
	
	
//	oci_bind_by_name($stid, ':LOGIN', $param['LOGIN']);
//	oci_bind_by_name($stid, ':PWD', $param['PWD']);


	foreach ($param as $key => $val) {
    	// oci_bind_by_name($stid, $key, $val) does not work
    	// because it binds each placeholder to the same location: $val
    	// instead use the actual location of the data: $ba[$key]
    	oci_bind_by_name($stid, $key, $param[$key]);
	}

	if (!$stid) {
    	$e = oci_error($conn);
    	trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
	}
	
	// Exécution de la logique de la requête
	$r = oci_execute($stid);
	if (!$r) {
    	$e = oci_error($stid);
    	trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
	}
	
	// Récupération des résultats de la requête
	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
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
	if (isset($r[0])) {
		print_r(($r[0]));
		if (strtoupper($r[0]) == strtoupper($login)) {
			// Yes ! this was successfull
			return strtoupper($r[0]);
		}
	}
	// here is an unsuccessful attempt...
	return "";
}

?>