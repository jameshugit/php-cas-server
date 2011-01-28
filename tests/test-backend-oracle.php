<?php
/*
 * Test of the oracle backend.
 */
require_once('../config.inc.php'); 
require_once(CAS_PATH.'/views/auth_success.php'); 
require_once(CAS_PATH.'/views/auth_failure.php'); 

		
echo "<h1>Oracle Backend unit tests</h1>";
echo "
<a href='?test=oci'>Test oci</a>&nbsp;|&nbsp;
<a href='?test=credential'>Test credential</a>&nbsp;|&nbsp;
<a href='?test=token'>Test CAS2 Token</a>
<br/>";

if (isset($_GET['test']) and $_GET['test'] != "") {
	echo "<h2>Testing ".$_GET['test']." now !</h2>";
} else {$_GET['test'] = "";}

//
// test credential function "verifyLoginPasswordCredential"
//
if ($_GET['test'] == "credential") {
echo "A form to test 'verifyLoginPasswordCredential' function.";
echo "
<form method='post'>
<table>
<tr><td>Login</td><td><input type='text' name='plogin' /></td></tr>
<tr><td>Pwd</td><td><input type='password' name='ppwd' /></td></tr>
<tr><td colspan='2' align='center'><input type='submit' value='Test' /></td></tr>
</table>
</form>
";
if (isset($_POST['plogin']) && isset($_POST['ppwd'])) {
	echo "Received :<br/>";
	echo "<ul><li>login = ".$_POST['plogin']."</li>";
	echo "<li>pwd = ".strlen($_POST['ppwd'])." chars.</li>";
	echo "<li>The SQL Stament in use is '".SQL_AUTH."'</li></ul>";
	
	$ret  = verifyLoginPasswordCredential($_POST['plogin'], $_POST['ppwd']);
	
	echo "verifyLoginPasswordCredential returned : '".$ret."'";
	
	if ($ret != "") $authStatus = "<span style='color:green;'>successful</span>";
	else $authStatus = "<span style='color:red;'>unsuccessful</span>";
	
	echo "<br/>verifyLoginPasswordCredential was <b>".$authStatus."</b>";
}

}

//
// Test OCI.
//
if ($_GET['test'] == "oci") {
	if (isset($_POST['psql'])) $select_stmt = str_replace("\'", "'", $_POST['psql']);
	else $select_stmt = "select '12345' col1, 'AZERTY' col2 from dual";
	
	echo "Database name in config.inc.php is <span style='color:red;'>".BACKEND_DBNAME."</span><br/>
	Username in config.inc.php is <span style='color:red;'>".BACKEND_DBUSER."</span><br/>
	<form method='post'>
	Testing an SQL statment :
	<table>
	<tr><td>SQL : </td><td><textarea name='psql' cols='50' rows='10'/>".$select_stmt."</textarea></td></tr>
	<tr><td colspan='2' align='center'><input type='submit' value='Execute' /></td></tr>
	</table>
	</form>";

	if (isset($_POST['psql'])) {
		$db = _dbConnect();
		echo "sql statment is '$select_stmt'<br/>";
	
		$r = _dbExecuteSQL($db, $select_stmt, array());
		print_r($r);
		echo "<table border='1'>\n";
		
		foreach ($r as $k => $row) {
			
			if ($k == 0) {
				echo "<tr>";
				foreach($row as $col => $val) echo "<th>". $col . "</th>";
				echo "</tr>";
			}
			echo "<tr>";
			foreach($row as $col => $val) echo "<td>". $val . "</td>";
    		echo "</tr>";
 		}
 		echo "</table>\n";
		_dbDisconnect($db);
	
	}
}

//
// Test serviceValidate function.
//

if ($_GET['test'] == "token") {
	global $CONFIG;
	
	echo "
	<form action='?test=token' method='post'>
	Testing the cas2 like return token :
	<table>
	<tr><td>Login : </td><td><input type='text' id='plogin' name='plogin' value='".(isset($_POST['plogin'])? $_POST['plogin'] : "")."'/></td></tr>
	<tr><td>Service : </td><td><input type='text' id='psite' name='psite' size='40' value='".(isset($_POST['psite'])? $_POST['psite'] : "http://")."'/></td></tr>
	<tr><td colspan='2' align='center'><input type='submit' value='Test...' /></td></tr>
	</table>
	</form>";
	

	if (isset($_POST['psite']) && isset($_POST['plogin'])) {
		echo "Received :<br/>";
		echo "<ul><li>login = ".$_POST['plogin']."</li>";
		echo "<li>service = ".$_POST['psite']."</li></ul>";
		echo "<li>the index of this service is ".getServiceIndex($_POST['psite'])."</li></ul>";
		echo "<pre>";
		echo htmlentities(getServiceValidate($_POST['plogin'], $_POST['psite']));
		echo "</pre>";
		echo "<textarea name='ptoken' cols='100' rows='20'>".getServiceValidate($_POST['plogin'], $_POST['psite'])."</textarea>";
	
	}
}
?>
