<?php
/*
 * Test of SAML.
 */
require_once('../config.inc.php'); 
require_once('../lib/ticket.php'); 
require_once('../views/auth_success.php'); 
require_once('../views/auth_failure.php'); 
echo "<h1>SAML/CAS unit tests</h1>";

//$SERVICE  = 'Umpa-lumpa-Serv';
$SERVICE  = 'http://pronote.dev.laclasse.lan:8080/pornote.net/cas/validationcas/'; 
$USERNAME = 'charlie';

$tgt = new TicketGrantingTicket();
$tgt->create($USERNAME);
$key = $tgt->key();

$tgt = new TicketGrantingTicket();
$tgt->find($key);

$tgt = $key;

$st = new ServiceTicket();
$st->create($tgt, $SERVICE, $USERNAME);
$stkey = $st->key();

$validateSTsaml = '<?xml version="1.0"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
  <SOAP-ENV:Header/>
  <SOAP-ENV:Body>
    <samlp:Request xmlns:samlp="urn:oasis:names:tc:SAML:1.0:protocol" MajorVersion="1"
      MinorVersion="1" RequestID="_192.168.16.51.1024506224022"
      IssueInstant="2002-06-19T17:03:44.022Z">
      <samlp:AssertionArtifact>
        '.$stkey.'
      </samlp:AssertionArtifact>
    </samlp:Request>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

$header = array(
  "Content-type: text/xml;charset=\"utf-8\""
);


echo "TGT = " . $key."<br>";
echo "ST = ".$stkey."<br>";
echo "<h2> here is a typical saml message to ask for ST validation...</h2>";
echo "<pre>".htmlentities($validateSTsaml)."</pre>";

echo "
<script>function go(){location.href=\"?action=samlValidate\";}</script>
<input type='button' value='Post this to CAS' onclick='javascript:go();' />";

if (isset($_REQUEST['action']) && $_REQUEST['action']=='samlValidate') {
    echo "<h3>Let's do the post with curl...</h3>";
    
    $urlValidate = "http://".$_SERVER['SERVER_NAME']."/sso/samlValidate?TARGET=".urlencode("http://pronote.dev.laclasse.lan:8080/pornote.net/cas/validationcas/");
   echo "Url smalValidate = ".$urlValidate;

    $post_data = array();
    $post_data['ticket'] = $validateSTsaml;
    $post_data['TARGET'] = "http://pronote.dev.laclasse.lan:8080/pornote.net/cas/validationcas/";
    
    $ch = curl_init($urlValidate);
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$validateSTsaml);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION ,1);
    curl_setopt($ch, CURLOPT_HEADER,0);  // DO NOT RETURN HTTP HEADERS
    curl_setopt($ch, CURLOPT_HTTPHEADER,     $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);  // RETURN THE CONTENTS OF THE CALL
    $Rec_Data = curl_exec($ch);

    echo "<pre>".htmlentities($Rec_Data)."</pre>"; 
}
//echo "
//<iframe src='http://www.dev.laclasse.com/sso/samlValidate'></iframe>
//";

//echo htmlentities(getServiceValidate('plevallois', 'http://pronote.dev.laclasse.lan:8080/*'));

?>
