<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
function validateSaml($samlRequest,$samlSchema)
{
    assert('is_string($samlRequest)');
    assert('is_string($samlSchema)');
    
    
    try{
    $dom = new DOMDocument(); 
    $dom->loadXML($samlRequest); 
    $validschema = $dom->schemaValidate($samlSchema);
    
    return $validschema;
    }
    catch(Exception $e)
    {
        
        if ($e->getCode()==2)
            return 1; 
        else 
            return 0; 
    }
    //return $validschema;
}

class SamloneResponse{
    
    public $ResponseID; 
    public $InResponseTo;
    public $IssueInstant; 
    public $Status; // xml element
    public $Assertions; // array of xml elements 
    
    
    public  function __construct() {
        
    }
    
    public function getResponseID() {
        return $this->ResponseID;
    }

    public function setResponseID($ResponseID) {
        $this->ResponseID = $ResponseID;
    }

    public function getInResponseTo() {
        return $this->InResponseTo;
    }

    public function setInResponseTo($InResponseTo) {
        $this->InResponseTo = $InResponseTo;
    }

    public function getIssueInstant() {
        return $this->IssueInstant;
    }

    public function setIssueInstant($IssueInstant) {
        $this->IssueInstant = $IssueInstant;
    }

    public function getStatus() {
        return $this->Status;
    }

    public function setStatus($Status) {
        $this->Status = $Status;
    }

    public function getAssertions() {
        return $this->Assertions;
    }

    public function setAssertions($Assertions) {
        $this->Assertions = $Assertions;
    }

        
    
    
     
}

class SamloneRequest{
    public $RequestID;
    public $IssueInstant; 
    public $AssertionArtifact;
    
    public  function __construct() {
    
    }  
    
    public function getRequestId()
    {
        return $this->RequestID;
    }
    public function setRequestId($requestId)
    {
        $this->RequestID = $requestId;
    }
    
    public function getIssueInstant(){
        
        return $this->IssueInstant;
    }
    public function setIssueInstant($issueinstant){
       $this->IssueInstant=$issueinstant;
    }
    
    public function getAssertionArtifact()
    {
        return $this->AssertionArtifact;
    }
    
    public function setAssertionArtifact($artifact)
    {
        $this->AssertionArtifact=$artifact;
    }
}
    
class Status
    {
      public $satusCode;
      public function __construct()
      {}
      
        public function getSatusCode() {
            return $this->satusCode;
        }

        public function setSatusCode($satusCode) {
            $this->satusCode = $satusCode;
        }


    }
    
    class Assertion
    {
        public $AssertionID;
        public $Issuer;
        public $Conditions;
        public $AuthenticationStatement;
        
        public function __construct() {
            
        }
        
        public function getAssertionID() {
            return $this->AssertionID;
        }

        public function setAssertionID($AssertionID) {
            $this->AssertionID = $AssertionID;
        }

        public function getIssuer() {
            return $this->Issuer;
        }

        public function setIssuer($Issuer) {
            $this->Issuer = $Issuer;
        }

        public function getConditions() {
            return $this->Conditions;
        }

        public function setConditions($Conditions) {
            $this->Conditions = $Conditions;
        }

        public function getAuthenticationStatement() {
            return $this->AuthenticationStatement;
        }

        public function setAuthenticationStatement($AuthenticationStatement) {
            $this->AuthenticationStatement = $AuthenticationStatement;
        }


    }
    
    class Condition
    {
         public $NotBefore;
         public $NotOnOrAfter;
         
         function __construct() {
             
         }
         public function getNotBefore() {
             return $this->NotBefore;
         }

         public function setNotBefore($NotBefore) {
             $this->NotBefore = $NotBefore;
         }

         public function getNotOnOrAfter() {
             return $this->NotOnOrAfter;
         }

         public function setNotOnOrAfter($NotOnOrAfter) {
             $this->NotOnOrAfter = $NotOnOrAfter;
         }

    }
    
    class AuthenticationStatment
    {
        public $AuthenticationMethod;
        public $AuthenticationInstant;
        public $Subject;
        
        public function __construct() {
           
        }
        public function getAuthenticationMethod() {
            return $this->AuthenticationMethod;
        }

        public function setAuthenticationMethod($AuthenticationMethod) {
            $this->AuthenticationMethod = $AuthenticationMethod;
        }

        public function getAuthenticationInstant() {
            return $this->AuthenticationInstant;
        }

        public function setAuthenticationInstant($AuthenticationInstant) {
            $this->AuthenticationInstant = $AuthenticationInstant;
        }

        public function getSubject() {
            return $this->Subject;
        }

        public function setSubject($Subject) {
            $this->Subject = $Subject;
        }


        
    }
    
    
    class Subject{
        public $NameIdentifier;
        public $SubjectConfirmation;
        
        public function __contstruct()
        {
            
        }
        public function getNameIdentifier() {
            return $this->NameIdentifier;
        }

        public function setNameIdentifier($NameIdentifier) {
            $this->NameIdentifier = $NameIdentifier;
        }

        public function getSubjectConfirmation() {
            return $this->SubjectConfirmation;
        }

        public function setSubjectConfirmation($SubjectConfirmation) {
            $this->SubjectConfirmation = $SubjectConfirmation;
        }
        
    }
    
    class SubjectConfirmation
    {
        public $ConfirmationMethod; 
        
        public function __construct()
        {
            
        }
        public function getConfirmationMethod() {
            return $this->ConfirmationMethod;
        }

        public function setConfirmationMethod($ConfirmationMethod) {
            $this->ConfirmationMethod = $ConfirmationMethod;
        }


    }
    

?>
