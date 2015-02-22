<?php
namespace CB;
class NameValuePair
{
    protected $NameValuePair = [];

    // $o = new CB\NameValuePair();
    public function __construct()
    {
    }

    // $o->name1 = 'value1';
    public function __set($name, $value)
    {
        $this->NameValuePair[$name] = $value;
    }

    // echo $o->name1;
    public function __get($key)
    {
        if (!array_key_exists($key, $this->NameValuePair))
         throw new \Exception("key '$key' doesnt exist");

        return $this->NameValuePair[$key];
    }

    public function getArray()
    {
        return $this->NameValuePair;
    }
}
?>
