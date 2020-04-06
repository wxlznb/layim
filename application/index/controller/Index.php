<?php
namespace app\index\controller;

class Index
{
    public function index()
    {
        echo 3;
    }

    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }
}
