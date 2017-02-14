<?php
/**
	Jetons spécifiques aux manuels scolaires numériques
	Les balises apparaîssent même si elles ne sont pas valuées.
	
	@file ent_manuels_numeriques.php
	@author PGL pgl@erasme.org
	@param 
	@returns a string with the name o f the attribute and its value
*/
//include_once('../config.inc.php');
define('T', "\t");
define ('VALIDITY',8*60*60); 
class StatusCode
{
    const Success = 0;
    const VersionMismatc = 1;
    const Requester= 2; 
    const Responder = 3; 
    const RequestVersionTooHigh=4; 
    const RequestVersionTooLow=5; 
    const RequestVersionDeprecated=6; 
    const TooManyResponses= 7; 
    const RequestDenied= 8; 
    // etc.
}


function Status($status)
{
    $stat='<Status>
         <StatusCode Value="samlp:'.$status.'"></StatusCode>
         </Status>'; 
    
    return $stat; 
}
function FailureStatus($status, $message)
{
    $stat='<Status><StatusCode Value="samlp:'.$status.'"/>
                     <StatusMessage>'.$message().'</StatusMessage></Status>';  
    
    return $stat; 
}

function Attribute($attributeName, $attributeNS, $attributeValue)
{
    $Attribute=' <Attribute AttributeName="'.$attributeName.'" AttributeNamespace="'.$attributeNS.'">
            <AttributeValue>'.$attributeValue.'</AttributeValue>
          </Attribute>'; 
    
    return $Attribute; 
}

function Subject($nameIdentifier, $confirmationMethod)
{
    $Subject='<Subject>
            <NameIdentifier>'.$nameIdentifier.'</NameIdentifier>
            <SubjectConfirmation>
              <ConfirmationMethod>'.$confirmationMethod.'
              </ConfirmationMethod>
            </SubjectConfirmation>
          </Subject>';
    return $Subject; 
}

function Conditions($notBefore, $NotorAfter)
{ 
    $Conditions='
<Conditions
           NotBefore="'. $notBefore.'" 
           NotOnOrAfter="'. $NotorAfter.'"> 
       <AudienceRestrictionCondition>
            <Audience>
              https://some-service.example.com/app/
            </Audience>
          </AudienceRestrictionCondition>
          </Conditions>'; 
return $Conditions; 

}

function AuthenticationStatement($authenticationMethod,$Subject, $instant){
    
         $Authentication = '<AuthenticationStatement
           AuthenticationMethod="'.$authenticationMethod.'"
           AuthenticationInstant= "'.SimpleSAML_Utilities::generateTimestamp($instant).'">'.$Subject.'
         </AuthenticationStatement>';
         return $Authentication; 
}
function AttributeStatement($Subject,$Attributes)
{
    $AttributeStatement='<AttributeStatement>';
    $AttributeStatement.=$Subject;
    foreach ($Attributes as $Attribute) {
        $AttributeStatement.=$Attribute; 
    }
    $AttributeStatement.='</AttributeStatement>'; 
    
    return $AttributeStatement; 
}

function Assertion($Conditions, $Subject, $AttributeStatement, $AuthenticationStatement, $time)
{
    $Assertion='<Assertion xmlns="urn:oasis:names:tc:SAML:1.0:assertion"
         MajorVersion="1" MinorVersion="1"
         AssertionID="'.SimpleSAML_Utilities::generateID().'"
         Issuer="'.SimpleSAML_Utilities::selfURLhost().'"
         IssueInstant="'. SimpleSAML_Utilities::generateTimestamp($time).'">';
    $Assertion.=$Conditions; 
    $Assertion.=$Subject;
    $Assertion.=$AttributeStatement;
    $Assertion.=$AuthenticationStatement;
    $Assertion.='</Assertion>';
    
    return $Assertion; 
}

// in this case only one Assertion is allowd 
function Response($Assertion, $Status, $time)
{
    $Response='<Response
                               xmlns="urn:oasis:names:tc:SAML:1.0:protocol" 
                               xmlns:saml="urn:oasis:names:tc:SAML:1.0:assertion" 
                               xmlns:samlp="urn:oasis:names:tc:SAML:1.0:protocol" 
                               xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
                               xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
       MajorVersion="1" MinorVersion="1"
       ResponseID="'.SimpleSAML_Utilities::generateID().'" 
       IssueInstant="'. SimpleSAML_Utilities::generateTimestamp($time). '" Recipient="Pronote"> '; 
    $Response.=$Status; 
    $Response.=$Assertion;
    $Response.='</Response>';
    
    return $Response; 
}


function PronoteTokenBuilder($Statuscode,$Attributes,$nameIdentifier,$message) {

  global $CONFIG; 
    // for now  we have two cases : success or RequestDenied

         //success message
         if ($Statuscode==  StatusCode::Success)
         {
             $Status= Status('Success');
             
             $Subject=Subject($nameIdentifier, 'urn:oasis:names:tc:SAML:1.0:cm:artifact');
             
             $AttributesArray = array(); 
             $index=0; 
             foreach ($Attributes as $key => $value) {
                 $attr=Attribute($key,'http://laclasse.com', $value);
                 $AttributesArray[$index]=$attr; 
                 $index+=1;     
             }
             
             $AttributeStatement=AttributeStatement($Subject, $AttributesArray);
             
             
             $time = time(); 
             $validity= time()+VALIDITY;
             $notBefore=SimpleSAML_Utilities::generateTimestamp($time); 
             $notorAfter=SimpleSAML_Utilities::generateTimestamp($validity);
             $Conditions=Conditions($notBefore, $notorAfter); 
             
             $AuthenticationStatement = AuthenticationStatement('urn:oasis:names:tc:SAML:1.0:am:password', $Subject, $time);
             
             $Assertion=Assertion($Conditions, $Subject, $AttributeStatement, $AuthenticationStatement,$time); 
             
             $Response = Response($Assertion, $Status, $time); 
             
             return $Response;  
             
             
             
             
             
         }
        // 
         else
         {
             $Statuscode=StatusCode::Responder; 
             $Status= Status('Responder');
             
             $time = time(); 
             $Response= Response('', $Status, $time); 
             
             return $Response; 
             
             
         }
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


