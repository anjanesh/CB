<?php
namespace CB\HTML;
class Form_Ex extends \Exception
{
    const E_UNKNOWN          = 0;
    const E_NOT_POSTED       = 1;
    const E_ILLEGAL          = 9;

    /*
    Constructor
    Parameters
       code (INT) One of the error code constants E_*
       [message] (string) Optional - Add error message with custom  message
    Returns : none
    Example : throw new CB_HTML_Form_Ex(CB_HTML_Form_Ex::E_NOT_POSTED);
    */
    public function __construct($code = self::E_UNKNOWN, $message = NULL)
    {
        switch ($code)
        {
            case self::E_NOT_POSTED:
             $msg = "Form was not POSTed";
            break;

            case self::E_ILLEGAL:
             $msg = "An illegal operation was performed";
            break;

            case self::E_UNKNOWN: default:
             $msg = "An unknown message has occurred";
            break;
        }

        if (isset($message)) $msg .= ".\n$message."; # Add extra message to default message line
         parent::__construct($msg, $code);
    }
 }

abstract class Form
 {
        // Members
        protected $isPOSTed     = FALSE; # Has the <form> been POSTed ?
        protected $isValidInput = FALSE; # Are the input arguments ok ? Cannot be TRUE on isPOSTed = FALSE

        protected $method; # There can be different functions for the same form. Eg, add, edit, delete etc
        protected $number; # There can also be multiple forms for the same function. Eg, add, quick add - we can represent add with 0, and quick add with 1

        protected $values = array(); # Associative array - 'Email' => (isset($_POST['Email']) ? $_POST['Email'] : $Email)
        protected $Err;              # Object of CB_HTML_FormMessages

        protected $exp_POST; # Expected POST values

        // abstract protected function __construct($methodName, $number);
        public function __get($name)
         {
                switch ($name)
                 {
                        case 'isPOSTed':
                         return $this->isPOSTed;
                        break;

                        case 'isValidInput':
                         return $this->isValidInput;
                        break;

                        case 'values':
                         return $this->values;
                        break;

                        case 'getHTML':
                         return self::getHTMLForm();
                        break;

                        case 'Err':
                         return $this->Err;
                        break;
                 }
         }

        abstract protected function validateInput();
        abstract protected function setValues();
        abstract protected function processForm();

        protected function checkPOST()
        {
            /*
            echo "array_keys(\$_POST) = "; print_r(array_keys($_POST)); echo "<br>";
            echo "\$this->exp_POST = "; print_r($this->exp_POST); echo "<br>";
            print_r(array_diff($this->exp_POST, array_keys($_POST)));
            die();
            */
            
            
            # POSTed
            if (count(array_diff($this->exp_POST, array_keys($_POST))) == 0) # If name=somearray[] and nothing is selected, somearray doesn't get sent in $_POST
             return TRUE;
        }

        public function validateInputOnPOST()
        {
            $this->setValues();
            $this->checkPOST();
            if ($this->isPOSTed) $this->validateInput();
             return $this;
        }
 }
?>
