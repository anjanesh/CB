<?php
function __autoload_CB($className)
{
    # echo $className."\n";

    $classFileName = substr($className, 3);          # Get rid off CB\
    if (substr($classFileName, -3) == '_Ex')         # Get rid off _Ex - 2 classes are stored in 1 file - CB_className and CB_className_Ex
     $classFileName = substr($classFileName, 0, -3);

    $filename = CB_DIR_LIB.str_replace('\\', '/', $classFileName).'.php';
    # echo $filename."\n";

    if (!file_exists($filename))
     return FALSE;

    require_once $filename;
    return TRUE;        
}

function exception_handler_CB($exception)
{
    echo "Uncaught exception: " , $exception->getMessage(), "\n";
    echo "Uncaught exception: " , $exception, "\n";
    CB\Main::log($exception);
    die("Script Halted");        
}
?>
