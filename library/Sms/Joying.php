<?php

/**
 * 51joying专用短信通道
 * @author len
 *
 */

class Sms_Joying
{
    const LASTCONTENT = '中盈公司';//结束语
    const SENDBATCHMESSAGEURL = 'http://www.jianzhou.sh.cn/JianzhouSMSWSServer/http/sendBatchMessage';//普通短信
    const SENDAUDIOURL = 'http://www.jianzhou.sh.cn/JianzhouSMSWSServer/http/sendAudio';//语音短信
    const SENDPERSONALMESSAGESURL = 'http://www.jianzhou.sh.cn/JianzhouSMSWSServer/http/sendPersonalMessages';//个性化短信
    const GETUSERINFOURL = 'http://www.jianzhou.sh.cn/JianzhouSMSWSServer/http/getUserInfo';//获取余额
    const MODIFYPASSWORDURL = 'http://www.jianzhou.sh.cn/JianzhouSMSWSServer/http/modifyPassword';//修改密码(暂时不弄)
    const VALIDATEUSERURL = 'http://www.jianzhou.sh.cn/JianzhouSMSWSServer/http/validateUser';//验证密码

    //获取账号配置信息
    public static function getAccountInfo()
    {
        $aAccount = Yaf_G::getConf('aJoyingSms');
        return $aAccount;
    }

    /**
     * 获取余额
     * @param $sMobile 目标号码,号码与号码之间用 英文; 号分割
     * @param $sContent 需要发送的短信内容。 采用UTF-8编码。
     * @param $sTime 格式如20130201120000,14位长度,非必要参数,即时短信请留空
     * @return mixed
     */
    public static function getUserInfo()
    {
        $aAccount = self::getAccountInfo();
        $post_data = array(
            "account" => $aAccount['account'],
            "password" => $aAccount['password'],
        );
        $post_data = http_build_query($post_data);
        return self::curl(self::GETUSERINFOURL, true, $post_data);
    }

    /**
     * 验证密码
     * @return mixed
     */
    public static function validateUser()
    {
        $aAccount = self::getAccountInfo();
        $post_data = array(
            "account" => $aAccount['account'],
            "password" => $aAccount['password'],
        );
        $post_data = http_build_query($post_data);
        return self::curl(self::VALIDATEUSERURL, true, $post_data);
    }

    /**
     * 发送普通短信
     * @param $sMobile 目标号码,号码与号码之间用 英文; 号分割
     * @param $sContent 需要发送的短信内容。 采用UTF-8编码。
     * @param $sTime 格式如20130201120000,14位长度,非必要参数,即时短信请留空
     * @return mixed
     */
    public static function sendBatch($sMobile,$sContent,$sTime = '')
    {
        $aAccount = self::getAccountInfo();
        $post_data = array(
            "account" => $aAccount['account'],
            "password" => $aAccount['password'],
            "destmobile" => $sMobile,
            "msgText" => $sContent.'【'.self::LASTCONTENT.'】',
            "sendDateTime" => $sTime
        );
        $post_data = http_build_query($post_data);
        return self::curl(self::SENDBATCHMESSAGEURL, true, $post_data);
    }

    /**
     * 发送语音短信
     * @param $sMobile 目标号码,号码与号码之间用 英文; 号分割
     * @param $sContent 需要发送的短信内容。 采用UTF-8编码。
     * @param $sTime 格式如20130201120000,14位长度,非必要参数,即时短信请留空
     * @return mixed
     */
    public static function sendAudio($sMobile,$sContent,$sTime = '')
    {
        $aAccount = self::getAccountInfo();
        $post_data = array(
            "account" => $aAccount['account'],
            "password" => $aAccount['password'],
            "destmobile" => $sMobile,
            "msgText" => $sContent,
            "sendDateTime" => $sTime
        );
        $post_data = http_build_query($post_data);
        return self::curl(self::SENDAUDIOURL, true, $post_data);
    }

    /**
     * 个性化短信
     * @param $sMobile 目标号码,1.多个手机号码用||分割2.建议一次最多提交3000左右的号码
     * @param $sContent 需要发送的短信内容。 短消息内容,多个内容用||分隔, UTF-8编码号码和内容||分隔数量必须相等相同内容禁止使用此方法，请使用普通短信提交方法
     * @param $sTime 格式如20130201120000,14位长度,非必要参数,即时短信请留空
     * @return mixed
     */
    public static function sendPersonalMessages($sMobile,$sContent)
    {
        $aAccount = self::getAccountInfo();
        $post_data = array(
            "account" => $aAccount['account'],
            "password" => $aAccount['password'],
            "destMobiles" => $sMobile,
            "msgContents" => $sContent,
        );
        $headers = ['Content-Type: application/x-www-form-urlencoded; charset=utf-8'];
        return self::curl(self::SENDPERSONALMESSAGESURL, true, $post_data);
    }

    /**
     * 远程访问地址
     * @param $url
     * 访问url
     */
    public static function curl($sUrl, $bPost = false, $aData = array(), $host = '', $header = [], $isRedirect = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, true);
        curl_setopt($ch, CURLOPT_URL, $sUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($host)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $host);//设置host
        }
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($bPost) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        if ($bPost && !empty($aData)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $aData);
        }
        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
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
}