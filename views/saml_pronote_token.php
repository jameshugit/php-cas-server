<?php
/**
	Jetons spécifiques aux manuels scolaires numériques
	Les balises apparaîssent même si elles ne sont pas valuées.
	
	@file ent_manuels_numeriques.php
	@author PGL pgl@erasme.org
	@param 
	@returns a string with the name o f the attribute and its value
*/
define('T', "\t");

function view_saml_pronote_token($t) {
	$jeton = '
	<?xml version="1.0" encoding="UTF-8"?>
    <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
        <SOAP-ENV:Header/>
        <SOAP-ENV:Body>
            <Response xmlns="urn:oasis:names:tc:SAML:1.0:protocol"
            xmlns:saml="urn:oasis:names:tc:SAML:1.0:assertion"
            xmlns:samlp="urn:oasis:names:tc:SAML:1.0:protocol"
            xmlns:xsd="http://www.w3.org/2001/XMLSchema"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            IssueInstant="2008-09-22T20:38:28.672Z"
            MajorVersion="1"
            MinorVersion="1"
            Recipient="https://trogdor.princeton.edu:8443/test1/"
            ResponseID="_eada71e012b88219d7ecb15c3432f002">
            <Status>
                <StatusCode Value="samlp:Success"></StatusCode>
            </Status>
            <Assertion xmlns="urn:oasis:names:tc:SAML:1.0:assertion"
            AssertionID="_17fb3f1437c7fe89e36594c1141ec31f"
            IssueInstant="2008-09-22T20:38:28.672Z"
            Issuer="localhost"
            MajorVersion="1"
            MinorVersion="1">
                <Conditions NotBefore="2008-09-22T20:38:28.672Z" NotOnOrAfter="2008-09-22T20:38:58.672Z">
                <AudienceRestrictionCondition>
                <Audience>https://trogdor.princeton.edu/test1/</Audience>
                </AudienceRestrictionCondition></Conditions>
                <AttributeStatement>
                <Subject>
                <NameIdentifier>mbarton</NameIdentifier>
                <SubjectConfirmation>
                <ConfirmationMethod>urn:oasis:names:tc:SAML:1.0:cm:artifact</ConfirmationMethod>
                </SubjectConfirmation>
                </Subject>';
                
	foreach($t as $k => $v) {
		$token .= _addCasAttr($k, $v);
	$jeton .= T."</cas:UserAttributes>\n";
	
    $jeton .= T.'</AttributeStatement>
                <AuthenticationStatement AuthenticationInstant="2008-09-22T20:38:28.375Z"
                AuthenticationMethod="urn:oasis:names:tc:SAML:1.0:am:unspecified">
                <Subject>
                <NameIdentifier>mbarton</NameIdentifier>
                <SubjectConfirmation>
                <ConfirmationMethod>urn:oasis:names:tc:SAML:1.0:cm:artifact</ConfirmationMethod>
                </SubjectConfirmation>
                </Subject>
                </AuthenticationStatement>
            </Assertion>
            </Response>
        </SOAP-ENV:Body>
    </SOAP-ENV:Envelope>';
	$jeton .= T."<cas:UserAttributes>\n";
	
	return $jeton;
}

/**
	_addCasAttr : returns a well xml formated CAS attributes.
	@author PGL pgl@erasme.org
	@param $n name
	@param $v value
	@param $tab number of indenting tabs
	@returns an xml formated cas attribute
*/
function _addCasAttr($n,$v,$tab){
	$att="<cas:".$n.">".trim($v, " ")."</cas:".$n.">\n";
	'<Attribute AttributeName="'.$n.'" AttributeNamespace="http://www.ja-sig.org/products/cas/"><AttributeValue>'.$v.'</AttributeValue></Attribute>';
	$tabs="";
	for($i=1;$i<=$tab;$i++) $tabs.=T;
	return $tabs.$att;
}

?>