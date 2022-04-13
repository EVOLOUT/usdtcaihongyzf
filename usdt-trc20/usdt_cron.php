<?php
ignore_user_abort();//
set_time_limit(0);//
$interval=3;//
$trade_no = $_GET['trade_no'];
do{
    $yz = file_get_contents("http://".$_SERVER['HTTP_HOST']."/usdt-trc20/cron.php?trade_no=$trade_no");//
    if ($yz == "USDT-TRC20 收款成功") {
        echo "yes";//
        exit();
    }else{
        $sc = $yz;//
    }
sleep($interval);//
}while(true);//
?>
