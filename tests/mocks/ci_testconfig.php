<?php

class CI_TestConfig
{
    public $config = [];
    public $_config_paths = [APPPATH];
    public $loaded = [];

    public function item($key)
    {
        return isset($this->config[$key]) ? $this->config[$key] : false;
    }

    public function load($file, $arg2 = false, $arg3 = false)
    {
        $this->loaded[] = $file;

        return true;
    }
}
