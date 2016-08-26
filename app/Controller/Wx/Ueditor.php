<?php

/**
 * 提供给百度Ueditor编辑上传文件使用
 *  
 *  User: pancke@qq.com
 *  Date: 2015-10-27
 *  Time: 上午9:30:05
 */
class Controller_Index_Ueditor extends Yaf_Controller
{
 
    public function indexAction ()
    {
        $action = $this->getParam('action');
        header("Access-Control-Allow-Origin: *");
        // header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
        
        switch ($action) {
            case 'config':
                $config = $this->config();
                $result = json_encode($config);
                break;
            case 'uploadimage':
            case 'uploadvideo':
                $aConfig = $this->config();
                $result = json_encode($this->uploadimg($aConfig));
                break;
            default:
                $result = json_encode(array(
                    'state' => '请求地址出错'
                ));
        }
        
        $sCallback = $this->getParam('callback');
        if ($sCallback) {
            $this->getResponse()->setBody($sCallback . '(' . $result . ')');
        } else {
            $this->getResponse()->setBody($result);
        }
        
        return false;
    }

    /**
     * 上传视频
     *
     * @param
     *            $aConfig
     * @return array
     */
    public function uploadimg ($aConfig)
    {
        $aReturn = array(
            'state' => 'SUCCESS',
            'url' => '',
            'title' => '',
            'original' => '',
            'type' => '',
            'size' => ''
        );
        
        do {
        
            $sFileField = $aConfig['imageFieldName'];
            if (! isset($_FILES[$sFileField])) {
                $aReturn['state'] = '上传文件为空';
                break;
            }
        
            $sFromURL = $this->getRequest()->getHttpReferer();
            $oStorage = new File_Storage();
            $mResult  = $oStorage->isAllowedDomain($sFromURL);
            
            if (! $mResult) {
                // 当前站点不允许上传到图片服务器
                $aReturn['state'] = '当前站点不允许上传到服务器';
                break;
            }
        
            $sIP = $this->getRequest()->getClientIP();
            $sDomain = Util_Uri::getDomain($sFromURL);
            $aFile = $_FILES[$sFileField];
            list ($iError, $mResult) = $oStorage->saveFile($aFile['name'], $aFile['tmp_name'], $aFile['error'], $aFile['size'], $sIP, $sDomain);
            if ($iError !== 0) {
                $aReturn['state'] = $mResult;
                break;
            }
        
            $sCDNDomain = Yaf_G::getConf('file', 'domain');
            $aReturn['url'] = 'http://' . $sCDNDomain . '/view/' . $mResult['sKey'] . '.' . $mResult['sExt'];
            $aReturn['title'] = basename($aFile['name']);
            $aReturn['original'] = basename($aFile['name']);
            $aReturn['type'] = $mResult['sExt'];
            $aReturn['size'] = $mResult['iSize'];
        } while (false);
        
        return $aReturn;
    }

    public function getStateMap ($p_iError)
    {
        return isset(self::$aSateMap[$p_iError]) ? self::$aSateMap[$p_iError] : self::$stateMap["ERROR_UNKNOWN"];
    }
    
    // 获取配置项
    public function config ()
    {
        $config = array(
            /* 上传图片配置项 */
            "imageActionName" => "uploadimage", /* 执行上传图片的action名称 */
            "imageFieldName" => "upfile", /* 提交的图片表单名称 */
            "imageMaxSize" => 2048000, /* 上传大小限制，单位B */
            "imageAllowFiles" => array(
                ".png",
                ".jpg",
                ".jpeg",
                ".gif"
            ), /* 上传图片格式显示 */
            "imageCompressEnable" => true, /* 是否压缩图片,默认是true */
            "imageCompressBorder" => 1600, /* 图片压缩最长边限制 */
            "imageInsertAlign" => "none", /* 插入的图片浮动方式 */
            "imageUrlPrefix" => "", /* 图片访问路径前缀 */
            "imagePathFormat" => "",

            /* 涂鸦图片上传配置项 */
            "scrawlActionName" => "uploadscrawl", /* 执行上传涂鸦的action名称 */
            "scrawlFieldName" => "upfile", /* 提交的图片表单名称 */
            "scrawlPathFormat" => "", /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "scrawlMaxSize" => 2048000, /* 上传大小限制，单位B */
            "scrawlUrlPrefix" => "", /* 图片访问路径前缀 */
            "scrawlInsertAlign" => "none",

            /* 截图工具上传 */
            "snapscreenActionName" => "uploadimage", /* 执行上传截图的action名称 */
            "snapscreenPathFormat" => "", /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "snapscreenUrlPrefix" => "", /* 图片访问路径前缀 */
            "snapscreenInsertAlign" => "none", /* 插入的图片浮动方式 */

            /* 抓取远程图片配置 */
            // "catcherLocalDomain" => ["127.0.0.1", "localhost", "img.baidu.com"],
            // "catcherActionName" => "catchimage", /* 执行抓取远程图片的action名称 */
            // "catcherFieldName" => "source", /* 提交的图片列表表单名称 */
            // "catcherPathFormat" => "", /* 上传保存路径,可以自定义保存路径和文件名格式 */
            // "catcherUrlPrefix" => "", /* 图片访问路径前缀 */
            // "catcherMaxSize" => 2048000, /* 上传大小限制，单位B */
            // "catcherAllowFiles" => array(".png", ".jpg", ".jpeg", ".gif", ".bmp"), /* 抓取图片格式显示 */
            
            /* 上传视频配置 */
            "videoActionName" => "uploadvideo", /* 执行上传视频的action名称 */
            "videoFieldName" => "upfile", /* 提交的视频表单名称 */
            "videoPathFormat" => "", /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "videoUrlPrefix" => "", /* 视频访问路径前缀 */
            "videoMaxSize" => 102400000, /* 上传大小限制，单位B，默认100MB */
            "videoAllowFiles" => [
                ".flv",
                ".swf",
                ".mp4"
            ],
            // ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
            // ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid"], /* 上传视频格式显示 */
            
            /* 上传文件配置 */
            "fileActionName" => "uploadfile", /* controller里,执行上传视频的action名称 */
            "fileFieldName" => "upfile", /* 提交的文件表单名称 */
            "filePathFormat" => "", /* 上传保存路径,可以自定义保存路径和文件名格式 */
            "fileUrlPrefix" => "", /* 文件访问路径前缀 */
            "fileMaxSize" => 51200000, /* 上传大小限制，单位B，默认50MB */
            "fileAllowFiles" => [
                ".png",
                ".jpg",
                ".jpeg",
                ".gif",
                ".flv",
                ".swf",
                ".mp4"
            ]
        
        // ".png", ".jpg", ".jpeg", ".gif", //".bmp",
        // ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
        // ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
        // ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
        // ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"
        // ), /* 上传文件格式显示 */
                )        

        /* 列出指定目录下的图片 */
        // "imageManagerActionName" => "listimage", /* 执行图片管理的action名称 */
        // "imageManagerListPath" => "/ueditor/php/upload/image/", /* 指定要列出图片的目录 */
        // "imageManagerListSize" => 20, /* 每次列出文件数量 */
        // "imageManagerUrlPrefix" => "", /* 图片访问路径前缀 */
        // "imageManagerInsertAlign" => "none", /* 插入的图片浮动方式 */
        // "imageManagerAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp"], /* 列出的文件类型 */
        
        /* 列出指定目录下的文件 */
        // "fileManagerActionName" => "listfile", /* 执行文件管理的action名称 */
        // "fileManagerListPath" => "/ueditor/php/upload/file/", /* 指定要列出文件的目录 */
        // "fileManagerUrlPrefix" => "", /* 文件访问路径前缀 */
        // "fileManagerListSize" => 20, /* 每次列出文件数量 */
        // "fileManagerAllowFiles" => array(
        // ".png", ".jpg", ".jpeg", ".gif", ".bmp",
        // ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
        // ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
        // ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
        // ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"
        // ), /* 列出的文件类型 */
        ;
        
        return $config;
    }

    static $aSateMap = array(
        "SUCCESS", // 上传成功标记，在UEditor中内不可改变，否则flash判断会出错
        "文件上传出错",
        "临时文件错误",
        "文件大小超出网站限制",
        "文件类型不允许",
        "文件保存时出错",
        "ERROR_UNKNOWN" => "未知错误"
    );
}