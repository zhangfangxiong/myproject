<?php
$config['database']['default']['master'] = array(
    'dsn' => 'mysql:host=127.0.0.1;dbname=51joying',
    'user' => 'root',
    'pass' => 'xjc.123',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);
$config['database']['default']['salve'] = array(
    'dsn' => 'mysql:host=127.0.0.1;dbname=51joying',
    'user' => 'root',
    'pass' => 'xjc.123',
    'init' => array(
        'SET CHARACTER SET utf8',
        'SET NAMES utf8'
    )
);

$config['cache']['bll'] = array(
    array(
        'host' => '120.24.78.234',
        'port' => 11211
    )
);

// 邮箱服务器配制
// $config["mailer"] = array(
//     'from_email' => 'pancke@163.com',
//     'from_name' => 'pancke',
//     'smtp_host' => 'smtp.163.com',
//     'smtp_user' => 'pancke',
//     'smtp_pass' => 'xjc.123',
//     'smtp_port' => '25',
//     'smtp_secure' => ''
// );

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
        'linkurl' => "http://test.services.ikang.com/ikang-service/rs/cooperation/",
        'preTag' => "_ikang@",
        'onlyCode' => "tk2lx023j435",
        'version' => "0.1",
    ),
    'renai' => array(
        'linkurl' => "http://218.80.216.50:12567/yxxt/services/",
        'preTag' => "SHRATJ",
        'onlyCode' => "SHZYBX001",
        'version' => "Version 1.0",
    ),
    'ruici' => array(
        'linkurl' => "http://crs.rich-healthcare.com:8068/RichHealthService/RichHealthThridInterface.svc",
        'tokenUrl' => "http://crs.rich-healthcare.com:8068/WebToken/TokenCreater.asmx",
        'identityID' => "ZhongYing",
        'password' => "123456",
        'macAddr' => "B8-AE-ED-28-D8-02"
    ),
    'ciming' => array(
        'linkurl' => "http://tjdata.srv.api.test.ciming.com:20010/cimingForExternalTest/ws/resultWs",
        'user' => "testorder",
        'password' => "cmjjktest2016"
    ),
    'meinian' => array(
        'linkurl' => "http://27.115.5.150:20280/api/",
        'appKey' => "20138168",
        'password' => "zhongying@123"
    ),
    'meinian_niandu' => array(
        'linkurl' => "http://27.115.5.150:20280/api/",
        'appKey' => "20138168",
        'password' => "zhongying@123"
    ),
    'meinian_ruzhi' => array(
        'linkurl' => "http://27.115.5.150:20280/api/",
        'appKey' => "20138168",
        'password' => "zhongying@123"
    )
);

return $config;