### 基于[exhcanges-php](https://github.com/zhouaini528/exchanges-php)SDK Websocket做的行情系统。

你可以基于该思路以及设计方案独立出自己的行情价格系统。

该方案是通过搭建websocket server获取数据，存放在redis。业务端可以通过redis实时获取行情数据。

#### 安装

直接下载代码，更新compoer update，可以直接运行代码。

#### websocket server搭建有两种方式。

1：exchanges-php SDK本身支持守护进程例如：

php huobi_server_spot.php start -d
php huobi_client_spot.php start -d

2:采用supervisor管理

配置如下[supervisor.ini](./supervisor.ini)


#### 需要你安装redis 并在config.php配置

配置如下[config.php](./config.php)

#### 最终redis效果如下

binance:depth:0:BTCUSDT

binance:depth:0:BCHUSDT

bitmex:depth:1:XBTH21

bitmex:depth:1:XBTM21

huobi:depth:0:btcusdt

huobi:depth:0:bchusdt

huobi:depth:1:BTC_CQ

huobi:depth:1:BCH_CQ

okex:depth:0:BTC-USDT

okex:depth:0:BCH-USDT

okex:depth:1:BTC-USD-210625

okex:depth:1:BCH-USD-210625

![img](./2.jpg)

redis数据key分4段
1段：交易所名称
2段：数据类型  默认：depth   其他选项：kline_1m  kline_5m   kline_1d
3段：价格类型  0：现货  1：币本位交割  2：usdt永续  3：币本位永续
4段：币对

redis数据value
average_buy:买一价
average_sell:卖一价
average_price:成交价
microtime:更新时间
origin：原始数据保存

