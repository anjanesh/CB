<?php
namespace CB\HTML;
class Document
{
    protected $title;  # String
    public $metaTags;  # CB\HTML\MetaTag
    protected $files = [
    'CSS' => ['src' => [], 'rel' => []],
    'JS'  => ['src' => []]
     ];

     public function __construct()
     {
         $this->metaTags = new \CB\HTML\MetaTag();              
     }

     public function __set($name, $value)
     {
         switch ($name)
         {
             case 'title':
              $this->$name = $value;
             break;
         }
     }

     public function __get($name)
     {
         if (in_array($name, array('title')))
          return $this->$name;

         switch ($name)
         {
             case 'CSS':
              return $this->htmlCSS();
             break;

             case 'JS':
              return $this->htmlJS();
             break;

             default:
              throw new Exception("No Getter for $name");                      
         }
     }

     public function addCSS($file, $rel = 'stylesheet')
     {
         $this->files['CSS']['src'][] = $file;
         $this->files['CSS']['rel'][] = $rel;
     }

     public function htmlCSS()
     {
         $html = '';

         foreach($this->files['CSS']['src'] as $i => $cssFile)
          $html .= "<link href='$cssFile' rel='{$this->files['CSS']['rel'][$i]}' type='text/css'/>\n";
         return $html;
     }

     public function addJS($file)
     {
         $this->files['JS']['src'][] = $file;
     }

     public function removeJS($file)
     {
         if (in_array($file, $this->files['JS']['src']))
         {
             $key = array_search($file, $this->files['JS']['src']);
             unset($this->files['JS']['src'][$key]);
             return TRUE;
         }
         else
          return FALSE;
     }

     public function htmlJS()
     {
         $html = '';

         foreach($this->files['JS']['src'] as $i => $jsFile)
          $html .= "<script type='text/javascript' src='$jsFile'></script>\n";
         return $html;
     }
}
?>
