<?php
namespace CB;

/**
 * Main_Ex class
 * @author Anjanesh Lekshminarayanan
 * @modified 01/10/2007
 */
class Main_Ex extends \Exception
{
    const E_UNKNOWN      = 0;        
    const E_NOT_SET      = 1;
    const E_EMPTY_VALUE  = 2;
    const E_BAD_DATATYPE = 3;
    const E_NOT_NUMERIC  = 4;
    const E_OUTOFBOUNDS  = 5;
    const E_DB_SQL       = 6;
    const E_NO_SETTER    = 7;
    const E_NO_GETTER    = 8;
    const E_EMPTY_STRING = 9;
    const E_NOT_ARRAY    = 10;
    const E_NOT_INT      = 11;
    const E_BAD_COUNT    = 12;
    const E_EMPTY_ARG    = 13;
    const E_ARRAYINDEX_OUTOFBOUNDS  = 14;

    /*
    Constructor
    Parameters :
       code (int) One of the error code constants E_*
       [message] (string) Optional - override error message with custom  message
    Returns : none
    Example : throw new SJS_Base_Ex(SJS_Base_Ex::E_NOT_NUMERIC);
    */
    public function __construct($code = self::E_UNKNOWN, $message = NULL)
    {
        switch ($code)
        {
            case self::E_EMPTY_VALUE:
             $msg = "Value cannot be blank";
            break;                    

            case self::E_EMPTY_STRING:
             $msg = "String cannot be empty";
            break;

            case self::E_EMPTY_ARG:
             $msg = "Argument cannot be empty";
            break;

            case self::E_DB_SQL:
             $msg = "DB SQL Error";
            break;

            case self::E_NOT_SET:
             $msg = "Variable must be assigned a value";
            break;

            case self::E_NO_SETTER:
             $msg = "No setter available";
            break;

            case self::E_NO_GETTER:
             $msg = "No getter available";
            break;

            case self::E_BAD_DATATYPE:
             $msg = "Bad Data Type";
            break;

            case self::E_NOT_NUMERIC:
             $msg = "Bad Data Type: Expected a Numeric Type";
            break;

            case self::E_OUTOFBOUNDS:
             $msg = "Value is out of Bounds";
            break;

            case self::E_NOT_ARRAY:
             $msg = "Expected an Array type.";
            break;

            case self::E_NOT_INT:
             $msg = "Bad Data Type: Expected an Integer Type";
            break;

            case self::E_BAD_COUNT:
             $msg = "Wrong Count";
            break;

            case self::E_ARRAYINDEX_OUTOFBOUNDS:
             $msg = "Array Index is Out-Of-Bounds";
            break;

            case self::E_UNKNOWN: default:
             $msg = "An unknown message has occurred";
            break;
        }

        if (isset($message)) $msg .= ".\n$message."; # Add extra message to default message line
        parent::__construct($msg, $code);            
     }
}

/**
 * Main class
 * @author Anjanesh Lekshminarayanan
 * @modified 01/09/2007
 * @description Base class for all classes
 */
abstract class Main
{
    const DEBUG_FILENAME = "debug.log";        

    /*
    Parameters :
      str      - the contents to be written - the time is inserted in the function
      Filename - the filename to which the contents need to be written - the default one is DEBUG_FILENAME constant
    */
    public static function log($str, $Filename = self::DEBUG_FILENAME)
    {
        $debug = debug_backtrace();

        $fh = fopen($Filename, "a");
        fwrite($fh, @date("d-m-Y H:i:s")."\n$str\n\ndebug_backtrace() = ".print_r($debug, TRUE).str_repeat("=", 100)."\n");
        fclose($fh);
    }
}
?>
