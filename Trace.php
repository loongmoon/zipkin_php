<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author Administrator
 */
//echo getcwd();

//$ini_array = parse_ini_file("config.ini");

//$GLOBALS['THRIFT_ROOT_'] = $ini_array["includepath"];   
$GLOBALS['service_name_in_zipkin'] = 'service_zhao';

$GLOBALS['zipkinCore_CONSTANTS'] = array();

$GLOBALS['zipkinCore_CONSTANTS']['CLIENT_SEND'] = "cs";

$GLOBALS['zipkinCore_CONSTANTS']['CLIENT_RECV'] = "cr";

$GLOBALS['zipkinCore_CONSTANTS']['SERVER_SEND'] = "ss";

$GLOBALS['zipkinCore_CONSTANTS']['SERVER_RECV'] = "sr";
include_once 'util.php';
//include_once 'shm.php';
include_once 'mq.php';
//include_once $GLOBALS['THRIFT_ROOT_'].'/zipkin/Thrift.php';    
require_once 'zipkinCore_types.php';   
//require_once $GLOBALS['THRIFT_ROOT_'].'/zipkin/transport/TMemoryBuffer.php';  

if (empty($_SERVER["REMOTE_ADDR"])) {
$_SERVER["REMOTE_ADDR"] = '127.0.0.1';
}
class ZKTrace
{
    static private  $SPAN_BUILDER = NULL;
    
    static private  $instance       = NULL;

    //private static  $shmopobj = NULL;


    private function  __construct() 
    {
        
        //self::$shmopobj = new shared();
    }

    public static function getInstance ()
    {
        if (is_null(self::$instance)) {
            self::$instance = new ZKTrace();
        }
        return self::$instance;
    }


    public  function serverReceive()
    {
		SpanBuilder::serverReceive();
    }


    //构建span,肯能是topspan,也可能不是
    public  function clientSend()
    {
        //判断共享内存中是否 “on”;
        //if (isset(self::$shmopobj->database) && self::$shmopobj->database != null &&  self::$shmopobj->database=="on" )
        //{
            SpanBuilder::clientSend();          
        //}
        //else
        //{
        //    //如果禁止收集的话，那么原先收集的也要清空。
        //    SpanBuilder::init();
        //}
    }
    
    public  function clientReceive()
    {
        //判断共享内存中是否 “on”;
        //if (isset(self::$shmopobj->database) && self::$shmopobj->database != null &&  self::$shmopobj->database=="on")
		//{ 
		SpanBuilder::clientReceive();
        //}
    }
    public  function serverSend()
    {
		SpanBuilder::serverSend();
    }
    
}

class SpanBuilder{

    private static $STACK  = NULL ;

    

    //$GLOBALS['STACK']
    public static function init()
    {
        //echo "--init--";
        self::$STACK = new SplStack();
		$headers = getallheaders();
		if (!empty($headers['X-B3-Traceid'])) {
			$GLOBALS['TRACEID'] = $headers['X-B3-Traceid'];
			$GLOBALS['HEADER_TRACEID'] = 'X-B3-Traceid:'.$GLOBALS['TRACEID'];
		}
		if (!empty($headers['X-B3-Spanid'])) {
			SpanContext::pushSpanId($headers['X-B3-Spanid']);
		}
        //self::$STACK->
    }
    public static function serverReceive()
    {
        if (is_null(self::$STACK)) {
            self::init();
        }
		$span = new Span();
		$headers = getallheaders();
		if (empty($headers['X-B3-Traceid']) || empty($headers['X-B3-Spanid'])) {
			$span->name     = 'get';
			$span->traceId = SpanContext::getCurrentTraceId();     
			$span->id       = SpanContext::getCurrentSpanId();
			$span->parentId= SpanContext::getParentSpanId();
		} else {
			$span->name     = 'get';
			$span->traceId = $GLOBALS['TRACEID'];     
			$span->id       = $headers['X-B3-Spanid'];
			if (!empty($headers['X-B3-Parentspanid'])) {
				$span->parentId= $headers['X-B3-Parentspanid'];
			}
			SpanContext::pushSpanId($headers['X-B3-Spanid']);
		}
        
        $span->annotations =  array(AnnotationBuilder::serverReceAnnotation()); 
		if (empty($span->binaryAnnotations)) {
			$span->binaryAnnotations = array();
		}
		self::$STACK->push($span);
    }
    public static function clientSend()
    {
        if (is_null(self::$STACK)) {
            self::init();
        }
        $span = new Span();
        $span->name     = 'get';
		$span->traceId = SpanContext::getCurrentTraceId();     
		$span->id       = SpanContext::getCurrentSpanId();
        $span->parentId= SpanContext::getParentSpanId();
        
        $span->annotations = array(AnnotationBuilder::clientSendAnnotation()); 
		if (empty($span->binaryAnnotations)) {
			$span->binaryAnnotations = array();
		}
		$GLOBALS['HEADER_SPANID'] = 'X-B3-SpanId:'.$span->id;
		$GLOBALS['HEADER_PARENTSPANID'] = 'X-B3-ParentSpanId:'.$span->parentId;
		self::$STACK->push($span);
    }

    public static function clientReceive()
    {
        if (is_null(self::$STACK)) {
            self::init();
            throw new Exception("STACK  is null ,when call clientReceive function before  The clientSend has  be called");
        }
        if(self::$STACK->count() == 0)
        {
            throw new Exception("STACK  is empty ,when call clientReceive function before  The clientSend has  be called");
        }

        $span =  self::$STACK->pop();
        SpanContext::clearSpanID();
        //echo gettype($span->annotations);
        array_push($span->annotations,AnnotationBuilder::clientReceAnnotation($span->name)); 
        //$span->annotations->push(AnnotationBuilder::serverSendAnnotation($span->name)); 
        //$span->annotations->push(AnnotationBuilder::clientReceAnnotation($span->name)); 

		//var_dump($span);exit;
        MessageQueue::getInstance()->push($span);
        //echo "over-";
    }
    public static function serverSend()
    {
        if (is_null(self::$STACK)) {
            self::init();
            throw new Exception("STACK  is null ,when call clientReceive function before  The clientSend has  be called");
        }
        if(self::$STACK->count() == 0)
        {
            throw new Exception("STACK  is empty ,when call clientReceive function before  The clientSend has  be called");
        }

        $span =  self::$STACK->pop();
        //echo gettype($span->annotations);
        array_push($span->annotations,AnnotationBuilder::serverSendAnnotation($span->name)); 
		//var_dump($span);exit;
        MessageQueue::getInstance()->push($span);
    }

}

class SpanContext
{
    private static $ID_STACK      = NULL;
    //private static $IS_TOP_SPAN   = TRUE;
     
    public static function getCurrentTraceId()
    {
        if(is_null($GLOBALS['TRACEID']))
        {
            $GLOBALS['TRACEID'] = Util::getRandInt();
        }
		$GLOBALS['HEADER_TRACEID'] = 'X-B3-TraceId:'.$GLOBALS['TRACEID'];
        return  $GLOBALS['TRACEID'];   
    }
    
    public static function getCurrentSpanId()
    {
        if(is_null(self::$ID_STACK))
        {
            self::$ID_STACK = new SplStack(); 
            self::$ID_STACK->push($GLOBALS['TRACEID']);
           // echo "<br/>getCurrentSpanId1 - >".count($ID_STACK);
            return $GLOBALS['TRACEID']; }
        else
        {
           $id = Util::getRandInt();
           self::$ID_STACK->push($id); 
          // echo "<br/>getCurrentSpanId2 - >".count($ID_STACK);
           return $id;
        }
    }
    
    public static function getParentSpanId()
    {
        if(self::isTopSpan())
        {
            return NULL;
        }
        else
        {
            $cid = self::$ID_STACK->pop(); 
            $pid = self::$ID_STACK->pop(); 
            self::$ID_STACK->push($pid);
            self::$ID_STACK->push($cid);
            return $pid; 
        }
        
    }
    
    public static function isTopSpan()
    {
        if(is_null($GLOBALS['TRACEID']) || is_null(self::$ID_STACK))
        {
            //echo "<br/>orrrr"; return TRUE;
        }
        else
        {
            if(count(self::$ID_STACK)>1)
            {
               // echo "<br/>stack >1";
                return FALSE;
            }
            else
            {
               // echo "<br/> !stack >1".count($ID_STACK);
                return TRUE;
            }
        }
    }

    public static function clearSpanID()
    {
        self::$ID_STACK->pop(); 
    }

    public static function pushSpanID($spanid)
    {
        if(is_null(self::$ID_STACK))
        {
            self::$ID_STACK = new SplStack(); 
		}
        self::$ID_STACK->push($spanid); 
    }
    
}



class AnnotationBuilder
{
    
    public static function clientSendAnnotation()
    {
        return self::makeAnnotation($GLOBALS['zipkinCore_CONSTANTS']['CLIENT_SEND']);
    }
    public static function  serverReceAnnotation()
    {
        return self::makeAnnotation($GLOBALS['zipkinCore_CONSTANTS']['SERVER_RECV']);
    }
    public static function  serverSendAnnotation()
    {
        return self::makeAnnotation($GLOBALS['zipkinCore_CONSTANTS']['SERVER_SEND']);
    }
    public static function  clientReceAnnotation()
    {
        return self::makeAnnotation($GLOBALS['zipkinCore_CONSTANTS']['CLIENT_RECV']);
    }
    
    
    
    
    private static function makeAnnotation($type)
    {
        $ann  = new Annotation ();
        $ann->timestamp = ( microtime(true)*1000000);
        $ann->endpoint      = EndpointBuilder::newDefaultEndpoint();
        $ann->value     = $type;
        return $ann;
    }
    
    
    
    
    
    
    
    
}

class EndpointBuilder {
    
    public static function newDefaultEndpoint() 
        {
                $epo = new Endpoint();
                $epo->ipv4 = ($long = $_SERVER["REMOTE_ADDR"]) ? $long : '';       
                $epo->port = 9091;
                $epo->serviceName = $GLOBALS['service_name_in_zipkin'];
                
        return $epo;
    }
    
    public static function newEndpoint($spanName,$port)
        {
        $epo = new Endpoint();
                //$epo->ipv4 = $long = ip2long($_SERVER["REMOTE_ADDR"]);;       
                $epo->ipv4 = ($long = $_SERVER["REMOTE_ADDR"]) ? $long : '';       
                $epo->port = $port;
                $epo->serviceName = $spanName;
                
        return $epo;
    }
}

?>
