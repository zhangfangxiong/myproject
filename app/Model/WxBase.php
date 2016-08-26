<?php

class Model_WxBase extends Model_Base
{
    /**
     * 接收微信消息
     * @return array
     */
    public static function receiveMsg()
    {
        //接收传送的数据
        $fileContent = file_get_contents("php://input");
        return self::dealxml($fileContent);
    }

    /**
     * 处理XML格式数据，返回数组
     * @param $xXml
     * @return array
     */
    public static function dealxml($xXml)
    {
        //转换为simplexml对象
        $xmlResult = simplexml_load_string($xXml, null, LIBXML_NOCDATA);
        //foreach循环遍历
        $aNode = array();
        foreach ($xmlResult->children() as $childItem) {
            //输出xml节点名称和值
            $item_arr = (array)$childItem;
            $aNode[$childItem->getName()] = $item_arr[0];
        }
        return $aNode;
    }

    /**
     * 远程访问地址
     * @param $url
     * 访问url
     */
    public static function curl($sUrl, $bPost = false, $aData = array(),$host = '',$header = [], $isRedirect = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, true);
        curl_setopt($ch, CURLOPT_URL, $sUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($host)) {
            curl_setopt($ch,CURLOPT_HTTPHEADER,$host);//设置host
        }
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($bPost) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        if ($bPost && !empty($aData)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $aData);
        }
        if (!empty($header)) {
            foreach($header as $key => $value) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $value);
            }

        }

        if ($isRedirect) {
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $content = curl_exec($ch);
        $response = curl_getinfo($ch);
        curl_close($ch);
        return $content;
    }

    /**
     * 下载远程文件到本地
     * @param $file_url
     * @param $save_to
     */
    public static function download_remote_file_with_curl($file_url, $save_to)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, $file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $file_content = curl_exec($ch);
        curl_close($ch);

        $downloaded_file = fopen($save_to, 'w');
        $result = fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);
        return $result;
    }
}