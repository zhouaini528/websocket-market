<?php

require '../vendor/autoload.php';
require 'config.php';

$okex=new \Lin\Okex\OkexWebSocket();

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

$okex->subscribe([
    'spot/depth5:BTC-USDT',
    'spot/depth5:BCH-USDT',
    'spot/depth5:ETH-USDT',
    'spot/depth5:EOS-USDT',
    'spot/depth5:BSV-USDT',


    'futures/depth5:BTC-USD-210326',
    'futures/depth5:BCH-USD-210326',
    'futures/depth5:ETH-USD-210326',
    'futures/depth5:EOS-USD-210326',
    'futures/depth5:BSV-USD-210326',
]);


$client = new Predis\Client($config['redis']);

$okex->getSubscribes(function($data) use($client){
    foreach ($data as $v){
        $rdata=resetData($v);

        $table=explode('/',$rdata['table']);

        switch ($table[0]){
            case 'spot':{
                $type=0;
                break;
            }
            case 'futures':{
                $type=1;
                break;
            }
        }

        $key='okex:depth:'.$type.':'.$rdata['symbol'];
        //echo $key.PHP_EOL;
        $client->hset($key,'average_buy',$rdata['average_buy']);
        $client->hset($key,'average_sell',$rdata['average_sell']);
        $client->hset($key,'average_price',$rdata['average_price']);
        $client->hset($key,'microtime',$rdata['microtime']);
        $client->hset($key,'origin',json_encode($rdata['origin']));
    }
},true);


/* spot
{
"table": "spot\/depth5",
    "data": [{
    "asks": [
        ["21952.7", "2.82300362", "6"],
        ["21953", "0.08605445", "1"],
        ["21954.2", "0.1", "1"],
        ["21954.3", "0.01", "1"],
        ["21954.9", "0.049", "1"]
    ],
        "bids": [
        ["21952.6", "0.46", "2"],
        ["21951.7", "0.00876711", "1"],
        ["21949.5", "0.026", "1"],
        ["21949.4", "0.1", "1"],
        ["21949.3", "0.1", "1"]
    ],
        "instrument_id": "BTC-USDT",
        "timestamp": "2020-12-17T03:01:22.690Z"
    }]
},*/


/*  future
{
"table": "futures\/depth5",
    "data": [{
"asks": [
    ["22888", "1", "0", "1"],
    ["22890.6", "40", "0", "1"],
    ["22891.69", "45", "0", "1"],
    ["22891.7", "39", "0", "1"],
    ["22891.93", "1", "0", "1"]
],
        "bids": [
    ["22887.62", "4", "0", "1"],
    ["22887.6", "68", "0", "3"],
    ["22883.04", "45", "0", "1"],
    ["22883.03", "1", "0", "1"],
    ["22882.72", "149", "0", "3"]
],
        "instrument_id": "BTC-USD-210326",
        "timestamp": "2020-12-17T03:01:22.604Z"
    }]
}
*/

function resetData($data){
    $table=$data['table'];
    $symbol=$data['data'][0]['instrument_id'];

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
        'table'=>$table,
    ];
}


