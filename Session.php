<?php
namespace CB;

/*
Session class
Author : Anjanesh Lekshminarayanan
Modified : 11/12/2014
*/
class Session extends Main
{
    private static $UserID   = FALSE; # UserID = 0 means anonymous user
    public  static $Info     = FALSE;
    private static $bStarted = FALSE;
    
    public static function init($UserID = FALSE)
    {
        if ($UserID) self::$UserID = $UserID; # TODO : Get User Info too
        
        if (!self::$bStarted)
        {
            self::$bStarted = TRUE;
            self::load();
        }
        
        if (!$UserID) self::$UserID = self::getUser();
    }
    
    private static function load()
    {
        session_module_name("user");
        session_set_save_handler(['Session', 'open'],
                                 ['Session', 'close'],
                                 ['Session', 'read'],
                                 ['Session', 'write'],
                                 ['Session', 'remove'],
                                 ['Session', 'gc']
                                 );
        session_start();                                
    }
    
    public static function IsLoggedIn()
    {
        if (self::$UserID > 0)
         return TRUE;
        else
         return FALSE; 
    }
    
    public static function UserID()
    {
        return self::$UserID;
    }
    
    private static function getUser()
    {
        $Row = MySQL::query("SELECT * FROM `cb_sessions` WHERE `SessionID` = '".session_id()."'", TRUE);
        
        if ($Row === FALSE)
        {
            $UserID = 0;
        }
        else
        {
            $UserID = $Row['UserID'];
            self::$Info = $Row;
        }
        
        return $UserID;
    }
    
    public static function open($path, $name)
    {
        return TRUE;
    }
    
    public static function close()
    {
        return TRUE;
    }
    
    public static function read($id)
    {
        $Row = MySQL::query("SELECT `Data` FROM `cb_sessions` WHERE `SessionID` = '$id'", TRUE);
        return $Row['Data'];
    }
    
    public static function write($id, $data)
    {
        if (self::$UserID === FALSE) return;
        $data = addslashes($data);
        $sql = "INSERT INTO `cb_sessions` VALUES ('$id', NOW(), '".self::$UserID."', '$data') ON DUPLICATE KEY UPDATE `UserID` = '".self::$UserID."', `Data` = '$data'";
        $res = MySQL::query($sql);
        return TRUE;
    }
    
    public static function remove($id)
    {
        MySQL::query("DELETE FROM `cb_sessions` WHERE `SessionID` = '$id'");
    }
    
    public static function gc()
    {
        return TRUE;
    }
}
?>
