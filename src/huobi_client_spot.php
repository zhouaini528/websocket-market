<?php

use \Lin\Huobi\HuobiWebSocket;

require '../vendor/autoload.php';
require 'config.php';

$huobi = new HuobiWebSocket();

$huobi->config([
    //Do you want to enable local logging,default false
    'log'=>false,
    //Or set the log name
    //'log' => ['filename' => 'spot'],

    //Daemons address and port,default 0.0.0.0:2211
    //'global'=>'127.0.0.1:2211',

    //Channel subscription monitoring time,2 seconds
    //'listen_time'=>2,

    //Channel data update time,default 0.5 seconds
    //'data_time'=>0.5,

    //Set up subscription platform, default 'spot'
    'platform' => 'spot', //options value 'spot' 'future' 'swap' 'linear' 'option'
    //Or you can set it like this
    /*
    'platform'=>[
        'type'=>'spot',
        'market'=>'ws://api.huobi.pro/ws',//Market Data Request and Subscription
        'order'=>'ws://api.huobi.pro/ws/v2',//Order Push Subscription

        //'market'=>'ws://api-aws.huobi.pro/ws',
        //'order'=>'ws://api-aws.huobi.pro/ws/v2',
    ],
    */
]);

$huobi->subscribe([
    'market.btcusdt.depth.step0',
    'market.bchusdt.depth.step0',
    'market.ethusdt.depth.step0',
    'market.bsvusdt.depth.step0',
    'market.eosusdt.depth.step0',
]);

$client = new Predis\Client($config['redis']);

$huobi->getSubscribes(function($data) use($client){
    foreach ($data as $v){
        if(!isset($v['ch'])) continue;

        $rdata=resetData($v);
        $key='huobi:depth:0:'.$rdata['symbol'];

        $client->hset($key,'average_buy',$rdata['average_buy']);
        $client->hset($key,'average_sell',$rdata['average_sell']);
        $client->hset($key,'average_price',$rdata['average_price']);
        $client->hset($key,'microtime',$rdata['microtime']);
        $client->hset($key,'origin',json_encode($rdata['origin']));
    }
},true);


/*
{
    "ch": "market.btcusdt.depth.step0",
	"ts": 1608169730910,
	"tick": {
    "bids": [
        [21682.14, 0.005],
        [21680.05, 0.13],
        [21680.04, 3.5],
        [21680.03, 0.105981],
    ],
		"asks": [
        [21682.15, 0.026999],
        [21683.86, 1.400959],
        [21683.97, 0.9898601531702912],
    ],
		"version": 116833074180,
		"ts": 1608169730905
	}
}
*/
function resetData($data){
    $symbol=explode('.',$data['ch']);

    $average_buy=current($data['tick']['bids']);
    $average_sell=current($data['tick']['asks']);

    //获取保留几位小数?
    $buy_tmp=explode('.',$average_buy[0]);
    $sell_tmp=explode('.',$average_sell[0]);
    $buy_len=$sell_len=0;
    if(count($buy_tmp)>1) $buy_len=strlen($buy_tmp[1]);
    if(count($sell_tmp)>1) $sell_len=strlen($sell_tmp[1]);
    $tmp_len=$buy_len >= $sell_len ? $buy_len : $sell_len;


    $average_price=bcdiv($average_buy[0]*$average_buy[1]+$average_sell[0]*$average_sell[1],$average_buy[1]+$average_sell[1],$tmp_len);

    $microtime=$data['tick']['ts'];
    $origin=['buy'=>$data['tick']['bids'],'sell'=>$data['tick']['asks']];

    return [
        'symbol'=>$symbol[1],
        'average_buy'=>$average_buy[0],
        'average_sell'=>$average_sell[0],
        'average_price'=>$average_price,
        'microtime'=>$microtime,
        'origin'=>$origin,
    ];
}
