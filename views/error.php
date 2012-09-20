<?
/*******************************************************************************
	@file : error.php 
	Gestion de template des errors.
*******************************************************************************/
require_once(CAS_PATH.'/views/footer.php');
require_once(CAS_PATH.'/views/header.php');

function viewError($msg) {
  $device = ""; // By default the device is a true real pc browser (not a mobile one)
  if ($_SESSION['isMobile']) {
    $device = 'Mobile';
  }

	call_user_func('getHeader'.$device);
  echo '
  <div id="mire">
      <div id="status" class="errors" style="height:120px;">'.$msg.'</div>
      <br class="clear" />
  </div>';
	call_user_func('getFooter'.$device);
}
