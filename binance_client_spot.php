<?php

require 'vendor/autoload.php';
require 'config.php';

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

$binance->subscribe([
    'btcusdt@depth',
    'bchusdt@depth',
    'ethusdt@depth',
    'eosusdt@depth',
    'bsvusdt@depth',
]);

$client = new Predis\Client($config['redis']);

$binance->getSubscribes(function($data) use($client){
    foreach ($data as $v){
        if(empty($v)) continue;

        $rdata=resetData($v);

        $key='binance:depth:0:'.$rdata['symbol'];
        $client->hset($key,'average_buy',$rdata['average_buy']);
        $client->hset($key,'average_sell',$rdata['average_sell']);
        $client->hset($key,'average_price',$rdata['average_price']);
        $client->hset($key,'microtime',$rdata['microtime']);
        $client->hset($key,'origin',json_encode($rdata['origin']));
    }
},true);


/*
{
	"stream": "bchusdt@depth",
	"data": {
		"e": "depthUpdate",
		"E": 1614652150383,
		"s": "BCHUSDT",
		"U": 2778129374,
		"u": 2778129512,
		"b": [
			["504.95000000", "1.00000000"],
			["504.93000000", "15.00000000"],
		],
		"a": [
			["505.03000000", "0.00000000"],
			["505.05000000", "0.00079000"],
		]
	}
}
*/
function resetData($data){
    $symbol=$data['data']['s'];

    $average_buy=current($data['data']['b']);
    $average_sell=current($data['data']['a']);

    //获取保留几位小数?
    $buy_tmp=explode('.',$average_buy[0]);
    $sell_tmp=explode('.',$average_sell[0]);
    $buy_len=$sell_len=0;
    if(count($buy_tmp)>1) $buy_len=strlen($buy_tmp[1]);
    if(count($sell_tmp)>1) $sell_len=strlen($sell_tmp[1]);
    $tmp_len=$buy_len >= $sell_len ? $buy_len : $sell_len;

    if(is_float($average_sell[1]) && ($average_buy[1]+$average_sell[1])!=0){
        $average_price=bcdiv($average_buy[0]*$average_buy[1]+$average_sell[0]*$average_sell[1],$average_buy[1]+$average_sell[1],$tmp_len);
    }
    else{
        $average_price=bcdiv($average_buy[0]+$average_sell[0],2,$tmp_len);
    }

    $microtime=$data['data']['E'];
    $origin=['buy'=>$data['data']['b'],'sell'=>$data['data']['a']];

    return [
        'symbol'=>$symbol,
        'average_buy'=>$average_buy[0],
        'average_sell'=>$average_sell[0],
        'average_price'=>$average_price,
        'microtime'=>$microtime,
        'origin'=>$origin,
    ];
}


