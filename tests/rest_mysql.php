<?php

//httpful.phar is a library that facilites sending rest requests 
// @ref = https://github.com/nategood/httpful
include_once('/var/www/sso/lib/rest_request.php');

     $url= 'http://www.dev.laclasse.com/pls/public/!ajax_server.service'; 
     $method = 'get'; 
     $headers = null;
     $secret_key = "";  // this is not a secret key change it to test .
     
     //test verify login // use a correct password 
     $servicename = "service_user_login";    
     $params = array("login" => "bsaleh", "password"=> md5("pass"), "servicename" => $servicename); 
     $request = new HttpRequest($url, $headers, $method, $params);
     $srr = new SimpleRestRequest($request); 
     $response = $srr->execute($secret_key);
     $arr = json_decode($response->body, true );
     print_r($arr); 
     //if ($arr["login"]== "BSALEH") echo " 1 - verify login test passed"; 
     
     //test user attributes 
     $servicename = "service_user_attributes";    
     $params = array("login" => "bsaleh", "servicename" => $servicename); 
     $request = new HttpRequest($url, $headers, $method, $params);
     $srr = new SimpleRestRequest($request); 
     $response = $srr->execute($secret_key);
     $arr = json_decode($response->body, true );
     print_r($arr); 
     //if ($arr["ENT_id"] == "178195") echo " 2 - verify Sso attributes test passed"; 
     
     //test user agent mail 
     $servicename = "service_user_agent_mail";    
     $params = array("email" => "agent.ent-test@ac-lyon.fr", "servicename" => $servicename); 
     $request = new HttpRequest($url, $headers, $method, $params);
     $srr = new SimpleRestRequest($request); 
     $response = $srr->execute($secret_key);
     //print_r($response); 
     $arr = json_decode($response->body, true );
     //print_r($arr[0]); 
     if ($arr[0]["login"]== "BSALEH") echo " 3 - verify email agent passed"; 
     
     //test user mail
     $servicename = "service_user_mail";    
     $params = array("email" => "bsaleh@laclasse.com", "servicename" => $servicename); 
     $request = new HttpRequest($url, $headers, $method, $params);
     $srr = new SimpleRestRequest($request); 
     $response = $srr->execute($secret_key);
     //print_r($response); 
     $arr = json_decode($response->body, true );
     //print_r ($arr);
      if ($arr[0]["login"]== "BSALEH") echo " 3 - verify email user passed"; 
     
     //test user atrrs pronote
     $servicename = "service_user_attrs_pronote";    
     $params = array("login" => "bsaleh", "servicename" => $servicename); 
     $request = new HttpRequest($url, $headers, $method, $params);
     $srr = new SimpleRestRequest($request); 
     $response = $srr->execute($secret_key);
     //print_r($response); 
     $arr = json_decode($response->body, true );
     if ($arr[0]["login"]== "BSALEH") echo " 4 - pronote  passed"; 
     //print_r ($arr);
     
     //test user attr grr
     $servicename = "service_user_attrs_grr";    
     $params = array("login" => "bsaleh", "servicename" => $servicename); 
     $request = new HttpRequest($url, $headers, $method, $params);
     $srr = new SimpleRestRequest($request); 
     $response = $srr->execute($secret_key);
     // print_r($response); 
     $arr = json_decode($response->body, true );
     //print_r($arr);
     if ($arr[0]["login"]== "bsaleh") echo " 5 - grr attributes passed";
     
     // test user eleve 
     $servicename = "service_user_eleve";    
     $params = array("nom" =>"Simonet", "prenom"=>"Mylene", "id_sconet" => "498605", "servicename" => $servicename); 
     $request = new HttpRequest($url, $headers, $method, $params);
     $srr = new SimpleRestRequest($request); 
     $response = $srr->execute($secret_key);
     // print_r($response); 
     $arr = json_decode($response->body, true );
     if (count($arr[0])>0) echo "6-eleve search passed"; 

     // test user parent_eleve
     $servicename = "service_user_parent_eleve";    
     $params = array("nom" => "Simonet", "prenom" =>"luc", "id_sconet" => "498605", "servicename" => $servicename); 
     $request = new HttpRequest($url, $headers, $method, $params);
     $srr = new SimpleRestRequest($request); 
     $response = $srr->execute($secret_key);
     // print_r($response); 
     $arr = json_decode($response->body, true );
     //print_r($arr); 
     if (count($arr[0])>0) echo " 7 - parent search passed"

?>
