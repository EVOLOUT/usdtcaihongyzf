# USDT彩虹易支付插件
这是PhyPay的彩虹易支付插件，支持商户直接TRC20收款到自己账户  
环境要求：网站必须部署在海外服务器  
原理：1.查询汇率采用https://api.coinmarketcap.com/   
      2.查询支付状态采取轮询波场  
安装步骤：  
1.放入文件到彩虹易支付  
2.管理后台-支付接口-添加支付方式 调用值为：usdtrc20  
3.自行修改用户中心源码，能让用户填写USDT地址和汇率即可  
4.更改Nginx和php的超时时间  
