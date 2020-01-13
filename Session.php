<?php
namespace CB;

/*
Session class
Author : Anjanesh Lekshminarayanan
Modified : 19/03/2019
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
        # session_module_name("user");
        session_set_save_handler(['\CB\Session', 'open'],
                                 ['\CB\Session', 'close'],
                                 ['\CB\Session', 'read'],
                                 ['\CB\Session', 'write'],
                                 ['\CB\Session', 'remove'],
                                 ['\CB\Session', 'gc']
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
        
        # http://php.net/manual/en/function.session-start.php#120589
        //check to see if $session_data is null before returning (CRITICAL)                
        return $Row['Data'] ?? ''; # Introduced in PHP 7 : https://stackoverflow.com/a/59687793/126833
        /*
        if($Row['Data'] == false || is_null($Row['Data']))
        {
			$session_data = '';
		}
		else
		{
			$session_data = $Row['Data'];
		}
		
        return $session_data;
        */
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
