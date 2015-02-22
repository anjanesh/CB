<?php
namespace CB;

abstract class Controller
{
    public function forward($Module, $Action = 'index')
    {
        $class = "CB_App_".ucfirst($Module);

        if (!class_exists($class))
         throw new \Exception("Module $Module not found");

        # new CB_App_Home()
        $controller = new $class();
        $controller->Module = ucfirst($Module);
        $controller->Section = $Action;

        # Call Methods
        $controller->init(); # Initializes Database connection, session
        $controller->dispatchAction($Action);
        $controller->finish();

        global $CB;
        
        # Compress HTML        
        # ob_start();
        require_once CB_DIR_UI."{$controller->Skin}/html/{$controller->Layout}.html"; # master layout template file
        /*
        $html = ob_get_clean();
        $htmls = explode("\n", $html);
        for ($i = 0; $i < count($htmls); $i++)
        {
            $htmls[$i] = trim($htmls[$i]);
        }
        $html = implode("", $htmls);
        echo $html;
        */
        
        exit(0);          
       }

       public function finish()
       {
           if (session_id() != '') session_write_close();              
       }
}

class FrontController extends Controller
{
       public static function createInstance()
       {
           $instance = new self();
           return $instance;
       }

       public function dispatch()
       {
           $Module = isset($_GET['module']) ? $_GET['module'] : "home";
           $Action = isset($_GET['action']) ? $_GET['action'] : "index";
           $this->forward($Module, $Action);
       }
}

abstract class ActionController extends Controller
{
       protected $Module;

       # Mainly $Action values
       protected $Section;

       # view data
       protected $data = [];

       public function setTemplateVariable($key, $value)
       {
              $this->data[$key] = $value;
       }

       public function __set($name, $value)
       {
              switch ($name)
              {
                     case 'Module':
                      $this->Module = $value;
                     break;

                     default:
                      throw new \Exception("No Setter for $name");
                     break;
              }
       }

       public function __get($name)
       {
       }

       public function dispatchAction($Action)
       {
           $actionMethod = "act_$Action";

           if (!method_exists($this, $actionMethod))
            die("Method $actionMethod not found");

           $this->Section = $Action;
           $this->$actionMethod(); # Can change Section value in action method
           $this->displayView();
       }

       public function displayView()
       {
           /*
            * __CLASS__ will return ActionController - not CB_App_<Module>              
            */
           global $CB;

           $CB['skin']   = $this->Skin;
           $CB['html']   = $this->html; # Object of HTML_Document
           $CB['layout'] = $this->Layout;
           $CB['template_file'] = "{$this->Module}.{$this->Section}.html";
           $CB['data']     = $this->data;
           $CB['Module']   = $this->Module;
           $CB['Section']  = $this->Section;

           /*
            Actual display called in index.html file              
            require_once "$skin/html/layout.html";
           */
       }
}
?>
