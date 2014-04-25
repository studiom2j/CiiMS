<?php
error_reporting(-1);
ini_set('display_errors', true);
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

function mergeArray($a,$b)
{
    $args=func_get_args();
    $res=array_shift($args);
    while(!empty($args))
    {
        $next=array_shift($args);
        foreach($next as $k => $v)
        {
            if(is_integer($k))
                isset($res[$k]) ? $res[]=$v : $res[$k]=$v;
            elseif(is_array($v) && isset($res[$k]) && is_array($res[$k]))
                $res[$k]=mergeArray($res[$k],$v);
            else
                $res[$k]=$v;
        }
    }
    return $res;
}

// Load the configs
$config = require_once __DIR__.DS.'config'.DS.'test.php';
$defaultConfig = require_once  __DIR__.DS.'config'.DS.'main.default.php';

$config = mergeArray($defaultConfig, $config);
unset($config['components']['request']);
unset($config['components']['user']);

// Include the composer dependencies
require(__DIR__.DS.'..'.DS.'vendor'.DS.'autoload.php');
require(__DIR__.DS.'..'.DS.'vendor'.DS.'yiisoft'.DS.'yii'.DS.'framework'.DS.'yiic.php');