<?php
$uid = $DB->getColumn("SELECT uid FROM pre_order WHERE trade_no='$trade_no' LIMIT 1");
$USDT_ADDRESS = $DB->getColumn("SELECT usdt_trc20 FROM pre_user WHERE uid='$uid' LIMIT 1");
$USDT_RATE = $DB->getColumn("SELECT usdt_rate FROM pre_user WHERE uid='$uid' LIMIT 1");
$USDT_VALID_TIME = 900;

/**
 * 支付结果 回调检测
 * @param bool $output
 */
function checkResult(bool $output = false)
{
    global $DB, $USDT_VALID_TIME;
    $list = getTransferInList();
    if (empty($list)) {

        return;
    }

    $date = date('Y-m-d H:i:s');
    $add  = date('Y-m-d H:i:s', time() - $USDT_VALID_TIME);
    $rows = $DB->query("select * from pre_order where status = 0 and channel = '11' and addtime >='$add'");
    while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
        foreach ($list as $item) {
            $lock = sys_get_temp_dir() . '/usdt-trc20_pay_' . $row['trade_no'] . '.dat';
            if (!file_exists($lock)) {
                continue;
            }

            $money = file_get_contents($lock);
            if ($money == $item['money'] && $item['time'] >= strtotime($row['addtime'])) {
                // 支付验证成功
                $trade_no = $row['trade_no'];
                if ($DB->exec("UPDATE `pre_order` SET `status` ='1',`getmoney` ='0' WHERE `trade_no`='$trade_no'")) {
                    $DB->exec("UPDATE `pre_order` SET `endtime` ='$date',`api_trade_no` ='{$item['trade_id']}' WHERE `trade_no`='$trade_no'");
                }

                if ($output) {

                    echo "USDT-TRC20 收款成功";
                }

                break;
            }
        }
    }


    if ($output) {

        echo "USDT-TRC20 回调检测成功";
    }
}

/**
 * 获取最近3小时内 usdt-trc20 所有收入转账
 */
function getTransferInList(): array
{
    global $USDT_ADDRESS;

    $result = [];
    $end    = time() * 1000;
    $start  = strtotime('-3 hour') * 1000;
    $params = [
        'limit'           => 300,
        'start'           => 0,
        'direction'       => 'in',
        'relatedAddress'  => $USDT_ADDRESS,
        'start_timestamp' => $start,
        'end_timestamp'   => $end,
    ];
    $api    = "https://apilist.tronscan.org/api/token_trc20/transfers?" . http_build_query($params);
    $resp   = get_curl($api);
    $data   = json_decode($resp, true);

    if (empty($data)) {

        return $result;
    }


    foreach ($data['token_transfers'] as $transfer) {
        if ($transfer['to_address'] == $USDT_ADDRESS && $transfer['finalResult'] == 'SUCCESS') {
            $result[] = [
                'time'     => $transfer['block_ts'] / 1000,
                'money'    => $transfer['quant'] / 1000000,
                'trade_id' => $transfer['transaction_id'],
            ];
        }
    }

    return $result;
}

/**
 * 获取最新USDT最新CNY汇率
 * @return float
 */
function getLatestRate(): float
{
    global $USDT_RATE;
    if ($USDT_RATE) {
        return $USDT_RATE;
    }
    $api    = 'https://api.coinmarketcap.com/data-api/v3/cryptocurrency/detail/chart?id=825&range=1H&convertId=2787';
    $resp   = get_curl($api);
    $data   = json_decode($resp, true);
    $points = $data['data']['points'];
    $point  = array_pop($points);

    return floatval($point['c'][0]);
}