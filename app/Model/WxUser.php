<?php
/**
 * Created by PhpStorm.
 * User: zhangfangxiong
 * Date: 15/3/30
 * Time: 上午12:24
 */

class Model_WxUser extends Model_WxBase
{
    const GETUSERLIST = "https://api.weixin.qq.com/cgi-bin/user/get";//获取当前关注着列表
    const USERINFO = "https://api.weixin.qq.com/cgi-bin/user/info";//获取用户信息
    const USERINFOBYOAUTH = "https://api.weixin.qq.com/sns/userinfo";//网页授权获取用户信息
    const OAUTH = "https://open.weixin.qq.com/connect/oauth2/authorize";//用户授权获取code
    const OAUTHBYCODE = 'https://api.weixin.qq.com/sns/oauth2/access_token';//通过code获取用户信息
    const OAUTISVALID = 'https://api.weixin.qq.com/sns/auth';//检验授权凭证（access_token）是否有效
    const REFRESH = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';//刷新access_token

    const SCOPEBASE = 'snsapi_base';
    const SCOPEINFO = 'snsapi_userinfo';
    const STATE = '111';
    const RESPONSETYPE = 'code';
    const WECHATREDIRECT = 'wechat_redirect';
    const REFRESHTOKEN = 'refresh_token';

    const GRANTTYPE = 'authorization_code';

    protected $sOuthRedirect_uri = '';//自定义

    /**
     * 关注者列表
     * @param $sToken
     * @return mixed
     */
    public static function getWxUserList($sToken)
    {
        $sGetUserList = self::GETUSERLIST . "?access_token=" . $sToken;
        $sReturn = self::curl($sGetUserList);
        return json_decode($sReturn, true);
    }

    /**
     * 获取微信用户信息
     * access_token    调用接口凭证
     * openid    用户的唯一标识
     * lang    返回国家地区语言版本，zh_CN 简体，zh_TW 繁体，en 英语
     * return 没有关注返回openid和是否关注，关注才返回用户信息
     */
    public static function getWxUserInfo($sToken, $sOpenID, $lang = "zh_CN")
    {
        $sGetUserInfo = self::USERINFO . "?access_token=" . $sToken . "&openid=" . $sOpenID . "&lang=" . $lang;
        // var_dump($sGetUserInfo);die;
        $sReturn = self::curl($sGetUserInfo);
        return json_decode($sReturn, true);
    }

    /**
     * 通过授权获取微信用户信息（需scope为 snsapi_userinfo）
     * access_token    网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
     * openid    用户的唯一标识
     * lang    返回国家地区语言版本，zh_CN 简体，zh_TW 繁体，en 英语
     */
    public static function getOauthWxUserInfo($sToken, $sOpenID, $lang = "zh_CN")
    {
        $sGetUserInfo = self::USERINFOBYOAUTH . "?access_token=" . $sToken . "&openid=" . $sOpenID . "&lang=" . $lang;
        $sReturn = self::curl($sGetUserInfo);
        return json_decode($sReturn, true);
    }

    /**
     * 用户授权(参数顺序要固定)
     * appid     是     公众号的唯一标识
     * redirect_uri     是     授权后重定向的回调链接地址，请使用urlencode对链接进行处理
     * response_type     是     返回类型，请填写code
     * scope     是     应用授权作用域，snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid），snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
     * state     否     重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节
     * #wechat_redirect     是     无论直接打开还是做页面302重定向时候，必须带此参数
     */
    public static function oauth($aParam, $scope = 0,$isret=0)
    {
        $sUrl = self::OAUTH . '?';
        $aParam['response_type'] = self::RESPONSETYPE;
        $aParam['scope'] = $scope == 1 ? self::SCOPEINFO : self::SCOPEBASE;
        $aParam['state'] = self::STATE;
        $sUrl .= http_build_query($aParam);
        $sUrl .= '#' . self::WECHATREDIRECT;
        if($isret){
            return $sUrl;
        }
        else{
            header("location:" . $sUrl);
        }
    }

    /**
     * 通过code获取用户信息
     * appid     是     公众号的唯一标识
     * secret     是     公众号的appsecret
     * code     是     填写第一步获取的code参数
     * grant_type     是     填写为authorization_code
     * @param $aParam
     */
    public static function getAccessTokenByCode($aParam)
    {
        $sUrl = self::OAUTHBYCODE . '?';
        $aParam['grant_type'] = self::GRANTTYPE;
        //print_r($aParam);die;
        $sUrl .= http_build_query($aParam);
        return json_decode(self::curl($sUrl), true);
    }

    /**
     * 判断access_token是否有效
     * access_token     网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
     * openid     用户的唯一标识
     * @param $aParam
     */
    public static function checkIsValid($aParam)
    {
        $sUrl = self::OAUTISVALID . '?';
        $sUrl .= http_build_query($aParam);
        return json_decode(self::curl($sUrl), true);
    }

    /**
     * 刷新access_token
     * appid     是     公众号的唯一标识
     * grant_type     是     填写为refresh_token
     * refresh_token     是     填写通过access_token获取到的refresh_token参数
     * @param $aParam
     */
    public static function refreshToken($aParam)
    {
        $sUrl = self::REFRESH . '?';
        $aParam['grant_type'] = self::REFRESHTOKEN;
        $sUrl .= http_build_query($aParam);
        return json_decode(self::curl($sUrl), true);
    }

    /**
     * 通过openid获取用户信息（scope为 snsapi_userinfo时，token为获取的临时token）
     * @param $sToken
     * @param $sOpenID
     * $iParantID 用户来源
     * @param int $scope 0:snsapi_base,1:snsapi_userinfo
     * @return mixed
     */
    public static function getUserInfoByOpenID($sToken, $sOpenID,$scope=0)
    {
        //获取本站用户信息
        $aData = Model_Customer::getUserByOpenID($sOpenID);
        if (empty($aData)) {
            //插入新数据
            $aData['sOpenID'] = $sOpenID;
            $aData['iType'] = Model_Customer::TYPE_WX;
            $aData['sUserName'] = Model_Customer::initUserName();
            $aData['iUserID'] = Model_Customer::addData($aData);
            if (empty($aData['iUserID'])) {
                return [];
            }
        }
        //获取微信信息
        if($scope) {
            $aWxUserInfo = self::getOauthWxUserInfo($sToken, $sOpenID);
        } else {
            $aWxUserInfo = self::getWxUserInfo($sToken, $sOpenID);
        }
        return array_merge($aData,$aWxUserInfo);
    }
}