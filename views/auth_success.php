<?php
/*******************************************************************************
	@file : auth_success.php 
	
	Template for CAS2 token when authetication as successed.
*******************************************************************************/
require_once(CAS_PATH.'/views/auth_footer.php');
require_once(CAS_PATH.'/views/auth_header.php');
require_once(CAS_PATH.'/views/auth_attribute.php');


/**
	viewAuthSuccess : Callback that displays the Authentication CAS2 like token.	
	
	@file auth_success.php 
	@author PGL pgl@erasme.org
	@param $t an array of param to be include in the view. this is a key/value array.
	@returns
*/
function viewAuthSuccess($viewName, $t){
	$token = viewAuthHeader();
	if ($viewName == 'Default') {
		if (is_array($t)){
			foreach($t as $k => $v) {
				$token .= viewAuthAtttribute($k, $v);
			}
		}
	}
	else { // custom view 
		if (file_exists(CAS_PATH.'/views/'.$viewName.'.php')) {
			require_once(CAS_PATH.'/views/'.$viewName.'.php');
			if (function_exists("view_$viewName")) $token .=  call_user_func("view_$viewName", $t);
			else $token .= _('The function "view_'.$viewName.'" does not exist in file "'.$viewName.'.php"  !');
		}
		else $token .= _('The file "'.$viewName.'.php" does not exist !');
	}
	$token .= viewAuthFooter();
	return $token;
}
?>