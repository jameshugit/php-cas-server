<?php
/*
@file rest_request 
defines functions and classes to send authenticated Rest request to restful api
*/ 

//httpful.phar is a library that facilites sending rest requests 
// @ref = https://github.com/nategood/httpful
include_once('httpful.phar');

/* 
As its name implies HttpRequest Class defines a an http request that needs to be signed
The Signature is generated using HMAC  algorithm  @ref = http://en.wikipedia.org/wiki/HMAC
signature is inspired from https://github.com/mgomes/api_auth gem for rails
*/ 
class HttpRequest
{
    var $url; 
    var $headers; 
    var $signed_headers; 
    var $method;
    var $params; // for post and put only
    
    function __construct($url, $headers, $method, $params) {
        $this->url = $url;
        $this->headers = $headers;
        $this->signed_headers = array();
        $this->method = $method;
        $this->params = $params;
    }
         
    public function getUrl() {
        return $this->url;
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function setHeaders($headers) {
        $this->headers = $headers;
    }


    public function getMethod() {
        return $this->method;
    }

    public function setMethod($method) {
        $this->method = $method;
    }

    public function getParams() {
        return $this->params;
    }

    public function setParams($params) {
        $this->params = $params;
    }

  
    // md5 is the digest of  parameters(body) sent in post or put requests
    private function calculate_md5()
    {
        // get param string 
        $params_string = http_build_query($this->params,'', ',');
        //var_dump($params_string);

        //To get the md5 binary format in php add TRUE to the md5() function.
        $Content_md5 = base64_encode(md5($params_string,true));
        //var_dump($Content_md5);
        return $Content_md5;
    }
    
    // populate headers in case if not specified on instantiation
    private function populate_headers()
    {
        $head = array(); 
        $content_type = isset($this->headers['Content-Type']) ? $this->headers['Content-Type'] : "plain/text"; 
        //var_dump($content_type);
        $head['Content-Type'] = $content_type;
        
        if (!isset($this->headers['Content-MD5'])&& ($this->method =='post'|| 'put')){
            $content_md5 = $this->calculate_md5();

        }elseif (!isset($this->headers['Content-MD5'])){
               $content_md5 = "";                  
        } 
        else
        {
            $content_md5 = $this->headers['Content-MD5']; 
        }
        //var_dump($content_md5);
        $head['Content-MD5'] = $content_md5; 
        
        $timestamp = isset($this->headers['Date']) ? $this->headers['Date'] :  gmdate("D, d M Y H:i:s T");
        //var_dump($timestamp); 
        
        $head['Date']=$timestamp; 
        
        $this->headers = $head; 
    }

    // Canonical string to be signed 
    /*
    canonical_string = 
      [ @request.content_type,
        @request.content_md5,
        @request.request_uri,
        @request.timestamp
      ].join(",")
    */
    private function build_canonical_string()
    {
        $this->populate_headers(); 
        $content_type  =   $this->headers['Content-Type'];
        //var_dump($content_type); 
        
        $content_md5 = $this->headers['Content-MD5']; 
       
        
        $timestamp = $this->headers['Date'];
        //var_dump($timestamp); 

        $canonical_string = $content_type.",".$content_md5.",".$this->url.",".$timestamp; 
        return $canonical_string; 

    }
    
    //hmac signature 
    private function hmac_signature($secret_key)
    {  
        $canonical_string = $this->build_canonical_string();
        //var_dump($canonical_string); 
        $signature = base64_encode(hash_hmac('sha1',$canonical_string, $secret_key,true));
        //var_dump($signature); 
        return $signature;
    }

   
    private function  auth_header($access_id , $secret_key)
    {
        return "APIAuth ".$access_id.":".$this->hmac_signature($secret_key); 
    }
    
    // returns signed headers
    public function getSigned_headers($access_id,$secret_key) {
        $this->signed_headers['Authorization'] = $this->auth_header($access_id, $secret_key);
        $this->signed_headers['Content-Type'] = $this->headers["Content-Type"];
        $this->signed_headers['Date'] = $this->headers["Date"];
        $this->signed_headers['Content-MD5']= $this -> headers["Content-MD5"];
        return $this->signed_headers;
    }
    
    
       
    
}

// RestRequest encapsulates the httpful functions for (post, get, put and delete)
class RestRequest{
    //$request is an instance of HttpRequest
    var $request; 
    function __construct($request) {
        $this->request = $request;
    }
    
    function execute($access_id, $secret_key)
    { 
        
        switch ($this->request->method){
            case "post":
                $r = \Httpful\Request::post($this->request->url, $this->request->params,"application/x-www-form-urlencoded")->autoParse(false)-> addHeaders($this->request->getSigned_headers($access_id,$secret_key))->expectsJson()->sendIt();
                break;
            case "get":
                $r = \Httpful\Request::get($this->request->url)->autoParse(false)-> addHeaders($this->request->getSigned_headers($access_id,$secret_key))->expectsJson()->sendIt();
                break;
             case "delete":
                $r = \Httpful\Request::delete($this->request->url)->autoParse(false)-> addHeaders($this->request->getSigned_headers($access_id,$secret_key))->expectsJson()->sendIt();
                break;
            case "put":
                $r = \Httpful\Request::put($this->request->url, $this->request->params,"application/x-www-form-urlencoded")->autoParse(false)-> addHeaders($this->request->getSigned_headers($access_id,$secret_key))->expectsJson()->sendIt();
                break; 
        }
        return $r; 
    }
}


    // Tests
    
    /*
    $params = array("id" => 5);
    $headers = array('Content-Type' => "application/x-www-form-urlencoded"); 
    $url = "http://localhost:9292/api/authsession";
    $method = "post"; 
    $access_id; 
    $secret_key; 
    
    $request = new HttpRequest($url,$headers, $method, $params); 

    //var_dump($request->build_canonical_string()); 
    //var_dump($request->auth_header("1044", "secret"));
    //var_dump($request->getSigned_headers("1044", "secret"));
    //var_dump($request->url); 
    //$r = \Httpful\Request::post($request->url, $request->params)->expectsJson()->sendIt();

    //print_r($r); 
    //$session_key = $r->body->key;
    //
    //
    $spr = new SignedPR($request); 
    $response =  $spr->execute("1044", "secret");
    if ($response->code == 201){
        $json_object = json_decode($response->body); 
        echo"key: " . $key = $json_object->key; 
    }
    else
    {
        var_dump( json_decode($response->body)); 
    }
    */
    
?>
