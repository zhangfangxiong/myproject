<?php
$config['file']['sStorageDir'] = '/data/www/dfs/raw';

$config['image']['aWaterMarkPath'] = array(
    0 => APP_PATH . '/../conf/watermark.png'
);

$config['image']['sDefaultWaterMarkPath'] = $config['image']['aWaterMarkPath'][0];

$config['image']['aWaterMarkPosition'] = array(
    1 => 'bottom-left',
    2 => 'bottom-right',
    3 => 'bottom-middle',
    4 => 'top-left',
    5 => 'top-right',
    6 => 'top-middle'
);

$config['image']['aNeedWaterMarkSize'] = array(
		'bNeed' => false,
		'iWidth'  => 241,
		'iHeight' => 241
);
$config['image']['aDimension'] = array(
    /*
    '54x80' => array(
        'width' => 54,
        'height' => 80
    ),
    '213x160' => array(
        'width' => 213,
        'height' => 160,
        'waterMark' => false
    )
    */
);

// 图片类型
$config['file']['aImageType'] = array(
    'gif',
    'jpg',
    'jpeg',
    'png',
    'webp',
    // 'xls',
    // 'xltx'
);

// 文件系统支持的文件格式
$config['file']['aAllowedType'] = array(
    'gif',
    'jpg',
    'jpeg',
    'png',
    'pdf',
    'doc',
    'docx',
    "flv",
    "swf",
    "mp4",
    'webp',
    'zip',
    'xls',
    'xlsx',
);

// 文件系统支持的文件格式
$config['file']['aAllowedViewType'] = $config['file']['aAllowedType'];
$config['file']['aAllowedDownloadType'] = $config['file']['aAllowedType'];

// 文件系统支持的大小
$config['file']['aAllowedSize'] = array(
    'iMin' => 1,
    'iMax' => 15728640
);

// 文件系统开放的域名
$config['file']['aAllowedDomain'] = array(
    '/' . preg_quote(strstr(ENV_DOMAIN, ':', true)) . '/'
);

return $config;