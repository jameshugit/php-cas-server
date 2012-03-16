<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class SamloneResponseMessage{
    
    public $ResponseID; 
    public $InResponseTo;
    public $IssueInstant; 
    public $Status; // xml element
    public $Assertions; // array of xml elements 
     
}
?>
