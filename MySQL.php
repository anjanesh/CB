<?php
namespace CB;

class MySQL_Ex extends \Exception
{
    const E_UNKNOWN           = 0;
    const E_CONNECTION_FAILED = 1;
    const E_DB_NOT_SELECTED   = 2;
    const E_EMPTY_VALUE       = 3;
    const E_BAD_SQL           = 4;
    const E_NO_CONNECTION     = 5;
    const E_FILE_OPEN         = 9;

    public function __construct($code = self::E_UNKNOWN, $message = NULL)
    {
        switch ($code)
        {
            case self::E_CONNECTION_FAILED:
             $msg = "Couldnt connect to host";
            break;

            case self::E_DB_NOT_SELECTED:
             $msg = "Couldnt select database";
            break;

            case self::E_EMPTY_VALUE:
             $msg = "Empty argument values";
            break;

            case self::E_BAD_SQL:
             $msg = "Bad SQL";
            break;

            case self::E_NO_CONNECTION:
             $msg = "Database Connection not initialized or closed";
            break;

            case self::E_FILE_OPEN:
             $msg = "Couldnt open file for writing";
            break;

            case self::E_UNKNOWN: default:
             $msg = "An unknown message has occurred";
            break;
        }

        if (isset($message)) // Add extra message to default message line
         $msg .= ".\n$message.";
        parent::__construct($msg, $code);
    }    
}

class MySQL
{
    const HISTORY_LOG_FILE = 'mysql.history.log'; # History log file

    /* Static Members
    ----------------------------------------------------------------------------------------------------------------------------------- */

    private static $hostname = FALSE;   # MySQL Host
    private static $username = FALSE;   # MySQL Username
    private static $password = FALSE;   # MySQL Password
    private static $database = FALSE;   # MySQL Database name
    private static $port     = FALSE;   # MySQL Port
    private static $sock     = FALSE;   # MySQL Sock
    private static $dblink   = NULL;    # MySQL connection
    private static $ThreadId;           # MySQL link's threadId - used to track down in which thread the query was executed
    private static $bConnected = FALSE; # If connected, this will set to TRUE
    private static $__total;            # To keep track of number of queries executed
    private static $bLogHistory = FALSE;# To log mysql history connections and queries or not

    /* Object Members
    ----------------------------------------------------------------------------------------------------------------------------------- */

    // Input
    private $sql;         # Full SQL statement
    private $one = TRUE;  # Return row instead of result resource if there is just one row ?

    // Output
    private $res = FALSE; # mysql_query()
    private $num;         # mysql_num_rows()
    private $aff;         # mysql_affected_rows(), if any
    private $row;         # mysql_fetch_assoc()
    private $rno;         # Row number

    /* Object Methods
    ----------------------------------------------------------------------------------------------------------------------------------- */
    
    public function __construct($sql, $getRowIf1Row = TRUE)
    {
        $this->one = $getRowIf1Row;
        self::set_SQL($sql);
    }
    
    public function __set($name, $value)
    {
        switch ($name)
        {
            case 'sql' : self::set_SQL($value); break;
            default    : throw new MySQL_Ex(Main_Ex::E_NO_SETTER, __class__." : $name"); break;
        }
    }    

    public function __get($name)
    {
        if (in_array($name, array('sql', 'res', 'num', 'aff', 'row', 'rno')))
         return $this->$name;
        
        switch ($name)
        {
            case "insert_id":
             return self::$dblink->insert_id;
            break;
            
            default:
             throw new MySQL_Ex(Main_Ex::E_NO_GETTER, __class__." : $name");
            break;
        }
        
        # throw new MySQL_Ex(Main_Ex::E_NO_GETTER, __class__." : $name");
    }
    
    public function __destruct()
    {
        $this->res->free();
    }

    private function set_SQL($sql)
    {        
        if (!is_string($sql)) throw new Main_Ex(Main_Ex::E_BAD_DATATYPE, "\$sql must of string type");
        if ($this->res) $this->res->free();

        $this->sql = $sql;
        $this->res = self::query($sql);
        $this->num = $this->res->num_rows; # Available only for SELECT
        $this->aff = self::$dblink->affected_rows; # Available only for UPDATE, DELETE queries
        $this->rno = 0;

        if ($this->num == 1 && $this->one) # If theres just one row, might as well iterate to the one and only row
         self::next();
    }

    public function next()
    {
        $this->row = $this->res->fetch_assoc();
        $this->rno++;
        return $this->row ? TRUE : FALSE;
    }

    public function prev()
    {
        if ($this->rno == 0) return FALSE;
        if (!$this->res->data_seek($this->rno - 1)) return FALSE;
        $this->row = $this->res->fetch_assoc();
        $this->rno--;
        return $this->row ? TRUE : FALSE;
    }

    public function reset()
    {
        $this->rno = 0;
        if (!$this->res->data_seek(0)) return FALSE;
        return TRUE;
    }    

    /* Static Methods
    ----------------------------------------------------------------------------------------------------------------------------------- */
    
    public static function init($h, $u, $p, $d, $port = 3306, $sock = NULL)
    {
        self::$hostname = $h;
        self::$username = $u;
        self::$password = $p;
        self::$database = $d;
        self::$port     = $port;
        self::$sock     = isset($sock) ? $sock : ini_get("mysqli.default_socket");
    }    
    
    /*
    Sets Database connection
    Parameters : none
    Returns    : TRUE on success or FALSE on failure
    */
    public static function connect()
    {
        if (self::$hostname === FALSE || self::$username === FALSE || self::$password === FALSE || self::$database === FALSE)
         throw new MySQL_Ex(MySQL_Ex::E_EMPTY_VALUE);

        if (self::$hostname == '') self::$hostname = "localhost"; # Fallback to default
        if (self::$username == '') self::$username = "root";      # Fallback to root

        # database name is mandatory
        if (self::$database == '') throw new MySQL_Ex(MySQL_Ex::E_EMPTY_VALUE, 'No database to connect to !');
        
        self::$dblink = new \mysqli(self::$hostname, self::$username, self::$password, self::$database, self::$port, self::$sock);

        if (self::$dblink->connect_error)
        {
            Main::log("Couldnt connect to database\\connect_error = ".self::$dblink->connect_error);
            throw new MySQL_Ex(MySQL_Ex::E_CONNECTION_FAILED);
        }

        $bConnected = TRUE;        

        return $bConnected;
    }   
    
    public static function close()
    {
        self::$dblink->close();
    }
    
    public static function query($sql, $returnRow = FALSE)
    {
        if (self::$dblink == NULL) # Connection not yet initialized or already closed
         throw new MySQL_Ex(MySQL_Ex::E_NO_CONNECTION);   
         
        $res = self::$dblink->query($sql);
        
        if (!$res)
        {
            Main::log("SQL Statement : $sql\nmysql_error() = ".self::$dblink->error);            
            throw new MySQL_Ex(MySQL_Ex::E_BAD_SQL, $sql.PHP_EOL.self::$dblink->error);
        }
        
        if ($returnRow)
        {
            if ($res->num_rows == 0) { $res->free(); return FALSE; } # No Rows ? Return FALSE
            $row = $res->fetch_assoc();
            $res->free();
            return $row;
        }
        else
         return $res;
    }
    
    public static function insert_id()
    {
        return self::$dblink->insert_id;
    }
}
?>
