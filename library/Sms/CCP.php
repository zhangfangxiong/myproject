<?php

/**
 * 运通讯SDK
 * @author len
 *
 */
require_once LIB_PATH . '/Sms/CCP/SDK/CCPRestSDK.php';

class Sms_CCP
{

    /**
     * 初始化REST SDK
     * @return Ambigous <NULL, unknown>
     */
    public static function getRest()
    {
        $aConf = Util_Common::getConf('CCP');
        global $accountSid, $accountToken, $appId, $serverIP, $serverPort, $softVersion;
        $rest = new REST($aConf['host'], $aConf['port'], $aConf['version']);
        $rest->setAccount($aConf['sid'], $aConf['token']);
        $rest->setAppId($aConf['appid']);
        
        return $rest;
    }
    
    /**
     * 发送模板短信
     *
     * @param
     *            to 手机号码集合,用英文逗号分开
     * @param
     *            datas 内容数据 格式为数组 例如：array('Marry','Alon')，如不需替换请填 null
     * @param $tempId 模板Id            
     */
    public static function sendTemplateSMS ($to, $datas, $tempId)
    {
        $aRet = array();
        $rest = self::getRest();

        // 发送模板短信
        // echo "Sending TemplateSMS to $to <br/>";
        $result = $rest->sendTemplateSMS($to, $datas, $tempId);
        if ($result == NULL) {
            $aRet['data'] = "result error!";
            $aRet['status'] = false;
        } else {
            if ($result->statusCode != 0) {
                $aRet['data'] =  "error code :" . $result->statusCode . "<br>";
                $aRet['data'] .=  "error msg :" . $result->statusMsg . "<br>";
                $aRet['status'] = false;
            } else {
                // echo "Sendind TemplateSMS success!<br/>";
                // 获取返回信息
                $smsmessage = $result->TemplateSMS;
                $aRet['data'] = "dateCreated:" . $smsmessage->dateCreated . "<br/>";
                $aRet['data'] .= "smsMessageSid:" . $smsmessage->smsMessageSid . "<br/>";
                $aRet['status'] = true;
            }
        }
        
        return $aRet;
    }

    /**
     * 语音验证码
     *
     * @param
     *            verifyCode 验证码内容，为数字和英文字母，不区分大小写，长度4-8位
     * @param
     *            playTimes 播放次数，1－3次
     * @param
     *            to 接收号码
     * @param
     *            displayNum 显示的主叫号码
     * @param
     *            respUrl 语音验证码状态通知回调地址，云通讯平台将向该Url地址发送呼叫结果通知
     * @param
     *            lang 语言类型。取值en（英文）、zh（中文），默认值zh。
     * @param
     *            userData 第三方私有数据
     */
    public static function voiceVerify ($verifyCode, $to, $playTimes = 2, $displayNum = null, $respUrl = null, $lang ='zh', $userData = null)
    {
        $aRet = array();
        $rest = self::getRest();
        
        // 调用语音验证码接口
        //echo "Try to make a voiceverify,called is $to <br/>";
        $result = $rest->voiceVerify($verifyCode, $playTimes, $to, $displayNum, $respUrl, $lang, $userData);
        if ($result == NULL) {
            $aRet['data'] = "result error!";
            $aRet['status'] = false;
        } else {
            if ($result->statusCode != 0) {
                $aRet['data'] =  "error code :" . $result->statusCode . "<br>";
                $aRet['data'] .=  "error msg :" . $result->statusMsg . "<br>";
                $aRet['status'] = false;
            } else {
                //echo "voiceverify success!<br>";
                // 获取返回信息
                $voiceVerify = $result->VoiceVerify;
                $aRet['data'] = "callSid:" . $voiceVerify->callSid . "<br/>";
                $aRet['data'] .= "dateCreated:" . $voiceVerify->dateCreated . "<br/>";
                $aRet['status'] = true;
            }
        }
        
        return $aRet;
    }
}