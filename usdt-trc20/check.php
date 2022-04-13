<?php
require '../includes/common.php';
global $trade_no;
$trade_no = isset($_GET['trade_no']) ? daddslashes($_GET['trade_no']) : exit('No trade_no!');
require PLUGIN_ROOT.'usdt-trc20/inc/inc.php';
checkResult();

@header('Content-Type: text/html; charset=UTF-8');

$row = $DB->getRow("SELECT * FROM pre_order WHERE trade_no='{$trade_no}' LIMIT 1");
$link = $row['return_url'];

if ($row['status'] >= 1) {
    exit('{"code":1,"msg":"付款成功","backurl":"' . $link . '"}');
} else {
    exit('{"code":-1,"msg":"未付款"}');
}
?>