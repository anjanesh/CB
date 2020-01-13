<?php
namespace CB;

class SQL_List_Ex extends \Exception
{
    const E_UNKNOWN          = 0;
    const E_EMPTY            = 1;
    const E_BLANK            = 2;
    const E_BAD_SQL          = 3;
    const E_INVALID_DB_TYPE  = 4;
    const E_ILLEGAL          = 5;
    
    /*
     * Parameters
     *  code (INT) One of the error code constants E_*
     *  [message] (string) append a custom error message
     * Returns : none
     * Example : throw new CB\SQL_List_Ex(CB\SQL_List_Ex::E_NO_USER);
     */
    public function __construct($code = self::E_UNKNOWN, $message = NULL)
    {
        switch ($code)
        {
            case self::E_BLANK:
             $msg = "Blank Value";
            break;
            
            case self::E_EMPTY:
             $msg = "Empty Value";
            break;
            
            case self::E_BAD_SQL:
             $msg = "Bad SQL Statement";
            break;
            
            case self::E_ILLEGAL:
             $msg = "Illegal Operation";
            break;
            
            case self::E_UNKNOWN: default:
             $msg = "An unknown message has occurred";
            break;
        }
        
        if (isset($message)) $msg .= ".\n$message."; # Add extra message to default message line
        parent::__construct($msg, $code);
    }
}

/*
 * CB\SQL_List class 
 * Author: Anjanesh Lekshminarayanan
 * Modified: 01/09/2007
 * Description: A generic list class based on a database-table fieldnames with filter criteria
 */
class SQL_List # throws SQL_List_Ex
{
    const SEARCH_ALL = 0;
    const SEARCH_ANY = 1;
    
    const DB_MySQL = 0;
    const DB_PDO   = 1;
    
    # $_GET keys passed from the form
    protected $GET = ['PageNo' => 'pg', 'Rows' => 'norpp', 'SortBy' => 'SortBy', 'AscDesc' => 'AscDesc', 'Keywords' => 'Keywords'];
    
    protected $dbType;                     # DB_MySQL | DB_PDO
    protected $SELECT;                     # (string) { SELECT ... } SQL statement
    protected $FROM;                       # (string) { FROM ... } SQL statement
    public    $WHERE;                      # (string) WHERE condition for SELECT statement
    protected $TextSearchFields = array(); # (array) Basic Search - Search mysql fields for textual search
    public    $Pg;                         # Object of CB\Pagination
    public    $data;                       # Object of CB\MySQL
    
    protected $tables = [];
    
    # (array) Used to filter out common words in textual search
    protected static $CommonWords = ['and', 'or', 'the', 'is', 'a', 'this'];
    
    /*
     * Parameters
     *  SELECT : SELECT statement
     *  FROM   :  FROM statement
     */
    public function __construct($SELECT, $FROM, $Type = self::DB_MySQL)
    {
        $this->SELECT = $SELECT;
        $this->FROM   = $FROM;
        $this->WHERE  = "1";                # Since all other condition clauses would be starting with AND, 1 makes it IF 1 AND
        $this->dbType = $Type;
        
        // Pagination Settings
        $this->Pg = new Pagination();
        
        $this->Pg->PageNo = isset($_GET[$this->GET['PageNo']]) ? $_GET[$this->GET['PageNo']] >= 1 ? $_GET[$this->GET['PageNo']] : 1  : 1;
        $this->Pg->Rows   = isset($_GET[$this->GET['Rows']])   ? $_GET[$this->GET['Rows']] >= 10  ? $_GET[$this->GET['Rows']]   : 10 : 10;
        
        $SortBy  = isset($_GET[$this->GET['SortBy']])  ? $_GET[$this->GET['SortBy']]  : 0;
        $AscDesc = isset($_GET[$this->GET['AscDesc']]) ? $_GET[$this->GET['AscDesc']] : Pagination::DSC;
        
        #$this->Pg->addSortPriority($SortBy, $AscDesc);    # First ORDER BY : ORDER BY Alumni.`Joined`
        #$this->Pg->addSortPriority($SortBy == 0 ? 1 : 0); # Second ORDER BY : ORDER BY Alumni.`Joined` if the above addSortPriority is 0, otherwise ORDER BY `FullName`
    }
    
    // $obj->add_WHERE("AND Alumni.`Batch` > 1998 AND Alumni.`Batch` <= 2001") 
    public function add_WHERE($expr) { $this->WHERE .= " $expr "; }
    
    /*
     * Set column(s) for textual search
     * $obj->addTextSearchFields("Alumni.`FirstName`", "Alumni.`MiddleName`", "Alumni.`LastName`", "Alumni.`About`");
     */
    public function addTextSearchFields()
    {
        $columns = func_get_args();
        foreach ($columns as $column)
        {
            $this->TextSearchFields[] = $column;
        }
    }
    
    // Apply Basic Search functionality to TextSearchFields
    public function filter_basicSearch($type = self::SEARCH_ALL)
    {
        if ($type != self::SEARCH_ALL && $type != self::SEARCH_ANY)
         $type = self::SEARCH_ALL;
        
        if (isset($_GET[$this->GET['Keywords']]))
         $this->WHERE .= self::SearchKeywords($_GET[$this->GET['Keywords']], $type);
    }
    
    // For ones not impementing $data as CB\MySQL
    public function getSQL($num)
    {        
        $SQL = "SELECT ".$this->SELECT." FROM ".$this->FROM." WHERE ".$this->WHERE; # echo $SQL."\n";
        $this->Pg->set($num);        
        $SQL .= $this->Pg->getSQL_ORDER_BY_LIMIT(); 
        return $SQL;
    }
    
    // This should be called after $WHERE is built, and before calling next()
    public function fetch($dblink = NULL)
    {
        $SQL = "SELECT ".$this->SELECT." FROM ".$this->FROM." WHERE ".$this->WHERE; # echo $SQL."\n";
        
        switch($this->dbType)
        {
            case self::DB_MySQL:
             $this->data = new MySQL($SQL, FALSE);
            break;
            
            case self::DB_PDO: # Will not work
             $this->data = new CB\PDO($this->SELECT, $this->FROM, $this->WHERE, $dblink);
            break;
            
            default: throw new SQL_List_Ex(SQL_List_Ex::E_INVALID_DB_TYPE);
        }
        
        $this->Pg->set($this->data->num);
        $SQL .= $this->Pg->getSQL_ORDER_BY_LIMIT();
        $this->data->sql = $SQL; # Reset SQL with added ORDER BY & LIMIT
        # echo $SQL."\n";
    }
    
    public function next()
    {
        return $this->data->next();
    }
    
    public function prev()
    {
        return $this->data->prev();
    }
    
    # From tablename select columns
    public function select($tablename, $columns) # Dropped
    {
    }
    
    /*
     * from table
     * @param $tablename
     * @param $alias
     */
    public function from($tablename, $alias = NULL) # Dropped
    {
        # Get rid off existing leading & trailing `
        $tablename = trim($tablename, " \t\n\r\0`");
        if (isset($alias)) $alias = trim($alias, " \t\n\r\0`");
        
        $this->tables[] = array('`'.$tablename.'`', isset($alias) ? '`'.$alias.'`' : '`'.$tablename.'`');
    }
    
    protected function SearchKeywords($Keywords, $searchType = self::SEARCH_ALL)
    {
        $Keywords = addslashes($Keywords);
        
        $WHERE = " AND (";
        
        // FULL MATCH: Any field containing the Exact string of keywords
        foreach ($this->TextSearchFields as $TextSearchField)
        {
            $WHERE .= "$TextSearchField LIKE '%$Keywords%' OR ";
        }
        
        $Words = explode(" ", $Keywords); // Identify words - separated by whitespace(s) - TODO - catch words within "..." as one word
        $Words = array_unique($Words);   // Remove redundant words
        
        // Remove Common Words
        $tempWords = [];
        foreach ($Words as $Word)
        {
            if (array_search($Word, self::$CommonWords) === FALSE)
             $tempWords[] = $Word;
        }
        $Words = $tempWords; unset($tempWords);
        
        if (count($Words) > 0) // Atleast one word IS NOT a common word - atleast one word is searchable
        {
            // Start : Phase II
            $WHERE .= " (";
            
            // Loop through valid keywords array
            foreach ($Words as $Word)
            {
                $WHERE .= "(";
                
                // Loop through TextSearchFields array
                foreach ($this->TextSearchFields as $TextSearchField)
                {
                    $WHERE .= "$TextSearchField LIKE '%$Word%' OR ";
                }
                
                $WHERE  = substr($WHERE, 0, -4); // Remove trailing OR
                
                switch ($searchType)
                {
                    case self::SEARCH_ALL : $WHERE .= ") AND "; break;
                    case self::SEARCH_ANY : $WHERE .= ") OR  "; break;
                }                
            }
            
            $WHERE  = substr($WHERE, 0, -5); // Remove trailing AND / OR
            $WHERE .= ")"; // End of OR
        }
        else // All words are common words - rejected - Example : searh terms are : this is a
        {
            $WHERE = substr($WHERE, 0, -4); // Remove trailing OR
        }
        
        $WHERE .= ")"; // End of AND
        
        return $WHERE;
    }    
}
?>
