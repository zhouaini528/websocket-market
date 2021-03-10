<?php

require '../vendor/autoload.php';
require 'config.php';

$bitmex=new \Lin\Bitmex\BitmexWebSocket();

$bitmex->config([
    //Do you want to enable local logging,default false
    'log'=>false,
    //Or set the log name
    //'log'=>['filename'=>'bitmex'],

    //Daemons address and port,default 0.0.0.0:2211
    //'global'=>'127.0.0.1:2211',

    //Channel data update time,default 0.5 seconds
    //'data_time'=>0.5,

    //Heartbeat time,default 30 seconds
    //'ping_time'=>30,

    //baseurl host
    //'baseurl'=>'ws://www.bitmex.com/realtime',//default
    //'baseurl'=>'ws://testnet.bitmex.com/realtime',//test
]);

$bitmex->subscribe([
    'orderBook10:XBTUSD',
    'orderBook10:XBTH21',
    'orderBook10:XBTM21',
]);


$client = new Predis\Client($config['redis']);

$bitmex->getSubscribes(function($data) use($client){
    foreach ($data as $v){
        $rdata=resetData($v);

        $key='bitmex:depth:1:'.$rdata['symbol'];
        //echo $key.PHP_EOL;
        $client->hset($key,'average_buy',$rdata['average_buy']);
        $client->hset($key,'average_sell',$rdata['average_sell']);
        $client->hset($key,'average_price',$rdata['average_price']);
        $client->hset($key,'microtime',$rdata['microtime']);
        $client->hset($key,'origin',json_encode($rdata['origin']));
    }
},true);



/*  future
{
		"table": "orderBook10",
		"action": "update",
		"data": [{
			"symbol": "XBTM21",
			"bids": [
				[23454.5, 9000],
				[23453.5, 5490],
				[23438, 5000],
				[23435.5, 175889],
				[23433, 1701],
				[23421.5, 5000],
				[23414.5, 6000],
				[23414, 1500],
				[23411.5, 20000],
				[23408, 50000]
			],
			"timestamp": "2020-12-17T07:15:08.349Z",
			"asks": [
				[23480.5, 10000],
				[23493.5, 5000],
				[23499, 332],
				[23503.5, 216566],
				[23510.5, 5000],
				[23520.5, 1405],
				[23521, 100],
				[23526.5, 1500],
				[23527, 5000],
				[23530, 35805]
			]
		}]
	}
*/

function resetData($data){
    $symbol=$data['data'][0]['symbol'];

    $average_buy=current($data['data'][0]['bids']);
    $average_sell=current($data['data'][0]['asks']);


    //获取保留几位小数?
    $buy_tmp=explode('.',$average_buy[0]);
    $sell_tmp=explode('.',$average_sell[0]);
    $buy_len=$sell_len=0;
    if(count($buy_tmp)>1) $buy_len=strlen($buy_tmp[1]);
    if(count($sell_tmp)>1) $sell_len=strlen($sell_tmp[1]);
    $tmp_len=$buy_len >= $sell_len ? $buy_len : $sell_len;

    $average_price=bcdiv($average_buy[0]*$average_buy[1]+$average_sell[0]*$average_sell[1],$average_buy[1]+$average_sell[1],$tmp_len);

    $microtime=strtotime($data['data'][0]['timestamp'])*1000;
    $temp=explode('.',$data['data'][0]['timestamp']);
    $microtime=$microtime+intval($temp[1]);

    $origin=['buy'=>$data['data'][0]['bids'],'sell'=>$data['data'][0]['asks']];

    return [
        'symbol'=>$symbol,
        'average_buy'=>$average_buy[0],
        'average_sell'=>$average_sell[0],
        'average_price'=>$average_price,
        'microtime'=>$microtime,
        'origin'=>$origin,
    ];
}


