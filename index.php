<?php
include 'util.php';
include 'Trace.php';
include 'http.class.php';

if (!function_exists('getallheaders'))   
{  
    function getallheaders()   
    {  
       foreach ($_SERVER as $name => $value)   
       {  
           if (substr($name, 0, 5) == 'HTTP_')   
           {  
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;  
           }  
       }  
       return $headers;  
    }  
}
echo "<pre>";
//var_dump($_SERVER);
//var_dump(getallheaders());exit;
ZKTrace::getInstance()->serverReceive();
usleep(100000);
ZKTrace::getInstance()->clientSend();

var_dump(HTTP::PHPGET('http://branch.zipkin.com/'));
usleep(100000);

ZKTrace::getInstance()->clientReceive();
usleep(100000);

ZKTrace::getInstance()->serverSend();
