<?php
/*******************************************************************************
	@file : error.php 
	Gestion de template des errors.
*******************************************************************************/

require_once(__ROOT__.'/views/footer.php');
require_once(__ROOT__.'/views/header.php');

function viewError($msg) {
	getHeader();
	echo
'		<div class="box" style="max-width: 400px; text-align: left; width: 100%">
			<div style="font-size: 30px; text-align: center; margin-bottom: 10px; padding: 10px; color: white; background-color: #eb5454;">
				Erreur
			</div>
			<div style="margin-bottom: 20px; color: #eb5454">
				<div>
					'.$msg.'
				</div>
			</div>
		</div>
';
	getFooter();
}
