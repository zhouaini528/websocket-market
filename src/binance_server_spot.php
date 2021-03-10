<?php

require '../vendor/autoload.php';

$binance = new \Lin\Binance\BinanceWebSocket();

$binance->config([
    //Do you want to enable local logging,default false
    //'log'=>true,
    //Or set the log name
    'log'=>['filename'=>'spot'],

    //Daemons address and port,default 0.0.0.0:2208
    //'global'=>'127.0.0.1:2208',

    //Heartbeat time,default 20 seconds
    //'ping_time'=>20,

    //Channel subscription monitoring time,2 seconds
    //'listen_time'=>2,

    //Channel data update time,0.1 seconds
    //'data_time'=>0.1,

    //baseurl
    'baseurl'=>'ws://stream.binance.com:9443',//default
    //'baseurl'=>'ws://fstream.binance.com',
    //'baseurl'=>'ws://dstream.binance.com',

]);

$binance->start();

