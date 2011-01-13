<?php
/*
 * Test de la classe de génération de tickets.
 */
require_once('../config.inc.php'); 
//include('../lib/backend.db.oracle.php'); 

echo "<h1>Oracle Backend unit tests</h1>";
echo "A form to test 'verifyLoginPasswordCredential' function.
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
	echo "<li>pwd = ".strlen($_POST['ppwd'])." chars.</li></ul>";
	
	$ret  = verifyLoginPasswordCredential($_POST['plogin'], $_POST['ppwd']);
	
	echo "verifyLoginPasswordCredential returned : ".$ret;

}

?>
