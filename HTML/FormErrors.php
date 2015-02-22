<?php
namespace CB\HTML;
class FormErrors_Ex extends \Exception
 {
        const E_UNKNOWN       = 0;
        const E_BAD_TYPE      = 1;

        /*
        Constructor
        Parameters
           code (INT) One of the error code constants E_*
           [message] (string) Optional - Add error message with custom  message
        Returns : none
        Example : throw new CB_News_HTML_Form_Ex(CB_News_HTML_Form_Ex::E_NO_NEWS);
        */
        public function __construct($code = self::E_UNKNOWN, $message = NULL)
         {
                switch ($code)
                 {
                        case self::E_BAD_TYPE:
                         $msg = "Bad Error Type - must be either of TYPE_ERROR | TYPE_WARN";
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
CB\HTML\Form_Errors : a replacement for CB_FormErrors Class
Author : Anjanesh Lekshminarayanan
Last Modified : 06/01/2008
*/
class FormErrors
{
        const TYPE_ERROR = 1;
        const TYPE_WARN  = 2;
        const TYPE_INFO  = 3;

        private $count = 0; # Total number of Errors Occurred

        private $_field    = array();
        private $_name     = array(); # This is not unique, a combination of _field & _name should be unique though
        private $_msg      = array();
        private $_detail   = array();
        private $_occurred = array();
        private $_type     = array();

        public function __construct()
        {
        }

        public function __get($name)
         {
                switch ($name)
                 {
                        case "count":
                         return $this->count;
                        break;

                        default:
                         throw new \CB\Main_Ex(\CB\Main_Ex::E_NO_GETTER);
                        break;
                 }
         }

        public function addErrorInfo($field, $name, $msg, $detail = '', $type = self::TYPE_ERROR)
         {
                if ($field == "")
                 throw new \CB\Main_Ex(\CB\Main_Ex::E_EMPTY_ARG, "field argument cannot be empty");

                if ($name == "")
                 throw new \CB\Main_Ex(\CB\Main_Ex::E_EMPTY_ARG, "name argument cannot be empty");

                if ($msg == "")
                 throw new \CB\Main_Ex(\CB\Main_Ex::E_EMPTY_ARG, "msg argument cannot be empty");

                if (!in_array($type, array(self::TYPE_ERROR, self::TYPE_WARN, self::TYPE_INFO)))
                 throw new FormErrors_Ex(FormErrors_Ex::E_BAD_TYPE);

                $i = count($this->_name); # New index, auto_increment

                $this->_field   [$i] = $field;
                $this->_name    [$i] = $name;
                $this->_msg     [$i] = $msg;
                $this->_detail  [$i] = $detail;
                $this->_occurred[$i] = FALSE;
                $this->_type    [$i] = $type;
         }

        # Trigger
        public function t($field, $name = NULL, $CustomMsg = NULL, $AppendCustomMsg = FALSE)
         {
                if (isset($name))
                 {
                        $keys = array_keys($this->_field, $field);

                        foreach($keys as $key)
                         if ($this->_name[$key] == $name)
                          {
                                 $this->count++;
                                 $this->_occurred[$key] = TRUE;

                                 if (isset($CustomMsg))
                                 {
                                         if ($AppendCustomMsg)
                                          $this->_msg[$key] .= $CustomMsg;
                                         else
                                          $this->_msg[$key] = $CustomMsg;
                                 }

                                 return;
                          }

                 }
                else # Trigger the first error having $field in $this->_field
                 {
                        $key = array_search($field, $this->_field);

                        if ($key === FALSE)
                         return FALSE;
                        else
                         {
                                $this->count++;
                                return $this->_occurred[$key] = TRUE;
                         }
                 }

                return FALSE;
         }

        public function ShowErrorMsgs($CSSclassName = NULL, $ErrorHeading = 'The following errors have occurred :')
        {
               $html  = '';
               if ($this->count == 0) return '';

               if (!isset($CSSclassName))
               {
                      $html .= '
                      <style type="text/css">
                      .Error-Info
                      {
                             font-family:Arial;
                             font-weight:normal;
                             font-style:normal;
                             background-color:#ff0033;
                             color:#ffffff;
                             border:0px outset #ff0000;
                             padding:2px 2px 0px 5px;
                      }
                      .Error-Info span
                      {
                             font-weight:bold;
                      }
                      .Error-Info ol
                      {
                             list-style-type: none;
                             margin-top:7px;
                             margin-bottom:0;
                      }
                      .Error-Info ol li
                      {
                             padding-bottom:3px;
                      }
                      </style>';
                      $CSSclassName = 'Error-Info';
               }

               $html .= "<div class='$CSSclassName'>";
               $html .= "<span>$ErrorHeading</span>";
               $html .= '<ol>';

               foreach($this->_occurred as $i => $occurred)
                if ($occurred)
                 $html .= "<li>".htmlentities($this->_msg[$i], ENT_QUOTES)."</li>";

               $html .= '</ol>';
               $html .= '</div>';

               return $html;
        }

        /*
         * Added : 4th September 2013
         */
        public function getErrorMsgs()
        {
            $messages = [];

            foreach($this->_occurred as $i => $occurred)
            {
                if ($occurred)
                 $messages[] = $this->_msg[$i];
            }

            return $messages;
        }
}
?>
