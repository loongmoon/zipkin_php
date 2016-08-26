# zipkin_php
zipkin的php客户端，采用json传输，
我试了github的zipkin_php_scribe(https://github.com/malakaw/zipkin_php_scribe)这个项目，不好用，传输的数据元素名跟zipkin需要的对应不起来，导致zipkin接收不到。
所以自己写了套采用json数据格式传输的zipkin的php客户端。
