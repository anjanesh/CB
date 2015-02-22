<?php
ini_set("log_errors"     , "1");
ini_set("error_log"      , "Errors.log.txt");
ini_set("display_errors" , "1");
# ini_set("session.name"   , "cibi");

error_reporting(E_ALL + E_STRICT);

if (isset($_SERVER['argc'][1])) # Script being run in Command line mode
 {
        $_SERVER['HTTP_HOST'] = "localhost";
 }

# Constants
if (!defined("CB_DIR_LIB"))
 define("CB_DIR_LIB", str_replace("\\", "/", dirname(__FILE__))."/");  # Forward slash works on Windows as well
?>
