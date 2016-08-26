<?php
    $MSGKEY = 519051; // Message

    $msg_id = msg_get_queue ($MSGKEY, 0600);
	msg_send($msg_id,8,"abcd",false,false,$err); 
	msg_receive($msg_id,0,$msgtype,4,$data,false,null,$err);
	echo "msgtype {$msgtype} data {$data}\n"; 

    msg_remove_queue ($msg_id);
?>
