<?
/*******************************************************************************
	@file : error.php 
	Gestion de template des errors.
*******************************************************************************/
require_once('footer.php');
require_once('header.php');

function viewError($msg) {
	getHeader();
	echo '<div id="status" class="errors" style="height:120px;">'.$msg.'</div>
	<br class="clear" />';
	getFooter();
}

?>