<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$soap = simplexml_load_file('Soap.xml'); 
   //print_r($c);
   $Request= null;  
  foreach ($soap->children("http://schemas.xmlsoap.org/soap/envelope/") as $tag => $item) {
      if ($tag=='Body')
      {    
    printf("balise : %s\n", $tag);
    print_r($item);
    
       foreach($item->children("urn:oasis:names:tc:SAML:1.0:protocol") as $key => $value)
       {
           if ($key='Request')
           {
             printf("balise : %s\n", $key);
             
             $Request= $value;
             print_r($value);
             
                }
      }
    
    
    
  }
  }
  print_r($Request);
  foreach ($Request->children("urn:oasis:names:tc:SAML:1.0:protocol") as $tag => $item)
  {
      printf("balise : %s\n", $tag);
      
       print_r($item);
       if ($tag=='AssertionArtifact')
       {
           $ticket_value= (String)$item[0];
           print_r($ticket_value);
       }
  }
  
?>
