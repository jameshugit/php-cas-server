<?php
/*
 * Test de la classe de génération de tickets.
 */
require_once('../config.inc.php'); 
//include('../lib/backend.db.oracle.php'); 

echo "<h1>Oracle Backend unit tests</h1>";
echo "<a href='?test=oci'>Test oci</a>&nbsp;|&nbsp;<a href='?test='>Test credential</a><br/><br/>";

//
// test credential functions
//
if ($_GET['test'] == "") {
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
	if (isset($_POST['psql'])) $select_stmt = $_POST['psql'];
	else $select_stmt = "select '12345' col1, 'AZERTY' col2 from dual";
	
	echo "
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
		//echo "<tr><th>"."COL1"."</th><th>"."COL2"."</th></tr>";
		
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

?>
