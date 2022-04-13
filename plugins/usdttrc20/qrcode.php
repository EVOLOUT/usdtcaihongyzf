<?php
@header('Content-Type: text/html; charset=UTF-8');
global $trade_no;
$trade_no = TRADE_NO;
require PAY_ROOT.'inc/inc.php';
if (empty($USDT_ADDRESS)) {
    exit('当前支付接口未开启');
}
$row = $DB->getRow("SELECT * FROM pre_order WHERE trade_no='{$trade_no}' LIMIT 1");
if (empty($row)) {
    exit('该订单号不存在，请返回来源地重新发起请求！');
}

$valid = (strtotime($row['addtime']) + $USDT_VALID_TIME) * 1000;
$lock  = sys_get_temp_dir() . '/usdt-trc20_pay_' . $trade_no . '.dat';
if (file_exists($lock)) {
    $rate    = getLatestRate();
    $usdt = file_get_contents($lock);
} else {
    $rate    = getLatestRate();
    $usdt    = round($row['money'] / $rate, 4);
    $addTime = date('Y-m-d H:i:s', strtotime($row['addtime']) - $USDT_VALID_TIME);
    $exist   = $DB->getRow("select * from pre_order where type = '6' and trade_no != '{$row['trade_no']}' and money = '{$row['money']}' and status = 0 and addtime >= '$addTime' order by trade_no desc limit 1");
    if ($exist) {
        $dat  = sys_get_temp_dir() . '/usdt-trc20_pay_' . $exist['trade_no'] . '.dat';
        $usdt = bcadd(file_get_contents($dat), 0.0001, 4);
    }

    file_put_contents($lock, $usdt);
}

include_once SYSTEM_ROOT . 'usdt-trc20/themes.php';
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
    <meta name="renderer" content="webkit">
    <meta name="HandheldFriendly" content="True"/>
    <meta name="MobileOptimized" content="320"/>
    <meta name="format-detection" content="telephone=no"/>
    <link href="//cdn.staticfile.org/twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <link rel="shortcut icon" href="<?php echo $conf['localurl'];?>usdt-trc20/static/img/tether.svg"/>
    <title>
        USDT 在线收银台
    </title>
    <link href="<?php echo $conf['localurl'];?>usdt-trc20/static/css/main.min.css" rel="stylesheet"/>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="icon">
            <img class="logo" src="<?php echo $conf['localurl'];?>usdt-trc20/static/img/tether.svg" alt="logo">
        </div>
        <h1>
            USDT-TRC20支付
        </h1>
        <label>
            <b>转账金额必须为下方显示的金额且需要在倒计时内完成转账，否则无法被系统确认！</b>
        </label>
        <label>
            <b>当前商户设置人民币对USDT汇率为：1:<?php echo $rate;?></b>
        </label>
    </div>
    <div class="content">
        <div class="section">
            <div class="title">
                <h1 class="amount parse-amount" data-clipboard-text="<?= $usdt; ?>" id="usdt">
                    <?= $usdt; ?> <span>USDT</span>
                </h1>
            </div>
            <div class="address parse-action" data-clipboard-text="<?= $USDT_ADDRESS; ?>" id="address">
                <?= $USDT_ADDRESS; ?>
            </div>
            <div class="main">
                <img class="qrcode"
                     src="<?php echo $conf['localurl'];?>usdt-trc20/static/qrcode/index.php?c=<?= $USDT_ADDRESS; ?>"
                     alt="qrcode">
            </div>
                <div id="copyaddress" class="btn btn-default" data-clipboard-text="<?= $USDT_ADDRESS; ?>">复制地址</div>&nbsp;
                <div id="copyamount" class="btn btn-default" data-clipboard-text="<?= $usdt; ?>">复制金额</div>&nbsp;
            <div class="timer">
                <ul class="downcount">
                    <li>
                        <span class="hours">00</span>
                        <p class="hours_ref">时</p>
                    </li>
                    <li class="seperator">:</li>
                    <li>
                        <span class="minutes">00</span>
                        <p class="minutes_ref">分</p>
                    </li>
                    <li class="seperator">:</li>
                    <li>
                        <span class="seconds">00</span>
                        <p class="seconds_ref">秒</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo $conf['localurl'];?>usdt-trc20/static/js/jquery.min.js"></script>
<script src="//lib.baomitu.com/layer/3.1.1/layer.js"></script>
<script src="<?php echo $conf['localurl'];?>usdt-trc20/static/js/clipboard.min.js"></script>
<script>
    $(function () {
        (new Clipboard('#copyamount')).on('success', function (e) {
            layer.msg('金额复制成功');
        });
        (new Clipboard('#copyaddress')).on('success', function (e) {
            layer.msg('地址复制成功');
        });

        // 支付时间倒计时

        function clock() {
            let timeout = new Date(<?=$valid; ?>);
            let now = new Date();
            let ms = timeout.getTime() - now.getTime();//时间差的毫秒数
            let second = Math.round(ms / 1000);
            let minute = Math.floor(second / 60);
            let hour = Math.floor(minute / 60);
            if (ms <= 0) {
                layer.alert("支付超时，请重新发起支付！", {icon: 5});
                return;
            }

            $('.hours').text(hour.toString().padStart(2, '0'));
            $('.minutes').text(minute.toString().padStart(2, '0'));
            $('.seconds').text((second % 60).toString().padStart(2, '0'));

            return setTimeout(clock, 1000);
        }

        setTimeout(clock, 1000);
    });

    // 检查是否支付完成
    function loadMsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "https://<?php echo $_SERVER['HTTP_HOST'];?>/usdt-trc20/check.php",
            timeout: 10000, //ajax请求超时时间10s
            data: {trade_no: "<?php echo $row['trade_no']?>"}, //post数据
            success: function (data, textStatus) {
                //从服务器得到数据，显示数据并继续查询
                if (data.code == 1) {
                    layer.msg('支付成功，正在跳转中...', {icon: 16, shade: 0.1, time: 15000});
                    setTimeout(window.location.href = data.backurl, 1000);
                } else {
                    setTimeout(loadMsg, 5000);
                }
            },
            //Ajax请求超时，继续查询
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                if (textStatus == "timeout") {
                    setTimeout(loadMsg, 1000);
                } else { //异常
                    setTimeout(loadMsg, 4000);
                }
            }
        });
    }

    window.onload = loadMsg();
</script>
</body>
</html>
<?php
$url="http://".$_SERVER['HTTP_HOST']."/usdt-trc20/usdt_cron.php";
$monitor = httpGet($url);
function httpGet($url)
{
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
$header = array(
'User-Agent: Mozilla/5.0 (Linux; Android 7.0; MHA-AL00 Build/HUAWEIMHA-AL00; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/43.0.2357.134 Mobile Safari/537.36',
);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); //设置等待时间
curl_setopt($ch, CURLOPT_TIMEOUT, 1); //设置cURL允许执行的最长秒数
curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$content = curl_exec($ch);
curl_close($ch);
return $content;
if ($monitor = 'yes')
{
    echo '';
}
else{
    echo '';
}
}
?>