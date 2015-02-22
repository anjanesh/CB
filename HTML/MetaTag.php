<?php
namespace CB\HTML;
class MetaTag
{
    // \CB\NameValuePair
    protected $tag;

    public function __construct()
    {
        $this->tag = new \CB\NameValuePair();
    }

    public function __set($name, $value)
    {
        $this->tag->$name = $value;
    }

    public function __get($name)
    {
        switch ($name)
        {
            default:
             throw new Exception("No getter for $name");
        }
    }

    public function __toString()
    {
        $html = '';

        $a = $this->tag->getArray();
        while (list($name, $content) = each($a))
        {
            $name = str_replace("'", '&#39;', $name);
            $content = str_replace("'", '&#39;', $content);
            $html .= "<meta name='$name' content='$content'/>\n";
        }

        return $html;
    }
}
?>
