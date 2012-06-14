<?

/** 
 * @file authentication.php
 * Interface that must be implemented by authenticators
 */

//include_once('/var/www/sso/config.inc.php'); 
//include_once('../config.inc.php'); 

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
         //function dbConnect($BACKEND_DBUSER, $BACKEND_DBPASS, $BACKEND_DBNAME);
        
         //function ExecuteQuery($conn, $sql, $param); 
        
         //function dbDisconnect($conn); 

   public function verifyLoginPasswordCredential($login, $password);

	/** 
	 * Returns the serviceValidate CAS 2.0 XML fragment response
	 * @param login Login for user (as returned by verifyLoginPasswordCredential)
	 * @param service Service that requests ST validation
	 * @return string containing loads of XML
	 */
	public function getServiceValidate($login, $service);

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

  public function Search_Eleve_By_Name_SconetId($nom,$prenom,$eleveid);


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
            //trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
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
            //trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            throw new Exception($e['message']);
        }

        // Exécution de la logique de la requête
        $b = oci_execute($stid);
        if (!$b) {
            $e = oci_error($stid);
            //trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
            throw new Exception($e['message']);
        }

        // fetch data
        while ($row = oci_fetch_assoc($stid)) {
            $recordSet[$idx] = $row;
            $idx++;
        }

        // Frre oci's mem
        oci_free_statement($stid);

        // Returning dataset
        return $recordSet;
    }
        
    private function dbDisconnect($conn) {
        oci_close($conn);
    }
	
        public function verifyLoginPasswordCredential($login, $password) {
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
	public function getServiceValidate($login, $service)
        {
            global $CONFIG;
	// index of the global array containing the list of autorized sites.
	$idxOfAutorizedSiteArray = getServiceIndex($service);
	$myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider']) ? 
							$CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['attributesProvider'] : SQL_FOR_ATTRIBUTES;

	$myTokenView = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele']) ? 
				   $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele'] : 'Default';
	// If service index is null, service is not allow to connect to our sso.
	//if ($idxOfAutorizedSiteArray == "")
	//	return viewAuthFailure(array('code'=> '', 
	//								 'message'=> _('This application is not allowed to authenticate on this server')));
	
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
	return viewAuthSuccess($myTokenView, $attributes);
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
	// If service index is null, service is not allow to connect to our sso.
	//if ($idxOfAutorizedSiteArray == "")
	//	return viewAuthFailure(array('code'=> '', 
	//								 'message'=> _('This application is not allowed to authenticate on this server')));
	
	// An array with the needed attributes for this service.
	$neededAttr = explode(	",", 
							str_replace(" ", "", 
							strtoupper($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']))
						);
        
//                                                foreach ($neededAttr as $value) {
//                                                    echo "$value \n"; 
//                                                    
//                                                }
	$attributes = array(); // What to pass to the function that generate token
	
	/// @note : no need for the moment... $CASversion = $CONFIG['CAS_VERSION'];
	
	// Adding data to the array for displaying.
	// user attribute is requiered in any way.
	// this is requiered in CAS 1.0 for phpCAS Client.
	$attributes['user'] = $login;
        //echo $myAttributesProvider; 
	
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
      $r = $this->ExecuteQuery($query, array('nom'=> $nom,  'prenom'=> $prenom, 'elevid' => $eleveid));
      return $r;

    }
    public function Search_Eleve_By_Name_SconetId($nom,$prenom,$eleveid)
    {
      global $CONFIG;
      $query = Search_student_By_Name_SconetId;
      $r = $this->ExecuteQuery($query, array('nom'=> $nom,  'prenom'=> $prenom, 'eleveid' => $eleveid));
      return $r;


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
                 
                //print_r($row); 
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
        //print_r($r); 
        //mysqldisconnect($db);
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
	public function getServiceValidate($login, $service)
        {
            global $CONFIG;
	// index of the global array containing the list of autorized sites.
	$idxOfAutorizedSiteArray = getServiceIndex($service);
	$myAttributesProvider = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['MysqlattributesProvider']) ? 
							$CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['MysqlattributesProvider'] : MySQL_FOR_ATTRIBUTES;

	$myTokenView = isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele']) ? 
				   $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['tokenModele'] : 'Default';
	// If service index is null, service is not allow to connect to our sso.
	//if ($idxOfAutorizedSiteArray == "")
	//	return viewAuthFailure(array('code'=> '', 
	//								 'message'=> _('This application is not allowed to authenticate on this server')));
	
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
   
        $r= $this->ExecuteQuery($myAttributesProvider, array(':LOGIN'=>$login)); 
	//$r = _dbExecuteSQL($db, $myAttributesProvider, array('LOGIN'=>$login));
        //print_r($r);
	
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
	return viewAuthSuccess($myTokenView, $attributes);
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
        // If service index is null, service is not allow to connect to our sso.
        //if ($idxOfAutorizedSiteArray == "")
        //	return viewAuthFailure(array('code'=> '', 
        //								 'message'=> _('This application is not allowed to authenticate on this server')));
        // An array with the needed attributes for this service.
        $neededAttr = explode(",", str_replace(" ", "", strtoupper(isset($CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes']) ?
                                        $CONFIG['AUTHORIZED_SITES'][$idxOfAutorizedSiteArray]['allowedAttributes'] :
                                        'login,nom,prenom,dateNaissance,codePostal,categories'))
        );

//                                                foreach ($neededAttr as $value) {
//                                                    echo "$value \n"; 
//                                                    
//                                                }
        $attributes = array(); // What to pass to the function that generate token
        /// @note : no need for the moment... $CASversion = $CONFIG['CAS_VERSION'];
        // Adding data to the array for displaying.
        // user attribute is requiered in any way.
        // this is requiered in CAS 1.0 for phpCAS Client.
        $attributes['user'] = $login;
        //echo $myAttributesProvider; 
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

}

//DBFactory Class to instansiate the suitable DATabase handler class (Oracle or mysql)

//--------------------------------------------------------------------------------------//

class DBFactory{
   // create database class instance
   public function createDB
($db,$user='',$password='',$database='db.sqlite'){
     if($db!='MYSQL'&&$db!='ORACLE'){
       throw new Exception('Invalid type of database class');
     }
     return new $db($user,$password,$database);
   }
}

/*

$auth = new ORACLE(BACKEND_DBUSER, BACKEND_DBPASS, BACKEND_DBNAME); 
print_r($auth); 
echo $auth->verifyLoginPasswordCredential('bsaleh', '6333033azerty'); 
//echo $auth;
//print_r( $auth->getSamlAttributes('abelilita','*://sesamath2.sesamath.net/*'));

$auth2= new MYSQL(MYSQL_DBUSER, MYSQL_DBPASS,MYSQL_DBNAME); 
print_r($auth2); 
echo $auth2->verifyLoginPasswordCredential('abelilita', 'abelilita');

   $factoryInstance = new DBFactory();
   $db=$factoryInstance->createDB('MYSQL',MYSQL_DBUSER, MYSQL_DBPASS,MYSQL_DBNAME);
   echo $db->verifyLoginPasswordCredential('abelilita', 'abelilita');
  //print_r( $db->getSamlAttributes('abelilita','*://sesamath2.sesamath.net/*')); 
   
*/




?>
