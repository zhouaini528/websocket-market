<?php


require '../vendor/autoload.php';

$okex = new \Lin\Okex\OkexWebSocket();

$okex->config([
    //Do you want to enable local logging,default false
    'log'=>false,
    //Or set the log name
    //'log'=>['filename'=>'okex'],

    //Daemons address and port,default 0.0.0.0:2207
    //'global'=>'127.0.0.1:2208',

    //Heartbeat time,default 20 seconds
    //'ping_time'=>20,

    //Channel subscription monitoring time,2 seconds
    //'listen_time'=>2,

    //Channel data update time,0.1 seconds
    //'data_time'=>0.1,
]);

$okex->start();


