<? 

//phpinfo(); 
define('MYSQL_DBNAME', 'mysql:host=192.168.0.206;dbname=annuaire');
/** Database username */
define('MYSQL_DBUSER', 'root');
/** Database password */
define('MYSQL_DBPASS', 'root');
try {
          $conn = new PDO(MYSQL_DBNAME, MYSQL_DBUSER, MYSQL_DBPASS);
              } catch (PDOException $e) {
                        echo $e->getMessage();
                                showError($e->getMessage());
              }
echo 'success'; 

?>
