<?php
ini_set('date.timezone','Asia/Shanghai');

define('ENV_CHANNEL', 'www');
define('ENV_DOMAIN', $_SERVER['HTTP_HOST']);
if ($_SERVER['SERVER_ADDR'] == '120.24.78.234') {
    define('ENV_SCENE', 'dev');
} else {
    define('ENV_SCENE', 'ga');
}

if (ENV_SCENE == 'dev') {
    //报告运行时错误
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
}

define('ENV_DOMAIN', $_SERVER['HTTP_HOST']);
define("APP_PATH", realpath(__DIR__ . '/../app'));
define("LIB_PATH", realpath(__DIR__ . '/../library'));

try {
    require_once LIB_PATH . '/loader.php';
    $app = new Yaf_Application();
    $app->bootstrap()->run();
} catch (Exception $e) {
    echo $e->getMessage();
}
