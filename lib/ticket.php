<?php 
/*******************************************************************************
	@filename : ticket.php 
	@description : Classe de gestion des tickets ST et TGT. Comporte 2 mŽthodes 
	publiques de restitution de tickets.
*******************************************************************************/
class ticket {
	static $ticketType; // type de ticket ST ou TGT


	// Constructeur
	function ticket($pTypeTicket) {
		// type de ticket
		ticket::$ticketType = $pTypeTicket;
	}

	// ouverture d'un nouveau fichier de log
	private function _open($nomFichier) {
		log::$logFileName = LOG_FOLDER.$nomFichier."_".log::_getDateDuJour().".html";
		log::$handle=fopen(log::$logFileName, "w+");
	}

}

?>