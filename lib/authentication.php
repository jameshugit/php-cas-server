<?php

/** 
 * @file authentication.php
 * Interface that must be implemented by authenticators
 */

include_once(CAS_PATH.'/lib/functions.php');
require_once(CAS_PATH.'/lib/rest_request.php'); 
require_once(CAS_PATH.'/views/auth_success.php');
require_once(CAS_PATH.'/views/auth_failure.php');

interface casAuthentication {

    /**
     * Validates login and password
     * This function returns the username that has to be associated to the TGT
     * This is useful, for instance, if you want to change the username on the fly after authentication
     * e.g. changing the username to uppercase, or appending some string, etc...
     * @param login User login
     * @param password User password
     * @return string containing the user login (credentials ok) or an empty string (authentication failed)
     */
    public function verifyLoginPasswordCredential($login, $password);

    /**
     * Returns the serviceValidate CAS 2.0 XML fragment response
     * @param login Login for user (as returned by verifyLoginPasswordCredential)
     * @param service Service that requests ST validation
     * @return string containing loads of XML
     */
    public function getServiceValidate($login, $service, $pgtIou);

    /**
     * Returns the smalValidate CAS 1.0 XML fragment response
     * @param login Login for user (as returned by verifyLoginPasswordCredential)
     * @param service Service that requests ST validation
     * @return string containing loads of XML
     */
    public function getSamlAttributes($login, $service);

    /**
     * Returns the validate CAS 1.0 response
     * @param login Login for user (as returned by verifyLoginPasswordCredential)
     * @param service Service that requests ST validation
     * @return string containing loads of XML
     */
    public function getValidate($login, $service);

    public function Search_User_By_Email($mail);

    //InsEmail means institutional Email 
    public function Search_Agent_By_InsEmail($mail);

    public function Search_Parent_By_Name_EleveSconetId($nom, $prenom, $eleveid);

    public function Search_Eleve_By_Name_SconetId($nom, $prenom, $eleveid);

    public function has_default_password($login);

    public function update_password($login,$pwd); 

}

// ORACLE IMPLEMENTATION
//-----------------------------------------------------------------//

class ORACLE implements casAuthentication
{
    private $conn; 
    
     function __construct($BACKEND_DBUSER, $BACKEND_DBPASS, $BACKEND_DBNAME) {
        $this->conn = $this->dbConnect($BACKEND_DBUSER, $BACKEND_DBPASS, $BACKEND_DBNAME);
    }
    
    public function __destruct() {
        $this->dbDisconnect($this->conn);
    }
    private function dbConnect($BACKEND_DBUSER, $BACKEND_DBPASS, $BACKEND_DBNAME) {
        $conn = oci_connect($BACKEND_DBUSER, $BACKEND_DBPASS, $BACKEND_DBNAME);
        if (!$conn) {
            $e = oci_error();
            throw new Exception($e['message']);
        }
        return $conn;
    }
        
    private function ExecuteQuery($sql, $param) {
        $recordSet = array();
        $idx = 0;
        // Prepare SQL
        $stid = oci_parse($this->conn, $sql);

        if (is_array($param) && isset($param)) {
            foreach ($param as $key => $val) {
                // oci_bind_by_name($stid, $key, $val) does not work
                // because it binds each placeholder to the same location: $val
                // instead use the actual location of the data: $param[$key]
                oci_bind_by_name($stid, $key, $param[$key], 2000, SQLT_CHR);
            }
        }

        if (!$stid) {
            $e = oci_error($this->conn);
            throw new Exception($e['message']);
        }

        // Exécution de la logique de la requête
        $b = oci_execute($stid);
        if (!$b) {
            $e = oci_error($stid);
            throw new Exception($e['message']);
        }

        // fetch data
        while ($row = oci_fetch_assoc($stid)) {
            $recordSet[$idx] = $row;
            $idx++;
        }
        // Free oci's mem
        oci_free_statement($stid);

        // Returning dataset
        return $recordSet;
    }
        
    private function dbDisconnect($conn) {
        oci_close($conn);
    }
	
    public function verifyLoginPasswordCredential($login, $password) 
    {
        $sqlParam = array('LOGIN' => $login, 'PWD' => $password);
        $r = $this->ExecuteQuery(SQL_AUTH, $sqlParam);


        // If no record returned, credential is not valid.	
        if (!$r)
            return "";

        // See what we have to do.
        // just deal with the first reccord.
        $rowSet = $r[0];
        if (isset($rowSet)) {
            if (strtoupper($rowSet['LOGIN']) == strtoupper($login)) {
                // Yes ! this was successfull
                return strtoupper($rowSet['LOGIN']);
            }
        }
        // here is an unsuccessful attempt...
        return "";
    }

	/** 
	 * Returns the serviceValidate CAS 2.0 XML fragment response
	 * @param login Login for user (as returned by verifyLoginPasswordCredential)
	 * @param service Service that requests ST validation
	 * @return string containing loads of XML
	 */
	public function getServiceValidate($login, $service,$pgtIou)
    {
        global $CONFIG;
    	// index of the global array containing the list of autorized sites.
    	$idxOfAutorizedSiteArray = getServiceIndex($service);
    	$myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider']) ? 
    							$CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider'] : SQL_FOR_ATTRIBUTES;

    	$myTokenView = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele']) ? 
    				   $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele'] : 'Default';

    	// An array with the needed attributes for this service.
    	$neededAttr = explode(	",", 
    							str_replace(" ", "", 
    							strtoupper($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']))
                );

    	$attributes = array(); // What to pass to the function that generate token
    	
    	/// @note : no need for the moment... $CASversion = $CONFIG['CAS_VERSION'];
    	
    	// Adding data to the array for displaying.
    	// user attribute is requiered in any way.
    	// this is requiered in CAS 1.0 for phpCAS Client.
    	$attributes['user'] = $login;
    	
    	// executing second SQL Statment for other attributes.
    	$r = $this->ExecuteQuery($myAttributesProvider,array('LOGIN'=>$login)); 
            
    	// Should have only one row returned.
    	$rowSet = $r[0];
    	if (isset($rowSet)) {
    		// For all attributes returned
    		foreach($rowSet as $idx => $val) {
    			if (in_array(strtoupper($idx), $neededAttr)) {
    				$attributes[$idx] = $val;
    			}
    		}
    	}
    	
    	// call the token model with the default view or custom view
    	return viewAuthSuccess($myTokenView, $attributes,$pgtIou);
    }

	/** 
	 * Returns the smalValidate CAS 1.0 XML fragment response
	 * @param login Login for user (as returned by verifyLoginPasswordCredential)
	 * @param service Service that requests ST validation
	 * @return string containing loads of XML
	 */
	public function getSamlAttributes($login, $service)
    {
        global $CONFIG;
    	// index of the global array containing the list of autorized sites.
    	$idxOfAutorizedSiteArray = getServiceIndex($service);
    	$myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider']) ? 
    							$CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider'] : SQL_FOR_ATTRIBUTES;

    	$myTokenView = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele']) ? 
    				   $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele'] : 'Default';
    	
    	// An array with the needed attributes for this service.
    	$neededAttr = explode(	",", 
    							str_replace(" ", "", 
    							strtoupper($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']))
    						);
            
    
    	$attributes = array(); // What to pass to the function that generate token
    	
    	/// @note : no need for the moment... $CASversion = $CONFIG['CAS_VERSION'];
    	
    	// Adding data to the array for displaying.
    	// user attribute is requiered in any way.
    	// this is requiered in CAS 1.0 for phpCAS Client.
    	$attributes['user'] = $login;
    	
    	// executing second SQL Statment for other attributes.

    	$r = $this->ExecuteQuery($myAttributesProvider, array(':LOGIN' => $login));
    	
    	// Should have only one row returned.
    	$rowSet = $r[0];
            //echo count($rowSet);  
    	if (isset($rowSet)) {
    		// For all attributes returned
    		foreach($rowSet as $idx => $val) {
    			if (in_array(strtoupper($idx), $neededAttr)) {
    				$attributes[$idx] = $val;
    			}
    		}
    	}
            return $attributes; 
    }

	/** 
	 * Returns the validate CAS 1.0 response
	 * @param login Login for user (as returned by verifyLoginPasswordCredential)
	 * @param service Service that requests ST validation
	 * @return string containing loads of XML
	 */
	public function getValidate($login, $service)
    {
        return 0; 
    }

    public function Search_User_By_Email($mail)
    {
      
      global $CONFIG;
      $query = Search_user_by_mail;
      $r = $this->ExecuteQuery($query, array('mail'=> $mail));
      return $r;
    }
    //InsEmail means institutional Email·
    public function Search_Agent_By_InsEmail($mail)
    {
      global $CONFIG;
      $query = Search_Agent_by_mail;
      $r =$this->ExecuteQuery($query, array('mail'=> $mail));
      return $r;

    }
    public function Search_Parent_By_Name_EleveSconetId($nom, $prenom, $eleveid)
    {
      global $CONFIG;
      $query = Search_Parent_By_Name_EleveId;
      $r = $this->ExecuteQuery($query, array(':nom'=> $nom,  ':prenom'=> $prenom, ':elevid' => $eleveid));
      return $r;

    }
    public function Search_Eleve_By_Name_SconetId($nom,$prenom,$eleveid)
    {
      global $CONFIG;
      $query = Search_student_By_Name_SconetId;
      $r = $this->ExecuteQuery($query, array('nom'=> $nom,  'prenom'=> $prenom, 'eleveid' => $eleveid));
      return $r;


    }

    public function has_default_password($login){
      global $CONFIG;
      $query = Is_Default_password;
      $r = $this-> ExecuteQuery($query, array(':login'=> $login));
      return $r[0]["COMPTES.IS_DEFAULT_PASSWORD(:LOGIN)"];    
    }

    public function update_password($login,$pwd){
        global $CONFIG; 
        $query = Update_password; 
        $r = $this-> ExecuteQuery($query, array(':login' => $login, ':pwd' =>$pwd));
    } 


}


// MYSQL IMPLEMENTAION 
//-----------------------------------------------------------------//

class MYSQL implements casAuthentication
{
    private $conn; 
    
    function __construct($BACKEND_DBUSER, $BACKEND_DBPASS, $BACKEND_DBNAME) {
        $this->conn = $this->dbConnect($BACKEND_DBUSER, $BACKEND_DBPASS, $BACKEND_DBNAME);
    }
    
    public function __destruct() {
        $this->dbDisconnect($this->conn);
    }
    private function dbConnect($BACKEND_DBUSER, $BACKEND_DBPASS, $BACKEND_DBNAME) {
        try {
            $conn = new PDO($BACKEND_DBNAME, $BACKEND_DBUSER, $BACKEND_DBPASS);
        } catch (PDOException $e) {
            echo $e->getMessage();
            showError($e->getMessage());
        }

        return $conn;
    }

    private function ExecuteQuery($sql, $param) {
        
        $recordSet = array();
            $idx = 0;

            if (!is_string($sql))
                throw new Exception('the query is not a string');
            if (empty($sql))
                throw new Exception('empty querty');
            try{
            $stmt = $this->conn->prepare($sql);
            //binding parameters
            if (is_array($param) && isset($param))  
            {
                       $stmt->execute($param);        
            }
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $recordSet[$idx] = $row;
                        $idx++;
                }
            
            }catch(PDOException $e){
                showError($e->getMessage());
            }           
            
            // Returning dataset
                return $recordSet;
        
    }
        
    private function dbDisconnect($conn) {
         $conn=null; 
    }
	
    public function verifyLoginPasswordCredential($login, $password) {
        $sqlParam = array('LOGIN' => $login, 'PWD' => $password);
        $query = MySQL_AUTH;
        $r = $this->ExecuteQuery($query, $sqlParam);
        // If no record returned, credential is not valid.	
        if (!$r)
            return "";

        // See what we have to do.
        // just deal with the first reccord.
        $rowSet = $r[0];
        if (isset($rowSet)) {
            if (strtoupper($rowSet['login']) == strtoupper($login)) {
                // Yes ! this was successfull
                return strtoupper($rowSet['login']);
            }
        }
        // here is an unsuccessful attempt...
        return "";
    }

	/** 
	 * Returns the serviceValidate CAS 2.0 XML fragment response
	 * @param login Login for user (as returned by verifyLoginPasswordCredential)
	 * @param service Service that requests ST validation
	 * @return string containing loads of XML
	 */
	public function getServiceValidate($login, $service, $pgtIou)
    {
        global $CONFIG;
    	// index of the global array containing the list of autorized sites.
    	$idxOfAutorizedSiteArray = getServiceIndex($service);
    	$myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['MysqlattributesProvider']) ? 
    							$CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['MysqlattributesProvider'] : MySQL_FOR_ATTRIBUTES;

      $myTokenView = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele']) ? 
    				   $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele'] : 'Default';
    	
    	// An array with the needed attributes for this service.
    	$neededAttr = explode(	",", 
    							str_replace(" ", "", 
    							strtoupper($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']))
    						);
    	$attributes = array(); // What to pass to the function that generate token
    	    	
    	// Adding data to the array for displaying.
    	// user attribute is requiered in any way.
    	// this is requiered in CAS 1.0 for phpCAS Client.
    	$attributes['user'] = $login;
    	
    	// executing second SQL Statment for other attributes.
       
        $r= $this->ExecuteQuery($myAttributesProvider, array(':LOGIN'=>$login)); 
    	
    	// Should have only one row returned.
    	$rowSet = $r[0];
    	if (isset($rowSet)) {
    		// For all attributes returned
    		foreach($rowSet as $idx => $val) {
    			if (in_array(strtoupper($idx), $neededAttr)) {
    				$attributes[$idx] = $val;
    			}
    		}
    	}
	
    	// call the token model with the default view or custom view
    	return viewAuthSuccess($myTokenView, $attributes,$pgtIou);
    }

	/** 
	 * Returns the smalValidate CAS 1.0 XML fragment response
	 * @param login Login for user (as returned by verifyLoginPasswordCredential)
	 * @param service Service that requests ST validation
	 * @return string containing loads of XML
	 */
	public function getSamlAttributes($login, $service) {
        global $CONFIG;
        // index of the global array containing the list of autorized sites.
        $idxOfAutorizedSiteArray = getServiceIndex($service);
        $myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['MysqlattributesProvider']) ?
                $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['MysqlattributesProvider'] : Mysql_FOR_PRONOTE;

        $myTokenView = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele']) ?
                $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele'] : 'Default';
        // An array with the needed attributes for this service.
        $neededAttr = explode(",", str_replace(" ", "", strtoupper(isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']) ?
                                        $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes'] :
                                        'login,nom,prenom,dateNaissance,codePostal,categories'))
        );

        $attributes = array(); // What to pass to the function that generate token
        /// @note : no need for the moment... $CASversion = $CONFIG['CAS_VERSION'];
        // Adding data to the array for displaying.
        // user attribute is requiered in any way.
        // this is requiered in CAS 1.0 for phpCAS Client.
        $attributes['user'] = $login;
        // executing second SQL Statment for other attributes.
       
        $r = $this->ExecuteQuery($myAttributesProvider, array(':LOGIN' => $login));
        
        

        // Should have only one row returned.
        $rowSet = $r[0];
        //echo count($rowSet);  
        if (isset($rowSet)) {
            // For all attributes returned
            foreach ($rowSet as $idx => $val) {
                if (in_array(strtoupper($idx), $neededAttr)) {
                    $attributes[$idx] = $val;
                }
            }
        }

        //echo count($attributes); 
        return $attributes;
    }

	/** 
	 * Returns the validate CAS 1.0 response
	 * @param login Login for user (as returned by verifyLoginPasswordCredential)
	 * @param service Service that requests ST validation
	 * @return string containing loads of XML
	 */
	public function getValidate($login, $service)
    {
        return 0; 
    }

    public function Search_User_By_Email($mail)
    {
      global $CONFIG;
      $query = Mysql_Search_User_by_mail;
      $r =$this->ExecuteQuery($query, array(':mail' => $mail));
      return $r;
    }
    //InsEmail means institutional Email
    public function Search_Agent_By_InsEmail($mail)
    {

     global $CONFIG;
     $query = Mysql_Search_Agent_by_mail;
     $r =$this->ExecuteQuery($query, array(':mail' => $mail));
     return $r;

    }

    public function Search_Parent_By_Name_EleveSconetId($nom, $prenom, $eleveid)
    {
      global $CONFIG;
      $query = Mysql_Search_Parent_By_Name_EleveId;
      $r = $this->ExecuteQuery($query,array('nom'=> $nom,  'prenom'=> $prenom, 'elevid' => $eleveid));
      return $r;
    }

    public function Search_Eleve_By_Name_SconetId($nom,$prenom,$eleveid)
    {
      global $CONFIG;
      $query = Mysql_Search_student_By_Id;
      $r =$this->ExecuteQuery($query,array(':nom'=>$nom,'prenom'=> $prenom, ':eleveid' => $eleveid));
      return $r;
    }
    public function has_default_password($login){

    }

    public function update_password($login,$pwd){
        
    }

}

//--------------------------------------------------------------------------------------//
// WEBAPI IMPLEMENTAION 
//--------------------------------------------------------------------------------------//
class WEBAPI implements casAuthentication
{
    var $api_access_key;
    var $api_secret_key; 
    var $api_url; 

    function __construct($BACKEND_API_ACCESS, $BACKEND_API_SECRET , $BACKEND_API_URL) {
        global $CONFIG;
        $this->api_access_key = $BACKEND_API_ACCESS; 
        $this->api_secret_key = $BACKEND_API_SECRET;
        $this->api_url = $BACKEND_API_URL;
        $this->log = new KLogger($CONFIG['DEBUG_FILE'], $CONFIG['DEBUG_LEVEL']);
    }
    // @getApi  finds an api parameters with a specific name
    //@param $api_name  
    //@return array of parameters or null if not found
    private function getApi($api_name)
    {
      global $CONFIG;
      foreach ($CONFIG['APIS'] as $api)
      {
             if (array_search($api_name, $api))
             {
                 return $api;
             }
      }
      return null;

    }

    private function build_get_request($api,$params_values, $path_param = null)
    {
        $query_string = ""; 
        $params = array_real_combine($api["url_params"], $params_values);
        if(is_null($params)){
            if(is_null($api["path_param"]))
               return null;
            else 
            { 
                //$query_string = is_null($path_param)? $query_string : ($query_string."/".urlencode ($path_param));
                $query_string = is_null($path_param)? $query_string : ($query_string."".urlencode ($path_param)); 
            }
        }
        else 
        {
            $query_string = is_null($path_param)? $query_string : ($query_string."".urlencode ($path_param)); 
        }

        $query_string = is_null($api["url_params"]) ?  $query_string : $query_string."?".http_build_query($params, '','&');
        $url =$this->api_url.$api['url'].$query_string;
        $headers = $api['headers']; 
        $method = $api['method']; 
        $parameters = array(); 
        return new HttpRequest($url, $headers, $method, $parameters); 
    }

    // for the moment this supports only body parameters
    // builds a put or  post requests
    private function build_post_request($api,$body_params)
    {
        $params = array_real_combine($api["body_params"], $body_params);
        if(is_null($params)){
            return null;  
        }
        $url = $this->api_url.$api['url']; 
        $headers = $api['headers'];
        $method = $api['method']; 
        $parameters = $params; 
        return new HttpRequest($url, $headers, $method, $parameters);
    }

    private function executeRequest($api,$params_values,$access_id, $secret_key,$path_params = null)
    {
        if ($api["method"] == "get" || "delete") 
        {
            $request = $this->build_get_request($api, $params_values,$path_params);

        } 
        elseif ($api["method"] == "post" || "put") 
        {
           $request = $this->build_post_request($api, $params_values);
        }
         $restrequest = new MysqlRestRequest($request); 
         $reponse = $restrequest->execute($secret_key);

         return $reponse; 
    }


    public function verifyLoginPasswordCredential($login, $password)
    {
        global $CONFIG; 
        $api = $this->getApi("verify_user_password"); 
        if (is_null($api))
            return "" ; 
        else
        {
            try{
                $response = $this->executeRequest($api, array($login, $password), $this->api_access_key, $this->api_secret_key); 
            }
            catch(Exception $e){
                return ""; 
            }

        }
        if ($response->code == 200) {
             $json_array = json_decode($response->body, true ); 
             return strtoupper($json_array['login']);
        }
        return "";

    }

    public function getServiceValidate($login, $service, $pgtIou)
    {
        global $CONFIG;
        // index of the global array containing the list of autorized sites.
        $idxOfAutorizedSiteArray = getServiceIndex($service);
        $myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider']) ? 
                                $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider'] : SQL_FOR_ATTRIBUTES;

        $myTokenView = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele']) ? 
                       $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele'] : 'Default';

        // An array with the needed attributes for this service.
        $neededAttr = explode(",", str_replace(" ", "", strtoupper(isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']) ?
                                        $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes'] :
                                        'login,nom,prenom,date_naissance,code_postal,categories')));

        $attributes = array(); // What to pass to the function that generate token
        $attributes['user'] = $login;
        switch ($myAttributesProvider) 
        {
            case SQL_FOR_ATTRIBUTES:
                $api = $this->getApi("sso_attributes");
                break;           
            case SQL_FOR_ATTRIBUTES_MEN:
                $api = $this->getApi("sso_attributes_men");
                break;
            case  SQL_FOR_PRONOTE:
                $api = $this->getApi("sso_attributes_pronote"); #sql_for_pronote
                break; 
        }

        if (!is_null($api))
        {
         try{
                $response = $this->executeRequest($api, null, $this->api_access_key, $this->api_secret_key, $login);
            }
            catch(Exception $e){
                throw new Exception($e->getMessage()); 
            }

        }

        if ($response->code == 200) {
           $rowSet = json_decode($response->body, true );
        }

        if (isset($rowSet)) {
            // For all attributes returned
            foreach($rowSet as $idx => $val) {
                if (in_array(strtoupper($idx), $neededAttr)) {
                    $attributes[$idx] = $val;
                }
            }
        }
        
        // call the token model with the default view or custom view
        return viewAuthSuccess($myTokenView, $attributes, $pgtIou); 
    }

    public function getSamlAttributes($login, $service)
    {
        global $CONFIG;
        // index of the global array containing the list of autorized sites.
        $idxOfAutorizedSiteArray = getServiceIndex($service);
        $myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider']) ? 
                                $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider'] : SQL_FOR_PRONOTE;

        $myTokenView = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele']) ? 
                       $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele'] : 'Default';

        // An array with the needed attributes for this service.
        $neededAttr = explode(  ",", str_replace(" ", "", strtoupper($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']))
                            );
        $attributes = array(); // What to pass to the function that generate token
        $attributes['user'] = $login;
        switch ($myAttributesProvider) 
        {
            case SQL_FOR_ATTRIBUTES:
                $api = $this->getApi("sso_attributes");                  
                break;           
            case SQL_FOR_ATTRIBUTES_MEN:
                $api = $this->getApi("sso_attributes_men"); 
                break;
            case  SQL_FOR_PRONOTE:
                $api =$this->getApi("sso_attributes_pronote"); #sql_for_pronote
                break; 
        }

        if (!is_null($api))
        {
         try{
                $response =$this->executeRequest($api, null, $this->api_access_key, $this->api_secret_key, $login);
            }
            catch(Exception $e){
                throw new Exception($e->getMessage()); 
            }

        }

        if ($response->code == 200) {
            $rowSet = json_decode($response->body, true ); 
        }

        if (isset($rowSet)) {
            // For all attributes returned
            foreach($rowSet as $idx => $val) {
                if (in_array(strtoupper($idx), $neededAttr)) {
                    $attributes[$idx] = $val;
                }
            }
        }
    
        // call the token model with the default view or custom view
        return $attributes; 

    }
    
    public function getValidate($login, $service)
    {
        return 0; 
    }

    public function Search_User_By_Email($mail)
    {
        global $CONFIG;
        $api = $this->getApi("Search_User_By_Email");

        if (!is_null($api))
        {
         try{
                $response = $this->executeRequest($api, array("login,nom,prenom", $mail), $this->api_access_key, $this->api_secret_key);
            }
            catch(Exception $e){
                throw new Exception($e->getMessage()); 
            }

        }
        if ($response->code == 200) {
            $r = json_decode($response->body, true ); 
        }

    
        return $r["Data"];  


    }

    public function Search_Agent_By_InsEmail($mail){
        global $CONFIG;
        $api = $this->getApi("Search_Agent_By_Instmail"); // search agent by academic emaiil

        if (!is_null($api))
        {
         try{
                $response =$this->executeRequest($api, array($mail), $this->api_access_key, $this->api_secret_key);
            }
            catch(Exception $e){
                $this->log->LogError($e-getMessage());
                throw new Exception($e->getMessage()); 
            }

        }
        if ($response->code == 200) {
            $this->log->LogDebug("Response from annuaire:".print_r($response->body, true));
            $r = json_decode($response->body, true ); 
        }
        else{
            $this->log->LogError("Response from annuaire:".print_r($response, true));
        }

        return $r['Data'];
    }

    public function Search_Parent_By_Name_EleveSconetId($nom, $prenom, $eleveid)
    {
        global $CONFIG;
        $api = $this->getApi("Search_Parent_By_Name_EleveSconetId");
        if (!is_null($api))
        {
         try{
                $response =$this->executeRequest($api, array($nom, $prenom, $eleveid), $this->api_access_key, $this->api_secret_key);
            }
            catch(Exception $e){
                $this->log->LogError($e-getMessage());
                throw new Exception($e->getMessage()); 
            }
        }
        if ($response->code == 200) {
            $this->log->LogDebug("Response from annuaire:".print_r($response->body, true));
            $r = json_decode($response->body, true );
        }
        else{
            $this->log->LogError("Response from annuaire:".print_r($response, true));
        }

        return $r['Data'];
    }


    public function Search_Eleve_By_Name_SconetId($nom, $prenom, $eleveid){
        global $CONFIG; 
        $api = $this->getApi("Search_Eleve_By_Name_SconetId");
        if (!is_null($api))
        {
         try{
                $response = $this->executeRequest($api, array($nom, $prenom, $eleveid), $this->api_access_key, $this->api_secret_key);
            }
            catch(Exception $e){
                $this->log->LogError($e-getMessage());
                throw new Exception($e->getMessage()); 
            }

        }
        if ($response->code == 200) {
            $this->log->LogDebug("Response from annuaire:".print_r($response->body, true));
            $r = json_decode($response->body, true );
        }else{
            $this->log->LogError("Response from annuaire:".print_r($response, true));
        }
        return $r['Data'];     
    }

    public function has_default_password($login){
        return 0; 
    }

    public function update_password($login,$pwd){
        return 0; 
    }
}

//--------------------------------------------------------------------------------------//
// Class ORACLEAPI 
//--------------------------------------------------------------------------------------//
class ORACLEAPI implements casAuthentication
{
    var $api_access_key;
    var $api_secret_key; 
    var $api_url; 

    function __construct($BACKEND_API_ACCESS, $BACKEND_API_SECRET , $BACKEND_API_URL) {
        $this->api_access_key = $BACKEND_API_ACCESS; 
        $this->api_secret_key = $BACKEND_API_SECRET;
        $this->api_url = $BACKEND_API_URL;
    }
    // @getApi  finds an api parameters with a specific name
    //@param $api_name  
    //@return array of parameters or null if not found
    private function getApi($api_name)
    {
      global $CONFIG;
      foreach ($CONFIG['APIS'] as $api)
      {
             if (array_search($api_name, $api))
             {
                 return $api;
             }
      }
      return null;

    }

    private function build_request($api,$params_values)
    {
        $params = array_real_combine($api["url_params"], $params_values);
        
        $url =$this->api_url;
        $headers = $api['headers']; 
        $method = "get"; 
        return new HttpRequest($url, $headers, $method, $params); 
    }

    // for the moment this supports only body parameters
    // builds a put or  post requests
    private function build_post_request($api,$body_params)
    {
        $params = array_real_combine($api["body_params"], $body_params);
        if(is_null($params)){
            return null;  
        }
        $url = $this->api_url.$api['url']; 
        $headers = $api['headers'];
        $method = $api['method']; 
        $parameters = $params; 
        return new HttpRequest($url, $headers, $method, $parameters);
    }

    private function executeRequest($api,$params_values, $secret_key)
    {
        if ($api["method"] == "get") 
        {
            $request = $this->build_request($api, $params_values);
        } 
        elseif ($api["method"] == "post" || "put") 
        {
           $request = $this->build_post_request($api, $params_values);
        }
         $rest_request = new SimpleRestRequest($request);
         $response = $rest_request->execute($secret_key); 
         return $response; 
    }


    public function verifyLoginPasswordCredential($login, $password)
    {
        global $CONFIG; 
        $api = $this->getApi("oracle_login_service"); 
        if (is_null($api))
            return "" ; 
        else
        {
            try{
                $pass = md5(utf8_decode($password)); 
                $response = $this->executeRequest($api, array($login, $pass,"service_user_login"),$this->api_secret_key); 
            }
            catch(Exception $e){
                //echo $e->getMessage(); 
                return ""; 
            }

        }
        if ($response->code = 200) {
             $json_array = json_decode(utf8_encode($response->body), true ); 
             return strtoupper($json_array['login']);
        }
        return "";

    }

    public function getServiceValidate($login, $service, $pgtIou)
    {
        global $CONFIG;
        // index of the global array containing the list of autorized sites.
        $idxOfAutorizedSiteArray = getServiceIndex($service);
        $myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider']) ? 
                                $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider'] : SQL_FOR_ATTRIBUTES;

        $myTokenView = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele']) ? 
                       $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele'] : 'Default';

        // An array with the needed attributes for this service.
        $neededAttr = explode(",", str_replace(" ", "", strtoupper(isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']) ?
                                        $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes'] :
                                        'login,nom,prenom,date_naissance,code_postal,categories')));

        $attributes = array(); // What to pass to the function that generate token
        $attributes['user'] = $login;
        $api = $this->getApi("oracle_service_user_attributes");

       
        if (!is_null($api))
        {
         try{
              switch ($myAttributesProvider) 
                {
                    case SQL_FOR_ATTRIBUTES:
                        //echo "sql for attributes \n"; 
                        $response = $this->executeRequest($api, array($login,"service_user_attributes"), $this->api_secret_key);
                        break;           
                    case SQL_FOR_ATTRIBUTES_MEN:
                        //il n'est pas encore developp
                        //echo "sql for attributes men \n"; 
                        $response = $this->executeRequest($api, array($login,"service_user_attributes_MEN"), $this->api_secret_key);
                        break;
                    case  SQL_FOR_PRONOTE:
                        //echo "sql for pronote \n"; 
                        $response = $this->executeRequest($api, array($login,"service_user_attrs_pronote"), $this->api_secret_key);
                        break;
                    case  SQL_FOR_GRR:
                        //echo "sql for Grr \n"; 
                        $response = $this->executeRequest($api, array($login,"service_user_attrs_grr"), $this->api_secret_key);
                        break; 
                }
               
            }
            catch(Exception $e){
                throw new Exception($e->getMessage()); 
            }

        }

        if ($response->code == 200) {
            $rowSet = json_decode(utf8_encode($response->body), true ); 
        }

        if (isset($rowSet[0])) {
            // For all attributes returned
            foreach($rowSet[0] as $idx => $val) {
                if (in_array(strtoupper($idx), $neededAttr)) {
                    $attributes[$idx] = $val;
                }
            }
        }
        // call the token model with the default view or custom view
        return viewAuthSuccess($myTokenView, $attributes, $pgtIou); 
    }

    public function getSamlAttributes($login, $service)
    {
        
        global $CONFIG;
        // index of the global array containing the list of autorized sites.
        $idxOfAutorizedSiteArray = getServiceIndex($service);
        $myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider']) ? 
                                $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider'] : SQL_FOR_ATTRIBUTES;

        $myTokenView = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele']) ? 
                       $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele'] : 'Default';

        // An array with the needed attributes for this service.
        $neededAttr = explode(",", str_replace(" ", "", strtoupper(isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']) ?
                                        $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes'] :
                                        'login,nom,prenom,date_naissance,code_postal,categories')));

        $attributes = array(); // What to pass to the function that generate token
        $attributes['user'] = $login;
        $api = $this->getApi("oracle_service_user_attributes");
       
        if (!is_null($api))
        {
         try{
              switch ($myAttributesProvider) 
                {
                    case SQL_FOR_ATTRIBUTES:
                        //echo "sql for attributes \n"; 
                        $response = $this->executeRequest($api, array($login,"service_user_attributes"), $this->api_secret_key);
                        break;           
                    case SQL_FOR_ATTRIBUTES_MEN:
                        //il n'est pas encore developp
                        //echo "sql for attributes men \n"; 
                        $response = $this->executeRequest($api, array($login,"service_user_attributes"), $this->api_secret_key);
                        break;
                    case  SQL_FOR_PRONOTE:
                        //echo "sql for pronote \n"; 
                        $response = $this->executeRequest($api, array($login,"service_user_attrs_pronote"), $this->api_secret_key);
                        break;
                    case  SQL_FOR_GRR:
                        //echo "sql for Grr \n"; 
                        $response = $this->executeRequest($api, array($login,"service_user_attrs_grr"), $this->api_secret_key);
                        break; 
                }
               
            }
            catch(Exception $e){
                throw new Exception($e->getMessage()); 
            }

        }

        if ($response->code == 200) {
            $rowSet = json_decode(utf8_encode($response->body), true );
            
        }

        if (isset($rowSet[0])) {
            // For all attributes returned
            foreach($rowSet[0] as $idx => $val) {
                if (in_array(strtoupper($idx), $neededAttr)) {
                    $attributes[$idx] = $val;
                }
            }
        }
    
        // call the token model with the default view or custom view
        return $attributes; 

    }
    
    public function getValidate($login, $service)
    {
        return 0; 
    }

    public function Search_User_By_Email($mail)
    {
        global $CONFIG;
        $api = $this->getApi("oracle_service_user_agent_mail");

        if (!is_null($api))
        {
         try{
                $response = $this->executeRequest($api, array($mail, "service_user_mail"), $this->api_secret_key);
            }
            catch(Exception $e){
                throw new Exception($e->getMessage()); 
            }

        }
        if ($response->code == 200) {
            $r = json_decode(utf8_encode($response->body), true ); 
        }

    
        return $r;  


    }

    public function Search_Agent_By_InsEmail($mail){
        global $CONFIG;
        $api = $this->getApi("oracle_service_user_agent_mail"); // search agent by academic emaiil

        if (!is_null($api))
        {
         try{
                $response =$this->executeRequest($api, array($mail, "service_user_agent_mail"), $this->api_secret_key);
            }
            catch(Exception $e){
                throw new Exception($e->getMessage()); 
            }

        }
         if ($response->code == 200) {
            $r = json_decode(utf8_encode($response->body), true ); 
        }

        return $r;   

    }

    public function Search_Parent_By_Name_EleveSconetId($nom, $prenom, $eleveid)
    {
        global $CONFIG; 
        $api = $this->getApi("oracle_service_user_parent_eleve");
        
        if (!is_null($api))
        {
         try{
                $response = $this->executeRequest($api, array($nom, $prenom, $eleveid, "service_user_parent_eleve"), $this->api_secret_key);
            }
            catch(Exception $e){
                throw new Exception($e->getMessage()); 
            }

        }
         if ($response->code == 200) {
            $r = json_decode(utf8_encode($response->body), true ); 
        }

    
        return $r; 
    }


    public function Search_Eleve_By_Name_SconetId($nom, $prenom, $eleveid){
        global $CONFIG; 
        $api = $this->getApi("oracle_service_user_eleve");
        if (!is_null($api))
        {
         try{
                $response = $this->executeRequest($api, array($nom, $prenom, $eleveid, "service_user_eleve"), $this->api_secret_key);
            }
            catch(Exception $e){
                throw new Exception($e->getMessage()); 
            }

        }
         if ($response->code == 200) {
            $r = json_decode(utf8_encode($response->body), true ); 
        }

    
        return $r;     
    }

    public function has_default_password($login){
        global $CONFIG; 
        $api = $this->getApi("oracle_service_is_default_password");
        if (!is_null($api))
        {
         try{
                $response = $this->executeRequest($api, array($login, "service_is_default_password"), $this->api_secret_key);
            }
            catch(Exception $e){
                throw new Exception($e->getMessage()); 
            }

        }
         if ($response->code == 200) {
            $r = json_decode(utf8_encode($response->body), true ); 
        }

        return $r["is_default_password"]== "1" ? 1 : 0;
    }

    public function update_password($login,$pwd){
        global $CONFIG; 
        $api = $this->getApi("oracle_service_update_password");
        if (!is_null($api))
        {
         try{
               $response = $this->executeRequest($api, array($login, "service_update_password"), $this->api_secret_key);
            }
            catch(Exception $e){
                throw new Exception($e->getMessage()); 
            }

        }
         if ($response->code == 200) {
            $r = json_decode(utf8_encode($response->body), true ); 
        }

        return $r;
    }
}

//--------------------------------------------------------------------------------------//
//DBFactory Class to instansiate the suitable DATabase handler class (Oracle or mysql)
//--------------------------------------------------------------------------------------//

class DBFactory{
   // create database class instance
   public function createDB($db,$user='',$password='',$database='db.sqlite'){
     if($db!='MYSQL'&&$db!='ORACLE'&&$db!='WEBAPI'&&$db!='ORACLEAPI'){
       throw new Exception('Invalid type of database class');
     }
     return new $db($user,$password,$database);
   }
}