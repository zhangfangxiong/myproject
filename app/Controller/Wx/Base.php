<?php

/**
 * 微信前端base文件
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 16/04/08
 * Time: 下午2:32
 */
class Controller_Wx_Base extends Yaf_Controller
{
    protected $aUser = [];//当前用户
    protected $iCurrUserID = 0;
    protected $sCurrOpenID = '';
    protected $sCompanyName = 'registers';//用户所属公司的用户名，定死的
    protected $sRedirectAction = 'bindidcard';//这个action名不能用
    protected $aCreateUser = [];//默认创建人用户信息

    protected $AccessToken = "";//这个应该存到memcache里面
    private static $_appid = "";
    private static $_appsecret = "";
    private static $_token = "";

    const ACCESSTOKENURL = "https://api.weixin.qq.com/cgi-bin/token";//获取TOKEN接口
    const GETWEIXINIPURL = "https://api.weixin.qq.com/cgi-bin/getcallbackip";//获取IP接口
    const GETTICKETURL = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';//获取js的ticket接口
    const WEIXINKEY = 'wx';//微信信息存缓存的前缀
    const JSONTYPE = 'jsapi';
    const HTTPHOST = '120.24.78.234:88';//当前httphost

    protected $aPriceKey = [
        1 => 'iManPrice',
        2 => 'iWomanPrice1',
        3 => 'iWomanPrice2'
    ];

    protected function _assignUrl()
    {
        //购买相关
        $this->assign('sVerifyUrl', '/wx/verify/image/');
        $this->assign('sIndexUrl', '/wx/');
        $this->assign('sListUrl', '/wx/list/');
        $this->assign('sDetailUrl', '/wx/detail/');
        $this->assign('sCartlistUrl', '/wx/cartlist/');
        $this->assign('sAddCartUrl', '/wx/addcart/');
        $this->assign('sDeleteCartUrl', '/wx/deletecart/');
        $this->assign('sBalanceUrl', '/wx/balance/');
        $this->assign('sBalanceValidateUrl', '/wx/balancevalidate/');
        $this->assign('sBalancePostUrl', '/wx/balancepost/');
        $this->assign('sPayUrl', '/wx/pay/');
        $this->assign('sGetRegionUrl', '/wx/getregion/');
        $this->assign('sStoreUrl', '/wx/store/');
        $this->assign('sGetStoreUrl', '/wx/getstore/');
        //预约相关
        $this->assign('sAppointmentDetailUrl', '/wx/appointment/detail/');
        $this->assign('sAjaxCheckCardUrl', '/wx/ajax/ajaxcheckcard/');
        $this->assign('sVerifyCheckUrl', '/wx/ajax/ajaxcheckcardverify/');
        $this->assign('sCardlistUrl', '/wx/appointment/cardlist/');
        $this->assign('sAddCardUrl', '/wx/appointment/addcard/');
        $this->assign('sReserveStoreUrl', '/wx/appointment/reservestore/');
        $this->assign('sReserveCommitUrl', '/wx/appointment/reservecommit/');
        $this->assign('sGetStoreListUrl', '/wx/getstoreList/');
        $this->assign('sUserInfoEditUrl', '/wx/appointment/userInfoEdit/');
        $this->assign('sMapUrl', '/wx/appointment/map/');
        $this->assign('sReserveDetailUrl', '/wx/appointment/reservedetail/');
        $this->assign('sReserveCancelUrl', '/wx/appointment/reservecancel/');
        $this->assign('sStoreListUrl', '/wx/appointment/storelist/');
        $this->assign('sUpgradeDetailUrl', '/wx/appointment/upgradedetail/');
        $this->assign('sGetReserveDateUrl', '/wx/appointment/getreservedate/');
        //个人信息相关
        $this->assign('sOrderListUrl', '/wx/orderlist/');
        $this->assign('sOrderDetailUrl', '/wx/orderdetail/');
        $this->assign('sCancelOrderUrl', '/wx/cancelorder/');
        $this->assign('sBindPhoneUrl', '/wx/account/bindphone/');
        $this->assign('sBindIdCardUrl', '/wx/account/bindidcard/');
        $this->assign('sUserInfoUrl', '/wx/account/userinfo/');
        $this->assign('sAccountUserInfoUrl', '/wx/account/userinfoedit/');
        $this->assign('sAjaxCheckBindphoneUrl', '/wx/ajax/ajaxcheckbindphone/');
        $this->assign('sSendSmsUrl', '/wx/verify/sms/');
        //支付相关
        $this->assign('sWeixinPayUrl', '/payment/weixin/pay/');
        $this->assign('sPayCheckUrl', '/payment/pay/check/');
        $this->assign('sPaySuccessUrl', '/payment/pay/success/');
        $this->assign('sPayFailUrl', '/payment/pay/fail/');
        $this->assign('sWxjsPayUrl', '/payment/wxjs/pay/');//js支付接口
        $this->assign('sWxjsUpgradePayUrl', '/payment/wxjs/upgradepay/');
        //$this->assign('sWxjsPayUrl', '/payment/wxjs/paytest/');//js支付测试接口
        $this->assign('sPayPostUrl', '/wx/paypost/');//js支付接口
        //静态页面
        $this->assign('sAboutUrl', '/wx/about/');//关于我们
        $this->assign('sPartnerUrl', '/wx/partner/');//合作伙伴
    }

    /**
     * 获取hr用户所能看到的产品
     * @param int $iPage
     */
    protected function getHrCanViewProduct()
    {
        $aCreateUser = $this->getCreatUser();
        if ($this->aUser['iCreateUserID'] == $aCreateUser['iUserID']) {
            return [];
        }
        $aProductList = Model_Product::getAllUserProduct($this->aUser['iCreateUserID'], 2, $this->aUser['iChannel']);
        return $aProductList;
    }

    /**
     * 获取所有用户所能看到的产品
     * @param int $iPage
     * @param $sProductID (排除hr能看到的产品)
     * @return mixed
     */
    public function getAllUserCanViewProduct($iPage = 1, $sProductID)
    {
        $aCreateUser = $this->getCreatUser();
        $aProductList = Model_Product::getUserViewProductList($aCreateUser['iCreateUserID'], 2, $aCreateUser['iChannel'], $iPage, false, $sProductID);
        return $aProductList;
    }

    /**
     * 获取默认创建人用户
     */
    protected function getCreatUser()
    {
        if (empty($this->aCreateUser)) {
            $this->aCreateUser = Model_User::getUserByUserName($this->sCompanyName, 2);
        }
        return $this->aCreateUser;
    }

    /**
     * 执行动作之前
     */
    public function actionBefore()
    {
        //微信配置项设置
        $this->_WxConfig();
        //微信授权
        $this->_initialize();
        //微信js配置
        $this->getSignPackage();

        $this->_assignUrl();
        $this->iCurrUserID = $this->aUser['iUserID'];
        $aUser = Model_Customer::getDetail($this->aUser['iUserID']);

        //加入创建人和渠道信息
        $aCreateUser = Model_User::getDetail($aUser['iCreateUserID']);
        if ($aUser) {
            $this->aUser['iCreateUserID'] = $aCreateUser['iUserID'];
            $this->aUser['iChannel'] = $aCreateUser['iChannel'];
            $this->aUser['iSex'] = $aUser['iSex'] != 1 ? ($aUser['iSex'] == 2 && $aUser['iMarriage'] == 1) ? 2 : 3 : 1;
        }

        $this->assign('aUser', $aUser);
        $this->assign('sStaticRoot', 'http://' . Yaf_G::getConf('static', 'domain'));
        $this->assign('aCommonLang', Model_Lang::getCommonLang());
        $this->assign('aMenu', Model_Lang::getWxMenu());
        $this->assign('iHomeIcon', 1);
        $this->assign('sTitle', 'error');
    }

    /**
     * 执行动作之后
     */
    public function actionAfter()
    {
        $this->_frame = 'wx.phtml';
    }

    //微信基础配置
    public function _WxConfig()
    {
        $aWxConfig = Yaf_G::getConf('wxConfig', null, 'common');
        $sCurrHost = $_SERVER['HTTP_HOST'];
        $aConfig = empty($aWxConfig[$sCurrHost]) ? $aWxConfig[self::HTTPHOST] : $aWxConfig[$sCurrHost];
        self::$_appid = $aConfig['APPID'];
        self::$_appsecret = $aConfig['APPSECRET'];
        self::$_token = $aConfig['TOKEN'];
    }

    /**
     * 微信授权（步骤）
     * 1，获取cookie
     * 2，如果cookie存在，通过cookie获取到openid，调用微信个人信息接口，合并用户信息
     * 3，如果cookie不存在，调用微信授权接口，获取到openid
     *      1，通过openid获取表中是否有用户数据，没有的话，跳绑定手机页面，绑定手机，插入一条数据或更新原有数据
     *      2，如果有用户数据，获取表中用户数据
     *      3，用户数据加入cookie
     *      4，调用微信个人信息接口，合并用户信息
     */
    protected function _initialize()
    {
        if ($this->checkSignature()) {
            $echostr = $this->getParam('echostr');
            if ($echostr !== null) {
                //第一次配置的基础验证
                echo $echostr;
                die;
            }
        }
        $this->aUser = Util_Cookie::get(Yaf_G::getConf('wxuserkey', 'cookie'));

        //$this->aUser = [];

//        $this->aUser['iUserID'] = 83;//测试用户
//        $this->aUser['sUserName'] = 'C-41872564';
//        $this->aUser['sOpenID'] = 'o5nrtw4lbOFJJ9XIOgdIMjODDhPs';


        if (empty($this->aUser)) {
            $aParam = $this->getParams();
            if (!empty($aParam['idcard']) && !empty($aParam['realname'])) {//卡绑定的post过程

            } elseif (isset($aParam['state']) && isset($aParam['code']) && Model_WxUser::STATE == $aParam['state']) {
                $aParams['appid'] = self::$_appid;
                $aParams['secret'] = self::$_appsecret;
                $aParams['code'] = $aParam['code'];
                //通过code获取openid
                $aData = Model_WxUser::getAccessTokenByCode($aParams);
                if (isset($aData['errcode']) && $aData['errcode'] > 0) {
                    return $this->show404($aData['errmsg'], false);
                }
                $this->sCurrOpenID = $aData['openid'];
                //通过openid获取用户信息
                $aUser = Model_Customer::getUserByOpenID($this->sCurrOpenID);
                if (empty($aUser)) {
                    if ($this->sRedirectAction != $this->_request->getActionName()) {// 跳转到绑定身份证页面
                        return $this->redirect('/wx/account/bindidcard/');
                    }
                } else {
                    $this->aUser['iUserID'] = $aUser['iUserID'];
                    $this->aUser['sOpenID'] = $this->sCurrOpenID;
                    $this->aUser['sUserName'] = $aUser['sUserName'];
                    //设置cookie
                    Util_Cookie::set(Yaf_G::getConf('wxuserkey', 'cookie'), $this->aUser);
                }
                //echo 111;die;
            } else {
                //获取微信授权code
                $aParam['appid'] = self::$_appid;
                $aParam['redirect_uri'] = $this->getCurrUrl();
                Model_WxUser::oauth($aParam);
            }
        }
    }

    /**
     * @return bool
     * 验证配置信息是否正确
     */
    protected function checkSignature()
    {
        $signature = $this->getParam("signature");
        $timestamp = $this->getParam("timestamp");
        $nonce = $this->getParam("nonce");
        $token = self::$_token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取AccessToken
     * @return bool|string
     */
    protected static function getAccessToken()
    {
        $sTokenKey = self::WEIXINKEY . "_" . $_SERVER['HTTP_HOST'] . "_AccessToken";
        $oCache = Util_Common::getCache();
        $sAccesstoken = $oCache->get($sTokenKey);
        if (empty($sAccesstoken)) {
            $tokenUrl = self::ACCESSTOKENURL;
            $tokenParamArr['appid'] = self::$_appid;
            $tokenParamArr['secret'] = self::$_appsecret;
            $tokenParamArr['grant_type'] = 'client_credential';
            $tokenUrl .= "?" . http_build_query($tokenParamArr);
            $data = Model_WxBase::curl($tokenUrl, false);
            $data = json_decode($data, true);
            $oCache->set($sTokenKey, $data['access_token'], 7000);
            $sAccesstoken = $data['access_token'];
        }
        return $sAccesstoken;
    }

    /**
     * 获取微信服务器IP地址
     * @return mixed
     */
    protected static function getWeixinIp()
    {
        $sUrl = self::GETWEIXINIPURL;
        $sAccessToken = self::getAccessToken();
        $sUrl .= "?access_token=" . $sAccessToken;
        $sData = Model_WxBase::curl($sUrl, false);

        return $sData;
    }

    /**
     * 获取当前URL
     * @param bool $isShare 是否是分享链接(默认所有分享出去的链接token变成当前人的token)
     * @param string $resourse 分享砍价链接需要resource参数
     */
    protected function getCurrUrl($isShare = false, $resourse = '')
    {
        $sCurrUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        if ($_SERVER['HTTP_HOST'] == 'm.weixin2.dev.ipo.com') {
            //$sCurrUrl = 'http://testwx.fangjiadp.com/' . $_SERVER['REQUEST_URI'];
        }
        if ($isShare) {
            $aCurrUrl = parse_url($sCurrUrl);
            if (!empty($aCurrUrl['query'])) {
                $sQuery = $aCurrUrl['query'];
                parse_str($sQuery, $aQuery);
                if (!empty($aQuery['token'])) {//去掉原有的token信息
                    unset($aQuery['token']);
                }
                if (!empty($aQuery['resource'])) {//去掉原有的resourse信息
                    unset($aQuery['resource']);
                }
                $aCurrUrl['query'] = http_build_query($aQuery);
                //$sCurrUrl = http_build_url(null,$aCurrUrl);//当前环境没这个方法
                $sCurrUrl = $aCurrUrl['scheme'] . '://' . $aCurrUrl['host'] . $aCurrUrl['path'] . '?' . $aCurrUrl['query'];
                $sCurrUrl .= '&token=' . $this->aUser['sMyToken'];
            } else {
                $sCurrUrl .= '?token=' . $this->aUser['sMyToken'];
            }
            if ($resourse) {
                $sCurrUrl .= '&resource=' . $resourse;
            }
        }
        return $sCurrUrl;
    }

    //生成分享码
    protected function createToken($iUserID)
    {
        return $iUserID;
        $key = Yaf_G::getConf('token', null, 'common');
        $sToken = Util_Crypt::encrypt($iUserID, $key);
        return $sToken;
    }

    //发起者iResourceID加密
    protected function encryptResourceID($iResourceID)
    {
        return $iResourceID;
        $key = Yaf_G::getConf('token', null, 'common');
        $sToken = Util_Crypt::encrypt($iResourceID, $key);
        return $sToken;
    }

    //解析分享码
    protected function decryptToken($sToken)
    {
        return $sToken;
        $key = Yaf_G::getConf('token', null, 'common');
        $iUserID = Util_Crypt::decrypt($sToken, $key);
        return $iUserID;
    }

    //删除用户缓存信息
    private function _flushCache()
    {
        $oCache = Util_Common::getCache();
        $iUserID = Util_Cookie::get('iUserID');
        if (!empty($iUserID)) {
            $sTokenKey = self::WEIXINKEY . "_aUser_" . $iUserID;
            $oCache->delete($sTokenKey);//删除用户缓存
            Util_Cookie::delete('iUserID');//删除本地cookie
            $sTokenKey = self::WEIXINKEY . "_" . $_SERVER['HTTP_HOST'] . "_AccessToken";
            $sJsApiTicket = self::WEIXINKEY . "_" . $_SERVER['HTTP_HOST'] . "_JsApiTicket";
            $oCache->delete($sJsApiTicket);//删除本地token信息
            //$oCache->delete($sTokenKey);//删除本地token信息
        }
        Util_Cookie::delete('CurrCity');
    }

    /*======================================================调用微信js需要的配置===========================================*/

    public function getSignPackage()
    {
        $jsapiTicket = $this->_getJsApiTicket();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = $this->_createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId" => self::$_appid,
            "nonceStr" => $nonceStr,
            "timestamp" => (string)$timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        $this->assign('signPackage', $signPackage);
    }

    private function _createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 获取AccessToken
     * @return bool|string
     */
    private function _getJsApiTicket()
    {
        $sJsApiTicket = self::WEIXINKEY . "_" . $_SERVER['HTTP_HOST'] . "_JsApiTicket";
        $oCache = Util_Common::getCache();
        $JsApiTicket = $oCache->get($sJsApiTicket);
        if (empty($JsApiTicket)) {
            $sticketUrl = self::GETTICKETURL;
            $tokenParamArr['type'] = self::JSONTYPE;
            $tokenParamArr['access_token'] = self::getAccessToken();
            $sticketUrl .= "?" . http_build_query($tokenParamArr);
            $data = Model_WxBase::curl($sticketUrl, false);
            $data = json_decode($data, true);
            $oCache->set($sJsApiTicket, $data['ticket'], 7000);
            $JsApiTicket = $data['ticket'];
        }
        return $JsApiTicket;
    }

}