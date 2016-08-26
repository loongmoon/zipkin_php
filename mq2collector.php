<?php

//对象转数组,使用get_object_vars返回对象属性组成的数组
function objectToArray($obj){
    $arr = is_object($obj) ? get_object_vars($obj) : $obj;
    if(is_array($arr)){
        return array_map(__FUNCTION__, $arr);
    }else{
        return $arr;
    }
}


//try{   
    include_once 'mq.php'; 
	include 'Trace.php';
    //error_reporting(E_ALL);    
     
    $span = MessageQueue::getInstance()->pop();

    //echo "-->".gettype($span);
    if(!is_null($span) && gettype($span)!= 'boolean')
    {
		$rk = new RdKafka\Producer();
		$rk->setLogLevel(LOG_DEBUG);
		$rk->addBrokers("127.0.0.1:9092");
		$topic = $rk->newTopic("zipkin");
		//var_dump(json_encode(array($span), true));exit;
		$topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(array($span), true));
	}
     
//    }catch(TException $tx){    
//        print 'TException: '.$tx->getMessage()."/n";    
//        echo  'TException: '.$tx->getMessage()."/n";    
//    }  

?>
