<?php
$config['database']['default']['master'] = array(
    'dsn' => 'mysql:host=10.24.26.127;dbname=51joying',
    'user' => 'www',
    'pass' => '51joying',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);
$config['database']['default']['salve'] = array(
    'dsn' => 'mysql:host=10.24.26.127;dbname=51joying',
    'user' => 'www',
    'pass' => '51joying',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

$config['cache']['bll'] = array(
    array(
        'host' => '127.0.0.1',
        'port' => 11211
    )
);

$config["mailer"] = array(
    'from_email' => 'test01@joying-insurance.com',
    'from_name' => '51joying',
    'smtp_host' => 'smtp.muchost.com',
    'smtp_user' => 'test01@joying-insurance.com',
    'smtp_pass' => 'Abc@1234',
    'smtp_port' => '25',
    'smtp_secure' => ''
);

// 云通讯配制
$config['CCP'] = array(
    'host' => 'app.cloopen.com',
    // 'host' => 'sandboxapp.cloopen.com',
    'port' => '8883',
    'version' => '2013-12-26',
    'sid' => 'aaf98f89544cd9d90154854b52e5369c',
    'token' => '2d584f21c13e48129b4b8e58909bb736',
    'appid' => '8a216da85577a5cc0155806d75bd0bf5'
);

//供应商接口地址配置
$config['supplier'] = array(
    'ikang' => array(
        'linkurl' => "https://services.health.ikang.com/ikang-service/rs/cooperation/",
        'preTag' => "_ikang@",
        'onlyCode' => "zzweofjwoi8347e",
        'version' => "0.1",
    ),
    'renai' => array(
        'linkurl' => "http://218.80.216.50:12567/yxxt/services/",
        'preTag' => "SHRATJ",
        'onlyCode' => "SHZYBX001",
        'version' => "Version 1.0",
    ),
    'ruici' => array(
        'linkurl' => "http://zj.rayelink.com:8005/RichHealthThridInterface.svc",
        'tokenUrl' => "http://zj.rayelink.com:8004/TokenCreater.asmx",
        'identityID' => "ZhongYing1",
        'password' => "dFwvF6SxKoqMdynF",
        'macAddr' => "B8-AE-ED-28-D8-02"
    ),
    'ciming' => array(
        'linkurl' => "http://tjdata.srv.api.ciming.com:20010/cimingForExternal/ws/resultWs",
        'user' => "ciicnew",
        'password' => "cmjjk2016"
    ),
    'meinian' => array(
        'linkurl' => "http://api.health-100.cn/StandardService/api/",
        'appKey' => "20138168",
        'password' => "zhongying@123"
    ),
    'meinian_niandu' => array(
        'linkurl' => "http://api.health-100.cn/StandardService/api/",
        'appKey' => "20138168",
        'password' => "2015@)!%zhiying"
    ),
    'meinian_ruzhi' => array(
        'linkurl' => "http://api.health-100.cn/StandardService/api/",
        'appKey' => "20153530",
        'password' => "zhiying@)!%3530"
    )

);

return $config;