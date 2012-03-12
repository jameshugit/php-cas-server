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
	if ($viewName == 'Default') {
        $token = viewAuthHeader();
		if (is_array($t)){
			foreach($t as $k => $v) {
				$token .= viewAuthAtttribute($k, $v);
			}
		}
    	$token .= viewAuthFooter();
	}
	else { // custom view 
		if (file_exists(CAS_PATH.'/views/'.$viewName.'.php')) {
			require_once(CAS_PATH.'/views/'.$viewName.'.php');
			if (function_exists("view_$viewName")) $token =  call_user_func("view_$viewName", $t);
			else $token = viewAuthHeader () . _('The function "view_'.$viewName.'" does not exist in file "'.$viewName.'.php"  !') . viewAuthFooter ();
		}
		else $token .= _('The file "'.$viewName.'.php" does not exist !');
	}
	return $token;
}