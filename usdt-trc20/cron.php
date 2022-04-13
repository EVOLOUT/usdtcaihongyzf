<?php
require '../includes/common.php';
global $trade_no;
$trade_no = $_GET['trade_no'];
require PLUGIN_ROOT.'usdt-trc20/inc/inc.php';
checkResult(true);