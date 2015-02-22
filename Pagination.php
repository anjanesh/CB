<?php
namespace CB;
/*
Pagination class
Author : Anjanesh Lekshminarayanan
Modified : 22/04/2009
*/
class Pagination
{
    # Constants
    const ASC  = "ASC";
    const DSC  = "DESC";

    # Members
    private $pg         = "pg";  # (str)        Query String as in url.com?pg=56

    private $Rows       = 10;    # (int)        Number of Rows Per Page
    private $MaxRows    = 100;   # (int)        Maximum Number of Rows Per Page, incase Rows is injected with a very large value
    private $TotalRows;          # (int)        Total number of Rows in set, normally a value returned by mysql_num_rows

    private $SortFields = [];    # (str array)  an array of field-names in a MySQL table

    private $SortBy     = [];    # (int array)  an array of SortFields indexes
                                 #              an array(2, 4) will ORDER BY $SortFields[2], $SortFields[4]

    private $SortOrders = [];    # (enum array) ASC or DSC constants for each index in SortBy
                                 #              an array(ASC, ASC) will ORDER BY $SortFields[2] ASC, $SortFields[4] ASC

    private $StartRecord;        # (int)        Starting Record Number

    private $MaxPages   = 25;    # (int)        Maximum number of page numbers (1 2 3 ... 25) to display per page
    private $PageNo     = 1;     # (int)        Current Page Number
    private $StartPgNo;          # (int)        Start Page Number
    private $StopPgNo;           # (int)        Stop Page Number
    private $LastPgNo;           # (int)        Last Page Number
    
    /*
    Constructor
    Parameters
           TotalRows  : Total number of rows - this is normally from mysql_num_rows()
       [PageNo]   : Current Page number
       [Rows]     : Number of Rows Per Page to display
           [MaxPages] : Optional - Maximum number of page numbers ( 1 2 3 .... 25 ) to be displayed per Page - Default 25
           [MaxRows]  : Maximum number of rows per page allowed
    */
    public function __construct($TotalRows = NULL, $PageNo = NULL, $Rows = NULL, $MaxPages = NULL, $MaxRows = NULL)
    {
        if (isset($TotalRows))
         self::set($TotalRows, $PageNo, $Rows, $MaxPages, $MaxRows);
    }
    
    public function __set($name, $value)
    {
        switch ($name)
        {
            case 'PageNo':
             $this->PageNo = self::__fallBackValue($value);
            break;
            
            case 'MaxPages':
             $this->MaxPages = self::__fallBackValue($value, 25, "integer", 5, 100);
            break;
            
            case 'Rows':
             self::setRows($value);
            break;
            
            default:
             throw new Main_Ex(Main_Ex::E_NO_SETTER, __CLASS__." : $name");
        }
    }
    
    public function __get($name)
    {
        if (in_array($name, array('TotalRows')))
         return $this->$name;
        
        switch($name)
        {
            case 'from': return $this->StartRecord + 1; break;
            case 'to': return $this->StartRecord + min($this->Rows, $this->TotalRows); break;
        }
        
        throw new Main_Ex(Main_Ex::E_NO_GETTER, __CLASS__." : $name");
    }
    
    public function __toString()
    {
    }
    
    /* Methods
    ----------------------------------------------------------------------------------------------------------------------------------- */
    
    public function set($TotalRows, $PageNo = NULL, $Rows = NULL, $MaxPages = NULL, $MaxRows = NULL)
    {
        $this->TotalRows = self::intBoundaryCheck($TotalRows, "Total Count", 0);
        
        if (isset($PageNo)) self::__set("PageNo", $PageNo);
        if (isset($Rows)) self::setRows($Rows, isset($MaxRows) ? $MaxRows : NULL);
        if (isset($MaxPages)) self::__set("MaxPages", $MaxPages);
        self::calc();
    }

    /*
    Set the number of rows per page
    Parameters
        n   : number of row
        [m] : Max number of rows
              Setting MaxRows alone is useless, since by then Rows could be out-of-limit
    */
    public function setRows($n, $m = NULL)
    {
        if (isset($m)) $this->MaxRows = self::__fallBackValue($m, 100, "integer", 1, 10000);
        $this->Rows = $n > $this->MaxRows ? $this->MaxRows : $n;
    }
    
    /*
     * Calculate Pagination values
     * Must be called after input-properties are changed
     */
    public function calc()
    {
        // Start Record of MySQL Query - used in LIMIT
        $this->StartRecord = ($this->PageNo - 1) * $this->Rows;
        
        // First Page number is assumed 1 - therefore FirstPgNo is not defined in the class
        $this->StartPgNo = (ceil($this->PageNo / $this->MaxPages) * $this->MaxPages) - $this->MaxPages + 1;
        $this->LastPgNo  = ceil($this->TotalRows / $this->Rows);
        $this->StopPgNo  = min($this->LastPgNo, $this->StartPgNo + $this->MaxPages);
    }
    
    /*
     * Add sortable fields
     * Each argument must be a valid MySQL fieldname
     * Example $Pg->addSortFields('C.`Name`', 'C.`Email`', 'C.`City`', 'C.`Mobile`', 'C.`Updated`');
     */
    public function addSortFields() 
    {
        $SortFields = func_get_args();
        foreach ($SortFields as $SortField)
        {
            $this->SortFields[] = $SortField;
        }
    }
    
    /*
     * Add Sort Priority
     * Examples:
     *  $Pg->addSortPriority(0, CB_Pagination::ASC);
     *  $Pg->addSortPriority("Contacts.`Name`", CB_Pagination::ASC);
     */
    public function addSortPriority($SortFieldIndex, $SortOrder = self::DSC)
    {
        if (is_int($SortFieldIndex))
         $Index = $SortFieldIndex;
        else // if argument1 is a MySQL column name (string) listed in $this->SortFields[]
         if (($Index = array_search($SortFieldIndex, $this->SortFields)) === FALSE) // Find the index in $this->SortFields[]
          throw new Main_Ex(Main_Ex::E_ARRAYINDEX_OUTOFBOUNDS, "SortFieldsIndex doesnt exist");
        
        // Make sure Index is not out-of-bounds
        if ($Index < 0 || $Index >= count($this->SortFields))
         throw new Main_Ex(Main_Ex::E_ARRAYINDEX_OUTOFBOUNDS, "SortFieldsIndex is Out-Of-Bounds");
        
        $this->SortBy[]     = $Index;
        $this->SortOrders[] = $SortOrder;
    } 
    
    # Set a previously defiend Sort Priority value added using the addSortPriority() method 
    public function setSortPriority($SortByIndex, $SortFieldIndex = NULL, $SortOrder = self::DSC)
    {
        if (!isset($this->SortBy[$SortByIndex]))
         throw new Exception("SortByIndex is Out-Of-Bounds");
        
        if (isset($SortFieldIndex))
         $this->SortBy[$SortByIndex] = $SortFieldIndex;
        
        $this->SortOrders[$SortByIndex] = $SortOrder;
    }
    
    public function getSQL_ORDER_BY()
    {
        $SQL = "";
        
        $n = count($this->SortBy);
        for ($i = 0; $i < $n; $i++)
         $SQL .= $this->SortFields[$this->SortBy[$i]]." ".$this->SortOrders[$i].", ";
        
        $SQL = substr($SQL, 0, -2)." ";
        
        return $SQL;
    }
    
    public function getSQL_LIMIT_offset()    { return $this->StartRecord; }
    public function getSQL_LIMIT_row_count() { return $this->Rows; }
    
    public function getSQL_ORDER_BY_LIMIT()
    {
        $SQL  = "\n";
        $SQL .= "ORDER BY ".self::getSQL_ORDER_BY();
        $SQL .= "LIMIT ".$this->StartRecord.", ".$this->Rows;
        return $SQL;
    }
    
    /*
     * Parameters
     *  [PageName] : The Page to have all links referenced to - default is index.html
     * Displays the Pagination linear table
     */
    public function Show($PageName = "index.html")
    {
        // References
        $pg = &$this->pg;
        $qs = substr(strstr($_SERVER['REQUEST_URI'], '?'), 1);
        
        /*
        Why $qs is not &$_SERVER['QUERY_STRING'];

        RewriteCond %{QUERY_STRING} ^(.*)$
        RewriteRule ^contact$ qs.php?module=contact&%1

        http://localhost/test/contact?a=23&b=45
        would give $_SERVER['QUERY_STRING'] as module=contact&a=23&b=45 when we actually want a=23&b=45
        */
        
        $PageNo    = $this->PageNo;
        $StartPgNo = $this->StartPgNo;
        $StopPgNo  = $this->StopPgNo;
        $LastPgNo  = $this->LastPgNo;
        
        // $IMG_Prev  = '<img alt="&lt;" src="'.CB_ICO.'prev.gif" style="border:0"/>';
        // $IMG_Next  = '<img alt="&gt;" src="'.CB_ICO.'next.gif" style="border:0"/>';
        
        $html_prev = '&#8882;';
        $html_next = '&#8883;';
        $html_first = '&#8678;'; # 8676 8678
        $html_last = '&#8680;'; # 8677 8680
        
        $html = '';
        
        if (preg_match("#$pg=[0-9]+(&|$)#i", $qs, $matches) > 0)
        {
            $First = htmlentities(str_replace($matches[0], "$pg=1".$matches[1], $qs));
            $Prev  = htmlentities(str_replace($matches[0], "$pg=".($PageNo - 1).$matches[1], $qs));
            $Next  = htmlentities(str_replace($matches[0], "$pg=".($PageNo + 1).$matches[1], $qs));
            $Last  = htmlentities(str_replace($matches[0], "$pg=".$LastPgNo.$matches[1], $qs));            
        }
        else
        {
            $First = htmlentities($qs == "" ? "$pg=1"              : $qs."&$pg=1");
            $Prev  = htmlentities($qs == "" ? "$pg=".($PageNo - 1) : $qs."&$pg=".($PageNo - 1));
            $Next  = htmlentities($qs == "" ? "$pg=".($PageNo + 1) : $qs."&$pg=".($PageNo + 1));
            $Last  = htmlentities($qs == "" ? "$pg=".$LastPgNo     : $qs."&$pg=".$LastPgNo);
        }
        
        $html .= '<table border=1 cellspacing="0" cellpadding="0" class="Pagination">';
        $html .= '<tr>';
        
        # << Fist -------------------------------------------------------------------------------------------------------------
        $html .= '<td>';
        if ($PageNo > 1)
         $html .= "<a title='First Page' href='$PageName?$First'>$html_first First</a>";
        else
         $html .= $html_first.self::html1('First');
        $html .= '</td>';
        
        # < Prev --------------------------------------------------------------------------------------------------------------
        $html .= '<td>';
        if ($PageNo == 1)
         $html .= $html_prev.self::html1('Prev');
        else
         $html .= "<a title='Previous Page' href='$PageName?$Prev'>$html_prev Prev</a>";
        $html .= '</td>';
        
        # Middle --------------------------------------------------------------------------------------------------------------
        $html .='<td style="text-align:center">';        
        for ($i = $StartPgNo; $i <= $StopPgNo; $i++)
        {
            if ($PageNo == $i)
             $html .= self::html1($i);
            else
            {
                if ($qs == '')
                 $Middle = "pg=$i";
                elseif (preg_match("#pg=[0-9]+(&|$)#i", $qs, $matches))
                 $Middle = str_replace($matches[0], "pg=".$i.$matches[1], $qs);
                else
                 $Middle = $qs."&pg=".$i;
                 
                $html .= '<a title="Page '.$i.'" href="'.$PageName.'?'.htmlentities($Middle).'">'.$i.'</a> ';
            }
        }
        $html .= '</td>';
        
        # > Next --------------------------------------------------------------------------------------------------------------
        $html .= '<td style="text-align:right">';
        if ($PageNo == $StopPgNo || $StopPgNo == 0)
         $html .= self::html1('Next').$html_next;
        else
         $html .= "<a title='Next Page' href='$PageName?$Next'>Next $html_next</a>";
        $html .= '</td>';
        
        # >> Last -------------------------------------------------------------------------------------------------------------
        $html .= '<td style="text-align:right">';
        if ($PageNo < $StopPgNo)
         $html .= "<a title='Last Page' href='$PageName?$Last'>Last $html_last</a>";
        else
         $html .= self::html1('Last').$html_last;
        $html .= '</td>';
        
        $html .= '</tr>';
        $html .= '</table>';
        
        return $html;        
    }    
    
    private function html1($Text)
    {
        return "&nbsp;<span style='color:#c0c0c0'>$Text</span>&nbsp;";
    }
    
    /*
     * An integer boundary check
     * Parameters
     *      value       : The value to be checked
     *      name        : Name to be used for error message
     *      [minValue]  : Minimum value allowed
     *      [maxValue]  : Maximum value allowed
     * Returns intval(value)
     */
    private function intBoundaryCheck($value, $name, $minValue = 1, $maxValue = 9999999999)
    {
        $value = intval($value);
        
        if ($value < $minValue)
         throw new Main_Ex(Main_Ex::E_OUTOFBOUNDS, "$name must be >= $minValue");
        
        if ($value > $maxValue)
         throw new Main_Ex(Main_Ex::E_OUTOFBOUNDS, "$name must be <= $maxValue");
        
        return $value;
    } 
    
    /*
     * A default value to fall back on if a value is not of the right type or out of bounds
     * This is used in preference to intBoundaryCheck() incase exception is not caught outside the class
     * Parameters
     *      value          : The value to be checked
     *      [defaultValue] : The value to fall back on
     *      [type]         : The value's datatype - Can be one of the following string values - integer, int, boolean, bool, float, string
     *      [minValue]     : Minimum value allowed
     *      [maxValue]     : Maximum value allowed
     * Returns converted value
     */
    private function __fallBackValue($value, $defaultValue = 1, $type = "integer", $minValue = 1, $maxValue = 9999999999)
    {
        if (!in_array($type, array("integer", "int", "boolean", "bool", "float", "string")))
         $type = "integer";
        
        settype($value, $type);
        if ($value < $minValue || $value > $maxValue)
         $value = $defaultValue;
        
        return $value;
    }
    
        /*
        ____________________________________________________________________________________________________________________________________
         Function TitleRow()
         Parameters : $Fields, [$Display = false]
         $Fields : multi-dimensional array of MySQL Field Values
                   $Fields is currently requires 4 types of arrays :
                   The first key is required, others are required ONLY if $Display = TRUE
                   The key of each array is as follows :
                   Field    - The MySQL Field name. Eg. Users.Username
                   Name     - The title / name of the column to be displayed
                   CSS      - Multidimentional array - 'td' and 'a' are keys.
                                td -> array
                                    Class -> array(f1, f2, f3, f4,..,fn)
                                    Style -> array(f1, f2, f3, f4,..,fn)
                                a -> array
                                    Class -> array(f1, f2, f3, f4,..,fn)
                                    Style -> array(f1, f2, f3, f4,..,fn)

                   If more customization of the <A>,<TD> or <IMG> is required then add to array and update code
                   within the if ($Display === TRUE) block
         $Display : Optional - if set to true then it'll do the displaying of each cell with the field name and
                    relevant links and sort icon
         Returns array of Sorted Links

        ============================================================================================================
        Example of this would be :

        $TitleRow = $Pg->TitleRow(
          array(
          "Field"     => array("Contact.FullName" ,"Contact.Email" , "Contact.Phone" ),
          "Name"      => array("Name"             ,"Email"         , "Phone"         ),
          "CSS"       => array(
            "td"      => array(
              "Class" => array(""                 ,""              ,""               ),
              "Style" => array(""                 ,""              ,""               )
                              ),
            "a"       => array(
              "Class" => array("pgTitleLink"      ,"pgTitleLink"   ,"pgTitleLink"    ),
              "Style" => array(""                 ,""              ,""               )
                              )
                              )
          )
          , TRUE
        );
        ____________________________________________________________________________________________________________________________________
        */    
    function TitleRow($Fields, $Display = FALSE)
    {
        $TitleRow = [];
        
        # Sets an array which will define the Links of Sorting in each displayable Field
        for ($i=0; $i<count($Fields['Field']); $i++)
        {
            $SortBy = array_search($Fields['Field'][$i], $this->SortFields);
            if ($SortBy === FALSE) { $TitleRow["Link"][$i] = ""; continue; }
            $qs = $_SERVER['QUERY_STRING'];
            
            if (preg_match('#SortBy=[0-9]+(&|$)#i', $qs, $matches) > 0)
             $qs = str_replace($matches[0], "SortBy=".$SortBy.$matches[1], $qs);
            else
             $qs = $qs."&SortBy=$SortBy";
            
            if (preg_match('#AscDesc=[0-9]+(&|$)#i', $qs, $matches) > 0)
             $qs = str_replace($matches[0], "AscDesc=".((int)!$this->AscDesc).$matches[1], $qs);
            else
             $qs = $qs."&AscDesc=".((int)!$this->AscDesc);
            
            $TitleRow["Link"][$i] = $_SERVER['PHP_SELF']."?".htmlentities($qs);
        }
        
        /*
         * Find the current SortBy Value - By what Field is it sorted right now.
         * This is to determine at which field (column) will the Sort Icon (Ascending or Descending) will be displayed
         */
        if (preg_match('#SortBy=([0-9]+)(&|$)#i', $_SERVER['QUERY_STRING'], $matches) > 0)
         $CurrentSortBy = trim($matches[1]);
        else 
         $CurrentSortBy = $this->SortBy;
        
        $key = array_search($this->SortFields[$CurrentSortBy], $Fields["Field"]);
        $TitleRow["SortIcon"]["Key"]    = $key;
        $TitleRow["SortIcon"]['Image']  = CB_ICO.($this->AscDesc == 0 ? "s_asc.png" : "s_desc.png");
        $TitleRow["SortIcon"]['Width']  = 11;
        $TitleRow["SortIcon"]['Height'] = 9;
        
        /*
         * Display The cells <td></td> of each Field - Easier rather than displaying it one by one outside of function
         * If not displaying ($Display = FALSE) then the array $TitleRow is returned - it contains all the required
         * attributes. $TitleRow is the EXACT array for displaying within this function (Just below)
         */
        if ($Display === TRUE)
        {
            for ($i=0; $i<count($Fields['Field']); $i++)
            {
                /*
                 * Take this example
                 * isset($a) ? $a == "" ? '' : 'class="'.$a.'"' : ''
                 * $a is replaced by the long array name like $Fields['CSS']['td']['Class'][$i]
                 */
                echo '<td'.
                    (isset($Fields['CSS']['td']['Class'][$i]) ? $Fields['CSS']['td']['Class'][$i] == "" ? '' : ' class="'.$Fields['CSS']['td']['Class'][$i].'"' : '').
                    (isset($Fields['CSS']['td']['Style'][$i]) ? $Fields['CSS']['td']['Style'][$i] == "" ? '' : ' style="'.$Fields['CSS']['td']['Style'][$i].'"' : '').
                    '>';
                
                echo $TitleRow['Link'][$i] != "" ?
                    '<a href="'.$TitleRow['Link'][$i].'"'.
                    (isset($Fields['CSS']['a']['Class'][$i]) ? $Fields['CSS']['a']['Class'][$i] == "" ? '' : ' class="'.$Fields['CSS']['a']['Class'][$i].'"' : '').
                    (isset($Fields['CSS']['a']['Style'][$i]) ? $Fields['CSS']['a']['Style'][$i] == "" ? '' : ' style="'.$Fields['CSS']['a']['Style'][$i].'"' : '').
                    '>'.$Fields['Name'][$i].
                    '</a>'
                    : $Fields['Name'][$i];
                
                if ($TitleRow['SortIcon']['Key'] == $i)
                    echo '&nbsp;<a href="'.$TitleRow['Link'][$i].'"><img src="'.$TitleRow['SortIcon']['Image'].'" alt=""/></a>';
                echo '</td>';
            }
        }
        
        /*
         * Return if $Display is FALSE
         *  Returns all attributes for displaying - better have $Display = TRUE for this function as it can do all
         *  displaying here - Classname and Style
         */
        if ($Display === FALSE)
         return $TitleRow;
    }
}
?>
