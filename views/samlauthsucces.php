<?php
                
/*
 * example success reponse
 <SOAP-ENV:Envelope
   xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Header/>
   <SOAP-ENV:Body>
     <samlp:Response
       xmlns:samlp="urn:oasis:names:tc:SAML:1.0:protocol"
       MajorVersion="1" MinorVersion="1"
       ResponseID="_P1YaA+Q/wSM/t/8E3R8rNhcpPTM="
       InResponseTo="_192.168.16.51.1024506224022"
       IssueInstant="2002-06-19T17:05:37.795Z">
       <samlp:Status>
         <samlp:StatusCode Value="samlp:Success"/>
       </samlp:Status>
       <saml:Assertion
         xmlns:saml="urn:oasis:names:tc:SAML:1.0:assertion"
         MajorVersion="1" MinorVersion="1"
         AssertionID="buGxcG4gILg5NlocyLccDz6iXrUa"
         Issuer="https://idp.example.org/saml"
         IssueInstant="2002-06-19T17:05:37.795Z">
         <saml:Conditions
           NotBefore="2002-06-19T17:00:37.795Z"
           NotOnOrAfter="2002-06-19T17:10:37.795Z"/>
         <saml:AuthenticationStatement
           AuthenticationMethod="urn:oasis:names:tc:SAML:1.0:am:password"
           AuthenticationInstant="2002-06-19T17:05:17.706Z">
           <saml:Subject>
             <saml:NameIdentifier
               Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress">
               user@idp.example.org
             </saml:NameIdentifier>
             <saml:SubjectConfirmation>
               <saml:ConfirmationMethod>
                 urn:oasis:names:tc:SAML:1.0:cm:artifact
               </saml:ConfirmationMethod>
             </saml:SubjectConfirmation>
           </saml:Subject>
         </saml:AuthenticationStatement>
       </saml:Assertion>
     </samlp:Response>
   </SOAP-ENV:Body>
 </SOAP-ENV:Envelope> 
 * 
 *  
 */

               // generic function for success and failure reponses
                function  soapReponse($SamlReponse)
                {
                header('Content-Type: text/xml',true);
		$outputFromIdp = '<?xml version="1.0" encoding="UTF-8"?>';
		$outputFromIdp .= '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">';
		$outputFromIdp .= '<SOAP-ENV:Body>';
                
                
                $outputFromIdp.=$SamlReponse;
		
                
                
                //$outputFromIdp .= $tempOutputFromIdp;
		$outputFromIdp .= '</SOAP-ENV:Body>';
		$outputFromIdp .= '</SOAP-ENV:Envelope>';
		print($outputFromIdp);
		exit(0);
                }

?>
