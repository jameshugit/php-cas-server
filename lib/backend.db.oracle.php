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

/*

// Préparation de la requête
$stid = oci_parse($conn, "select count(1) from utilisateurs where upper(login) = 'PLEVALLOIS' and upper(pwd) = 'UHN953!'");
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
print "<table border='1'>\n";
while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    print "<tr>\n";
    foreach ($row as $item) {
        print "    <td>" . ($item !== null ? htmlentities($item, ENT_QUOTES) : "") . "</td>\n";
    }
    print "</tr>\n";
}
print "</table>\n";

oci_free_statement($stid);

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
	// Prepare SQL
	$stid = oci_parse($conn, "select count(1) from utilisateurs where upper(login) = 'PLEVALLOIS' and upper(pwd) = 'UHN953!'");
	oci_bind_by_name($stid, ':LOGIN', $param['LOGIN']);
	oci_bind_by_name($stid, ':PWD', $param['PWD']);
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
	
	// Frre oci's mem
	oci_free_statement($stid);
	
	// Returning dataset
	return $r;
}


function verifyLoginPasswordCredential($login, $pwd) {
	$sqlParam = array('LOGIN'=>$login, 'PWD'=>$pwd);
	$db = _dbConnect();
	$r = _dbExecuteSQL($db, SQL_AUTH, $sqlParam);
	print_r($r);
	_dbDisconnect($db);
}

?>