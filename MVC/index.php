<?php
    
require 'config/paths.php';
require 'config/database.php';
use app\libs\Bootstrap;
    function __autoload($class){
        try{
            $class = explode('\\', $class);
            //folder's name  and namespaces need to be equal
            $cls = array_pop($class);
            $ns = array_pop($class);
            require_once "$ns\\$cls.php";
            //Using libs constant
            //require LIBS.  array_pop($class).'.php';
        }  catch (Exception $ex){
            throw $ex;
        }

    }
$app = new Bootstrap();

